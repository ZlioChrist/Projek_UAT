# Scraper — Data Sync dari wadahngopi.com

Folder terpisah untuk script automation/scraping agar tidak tercampur dengan
kode backend Laravel (`app/`, `routes/`, `resources/`).

## Isi

| File | Fungsi |
|---|---|
| `scrape_cafe.php` | Ambil data 1..n kafe dari `https://wadahngopi.com/cafes/{slug}` via HTTP (parse payload `cafeDetailComponent`) — jam operasional, status, dll. |
| `scrape_details.mjs` | Scraping menyeluruh via headless browser (Playwright): semua kafe dari /explore → detail → jam buka/tutup, menu, gambar, koordinat. |
| `scrape_list.mjs` | Ambil daftar slug/kartu kafe dari /explore (scroll + Muat Lagi). |
| `sync_local.php` | (opsional) Terapkan hasil scrape ke database lokal. |

## Cara pakai

```bash
# 1. Daftar slug kafe (output JSON ke file)
node scraper/scrape_list.mjs > scraper/slugs.json

# 2. Detail per kafe via HTTP (cepat)
php scraper/scrape_cafe.php scraper/slugs.json

# 3. Detail lengkap via browser (lambat tapi lengkap)
node C:\Users\chris\.codegpt\skills\browser-automation\browser.mjs \
  https://wadahngopi.com/explore --script scraper/scrape_details.mjs
```

## Catatan

- Semua script **read-only** terhadap situs live; tidak mengirim data.
- Rate-limit: jangan scrape semua kafe berturut-turut tanpa jeda —
  tambah `await page.waitForTimeout(1000)` antar halaman bila perlu.
