# Bluelotus Infrasafety — Website

Responsive marketing site with a PHP admin CMS. Almost all public content is editable from the admin panel.

## Run locally

```
php -S localhost:8000
```

- Site: http://localhost:8000  
- Admin: http://localhost:8000/admin/  
- First-time install: http://localhost:8000/admin/install.php  

MySQL credentials are in `admin/bootstrap.php` (`bluelotus` on `localhost:3306`, user `root`).

**Default login:** `admin` / `admin123` (change under Account)

## What you can manage in CMS

| Section | Controls |
|---------|----------|
| Products | Full product catalog (slug, category, copy, tags, features, images) |
| Services | Service tabs on Services / Home |
| Categories | Product categories + category page content (intro, buyers, use cases, FAQs) |
| Blog | Post list on Blog / Home |
| Testimonials | Home client feedback cards |
| Leadership | About page founders & leadership |
| Stats | Homepage / About counters |
| Clients | Homepage marquee names |
| Company | Name, tagline, logos, founded year |
| Contact | Phones, emails, WhatsApp, address, hours, map |
| Social | Facebook, LinkedIn, Instagram, YouTube |
| SEO | Domain, default title, description, keywords |
| Account | Change admin password |

Edits publish through `config/site.config.php` and sync `config/site.config.js`.

## Structure

```
saroj/
├── *.html                 Public pages
├── admin/                 PHP CMS
├── config/site.config.php Live config from database
├── config/site.config.js  Synced fallback
└── assets/                CSS, JS, images
```
