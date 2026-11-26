# Progress

Repo path: `/opt/homebrew/var/www/iworks-tools-core`

## v1.1.0 (2026-11-26 Europe/Dublin)

| Fix | Status | Notes |
|-----|--------|-------|
| Asset symlink / plugins_url | done | Register prio 5; notice on failure; symlink integration test |
| Design polish | done | Header band, pills, tabs, accordions, empty state, filters |
| Version 1.1.0 + examples + tag | done | Tag v1.1.0; PR #1 merged; CI green |

## v1.0.1 review fixes (2026-11-26 Europe/Dublin)

| Fix | Status | Notes |
|-----|--------|-------|
| 1 SizeScanner lean state | done | Dir aggregates, top 50 / dead 500 / unread 100; drop nodes when done |
| 2 Job AJAX public view | done | Manager::public_view; no stack/nodes in AJAX |
| 3 Scan root allow-list | done | ABSPATH / WP_CONTENT_DIR + iworks_tools_scan_roots |
| 4 Report download | done | Single-use user-tied transient; no POST/GET body |
| 5 Small i18n / DeadWeight / cancel 404 | done | Strings, backup_prefix dirs only, cancel 404 |
| 6 Version 1.0.1 + examples + tag | done | Tag v1.0.1 |

Earlier v1.0.0 work remains complete (see git history / tag `v1.0.0`).
