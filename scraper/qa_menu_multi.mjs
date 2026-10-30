export default async function run(page, ui) {
    const slugs = ["kopikumana", "88-coffee", "coffee-co-soul"];
    const results = [];
    for (const slug of slugs) {
        await page.goto(`http://127.0.0.1:8000/cafes/${slug}`, {
            waitUntil: "load",
        });
        try {
            const skip = page.getByText("Lewati", { exact: true }).first();
            if (await skip.isVisible()) await skip.click();
        } catch {}
        await page.waitForTimeout(1000);
        results.push({
            slug,
            ...(await page.evaluate(() => {
                const sec = document.querySelector("#cafe-menu");
                const imgs = [...(sec?.querySelectorAll("img") ?? [])];
                return {
                    fotoText:
                        sec?.innerText.match(/\d+ foto menu/i)?.[0] ?? null,
                    imgs: imgs.length,
                    loaded: imgs.filter((i) => i.complete && i.naturalWidth > 0)
                        .length,
                    emptyState: !!sec?.innerText.match(/Belum ada menu/i),
                };
            })),
        });
    }
    return results;
}
