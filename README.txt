=== Sillage ===
Contributors: lotfim
Donate link: https://github.com/lotfim/
Tags: analytics, log, visits, gdpr, privacy, audit
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GDPR-friendly visit log for logged-in users: who viewed what, when, and from where — plus filters, export, and an analytics dashboard.

== Description ==

Sillage records **front-office visits by logged-in users** to your pages, posts, and public custom post types. Review them in an admin table, filter with autocomplete, export the current result set, and open an analytics dashboard built on the same data.

It is a **self-hosted audit trail**, not a third-party analytics SaaS. Anonymous visitors are never logged. The WordPress admin area, archives, search results, and 404 pages are never logged.

= Visit log =

* User nicename, email, IP (with a configurable geolocation lookup link)
* Visited content with a type badge (Page / Post / CPT) and a link when the content still exists
* Entry date and estimated exit date
* Server-side DataTables: paging and sorting stay fast on large logs
* Filters (AND): user, content, date range — with Select2 autocomplete and locale-aware date pickers

= Analytics =

* KPI cards: visits, unique users, unique contents, average duration
* Visits and unique users over time (hourly when the range is short, otherwise daily)
* Top contents, top users, and a breakdown by content type
* Same filters as the visit log (default: last 30 days, capped at 366 days)

= Export =

* CSV, Excel (.xlsx), and PDF of the **currently filtered** dataset
* Generated on the server in chunks (not the visible table page only)

= Privacy =

* Optional IP anonymization (IPv4 last octet / IPv6 keep /48)
* Configurable retention (daily purge; `0` = never auto-purge)
* WordPress Tools → Export / Erase Personal Data includes Sillage rows
* Uninstalling does **not** delete logs or settings

= Requirements =

* WordPress 6.8+
* PHP 8.3+
* Capability `manage_options` for the admin screens

Source strings are English. A French (`fr_FR`) translation is bundled.

== Installation ==

1. Upload the `sillage` folder to `/wp-content/plugins/`, or install the zip from **Plugins → Add Plugin**.
1. Activate **Sillage** from the Plugins screen.
1. Open **Sillage → Settings** and set a retention period. Enable IP anonymization if you do not need full addresses.
1. Update your site privacy policy to mention this collection.
1. Visit a published page or post while logged in on the front of the site. Rows appear under **Sillage → Visit log**; totals appear under **Sillage → Analytics**.

== Frequently Asked Questions ==

= Are anonymous visitors logged? =

No. Only logged-in users are recorded, and only on public singular views (pages, posts, and public custom post types).

= Is wp-admin tracked? =

No. The admin area, REST/AJAX/cron/feeds, previews, the customizer, bots, prefetch/prerender, archives, search, 404s, and the posts index are skipped.

= Does uninstalling delete the logs? =

No. Deactivating or uninstalling keeps the log table and settings. Rows are removed only by the retention cron (when a period is set) or via WordPress personal data erasure.

= Is the exit date exact? =

No. Exit time is best-effort, sent with `navigator.sendBeacon()` when the tab is hidden or closed. Crashes and some mobile browsers may prevent it from firing. Average duration on the dashboard only uses rows that have an exit time.

= Can I exclude some roles from tracking? =

Not in this version. Every logged-in role is recorded, including administrators.

= Is this a replacement for Google Analytics or Matomo? =

No. Sillage answers “which logged-in user opened this content”. It does not track anonymous traffic, campaigns, or e-commerce.

= Is French supported? =

Yes. The plugin ships with a `fr_FR` translation. The admin language follows the user’s WordPress locale (including date filter formats).

= Where can I look up an IP? =

Each IP in the visit log links to a configurable lookup base URL (default: `https://ipinfo.io/`). Sillage does not ship a GeoIP database or a map.

= What happens if I have a lot of rows? =

The visit log is server-side (one page at a time). Analytics run SQL aggregations on a bounded date range. Exports stream in chunks of 500 rows.

== Screenshots ==

1. Visit log with filters, autocomplete, and export
2. Analytics dashboard: KPIs, time series, top contents and users
3. Settings: IP anonymization, retention, and PDF options

== Changelog ==

= 1.0.0 =
* Visit logging for logged-in users on front-office singulars
* Admin visit log (DataTables, filters, CSV/Excel/PDF export)
* Analytics dashboard (KPIs, charts, top lists)
* GDPR settings: IP anonymization, retention cron, privacy exporter/eraser
* English source strings and French (`fr_FR`) translation

== Upgrade Notice ==

= 1.0.0 =
Initial release.
