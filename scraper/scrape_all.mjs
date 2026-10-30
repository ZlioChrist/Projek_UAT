// Scrape ALL live cafe detail pages — full data: hours per day, status, facilities, menu, images, socials, maps
// Usage: node <skill>/browser.mjs https://wadahngopi.com/cafes/pluto-house-coffee --script scraper/scrape_all.mjs
export default async function run(page, ui) {
    // All 70 slugs scraped from /explore listing
    const slugs = [
        "pluto-house-coffee",
        "kelana-coffee",
        "coffee-co-soul",
        "jack-house-coffee-eatery",
        "althea-coffee-co",
        "wedhang-suguhan",
        "opini-kopi",
        "hitam-manis-kedai-kopi",
        "siliwangi-coffee",
        "212-coffee-space",
        "voorvo-food-drink",
        "braga-samarinda",
        "unico-coffee",
        "ngopiboss",
        "witness",
        "rapi-rapi-coffee",
        "frenco-coffee-eatery",
        "coffee-beat",
        "house-of-dondang",
        "titik-koma-antasari-samarinda",
        "you-coffee-and-brunch",
        "kopikumana-1",
        "gudang-kena",
        "kong-djie-coffee-samarinda",
        "in-coffee",
        "teras-roemah-samarinda",
        "labricca-coffee",
        "hexa-coffee-bar",
        "giras-kitchen-coffee",
        "jakarta-loc-coffe-and-space",
        "sela-coffee-tea",
        "sevelcrowd-social-coffee",
        "part-coffee",
        "kopi-maiko",
        "kopiloa-indonesia",
        "saqa-coffee-house-and-space",
        "noctis-coffee",
        "kopa-kopi-samarinda",
        "vlorry-coffee",
        "penny-royal",
        "noona-house",
        "sebelas-coffee-tea",
        "made-in-coldbrew-1",
        "saqa-house-1",
        "thehills-samarinda",
        "coffee-co",
        "lemutu-coffee",
        "nomadic-coffee",
        "moveoncafe",
        "toko-kopi-cerita",
        "kedai-ak",
        "pot-o-koffie-1",
        "pluto-house-and-coffee",
        "pojok-juanda",
        "bunga-kopi",
        "my-coffee-samarinda",
        "galuh-coffee",
        "double-dose",
        "pot-o-koffie",
        "wakeup-social",
        "88-coffee",
        "kana-coffee",
        "rayas-caffe",
        "kopikumana",
        "satukata-coffee-co",
        "widi-coffee",
        "tulodong-kopi",
        "laris-coffee",
        "kopi-komak",
        "28-coffee-samarinda-arh",
    ];
    const BASE = "https://wadahngopi.com/cafes/";
    const results = [];

    for (const slug of slugs) {
        try {
            await page.goto(BASE + slug, {
                waitUntil: "domcontentloaded",
                timeout: 90000,
            });
            // wait until the h1 (cafe name) is present — retry-friendly
            await page
                .waitForSelector("h1", { timeout: 30000 })
                .catch(() => {});
            await page.waitForTimeout(1200);

            const data = await page.evaluate(() => {
                const bodyText = document.body.innerText;
                const text = bodyText.replace(/\s+/g, " ");
                const pick = (re) => {
                    const m = text.match(re);
                    return m ? m[1].trim() : "";
                };

                // Status badge
                const status = pick(
                    /(SEDANG BUKA(?:\s+Sampai\s+\d{1,2}:\d{2})?|TUTUP(?:\s+Buka\s+\d{1,2}:\d{2})?|24\s*JAM)/i,
                );

                // Days + hours from page text (works if hours table rendered)
                const hours = {};
                for (const day of [
                    "Senin",
                    "Selasa",
                    "Rabu",
                    "Kamis",
                    "Jumat",
                    "Sabtu",
                    "Minggu",
                ]) {
                    const m = text.match(
                        new RegExp(
                            day +
                                "\\s+(\\d{1,2}[.:]\\d{2})\\s*[-–—]\\s*(\\d{1,2}[.:]\\d{2}|\\d{2}:\\d{2})",
                            "i",
                        ),
                    );
                    if (m) hours[day] = { open: m[1], close: m[2] };
                }

                // Facilities
                let facilities = [];
                const fm = text.match(
                    /FASILITAS TERSEDIA\s+(.*?)(?=HUBUNGI|Est\.|DAFTAR MENU|Menu|$)/i,
                );
                if (fm)
                    facilities = fm[1]
                        .split(/(?=[A-Z][a-z])/)
                        .map((s) => s.trim())
                        .filter((s) => s && s.length < 30);

                // Socials + maps
                const socials = [
                    ...new Set(
                        [...document.querySelectorAll("a")]
                            .filter((a) =>
                                /instagram\.com|tiktok\.com|wa\.me|whatsapp|facebook\.com/i.test(
                                    a.href || "",
                                ),
                            )
                            .map((a) => a.href),
                    ),
                ];
                const mapsUrl =
                    document.querySelector(
                        'a[href*="maps.app.goo.gl"], a[href*="google.com/maps"]',
                    )?.href || "";

                // Images
                const images = [
                    ...new Set(
                        [...document.querySelectorAll("img")]
                            .map((i) => i.src)
                            .filter((s) => /storage\/cafes/.test(s)),
                    ),
                ];

                // Address: the line right after the h1
                const h1 =
                    document.querySelector("h1")?.innerText?.trim() || "";
                let address = "";
                if (h1) {
                    const after = bodyText.split(h1)[1] || "";
                    const am = after.match(
                        /(Jl\.|Jln\.|Gg\.|Kawasan|Kompleks)[^\n]{5,140}/,
                    );
                    if (am) address = am[0].trim();
                }

                // Est.
                const est = pick(/Est\.\s*([^A-Za-z]{1,30})/i);

                return {
                    name: h1,
                    address,
                    est,
                    status,
                    hours,
                    facilities,
                    socials,
                    mapsUrl,
                    images,
                };
            });

            results.push({ slug, ...data });
        } catch (e) {
            results.push({ slug, error: String(e).slice(0, 150) });
        }
        // Polite delay
        await page.waitForTimeout(1000);
    }
    const final = {
        total: results.length,
        ok: results.filter((r) => !r.error).length,
        results,
    };

    // ---- SAVE TO DISK (survives disconnects) ----
    try {
        const fs = await import("fs");
        const path = await import("path");
        const out = path.join(process.cwd(), "scraper", "result_full.json");
        fs.writeFileSync(out, JSON.stringify(final, null, 2));
        await ui.log?.(out);
    } catch (e) {
        console.log("SAVE FAILED", String(e));
    }

    return final;
}
