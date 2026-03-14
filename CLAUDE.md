# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Wedding + baptism website for Liliana & Luís (bodaLilianaLuis.pt). Single-page static site with a PHP backend for RSVP handling. Hosted on OVH shared hosting (PHP 8.x + Apache).

## Stack

- **Frontend**: Vanilla HTML/CSS/JS — no build tools, no framework
- **Backend**: Single PHP endpoint (`rsvp.php`) — saves to CSV + sends email via `mail()`
- **i18n**: Three languages (PT default, EN, FR) via `assets/js/i18n.js` using `data-i18n` attributes on HTML elements
- **Fonts**: Google Fonts — Dancing Script (script), Playfair Display (serif), Lato (sans)

## Development

No build step. Open `index.html` in a browser or serve with any local server:

```bash
php -S localhost:8000
```

The RSVP form POSTs to `rsvp.php`, so a PHP server is needed to test form submission. RSVP data is saved to `data/rsvp.csv`.

## Architecture

**Single HTML page** (`index.html`) with sections: hero, our-story, schedule, locations, RSVP, footer.

**CSS theming** (`assets/css/style.css`): Uses CSS custom properties in `:root`. Note the variable names are swapped from what you'd expect — `--purple` is actually blue (#0ea5e9) and `--blue` is actually purple (#7c3aed). This is intentional; do not "fix" it.

**i18n system** (`assets/js/i18n.js`): `TRANSLATIONS` object with `pt`, `en`, `fr` keys. The `applyTranslations()` function sets `innerHTML` on all elements with `data-i18n="key"`. When adding new translatable text, add the key to all three language objects and use `data-i18n` on the HTML element.

**main.js**: Self-executing IIFEs for each feature — countdown timer, header scroll effect, hamburger menu, active nav highlighting (IntersectionObserver), scroll fade-in animations, RSVP form submission (fetch to `rsvp.php`). On RSVP fetch failure, the form shows success anyway (intentional UX decision).

**RSVP backend** (`rsvp.php`): Validates POST fields, appends to CSV, sends notification email to `NOTIFY_EMAIL` and confirmation email to the guest. Returns JSON `{ok: true/false}`.

## Deployment

Upload all files to OVH shared hosting root. The `.htaccess` handles HTTPS redirect, SPA fallback routing, security headers, gzip compression, and browser caching. The `data/` directory is protected from web access by both `.htaccess` files.

Before deploying, verify `NOTIFY_EMAIL` in `rsvp.php` is set to the correct address.