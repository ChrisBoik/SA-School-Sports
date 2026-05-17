# SA School Sports WordPress Plugin

Custom WordPress plugin for saschoolsports.co.za. Handles:
- Custom Banner Widget (main, with date/device/taxonomy visibility)
- Logo Shortcode Visibility (LSV) — customizer repeater control for logo shortcodes
- Category Loop – Top sidebar — widget area rendered above the first post on category archive pages

For prod ops, local stack, SSH, and client context see:
`~/Library/CloudStorage/OneDrive-Personal/Documents/Yard8/SASS/wordpress-local/CLAUDE.md`

## Plugin Files

| File | Runs in | Purpose |
|---|---|---|
| `sa-school-sports.php` | PHP | Main — widget class, widget_name filter, Widget Options device bypass, LSV customizer control, media shortcode, Category Loop – Top sidebar registration + `loop_start` injector |
| `customizer.js` | Customizer controls pane (parent frame) | Widget form behavior: media picker, live title rewrite, jQuery trigger for preview refresh, taxonomy badges, drag-to-move between sidebars, status computation |
| `custom-media-picker.js` | Customizer preview iframe | Rendered-widget UI on the right side: Edit/Remove/Up/Down/Drag buttons, jQuery UI sortable, sends `sass-*` messages back to controls pane |
| `customizer-device-preview.js` | Preview iframe | Listens for `previewedDevice` changes, toggles widgets via `data-sass-device-*` attributes and `extendedwopts-*` classes |
| `lsv-customizer.js` | Customizer controls pane | Logo Shortcode Visibility repeater UI |
| `widget-form.css` | Customizer controls pane | Widget form styling (section accordions, status badges, device toggles, tax list) |

## Deployment

Local is a live mount at `/var/www/html/wp-content/plugins/sa-school-sports` (bind-mounted from `~/Developer/sa-school-sports` via `docker-compose.yml` in `wordpress-local/`). Edit in this folder, refresh the browser.

**Deploying to prod**:

```bash
rsync -avz --progress \
  ~/Developer/sa-school-sports/ \
  saschoolsports:/var/www/html/saschoolsports.co.za/wp-content/plugins/sa-school-sports/ \
  --exclude='.git' --exclude='.DS_Store'
```

Always bump `wp_enqueue_script` / `wp_enqueue_style` version args in `sa-school-sports.php` when pushing JS/CSS changes — browsers cache by URL, and these URLs only change when the version string changes. Current versions: `customizer.js` 1.3.0, `widget-form.css` 1.3.0, `custom-media-picker.js` 1.2.0.

## Widget Architecture

### Instance data (per widget)

Stored in `wp_options.widget_custom_media_widget` as a serialized PHP array keyed by widget instance number. Each instance has:

- `title` — shown in widget header + bottom-right overlay in customizer preview
- `position_description` — shown in top-right overlay; used for admin orientation only
- `media_uri`, `media_aspect_ratio`
- `advertiser_uri` — clickable destination
- `tax_mode` (`show`/`hide`), `tax_scope` (1/2/3), `tax_categories` (int[])
- `show_from`, `show_to` — datetime-local
- `device_desktop`, `device_tablet`, `device_mobile` ('0'/'1')
- `extended_widget_opts-custom_media_widget-{ID}` — Widget Options plugin data embedded here (yes, weird)

### Visibility decision (in `widget()` method)

Runs in this order, short-circuiting on any `return`:

1. If **not** in customize preview AND device flags excluded → skip
2. If **not** in customize preview AND `show_from`/`show_to` out of range → skip
3. If `tax_categories` non-empty AND **not** in customize preview AND tax_mode+scope don't match current query → skip
4. Else render

Customizer preview deliberately bypasses 1–3. All logic is duplicated in `customizer-device-preview.js` for client-side device toggling.

## Customizer widget titles — the "(untitled)" gotcha

WordPress customizer **lazy-loads widget forms**. Until you expand a widget, its form HTML is not in the DOM. `rewriteSassWidgetTitles()` in `customizer.js` looks up the title from the widget form's `input[id*="-title"]`; if missing, it falls back to reading the widget setting from the customize API:

```js
var setting = api('widget_custom_media_widget[N]');
var val = setting ? setting.get() : null;
```

This is why we parse the control's `<li>` id attribute (`customize-control-widget_custom_media_widget-N`) and convert to `widget_custom_media_widget[N]`. If either form data or API data is missing, leave the PHP-rendered widget name alone — don't overwrite it with `(untitled)`.

## Why we use jQuery `.trigger('change')` not native `dispatchEvent`

WordPress's `customize-widgets.js` binds via jQuery delegated events (`container.on('change input', ':input', handler)`). While modern jQuery does dispatch on native events, WP's internal dirty-tracking is sensitive to jQuery event data (`.originalEvent` etc). Using `jQuery(el).trigger('change')` ensures WP reliably picks up form updates, which triggers the preview refresh cycle.

## Live media proxying

Not in this plugin; handled by `~/…/wordpress-local/config/mu-plugins/live-media-proxy.php` (mounted as mu-plugin). Rewrites media URLs from `http://localhost:8080/wp-content/uploads/…` to `https://saschoolsports.co.za/wp-content/uploads/…` on the frontend so we don't have to rsync gigabytes of uploads. Doesn't work in wp-admin (thumbnails won't load locally).

## Styling patterns

### Sections are `<details>` elements

The form uses native `<details>/<summary>` for collapsible sections. Status indicators (Expired/Scheduled/Hidden) appear in `::after` pseudo-elements on the first summary, driven by `.sass-status-*` CSS classes on the form element. Class is kept in sync by `syncAllFormStatusClasses()` on every DOM mutation (MutationObserver, debounced).

### Status badges

Three states, each with a CSS background tint:
- `.sass-status-expired` — red (show_to < now)
- `.sass-status-scheduled` — amber (show_from > now)
- `.sass-status-hidden` — grey (all 3 devices off)

Computed in JS (`computeWidgetStatus(form)`) from live form values, not from PHP-baked `data-status` attribute (which is stale once user edits).

## Dragging widgets between sidebars

`custom-media-picker.js` uses jQuery UI sortable scoped to each sidebar's parent element. Drag events send `sass-reorder-sidebar` messages to the controls pane via `wp.customize.preview`, which updates the `sidebars_widgets[sidebarId]` setting, which triggers WP's standard save flow.

Also supports programmatic move via the **Move to Sidebar** dropdown inside each widget form. On click, it directly calls `api('sidebars_widgets[src]').set(...)` and `api('sidebars_widgets[dst]').set(...)`.

## Category Loop – Top sidebar (added May 2026)

Third feature in this plugin — a widget area called **"Category Loop – Top"** (sidebar id `sass-category-loop-top`) that renders inside `.td-ss-main-content`, **above the first post card** on any category archive.

### Why it's wired this way

Category archives on this site are **not** rendered by the theme. tagDiv's `td-standard-pack` plugin intercepts WP's `template_include` filter and substitutes its own copy of `category.php` at `wp-content/plugins/td-standard-pack/Newspaper/category.php`, which calls `td-standard-pack/Newspaper/loop.php` for the post list. Neither `Newspaper/category.php` nor `Newspaper/loop-archive.php` runs — they're dead code on this site. Verified with a stamped `category.php` (marker did not appear) and a mu-plugin logging `template_include` (returned the plugin path).

Editing the plugin file would work but gets wiped on every td-standard-pack update.

### The hook

`add_action('loop_start', …)` fires once inside `WP_Query::the_post()` on the first iteration of the main loop, **before** `td-standard-pack`'s `td_template_layout->layout_open_element()` opens any column wrappers. So the widget output lands exactly between `<div class="td-ss-main-content">` and the first `td_module_X` card.

Guards (in order):
1. `static $rendered` flag — single-fire even if a Composer block re-runs a query on the same page
2. `is_admin()` — skip admin requests
3. `$query->is_main_query()` — ignore secondary queries
4. `is_category()` — only category archives (not tags, search, single, etc.)
5. `apply_filters('sass_show_category_loop_top_widget', true, $cat_id)` — opt-out per category
6. `is_active_sidebar('sass-category-loop-top')` — no output if empty (prevents orphan `<div>`)

### Using it

1. Appearance → Widgets (or Customizer → Widgets) — drop any Custom Banner Widget instance into **"Category Loop – Top"**
2. Set the widget's `tax_mode` / `tax_scope` / `tax_categories` to limit which category archives show that banner
3. One sidebar drives every category archive; per-banner visibility logic decides which renders where

### Per-category opt-out

```php
add_filter( 'sass_show_category_loop_top_widget', function( $show, $cat_id ) {
    return $cat_id === 1635 ? false : $show; // e.g. hide on hockey-results
}, 10, 2 );
```

## LSV (Logo Shortcode Visibility)

Second feature in this plugin — lets admins define logo replacements for shortcodes like `[logo_tag name=fairtree]`. Data is JSON-encoded into `theme_mod lsv_data`. Each entry has: name, image_url, fallback text, css_class, apply_in[] (content|titles|menus|widgets). Bots see plain text (for SEO); humans see the replacement image.

## Known limitations / future work

- Media picker: when selecting a new media item, the form's thumbnail `<img>` updates via DOM manipulation (see `openMediaWindow`), but the underlying widget preview in the iframe only refreshes through WP's standard selective refresh/full reload cycle. There may still be edge cases where the iframe shows stale media. If you see this, check `customizer.js`'s `mediaUploader.on('select')` handler.
- Media URLs locally show localhost:8080 after search-replace, which means the wp-admin thumbnail tries to load from localhost uploads — which typically don't exist locally (rsync excludes them). This is a known limitation, not a bug.
- `fab fa-x-twitter` isn't available in the currently-loaded FA 5.12.1. The X logo for Twitter is injected via CSS `background-image` SVG in the theme's Custom CSS (see wordpress-local/CLAUDE.md).

## Testing workflow

1. Edit plugin files in `~/Developer/sa-school-sports`
2. Refresh http://localhost:8080/wp-admin/customize.php
3. For preview-iframe JS: changes need cache-bust via version arg; edit `sa-school-sports.php` to bump version
4. Verify the fix on local, then `rsync` to prod as above

## Related files outside this plugin

- `~/…/wordpress-local/config/mu-plugins/live-media-proxy.php` — media URL rewriter
- `~/…/wordpress-local/config/php-local.ini` — increased PHP memory for heavy themes
- `~/…/wordpress-local/docker-compose.yml` — mounts this plugin directory into the WordPress container
