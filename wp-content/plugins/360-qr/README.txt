=== 360 QR ===
Donate link: https://www.paypal.com/ncp/payment/R2L6JRLUD6LGJ
Tags: qr code, dynamic qr, shortlink, redirects, analytics
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.14
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create and track dynamic QR codes in WordPress with editable destinations, scan statistics, six styles, center logos, and SVG/PNG downloads.

== Description ==

# 360 QR

360 QR lets you create and manage dynamic QR codes directly in WordPress. The public QR URL remains stable while you can change its destination URL whenever needed.

### Features

- Create, edit, activate, pause, and delete dynamic QR codes.
- Public QR URLs using the `/q/CODE` structure.
- Scan counter and last scan timestamp.
- Six visual QR styles: classic, rounded, dots, diamonds, rounded squares, and geometric.
- Local QR generation without an external QR image service.
- SVG vector download and PNG downloads in standard (512 px), high (1024 px), and maximum (2048 px) resolutions.
- Optional centered logo in QR codes (PNG, JPG, or WebP), with an illustrative preview in the creation form.
- QR preview in a modal window.
- Automatic page-title detection from a destination URL in the admin panel.
- Admin dashboard with pagination and quick actions.
- Frontend dashboard available through a shortcode for logged-in users.
- Frontend users can manage only their own QR codes.
- Compatible with the existing `wp_net360_qr` table used by earlier project versions.

### Donations

If 360 QR is useful to you, you can support its development with a [PayPal donation](https://www.paypal.com/ncp/payment/R2L6JRLUD6LGJ).

Developed and maintained by [NET360.PE](https://net360.pe/).

== Screenshots ==

1. QR management dashboard with dynamic QR codes, scan statistics, and quick actions.
2. Create a dynamic QR code with an editable destination URL and automatic title detection.

== Installation ==

1. Install and activate 360 QR from the WordPress Plugins screen.
2. Open **360 QR** in the WordPress admin menu to manage QR codes.
3. To display the frontend dashboard, create a page and add the shortcode `[net360qr_dashboard]`.
4. The shortcode requires the visitor to be logged in.
5. If you are upgrading from an earlier project version, keep the existing QR table to preserve registered codes.

== Frequently Asked Questions ==

= What happens if I change the destination URL? =
The public QR URL does not change. The QR redirects to the new destination URL.

= Where are scans stored? =
360 QR stores the scan counter and last scan timestamp in the WordPress database. The plugin does not store visitor IP addresses.

= Do I need an external service to generate QR images? =
No. QR images are generated locally using code included in the plugin.

== Changelog ==

= 1.0.14 =
* Reserved a genuine rounded, white cutout in the QR module field for the centered logo instead of simply painting a white panel over existing modules.
* Increased the logo backing area and maintained inner spacing so the logo sits more naturally within the QR design.
* Matched the rounded logo cutout and backing treatment across PNG and SVG rendering.
* Preserved the existing QR styles and optional-logo workflow.

= 1.0.13 =
* Improved centered-logo integration with a larger white backing and more consistent spacing in PNG and SVG exports.

= 1.0.12 =
* Shortened the plugin short description to comply with the WordPress.org 150-character limit.
* Updated the stable tag for this release.

= 1.0.11 =
* Sanitized and validated QR style values submitted through the admin and frontend forms.
* Clarified the security handling for public, read-only QR image download endpoints whose allowed size values are restricted and which do not change site data.
* Continued hardening the plugin in response to Plugin Check security findings.

= 1.0.10 =
* Addressed Plugin Check output-escaping findings for internally generated QR preview HTML and SVG output.
* Preserved the SVG markup required for QR previews and vector downloads while documenting the intentional trusted-output handling.

= 1.0.9 =
* Translated the plugin interface into English, including administration, frontend dashboard, forms, validation messages, QR style names, accessibility labels, and download options.
* Preserved existing QR records, destinations, logos, styles, and scan statistics.

= 1.0.8 =
* Fixed download menu positioning so the quality options are not clipped by the QR list's scrollable container.
* Improved menu placement when space is limited and when the page or viewport changes.

= 1.0.7 =
* Added download choices for PNG at 512 px (Standard), 1024 px (High Quality), and 2048 px (Maximum Quality).
* Added a scalable SVG download option for vector workflows.
* Made download choices available from the QR management actions.

= 1.0.6 =
* Improved the visual selection state for QR style cards and their radio controls.
* Refined the presentation and descriptions of the available QR styles to make selections clearer.

= 1.0.5 =
* Added visual example cards for the available QR styles in the admin and frontend forms.
* Improved style names and descriptions so users can preview the appearance before selecting it.

= 1.0.4 =
* Hardened QR rendering for compatibility with different PHP GD builds.
* Added safer validation for uploaded logo files before rendering.
* Retained all six QR styles and the optional centered logo.

= 1.0.3 =
* Added six visual QR module styles: classic, rounded, dots, diamonds, rounded squares, and geometric.
* Added an illustrative preview explaining that the centered logo is optional.
* Preserved dynamic destinations, scan statistics, and existing QR records.

= 1.0.2 =
* Added optional centered logo uploads for QR previews and SVG/PNG output.
* Added a backward-compatible database column to store each QR code's logo attachment.

= 1.0.1 =
* Addressed WordPress.org review findings for output buffering, nonce validation, permissions, and unique plugin prefixes.
* Renamed internal plugin identifiers to the unique `net360qr` prefix while keeping the public plugin slug `360-qr` unchanged.

= 1.0 =
* First stable release prepared for submission to WordPress.org.
* Updated the plugin description to reflect its current features.
* Added donation and NET360.PE links.
* Updated compatibility metadata for WordPress 7.1 and PHP 7.4+.
* Improved page-title detection with safe HTTP requests and response-size limits.
* Hardened SQL queries, redirects, input handling, and output handling for security review.
* Updated documentation and installation information.

== Upgrade Notice ==

= 1.0.14 =
Improves centered-logo integration with a rounded white cutout reserved in the QR module field, with matching PNG and SVG rendering. Verify scan reliability with your intended QR content and logo before wide deployment.
