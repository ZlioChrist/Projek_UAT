export default async function run(page, ui) {
  await page.evaluate(() => localStorage.setItem("wadah-onboarded", "1"));

  // Cards exist but are covered by the onboarding overlay (pointer-events-none on the grid).
  // Dismiss the onboarding by clicking "Mulai Jelajahi!" / "Lewati" until the overlay goes away.
  for (let i = 0; i < 5; i++) {
    const next = await page.$('button:has-text("Lanjut")');
    const skip = await page.$('button:has-text("Lewati")');
    const start = await page.$('button:has-text("Mulai Jelajahi")');
    const btn = start || next || skip;
    if (!btn) break;
    try {
      await btn.click();
      await page.waitForTimeout(700);
    } catch (e) {
      break;
    }
  }
  // Overlay check
  const overlayGone = await page.evaluate(
    () =>
      !document.querySelector(".onboarding-overlay") ||
      getComputedStyle(document.querySelector(".onboarding-overlay"))
        .display === "none",
  );

  await page.waitForTimeout(3000);

  const allCards = [];
  for (let scroll = 0; scroll < 80; scroll++) {
    const cards = await page.evaluate(() => {
      const out = [];
      document
        .querySelectorAll("a.cafe-card-2026, div.cafe-card-2026")
        .forEach((card) => {
          const name =
            card.querySelector(".cafe-card-title")?.innerText?.trim() || "";
          const addr =
            card.querySelector(".cafe-card-address span")?.innerText?.trim() ||
            "";
          const href = card.getAttribute("href") || "";
          const img =
            card.querySelector("img.cafe-card-img")?.getAttribute("src") || "";
          const status =
            card
              .querySelector(".cafe-status-badge-smart")
              ?.innerText?.replace(/\s+/g, " ")
              .trim() || "";
          const tags = [...card.querySelectorAll(".cafe-tag")].map((t) =>
            t.innerText.trim(),
          );
          const more =
            card.querySelector(".cafe-tag-more")?.innerText?.trim() || "";
          out.push({ name, addr, href, img, status, tags, more });
        });
      return out;
    });
    const seen = new Set(allCards.map((c) => c.href + "|" + c.name));
    cards.forEach((c) => {
      if (c.name && !seen.has(c.href + "|" + c.name)) allCards.push(c);
    });

    const previousCount = await page.locator("a.cafe-card-2026").count();
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(1500);

    let loadedMore = await page.locator("a.cafe-card-2026").count() > previousCount;
    const loadMore = await page.$('button:has-text("Muat Lagi")');
    if (!loadedMore && loadMore) {
      try {
        await loadMore.scrollIntoViewIfNeeded();
        await loadMore.click();
        await page.waitForFunction(
          (count) => document.querySelectorAll("a.cafe-card-2026").length > count,
          previousCount,
          { timeout: 15000 },
        );
        loadedMore = true;
      } catch (e) {
        loadedMore = false;
      }
    }

    if (loadedMore) continue;

    if (scroll > 1) break;
    try {
      await page.waitForTimeout(1000);
    } catch (e) {}
  }
  const results = allCards.map((card) => ({
    slug: (card.href || "").replace(/^.*\/cafes\//, "").replace(/\/$/, ""),
    name: card.name,
    address: card.addr,
    status: card.status,
    mapsUrl: "",
    images: [],
  }));
  const output = { overlayGone, count: allCards.length, cards: allCards, results };
  const fs = await import("fs");
  const path = await import("path");
  const out = path.join(process.cwd(), "scraper", "result_list.json");
  fs.writeFileSync(out, JSON.stringify(output, null, 2));
  await ui.log?.(out);

  return output;
}
