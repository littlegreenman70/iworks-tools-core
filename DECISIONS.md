# Decisions

| Date (Europe/Dublin) | Decision | Reason |
|----------------------|----------|--------|
| 2026-11-26 | Place repo at `/opt/homebrew/var/www/iworks-tools-core` (sibling of `www.iworks.new`) | Sensible local tools location next to the WordPress test bed. |
| 2026-11-26 | i18n: `Core::boot()` requires `text_domain`; `Strings::get()` calls `translate( $english, $text_domain )`. Default catalogue keys wrap English via `__()` with placeholder domain `iworks-tools-core` only as the source string table. Plugins must scan `vendor-prefixed/` with `wp i18n make-pot`. | Strauss cannot rewrite text domains; Plugin Check needs real domains on the plugin; simplest approach that keeps core domain-agnostic. |
| 2026-11-26 | Disable PHPCS `WordPress.WP.I18n` for the whole ruleset (src is the only shipped PHP). | Core cannot own a text domain; Strings centralises translation. |
| 2026-11-26 | Example-two version bump: Strauss post-process replaces `Core::VERSION` string to `1.0.1` via `bin/bump-example-two-version.sh` after Strauss. | Cleaner than forking the library; proves version election. |
| 2026-11-26 | Omit `composer.lock` from git for a library. | Consumers resolve their own lock; CI installs fresh. |
| 2026-11-26 | Menu dashicon `dashicons-admin-tools`, position `75` (after Tools). | Matches WP admin conventions. |
| 2026-11-26 | SizeScanner child threshold: 1 MB. | Matches prompt; keeps overview trees readable. |
| 2026-11-26 | Strauss constraint `^0.32` (not `^2.3`). | Packagist latest is 0.32.0; no 2.x series exists. |
