# Bluelotus Enterprises — Website

Responsive marketing site for a safety equipment supplier. Plain HTML/CSS/JS, no build step.

## Structure

```
saroj/
├── index.html
├── about.html
├── products.html
├── services.html
├── contact.html
├── blog.html
├── blog-single.html
├── privacy-policy.html
├── sitemap.xml
├── robots.txt
├── config/
│   └── site.config.js
└── assets/
    ├── css/
    │   ├── tokens.css
    │   └── site.css
    ├── js/
    │   ├── shell.js
    │   ├── render.js
    │   ├── app.js
    │   └── blog-single.js
    └── img/
        ├── sprite.svg
        ├── favicon.svg
        ├── hero.jpg
        ├── about.jpg
        ├── products.jpg
        ├── blog-1.jpg … blog-4.jpg
        └── og-cover.jpg
```

## Content

Company details, categories, services, testimonials, blog posts, and clients live in `config/site.config.js`. Update that file to change site-wide content.

## Scripts

| File | Role |
|------|------|
| jQuery 3.7.1 (CDN) | DOM helpers used by all site scripts |
| `shell.js` | Shared header, footer, floating actions |
| `render.js` | Binds config into the DOM |
| `app.js` | Nav, counters, tabs, forms, reveal |
| `blog-single.js` | Resolves `?post=` on article pages |


## Preview

```
python -m http.server 8000
```

Open `http://localhost:8000`.
