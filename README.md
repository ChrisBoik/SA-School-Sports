# SA School Sports

Custom WordPress plugin for [saschoolsports.co.za](https://saschoolsports.co.za).

## Features

- **Custom Banner Widget** — banner/ad widget with date scheduling, per-device toggles (desktop/tablet/mobile), and category-based visibility (`tax_mode` × `tax_scope` × `tax_categories`)
- **Logo Shortcode Visibility (LSV)** — Customizer repeater control mapping `[logo_tag name=…]` shortcodes to images with bot-safe fallback text
- **Category Loop – Top sidebar** — widget area rendered above the first post on category archive pages (via `loop_start`, sidebar id `sass-category-loop-top`)

## Internals & deploy

See [`CLAUDE.md`](./CLAUDE.md) for full architecture notes:
- Widget instance data layout and the three-layer visibility decision
- Customizer JS files and the live-edit / refresh cycle
- Why category archives are rendered by `td-standard-pack`, not the theme — and why the loop-top sidebar uses `loop_start` instead of editing plugin files
- Deployment via `rsync` to production

## License

MIT — see [`LICENSE`](./LICENSE).
