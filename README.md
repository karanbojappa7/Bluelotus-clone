# Bluelotus Infrasafety — Website

Server-rendered marketing site with a PHP admin CMS. Every public page is rendered by PHP so
search engines and social scrapers see real content and real meta tags — no JavaScript required.

## Run locally

```
# XAMPP (Apache + MySQL) — clean URLs need mod_rewrite
http://localhost/saroj/
http://localhost/saroj/admin/
```

First-time install: `/admin/install.php`. Once installed, that page requires login and a typed
confirmation before it will re-seed.

### Database credentials

Copy `admin/config.local.example.php` to `admin/config.local.php` and edit it. That file is
git-ignored. Environment variables (`CMS_DB_HOST`, `CMS_DB_NAME`, `CMS_DB_USER`, `CMS_DB_PASS`)
are also honoured. Defaults fall back to `bluelotus` on `localhost:3306` as `root`.

Change the admin password immediately after install — the dashboard shows a warning banner until
you do.

## URLs

| Page | URL |
|------|-----|
| Home | `/` |
| Catalog | `/products` |
| Category | `/products/<category-id>` |
| Product | `/product/<slug>` |
| Blog index | `/blog` |
| Article | `/blog/<slug>` |
| FAQ | `/faq` |
| About / Contact / Privacy | `/about`, `/contact`, `/privacy-policy` |
| Sitemap / robots | `/sitemap.xml`, `/robots.txt` (both generated from the database) |

Old `.html` and query-string URLs (`/product.html?slug=…`) 301-redirect to the new paths.

## SEO

Everything below is generated per page from CMS content — nothing is hardcoded:

- **Meta** — title, description, keywords, canonical, robots, Open Graph, Twitter Card.
- **Structured data** — `LocalBusiness` + `WebSite` on every page, plus `Product` (with `Offer`,
  brand, SKU, GTIN/MPN, availability, condition, price), `FAQPage`, `BlogPosting`, `ItemList`,
  and `BreadcrumbList` where relevant.
- **Sitemap** — every product, category, and post, with `lastmod` from the record's `updated_at`.
  Anything flagged *Hide from search engines* is excluded and served `noindex`.
- **Pagination** — `rel="prev"` / `rel="next"` and self-referencing canonicals.

Per-item SEO lives on each edit screen (Products, Categories, Blog) under **Search & social**,
with a live Google-result preview and length warnings.

## What you can manage in the CMS

| Section | Controls |
|---------|----------|
| Products | Catalog, images, ordering, and full per-product SEO + Product schema |
| Categories | Category pages, use cases, FAQs (published as FAQPage), per-category SEO |
| Services | Service tabs on the homepage |
| Blog | Posts with article body, cover image, and per-post SEO + Article schema |
| FAQ | Site-wide questions, published to `/faq` as FAQPage structured data |
| Testimonials · Leadership · Stats · Clients | Homepage and About content |
| Company · Contact · Social · SEO | Site-wide defaults |
| Account | Change admin password |

Saving publishes immediately through `config/site.config.php`, keeping `config/site.config.js`
in sync as a fallback.

## Structure

```
saroj/
├── index.php products.php category.php product.php   Public pages (server-rendered)
├── blog.php blog-single.php faq.php about.php …
├── sitemap.php robots.php 404.php
├── .htaccess                Clean URLs, 301s, security headers, caching
├── admin/                   PHP CMS
│   ├── lib/seo.php          Page shell, meta, structured data
│   ├── lib/seo_fields.php   Shared per-item SEO panel
│   ├── lib/security.php     Sessions, headers, login throttling
│   └── config.local.php     DB credentials (git-ignored)
└── assets/                  CSS, JS, images, uploads
```

## Security

- Login throttling (8 failures per IP per 15 min), CSRF on every form, hardened session cookies.
- Admin sends `X-Frame-Options: DENY` plus a Content-Security-Policy; no inline event handlers.
- Uploads are validated by image type, stored under random filenames, and served from a directory
  where script execution is disabled.
- `admin/data/` and `admin/config.local.php` are blocked at the web server.
