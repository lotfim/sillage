=== Sillage ===
Contributors: lotfim
Donate link: https://github.com/lotfim/
Tags: analytics, log, visits, gdpr, privacy
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

GDPR-friendly visit log: track which logged-in users viewed what, when, and from where.

== Description ==

Sillage records front-office visits by logged-in users to your pages, posts, and public custom post types. Review them in an admin table with filters and export to CSV, Excel, or PDF.

Anonymous visitors are never logged. The WordPress admin area, archives, search results, and 404 pages are never logged.

= Features =

* Visit log: user, email, IP (with geolocation lookup link), content, entry date, estimated exit date
* Filters with autocomplete: by user, by content, by date range
* Analytics dashboard: visits over time, top contents and users (admin)
* Export the filtered dataset to CSV, Excel, and PDF
* IP anonymization and configurable data retention
* WordPress personal data export and erase integration

= Privacy =

This plugin stores personal data (IP address, nicename, email) for logged-in users. Configure a retention period, consider IP anonymization, and disclose collection in your site's privacy policy. Uninstalling the plugin does not delete existing logs.

= Third-party libraries =

Bundled for Excel/PDF export (self-hosted, no CDN):

* PhpSpreadsheet (MIT)
* DomPDF (LGPL-2.1)
* DataTables (MIT)
* Select2 (MIT)
* Flatpickr (MIT)
* Chart.js (MIT)

== Installation ==

1. Upload the `sillage` folder to `/wp-content/plugins/`
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Sillage → Settings to configure retention and IP anonymization

== Frequently Asked Questions ==

= Are anonymous visitors logged? =

No. Only logged-in users are recorded, and only on the public site (singular pages, posts, and public custom post types).

= Does uninstalling delete the logs? =

No. Deactivating or uninstalling keeps the log table and settings. Rows are removed only by the retention cron (when a retention period is set) or via WordPress personal data erasure.

= Is the exit date exact? =

No. Exit time is best-effort, sent with `navigator.sendBeacon()` when the tab is hidden or closed. Crashes and some mobile browsers may prevent it from firing.

== Changelog ==

= 1.0.0 =
* Initial release: visit logging for logged-in users, admin filters, CSV/Excel/PDF export, GDPR settings.
