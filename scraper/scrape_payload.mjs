// Extract the cafeDetailComponent x-data payload from live detail pages
// Usage: node <skill>/browser.mjs https://wadahngopi.com/cafes/<slug> --script scraper/scrape_payload.mjs
export default async function run(page, ui) {
    const html = await page.content();
    const decoded = html
        .replace(/&quot;/g, '"')
        .replace(/&#039;/g, "'")
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>');

    // The x-data="cafeDetailComponent({ ... })" payload
    const m = decoded.match(/cafeDetailComponent\(([\s\S]*?)\)\s*"/);
    if (!m) return { error: 'no payload', sample: decoded.slice(0, 500) };
    let payload = m[1];
    // Quote unquoted JS keys for JSON.parse
    payload = payload.replace(/([{,]\s*)([A-Za-z_][A-Za-z0-9_]*)\s*:/g, '$1"$2":');
    // Remove trailing commas
    payload = payload.replace(/,\s*([\]}])/g, '$1');
    try {
        const data = JSON.parse(payload);
        return data;
    } catch (e) {
        return { error: String(e), payloadHead: payload.slice(0, 400) };
    }
}
