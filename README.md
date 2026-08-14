# Sillage

**GDPR-friendly visit log for WordPress — track who viewed what, when, and
from where — with filters, autocomplete, and PDF/CSV/Excel export.**

> ⚠️ **This README is a living document.** Every change that adds a
> setting, a REST endpoint, a database column/table, or a new dependency
> MUST update the corresponding section below in the same change. Do not
> let this file drift out of sync with the code — see
> `.cursor/rules/sillage.mdc`.

---

## Description

Sillage records and centralizes visits to your pages, posts, and custom
post types: who visited what, when, and from where. Built for admins who
need precise visit tracking on their WordPress site without relying on a
third-party analytics service.

## Features

- Detailed visit log: user (nicename, email), IP address with a
  geolocation lookup link, visited content (page, post, or any custom
  post type), entry date, and estimated exit date/session duration.
- Advanced filters with autocomplete: by user, by page/post, by date
  range.
- Export filtered data to PDF, CSV, and Excel.
- GDPR-conscious by design: configurable IP anonymization, automatic
  data purge based on a configurable retention period.
- *(Planned — Phase 2)* Analytics dashboard: traffic over time, top
  pages, per-user activity.

## Requirements

- PHP 8.3+
- WordPress 7.0+
- MySQL/MariaDB (whatever ships with your WordPress hosting)

## Installation

1. Upload the plugin to `/wp-content/plugins/sillage`, or install via
   the WordPress plugin directory (once published).
2. Activate through the "Plugins" menu in WordPress.
3. Go to **Sillage → Settings** to configure retention period and IP
   anonymization before relying on the log for compliance purposes.

## Settings

<!-- Keep this table in sync with admin/views/settings.php -->

| Setting | Description | Default |
|---|---|---|
| IP anonymization | Masks the last octet (IPv4) / trailing segment (IPv6) before storing | Off |
| Data retention (days) | Log rows older than this are purged daily via cron | 90 |
| IP geolocation link base URL | Used to build the "locate IP" link opened in a new tab | `https://ipinfo.io/` |

## Database

<!-- Keep in sync with includes/class-sillage-activator.php -->

Custom table: `{$wpdb->prefix}sillage_logs`

See `docs/sillage-plugin-specs.txt` §3 for the full schema. Current
schema version: `1.0` (tracked via the `sillage_db_version` option).

## REST API

<!-- Keep in sync with includes/class-sillage-rest.php -->

Namespace: `sillage/v1`

| Route | Method | Auth | Purpose |
|---|---|---|---|
| `/track/exit` | POST | Public (token-validated) | Closes a visit session via `navigator.sendBeacon()` |
| `/autocomplete/users` | GET | `manage_options` | User search for the admin filter selector |
| `/autocomplete/pages` | GET | `manage_options` | Content search for the admin filter selector |
| `/export` | POST | `manage_options` | Streams a filtered export (CSV/XLSX/PDF) |

## Dependencies

<!-- Keep in sync with composer.json / package.json -->

**PHP (Composer):**
- PhpSpreadsheet (Excel export)
- DomPDF (PDF export)

**JS (npm, bundled at build time — nothing loaded from a CDN in
production):**
- DataTables (server-side processing mode)
- Select2 (autocomplete)
- jQuery UI Datepicker (or lightweight alternative — see
  `docs/TECH-STACK.md`)

**CSS:**
- Tailwind CSS (compiled)

## Development

```bash
composer install
npm install
npm run build       # compiles Tailwind + bundles admin JS
```

See `docs/TECH-STACK.md` for the full stack rationale and open
decisions, and `docs/sillage-plugin-specs.txt` for the functional
specification.

## Roadmap

- [x] Phase 1: logging engine, admin list view, filters, export
- [ ] Phase 2: analytics dashboard (traffic charts, top pages, per-user
      activity, geographic map, session duration, anomaly detection)

## Privacy / GDPR

This plugin stores personal data (IP addresses, and — for logged-in
visitors — nicename and email). Enabling it means you are responsible
for:
- Configuring an appropriate retention period for your use case.
- Disclosing this data collection in your site's privacy policy.
- Considering IP anonymization if full IPs are not required for your
  purposes.

## Changelog

<!-- Keep in sync with each release -->

### 1.0.0 (unreleased)
- Initial development.

## License

GPL v2 or later, as required for WordPress.org distribution.
