# RelayForge WordPress Plugin

RelayForge WordPress connects a WordPress site to RelayForge and turns live travel inventory into polished, bookable website experiences. It is designed for tour operators, destination marketers, agencies, and travel businesses that need production-ready listings, package pages, inquiry flows, and branded embeds without rebuilding their website.

## What It Does

- Displays RelayForge tours, destinations, reviews, availability, and forms in WordPress.
- Creates SEO-friendly public routes for tours, destinations, and packages.
- Proxies protected RelayForge media so images render securely inside WordPress.
- Provides native WordPress shortcodes for listings and published RelayForge forms.
- Supports theme-aware colors, typography inheritance, and branded card templates.
- Includes a dedicated WordPress admin experience with diagnostics, previews, shortcodes, and design controls.

## Requirements

- WordPress 6.2 or newer
- PHP 8.0 or newer
- Pretty permalinks enabled for public tour/destination/package routes
- A RelayForge API key

## Installation

1. Upload the plugin folder to `wp-content/plugins/relayforge-wordpress` or install the packaged zip.
2. Activate `RelayForge WordPress` in WordPress admin.
3. Open `RelayForge` from the WordPress admin sidebar.
4. Paste your RelayForge secret key in settings.
5. If public routes do not load immediately, visit `Settings > Permalinks` and save once.

## Public Routes

The plugin registers these routes automatically:

- `/packages/`
- `/tours/{slug}/`
- `/destinations/{slug}/`
- `/relayforge-demo/packages/`
- `/relayforge-demo/tour/`
- `/relayforge-demo/destination/`

The `/packages/` route uses server-side pagination and renders live RelayForge tours as package cards with filters for search, destination, type, and price.

## Shortcodes

### Tours

```text
[relayforge_tours limit="6" columns="3" template="overlay" show_price="yes"]
```

Attributes:

- `limit`: number of tours to fetch.
- `columns`: grid columns from `1` to `4`.
- `template`: `default`, `minimal`, `split`, `overlay`, `magazine`, `luxury`, `adventure`, `compact`, `feature`, or `custom`.
- `show_price`: `yes`, `no`, or blank to follow plugin settings.
- `cta_text`: custom button text.
- `custom_css_class`: extra grid class.
- `style_pack`: named imported template pack.

### Destinations

```text
[relayforge_destinations limit="6" columns="3" template="default"]
```

### Reviews

```text
[relayforge_reviews limit="6"]
[relayforge_reviews tour_id="tour_123" limit="6"]
```

### Availability

```text
[relayforge_availability tour_id="tour_123" from="2026-01-01" to="2026-12-31" limit="6"]
```

### Forms

List available published RelayForge forms and copy ready-to-use shortcodes:

```text
[relayforge_forms]
```

Render a specific published form natively:

```text
[relayforge_form id="FORM_ID"]
[relayforge_form slug="contact-form"]
```

Render the hosted RelayForge embed in an iframe:

```text
[relayforge_form id="FORM_ID" mode="iframe" height="720"]
```

Native forms support RelayForge field types including short text, long text, email, phone, date, select, radio, and checkbox. Multi-step RelayForge forms render as a true one-field-per-step flow with progress, Back, Next, and Submit controls.

## Admin Experience

The plugin adds one top-level `RelayForge` menu in WordPress admin with:

- Get started onboarding with RelayForge branding.
- Read-only tours and destinations overview.
- Shortcode reference with copy-ready examples.
- Settings for connection, card layouts, colors, fonts, images, analytics, accessibility, and diagnostics.
- iOS-style toggles for on/off settings.
- A flat, WordPress-friendly interface that avoids heavy boxed layouts.

## Design And Branding

RelayForge WordPress follows the active WordPress theme by default:

- Accent color inheritance from theme presets and global styles.
- Text/surface color inheritance with manual override support.
- Theme font inheritance with runtime browser fallback.
- Optional custom card CSS and template packs.

The plugin includes RelayForge brand SVG assets for the admin sidebar icon and Get started page lockup.

## Card Templates

Built-in tour and destination card templates:

- Default
- Minimal
- Split
- Overlay
- Magazine
- Luxury
- Adventure
- Compact
- Feature
- Custom HTML

Custom HTML supports placeholders and simple conditionals:

```html
<article class="rfp-user-card custom-tour">
  {{#if image_url}}<img src="{{image_url}}" alt="{{title}}" />{{/if}}
  <h3>{{title}}</h3>
  {{#if location}}<p>{{location}}</p>{{/if}}
  {{#if price}}<strong>{{price}}</strong>{{/if}}
  <a href="{{link}}">{{cta_text}}</a>
</article>
```

Tour placeholders:

- `{{title}}`
- `{{location}}`
- `{{image_url}}`
- `{{price}}`
- `{{link}}`
- `{{cta_text}}`
- `{{duration}}`
- `{{rating}}`
- `{{reviews_count}}`
- `{{currency}}`
- `{{from_price}}`
- `{{badge}}`

Destination placeholders:

- `{{name}}`
- `{{description}}`
- `{{image_url}}`
- `{{link}}`
- `{{cta_text}}`
- `{{country}}`
- `{{tour_count}}`
- `{{best_time}}`

## Security

- RelayForge API keys stay server-side in WordPress settings.
- Protected media is proxied through WordPress instead of exposing API-key authenticated URLs in browser image tags.
- Form submissions use WordPress nonces and submit through the plugin API client.
- Output is escaped and sanitized for WordPress rendering contexts.

## Build

Use the included build script to create the deployable plugin directory and zip:

```bash
bash scripts/build-plugin.sh
```

Output:

- `/Users/kefamgaya/Documents/relayforge-wordpress`
- `/Users/kefamgaya/Documents/relayforge-wordpress.zip`

## Development Notes

- Main bootstrap: `relayforge-wordpress.php`
- Plugin coordinator: `includes/class-plugin.php`
- API client: `includes/class-api-client.php`
- Shortcodes: `includes/class-shortcodes.php`
- Admin settings: `includes/class-settings.php`
- Public templates: `templates/`
- Frontend CSS/JS: `assets/css/relayforge.css`, `assets/js/relayforge.js`
- Admin CSS/JS: `assets/css/relayforge-admin.css`, `assets/js/relayforge-admin.js`

## License

GPLv2 or later.
