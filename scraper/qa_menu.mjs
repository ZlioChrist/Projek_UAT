export default async function run(page, ui) {
    // Dismiss onboarding modal if present
    try {
        const skip = await page.getByText("Lewati", { exact: true }).first();
        if (await skip.isVisible()) await skip.click();
        await page.waitForTimeout(500);
    } catch {}
    // Scroll to the menu section and wait for images
    await page.locator("#cafe-menu").scrollIntoViewIfNeeded();
    await page.waitForTimeout(1500);
    const info = await page.evaluate(() => {
        const imgs = [...document.querySelectorAll("#cafe-menu img")];
        return {
            count: imgs.length,
            loaded: imgs.filter((i) => i.complete && i.naturalWidth > 0).length,
            broken: imgs
                .filter((i) => i.complete && i.naturalWidth === 0)
                .map((i) => i.src)
                .slice(0, 3),
            header:
                document
                    .querySelector("#cafe-menu")
                    ?.innerText.match(/\d+ foto menu/)?.[0] ?? null,
        };
    });
    await page
        .locator("#cafe-menu")
        .screenshot({ path: "scraper/menu_section.png" });
    return info;
}
