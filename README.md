# Sillage

**GDPR-friendly visit log for WordPress — track which logged-in users
viewed what, when, and from where — with filters, autocomplete, and
PDF/CSV/Excel export.**

> ⚠️ **This README is a living document.** Every change that adds a
> setting, a REST endpoint, a database column/table, or a new dependency
> MUST update the corresponding section below in the same change. Do not
> let this file drift out of sync with the code — see
> `.cursor/rules/sillage.mdc`.

---

## Description

Sillage records front-office visits by **logged-in users** to your pages,
posts, and public custom post types: who visited what, when, and from
where. Built for admins who need precise visit tracking on their
WordPress site without relying on a third-party analytics service.

Anonymous visitors are never logged. wp-admin, archives, search, and
404s are never logged.

## Features

- Detailed visit log: user (nicename, email), IP address with a
  geolocation lookup link, visited content (page, post, or any public
  custom post type), entry date, and estimated exit date.
- Advanced filters with autocomplete: by user, by page/post, by date
  range.
- Export filtered data to PDF, CSV, and Excel.
- GDPR-conscious by design: configurable IP anonymization, automatic
  data purge based on a configurable retention period, WordPress
  personal data export/erase integration.
- *(Planned — Phase 2)* Analytics dashboard: traffic over time, top
  pages, per-user activity.

## Requirements

- PHP 8.3+
- WordPress 6.8+
- MySQL/MariaDB (whatever ships with your WordPress hosting)

## Installation

1. Upload the plugin to `/wp-content/plugins/sillage`, or install via
   the WordPress plugin directory (once published).
2. Activate through the "Plugins" menu in WordPress.
3. Go to **Sillage → Settings** to configure retention period and IP
   anonymization before relying on the log for compliance purposes.

Uninstalling the plugin **does not** delete the log table or settings.
The only automatic deletion is the retention cron (when enabled).

## Settings

<!-- Keep this table in sync with admin/views/settings.php -->

| Setting | Description | Default |
|---|---|---|
| IP anonymization | Masks the last octet (IPv4) / trailing bits (IPv6, keep /48) before storing | Off |
| Data retention (days) | Log rows older than this are purged daily via cron. `0` disables auto-purge | 90 |
| IP geolocation link base URL | Used to build the "locate IP" link opened in a new tab | `https://ipinfo.io/` |

## Database

<!-- Keep in sync with includes/class-sillage-database.php -->

Custom table: `{$wpdb->prefix}sillage_logs`

See `sillage-plugin-specs.txt` §3 for the full schema. Current schema
version: `1.0.0` (tracked via the `sillage_db_version` option).

Columns: `id`, `user_id`, `user_nicename`, `user_email`, `ip_address`,
`object_id`, `object_title`, `object_type`, `entry_date`, `exit_date`,
`session_token`.

## REST API

<!-- Keep in sync with includes/class-sillage-rest.php -->

Namespace: `sillage/v1`

| Route | Method | Auth | Purpose |
|---|---|---|---|
| `/track/exit` | POST | Logged-in (token-validated, rate-limited) | Closes a visit session via `navigator.sendBeacon()` |
| `/logs` | GET | `manage_options` | DataTables server-side page of log rows |
| `/autocomplete/users` | GET | `manage_options` | User search for the admin filter selector |
| `/autocomplete/pages` | GET | `manage_options` | Content search for the admin filter selector |

Filtered file export is an `admin-post.php` action (`sillage_export`),
not a REST route.

## Dependencies

<!-- Keep in sync with composer.json / package.json -->

**PHP (Composer):**
- PhpSpreadsheet (Excel export)
- DomPDF (PDF export)

**JS (npm, bundled at build time — nothing loaded from a CDN in
production):**
- DataTables (server-side processing mode)
- Select2 (autocomplete)

**CSS:**
- Tailwind CSS v3 (compiled, `sil-` prefix)

## Development

```bash
composer install
npm install
npm run build       # compiles Tailwind + bundles admin JS
```

See `TECH-STACK.md` for the full stack rationale, and
`sillage-plugin-specs.txt` for the functional specification.

## Roadmap

- [x] Phase 1: logging engine, admin list view, filters, export
- [ ] Phase 2: analytics dashboard (traffic charts, top pages, per-user
      activity, geographic map, session duration, anomaly detection)

## Privacy / GDPR

This plugin stores personal data for **logged-in users only** (IP
addresses, nicename, and email). Enabling it means you are responsible
for:
- Configuring an appropriate retention period for your use case.
- Disclosing this data collection in your site's privacy policy.
- Considering IP anonymization if full IPs are not required for your
  purposes.

WordPress **Tools → Export / Erase Personal Data** includes Sillage log
rows for the requested user.

## Changelog

<!-- Keep in sync with each release -->

### 1.0.0 (unreleased)
- Initial development.

## License

GPL v2 or later, as required for WordPress.org distribution.
