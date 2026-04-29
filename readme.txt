=== RelayForge WordPress ===
Contributors: relayforge
Tags: travel, tourism, tours, bookings, destinations, forms
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect WordPress to RelayForge and publish live tours, destinations, package pages, reviews, availability, and inquiry forms.

== Description ==

RelayForge WordPress brings RelayForge travel inventory into WordPress with production-ready frontend routes, shortcodes, branded cards, native inquiry forms, secure media rendering, and an operator-friendly admin experience.

Key features:

* Live tours, destinations, reviews, availability, and published RelayForge forms.
* Public routes for `/packages/`, `/tours/{slug}/`, and `/destinations/{slug}/`.
* Secure media proxy for RelayForge protected images.
* Server-side package pagination.
* Native WordPress shortcodes for cards, forms, reviews, and availability.
* True native multistep form rendering for RelayForge multistep forms.
* Theme-aware colors and typography with manual override support.
* Multiple card templates: Default, Minimal, Split, Overlay, Magazine, Luxury, Adventure, Compact, Feature, and Custom HTML.
* Flat branded admin UI with diagnostics, shortcode reference, and copy-ready form shortcodes.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/relayforge-wordpress` or install the zip.
2. Activate `RelayForge WordPress` in WordPress admin.
3. Open `RelayForge` from the WordPress admin sidebar.
4. Paste your RelayForge secret key.
5. If routes do not load immediately, visit `Settings > Permalinks` and save once.

== Shortcodes ==

Tours:

`[relayforge_tours limit="6" columns="3" template="overlay" show_price="yes"]`

Destinations:

`[relayforge_destinations limit="6" columns="3" template="default"]`

Reviews:

`[relayforge_reviews limit="6"]`

Availability:

`[relayforge_availability tour_id="tour_123" limit="6"]`

List published RelayForge forms and their ready shortcodes:

`[relayforge_forms]`

Render one RelayForge form natively:

`[relayforge_form id="FORM_ID"]`

Render one RelayForge form as an iframe:

`[relayforge_form id="FORM_ID" mode="iframe" height="720"]`

== Public Routes ==

The plugin registers:

* `/packages/`
* `/tours/{slug}/`
* `/destinations/{slug}/`
* `/relayforge-demo/packages/`
* `/relayforge-demo/tour/`
* `/relayforge-demo/destination/`

== Card Templates ==

Available card templates:

* Default
* Minimal
* Split
* Overlay
* Magazine
* Luxury
* Adventure
* Compact
* Feature
* Custom HTML

Custom HTML supports placeholders and conditional blocks such as `{{#if price}}...{{/if}}`.

Tour placeholders include `{{title}}`, `{{location}}`, `{{image_url}}`, `{{price}}`, `{{link}}`, `{{cta_text}}`, `{{duration}}`, `{{rating}}`, `{{reviews_count}}`, `{{currency}}`, `{{from_price}}`, and `{{badge}}`.

Destination placeholders include `{{name}}`, `{{description}}`, `{{image_url}}`, `{{link}}`, `{{cta_text}}`, `{{country}}`, `{{tour_count}}`, and `{{best_time}}`.

== Security ==

RelayForge API keys remain server-side in WordPress. Protected media is proxied through WordPress, and native form submissions use WordPress nonces before submitting through the RelayForge API client.

== Changelog ==

= 0.2.1 =
* Added branded admin assets.
* Added theme-aware color and typography inheritance.
* Added secure media proxying.
* Added public tour, destination, and packages routes.
* Added server-side packages pagination.
* Added native published form rendering, form directory shortcode, and multistep forms.
* Added richer card templates, settings toggles, and admin diagnostics.
