export default async function run(page, ui) {
    const fs = await import("fs");
    const path = await import("path");
    const crypto = await import("crypto");
    const root = process.cwd();
    const sourcePath = path.join(root, "scraper", "public_export.json");
    const outputPath = path.join(root, "scraper", "public_export_local.json");
    const exportData = JSON.parse(fs.readFileSync(sourcePath, "utf8"));
    const cache = new Map();
    const errors = [];

    const localize = async (value, label) => {
        if (!value) return "";

        let url;
        try {
            url = new URL(value, "https://wadahngopi.com");
        } catch {
            errors.push({ source: value, reason: "invalid URL" });
            return "";
        }

        if (url.protocol !== "https:" || !["wadahngopi.com", "www.wadahngopi.com", "images.unsplash.com"].includes(url.hostname)) {
            errors.push({ source: url.href, reason: "host not allowlisted" });
            return "";
        }

        const key = url.href;
        if (cache.has(key)) return cache.get(key);

        const extension = path.extname(url.pathname).toLowerCase() || ".jpg";
        const hash = crypto.createHash("sha256").update(key).digest("hex");
        const relative = path.posix.join("imports", "public", `${hash}${extension}`);
        const absolute = path.join(root, "storage", "app", "public", ...relative.split("/"));

        try {
            if (!fs.existsSync(absolute)) {
                const response = await page.request.get(url.href, { timeout: 30000 });
                if (!response.ok()) throw new Error(`HTTP ${response.status()}`);
                const contentType = response.headers()["content-type"] || "";
                if (!contentType.toLowerCase().startsWith("image/")) throw new Error(`Unexpected content type: ${contentType}`);
                fs.mkdirSync(path.dirname(absolute), { recursive: true });
                fs.writeFileSync(absolute, await response.body());
            }
            cache.set(key, relative);
            return relative;
        } catch (error) {
            errors.push({ source: url.href, label, reason: String(error) });
            cache.set(key, "");
            return "";
        }
    };

    for (const [type, records] of [["cafes", exportData.cafes], ["roasteries", exportData.roasteries]]) {
        for (const record of records) {
            const originalImage = record.image;
            record.image_source_url = originalImage || "";
            record.image = await localize(originalImage, `${type}/${record.slug}/cover`);

            for (const field of ["gallery_images", "menu_images"]) {
                const originals = record[field] || [];
                const localized = [];
                for (let index = 0; index < originals.length; index++) {
                    const relative = await localize(originals[index], `${type}/${record.slug}/${field}/${index + 1}`);
                    if (relative) localized.push(relative);
                }
                record[`${field}_source_urls`] = originals;
                record[field] = localized;
            }
        }
    }

    for (const record of exportData.information) {
        const originals = record.images || [];
        const localized = [];
        for (let index = 0; index < originals.length; index++) {
            const relative = await localize(originals[index], `information/${record.slug}/${index + 1}`);
            if (relative) localized.push({ source: originals[index], path: relative });
        }

        record.image_source_url = record.image || "";
        record.images_source_urls = originals;
        record.images = localized.map((image) => image.path);
        if (localized.length > 0) record.image = localized[0].path;
        for (const image of localized) {
            record.content_html = (record.content_html || "").split(image.source).join(`/storage/${image.path}`);
        }
    }

    exportData.asset_downloads = { unique_urls: cache.size, downloaded_or_existing: [...cache.values()].filter(Boolean).length, errors };
    fs.writeFileSync(outputPath, JSON.stringify(exportData, null, 2), "utf8");
    await ui.log?.(`Saved ${outputPath}`);

    return {
        outputPath,
        uniqueUrls: cache.size,
        downloadedOrExisting: exportData.asset_downloads.downloaded_or_existing,
        failures: errors.length,
        failureExamples: errors.slice(0, 10),
    };
}
