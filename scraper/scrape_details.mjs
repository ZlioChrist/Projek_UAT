// Scrape detail pages: extract via the Alpine data JSON + DOM text of the live site
// Usage: node <skill>/browser.mjs https://wadahngopi.com/explore --script scraper/scrape_details.mjs
export default async function run(page, ui) {
    await page.goto("https://wadahngopi.com/explore", {
        waitUntil: "domcontentloaded",
        timeout: 90000,
    });
    await page.evaluate(() => localStorage.setItem("wadah-onboarded", "1"));
    await page.reload({ waitUntil: "domcontentloaded", timeout: 90000 });
    await page.waitForSelector(".cafe-card-2026", {
        timeout: 60000,
        state: "attached",
    });

    // Remove the onboarding overlay so the grid is interactive
    await page.evaluate(() => {
        localStorage.setItem("wadah-onboarded", "1");
        const ov = document.querySelector(".onboarding-overlay");
        if (ov) ov.remove();
    });

    // Collect all cafe hrefs by scrolling + load more
    const hrefs = new Set();
    for (let scroll = 0; scroll < 80; scroll++) {
        const links = await page.evaluate(() =>
            [...document.querySelectorAll("a.cafe-card-2026")].map((a) =>
                a.getAttribute("href"),
            ),
        );
        links.forEach((h) => h && hrefs.add(h));
        const prev = hrefs.size;
        await page.evaluate(() => window.scrollBy(0, 2500));
        await page.waitForTimeout(1200);
        const loadMore = await page.$('button:has-text("Muat Lagi")');
        if (loadMore) {
            try {
                await loadMore.click();
                await page.waitForTimeout(2500);
            } catch (e) {}
        }
        if (hrefs.size === prev && scroll > 4) break;
    }

    const slugs = [...hrefs].map((h) =>
        h.replace("https://wadahngopi.com/cafes/", "").replace(/\/$/, ""),
    );

    // Now visit each detail page and extract structured data
    const results = [];
    for (const slug of slugs) {
        try {
            await page.goto(`https://wadahngopi.com/cafes/${slug}`, {
                waitUntil: "domcontentloaded",
                timeout: 60000,
            });
            await page.waitForTimeout(2000);

            const data = await page.evaluate(() => {
                const all = (sel) =>
                    [...document.querySelectorAll(sel)].map((e) =>
                        e.innerText.trim(),
                    );

                // Facilities list: items after "FASILITAS TERSEDIA"
                let facilities = [];
                const facHeading = [
                    ...document.querySelectorAll("h2,h3,h4,div,span,p"),
                ].find((e) => /FASILITAS TERSEDIA/i.test(e.innerText || ""));
                if (facHeading) {
                    let container =
                        facHeading.closest("section,div") ||
                        facHeading.parentElement;
                    if (container) {
                        facilities = [...container.querySelectorAll("span,div")]
                            .map((e) => e.innerText.trim())
                            .filter(
                                (t) =>
                                    t &&
                                    t.length < 40 &&
                                    !/FASILITAS/i.test(t) &&
                                    !/HUBUNGI/i.test(t) &&
                                    !/EST\./i.test(t),
                            );
                        facilities = [...new Set(facilities)];
                    }
                }

                // Operating hours: look for day-name pairs in the page text
                const text = document.body.innerText.replace(/\s+/g, " ");
                const hours = {};
                const dayNames = [
                    "Senin",
                    "Selasa",
                    "Rabu",
                    "Kamis",
                    "Jumat",
                    "Sabtu",
                    "Minggu",
                ];
                for (const day of dayNames) {
                    const m = text.match(
                        new RegExp(
                            day +
                                "\\s+(\\d{1,2}[.:]\\d{2})\\s*[-–]\\s*(\\d{1,2}[.:]\\d{2})",
                            "i",
                        ),
                    );
                    if (m)
                        hours[day] = {
                            open: m[1].replace(":", ":"),
                            close: m[2],
                        };
                }
                const is24 = /24\s*jam/i.test(text);
                const closed = /Tutup|LIBUR/i.test(text);

                // Google Maps link (gives lat/lng or place)
                let mapsUrl = "";
                const mapsLink = [...document.querySelectorAll("a")].find((a) =>
                    /google\.com\/maps|goo\.gl\/maps|maps\.app/i.test(
                        a.href || "",
                    ),
                );
                if (mapsLink) mapsUrl = mapsLink.href;

                // Social links
                const socials = [...document.querySelectorAll("a")]
                    .filter((a) =>
                        /instagram\.com|tiktok\.com|wa\.me|api\.whatsapp|facebook\.com|x\.com|twitter\.com/i.test(
                            a.href || "",
                        ),
                    )
                    .map((a) => a.href);
                const uniqueSocials = [...new Set(socials)];

                // Images
                const images = [...document.querySelectorAll("img")]
                    .map((i) => i.src)
                    .filter((s) => /storage\/cafes/.test(s));

                // Status badge
                const status = (
                    document.querySelector(
                        '.status-badge, .realtime-status, [class*="status"]',
                    )?.innerText || ""
                )
                    .replace(/\s+/g, " ")
                    .trim()
                    .slice(0, 60);

                // Description: paragraph before "HUBUNGI"
                let desc = "";
                const hubungiIdx = text.indexOf("HUBUNGI");
                if (hubungiIdx > 0) {
                    const before = text.slice(0, hubungiIdx);
                    const parts = before.split(/Est\.|SEJARAH|TENTANG/i);
                    desc = (parts[parts.length - 1] || "")
                        .replace(
                            /^.*?(Pluto|Kelana|Coffee|Kopi|Kedai|Wadah)?/,
                            "",
                        )
                        .trim()
                        .slice(0, 500);
                }

                return {
                    name: document.querySelector("h1")?.innerText?.trim() || "",
                    address:
                        document.body.innerText
                            .match(/Jl\.([^\n]{5,120})/)?.[0]
                            ?.trim() || "",
                    est:
                        text.match(/Est\.\s*([^A-Z]{1,40})/i)?.[1]?.trim() ||
                        "",
                    status,
                    hours,
                    is24,
                    facilities: facilities.slice(0, 20),
                    mapsUrl,
                    socials: uniqueSocials,
                    images,
                    desc,
                };
            });
            results.push({ slug, ...data });
        } catch (e) {
            results.push({ slug, error: String(e).slice(0, 200) });
        }
    }
    return { total: slugs.length, slugs, results };
}
