// Scrape the cafeDetailComponent payload from ALL live cafe detail pages (via raw HTTP, fast)
// Usage: node <skill>/browser.mjs https://wadahngopi.com/cafes/pluto-house-coffee --script scraper/scrape_payload_all.mjs > output
export default async function run(page, ui) {
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
            const resp = await page.request.get(BASE + slug, {
                timeout: 45000,
            });
            const html = await resp.text();
            const decoded = html
                .replace(/&quot;/g, '"')
                .replace(/&#039;/g, "'")
                .replace(/&amp;/g, "&")
                .replace(/&lt;/g, "<")
                .replace(/&gt;/g, ">");

            const m = decoded.match(/cafeDetailComponent\(([\s\S]*?)\)\s*"/);
            if (!m) {
                results.push({ slug, error: "no payload" });
                continue;
            }
            let payload = m[1]
                .replace(/([{,]\s*)([A-Za-z_][A-Za-z0-9_]*)\s*:/g, '$1"$2":')
                .replace(/,\s*([\]}])/g, "$1");
            try {
                results.push({ slug, payload: JSON.parse(payload) });
            } catch (e) {
                results.push({
                    slug,
                    error: "json: " + String(e).slice(0, 80),
                });
            }
        } catch (e) {
            results.push({ slug, error: String(e).slice(0, 120) });
        }
    }
    return {
        total: results.length,
        ok: results.filter((r) => r.payload).length,
        results,
    };
}
