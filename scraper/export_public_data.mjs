export default async function run(page, ui) {
    const data = await page.evaluate(async () => {
        const requestDocument = async (path) => {
            const response = await fetch(path, { cache: "no-store" });
            if (!response.ok) throw new Error(`${path}: HTTP ${response.status}`);
            return new DOMParser().parseFromString(await response.text(), "text/html");
        };

        const collectCards = async (path, selector, type) => {
            const document = await requestDocument(path);
            return [...document.querySelectorAll(selector)].map((card) => {
                const url = new URL(card.getAttribute("href") || "", location.origin);
                const slug = url.pathname.split("/").filter(Boolean).at(-1) || "";
                const badge = card.querySelector(".cafe-status-badge-smart");
                const classes = badge?.className?.split(/\s+/) || [];

                return {
                    type,
                    slug,
                    url: url.href,
                    name: card.querySelector(".cafe-card-title")?.textContent?.trim() || "",
                    address: card.querySelector(".cafe-card-address span")?.textContent?.trim() || "",
                    status: badge?.textContent?.replace(/\s+/g, " ").trim() || "",
                    is_open: classes.includes("open"),
                    image: card.querySelector("img.cafe-card-img")?.getAttribute("src") || "",
                    facilities: [...card.querySelectorAll(".cafe-tag")].map((tag) => tag.textContent.trim()),
                };
            }).filter((card) => card.slug && card.name);
        };

        const detail = async (record) => {
            try {
                const document = await requestDocument(new URL(record.url).pathname);
                const images = [...new Set([...document.querySelectorAll("img")]
                    .map((image) => image.src)
                    .filter((src) => /\/storage\/(?:cafes|roasteries)\//.test(src)))];
                const menuImages = images.filter((src) => /\/menu-gallery\/|\/menus\//.test(src));
                const galleryImages = images.filter((src) => !menuImages.includes(src));
                const links = [...new Set([...document.querySelectorAll("a")]
                    .map((anchor) => anchor.href)
                    .filter((href) => /maps\.app\.goo\.gl|google\.com\/maps|instagram\.com|tiktok\.com|wa\.me|facebook\.com/i.test(href)))];
                const paragraphs = [...document.querySelectorAll("p")]
                    .map((paragraph) => paragraph.textContent.replace(/\s+/g, " ").trim())
                    .filter((text) => text.length > 30);

                return {
                    ...record,
                    description: paragraphs[0] || "",
                    paragraphs,
                    google_maps_url: links.find((href) => /maps\.app\.goo\.gl|google\.com\/maps/i.test(href)) || "",
                    social_links: links.filter((href) => /instagram\.com|tiktok\.com|wa\.me|facebook\.com/i.test(href)),
                    gallery_images: galleryImages,
                    menu_images: menuImages,
                    products: [...document.querySelectorAll("[data-menu-item], .menu-product")].map((item) => ({
                        name: item.querySelector("h3,h4,[data-menu-name]")?.textContent?.trim() || "",
                        description: item.querySelector("p,[data-menu-description]")?.textContent?.trim() || "",
                        price: item.querySelector("[data-menu-price]")?.textContent?.trim() || "",
                    })).filter((item) => item.name),
                };
            } catch (error) {
                return { ...record, detail_error: String(error) };
            }
        };

        const cafePrefixes = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        const cafeMap = new Map();
        const fetchErrors = [];
        for (const prefix of cafePrefixes) {
            try {
                const cards = await collectCards(`/explore?activeLetter=${encodeURIComponent(prefix)}&sort=name_az`, "main.explore-cafe-grid a.cafe-card-2026", "cafe");
                for (const card of cards) cafeMap.set(card.slug, card);
            } catch (error) {
                fetchErrors.push({ source: `cafe:${prefix}`, error: String(error) });
            }
        }

        const cafes = [];
        for (const cafe of cafeMap.values()) {
            cafes.push(await detail(cafe));
            await new Promise((resolve) => setTimeout(resolve, 120));
        }

        const roasteryCards = await collectCards("/roastery", "main.explore-cafe-grid a.cafe-card-2026", "roastery");
        const roasteries = [];
        for (const roastery of roasteryCards) {
            roasteries.push(await detail(roastery));
            await new Promise((resolve) => setTimeout(resolve, 120));
        }

        const informationMap = new Map();
        for (const path of ["/information", "/information?page=2"]) {
            try {
                const document = await requestDocument(path);
                for (const anchor of document.querySelectorAll('a[href*="/information/"]')) {
                    const url = new URL(anchor.href);
                    const slug = url.pathname.split("/").filter(Boolean).at(-1) || "";
                    if (slug) informationMap.set(slug, { slug, url: url.href });
                }
            } catch (error) {
                fetchErrors.push({ source: path, error: String(error) });
            }
        }

        const information = [];
        for (const record of informationMap.values()) {
            try {
                const document = await requestDocument(new URL(record.url).pathname);
                const article = document.querySelector("article");
                const content = article?.querySelector(".prose-news, .prose") || article;
                const image = article?.querySelector("img")?.src || "";
                const summary = [...(article?.querySelectorAll("p") || [])]
                    .map((paragraph) => paragraph.textContent.replace(/\s+/g, " ").trim())
                    .find((text) => text.length > 50) || "";
                const category = [...(article?.querySelectorAll("span") || [])]
                    .map((span) => span.textContent.trim())
                    .find((text) => ["Berita", "Edukasi", "Lomba", "Promo"].includes(text)) || "";

                information.push({
                    ...record,
                    title: article?.querySelector("h1")?.textContent.replace(/\s+/g, " ").trim() || document.title,
                    category,
                    summary,
                    published_at: article?.querySelector("time")?.dateTime || article?.querySelector("time")?.textContent.trim() || "",
                    views: Number(article?.textContent.match(/(\d+)\s*Views/i)?.[1] || 0),
                    image,
                    images: [...new Set([...(article?.querySelectorAll("img") || [])].map((item) => item.src))],
                    content_html: content?.innerHTML || "",
                });
            } catch (error) {
                information.push({ ...record, detail_error: String(error) });
            }
            await new Promise((resolve) => setTimeout(resolve, 120));
        }

        return {
            exported_at: new Date().toISOString(),
            source: "public pages only",
            totals: {
                cafes: cafes.length,
                roasteries: roasteries.length,
                information: information.length,
                structured_cafe_products: cafes.reduce((sum, cafe) => sum + (cafe.products?.length || 0), 0),
                cafe_menu_images: cafes.reduce((sum, cafe) => sum + (cafe.menu_images?.length || 0), 0),
                roastery_menu_images: roasteries.reduce((sum, roastery) => sum + (roastery.menu_images?.length || 0), 0),
            },
            limitations: [
                "Admin users and other private records are not included.",
                "Menu photos are included; prices are exported only when the public page exposes structured product data.",
            ],
            fetch_errors: fetchErrors,
            cafes,
            roasteries,
            information,
        };
    });

    const fs = await import("fs");
    const path = await import("path");
    const cafeDetailPath = path.join(process.cwd(), "scraper", "result_full.json");
    if (fs.existsSync(cafeDetailPath)) {
        const detailExport = JSON.parse(fs.readFileSync(cafeDetailPath, "utf8"));
        const detailBySlug = new Map((detailExport.results || []).map((record) => [record.slug, record]));

        for (const cafe of data.cafes) {
            const detail = detailBySlug.get(cafe.slug);
            if (!detail || detail.error) continue;

            const images = (detail.images || []).filter((src) => typeof src === "string");
            const galleryImages = images.filter((src) => /\/storage\/cafes\/gallery\//i.test(src));
            const menuImages = images.filter((src) => /\/storage\/cafes\/menu-gallery\//i.test(src));
            const coverImage = images.find((src) => /\/storage\/cafes\/[^/]+\.[a-z0-9]+(?:\?.*)?$/i.test(src));

            cafe.image = coverImage || cafe.image;
            cafe.gallery_images = [...new Set([...(cafe.gallery_images || []), ...galleryImages])];
            cafe.menu_images = [...new Set([...(cafe.menu_images || []), ...menuImages])];
            cafe.google_maps_url = cafe.google_maps_url || detail.mapsUrl || "";
            cafe.social_links = [...new Set([...(cafe.social_links || []), ...(detail.socials || [])])];
        }
    }

    data.totals.cafe_menu_images = data.cafes.reduce((sum, cafe) => sum + (cafe.menu_images?.length || 0), 0);
    data.totals.cafe_gallery_images = data.cafes.reduce((sum, cafe) => sum + (cafe.gallery_images?.length || 0), 0);

    const outputPath = path.join(process.cwd(), "scraper", "public_export.json");
    fs.writeFileSync(outputPath, JSON.stringify(data, null, 2), "utf8");
    await ui.log?.(`Saved ${outputPath}`);

    return {
        outputPath,
        totals: data.totals,
        cafeErrors: data.cafes.filter((cafe) => cafe.detail_error).length,
        roasteryErrors: data.roasteries.filter((roastery) => roastery.detail_error).length,
        informationErrors: data.information.filter((item) => item.detail_error).length,
        listingErrors: data.fetch_errors.length,
    };
}
