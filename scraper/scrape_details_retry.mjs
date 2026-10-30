// Scrape ONLY the remaining detail pages (with retry) — complements scrape_details.mjs
// Usage: node <skill>/browser.mjs https://wadahngopi.com/explore --script scraper/scrape_details_retry.mjs
import fs from "node:fs";

const INPUT = "C:/Users/chris/AppData/Local/Temp/slugs_all.json"; // all 70 slugs
const BASE = "https://wadahngopi.com/cafes/";

export default async function run(page, ui) {
    const slugs = JSON.parse(fs.readFileSync(INPUT, "utf8"));
    const results = [];

    for (const slug of slugs) {
        let ok = false;
        for (let attempt = 0; attempt < 3 && !ok; attempt++) {
            try {
                await page.goto(BASE + slug, {
                    waitUntil: "domcontentloaded",
                    timeout: 45000,
                });
                await page.waitForTimeout(1500);

                const data = await page.evaluate(() => {
                    const text = document.body.innerText.replace(/\s+/g, " ");
                    const pick = (re) => {
                        const m = text.match(re);
                        return m ? m[1].trim() : "";
                    };

                    // Status badge text (e.g. "SEDANG BUKA Sampai 03:00" / "TUTUP Buka 17:00")
                    const status = pick(
                        /(SEDANG BUKA[^A-Z]{0,20}|TUTUP[^A-Z]{0,20})/,
                    );

                    // Facilities: words between FASILITAS TERSEDIA and next section header
                    let facilities = [];
                    const fm = text.match(
                        /FASILITAS TERSEDIA\s+(.*?)(?=HUBUNGI|Est\.|DAFTAR MENU|$)/i,
                    );
                    if (fm)
                        facilities = fm[1]
                            .split(/\s{2,}|\s(?=[A-Z][a-z]{2,})/)
                            .map((s) => s.trim())
                            .filter(
                                (s) =>
                                    s &&
                                    s.length < 30 &&
                                    !/^(Lokasi|Google|Maps|Chat|WhatsApp|INSTAGRAM|TIKTOK|FACEBOOK)$/i.test(
                                        s,
                                    ),
                            );

                    // Socials
                    const socials = [...document.querySelectorAll("a")]
                        .filter((a) =>
                            /instagram\.com|tiktok\.com|wa\.me|whatsapp|facebook\.com/i.test(
                                a.href || "",
                            ),
                        )
                        .map((a) => a.href);
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

                    // Description
                    const desc = pick(
                        /(?:Est\.[^A-Z]{0,30})?(?:\d{4})?\s*([A-Z][^.]{40,600}\.)/,
                    );

                    return {
                        name:
                            document.querySelector("h1")?.innerText?.trim() ||
                            "",
                        address: pick(/Jl\..{5,120}/),
                        status,
                        facilities,
                        socials: [...new Set(socials)],
                        mapsUrl,
                        images,
                        desc,
                    };
                });
                results.push({ slug, ...data });
                ok = true;
            } catch (e) {
                if (attempt === 2)
                    results.push({ slug, error: String(e).slice(0, 150) });
                await page.waitForTimeout(3000);
            }
        }
        await page.waitForTimeout(800);
    }
    return { total: results.length, results };
}
