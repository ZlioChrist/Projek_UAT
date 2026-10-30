// Extract per-day operating hours from live cafe detail pages via the Livewire snapshot
// Usage: node <skill>/browser.mjs https://wadahngopi.com/cafes/pluto-house-coffee --script scraper/scrape_hours.mjs
export default async function run(page, ui) {
    const slugs = ["pluto-house-coffee","kelana-coffee","coffee-co-soul","jack-house-coffee-eatery","althea-coffee-co","wedhang-suguhan","opini-kopi","hitam-manis-kedai-kopi","siliwangi-coffee","212-coffee-space","voorvo-food-drink","braga-samarinda","unico-coffee","ngopiboss","witness","rapi-rapi-coffee","frenco-coffee-eatery","coffee-beat","house-of-dondang","titik-koma-antasari-samarinda","you-coffee-and-brunch","kopikumana-1","gudang-kena","kong-djie-coffee-samarinda","in-coffee","teras-roemah-samarinda","labricca-coffee","hexa-coffee-bar","giras-kitchen-coffee","jakarta-loc-coffe-and-space","sela-coffee-tea","sevelcrowd-social-coffee","part-coffee","kopi-maiko","kopiloa-indonesia","saqa-coffee-house-and-space","noctis-coffee","kopa-kopi-samarinda","vlorry-coffee","penny-royal","noona-house","sebelas-coffee-tea","made-in-coldbrew-1","saqa-house-1","thehills-samarinda","coffee-co","lemutu-coffee","nomadic-coffee","moveoncafe","toko-kopi-cerita","kedai-ak","pot-o-koffie-1","pluto-house-and-coffee","pojok-juanda","bunga-kopi","my-coffee-samarinda","galuh-coffee","double-dose","pot-o-koffie","wakeup-social","88-coffee","kana-coffee","rayas-caffe","kopikumana","satukata-coffee-co","widi-coffee","tulodong-kopi","laris-coffee","kopi-komak","28-coffee-samarinda-arh"];
    const BASE = 'https://wadahngopi.com/cafes/';
    const results = [];

    for (const slug of slugs) {
        try {
            const resp = await page.request.get(BASE + slug, { timeout: 45000 });
            const html = await resp.text();
            const decoded = html
                .replace(/&quot;/g, '"')
                .replace(/&#039;/g, "'")
                .replace(/&amp;/g, '&')
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>');

            // Find wire:snapshot payloads and look for hour-ish props
            const found = {};
            const snapRe = /wire:snapshot="([^"]+)"/g;
            let m;
            while ((m = snapRe.exec(decoded)) !== null) {
                const snap = m[1].replace(/\\u0022/g, '"');
                try {
                    const j = JSON.parse(snap);
                    const walk = (obj, path = '') => {
                        if (!obj || typeof obj !== 'object') return;
                        for (const [k, v] of Object.entries(obj)) {
                            const p = path ? path + '.' + k : k;
                            if (/hour|open|close|jam/i.test(k) && (typeof v === 'string' || typeof v === 'number')) {
                                found[p] = v;
                            }
                            if (typeof v === 'object') walk(v, p);
                        }
                    };
                    walk(j.data?.props || {});
                } catch (e) { /* not json */ }
            }

            // Also grep raw HTML for "HH:MM - HH:MM" patterns near day names
            const textMatches = {};
            for (const day of ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu','Monday','Tuesday']) {
                const dm = decoded.match(new RegExp(day + '[^0-9]{0,60}(\\d{1,2}[.:]\\d{2})[^0-9]{0,10}(\\d{1,2}[.:]\\d{2})', 'i'));
                if (dm) textMatches[day] = dm[1] + ' - ' + dm[2];
            }

            results.push({ slug, snapshotHours: found, textHours: textMatches });
        } catch (e) {
            results.push({ slug, error: String(e).slice(0, 120) });
        }
    }
    return { total: results.length, withHours: results.filter(r => Object.keys(r.snapshotHours || {}).length || Object.keys(r.textHours || {}).length).length, results };
}
