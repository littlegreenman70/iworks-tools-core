# Progress

Repo path: `/opt/homebrew/var/www/iworks-tools-core`

## v1.1.2 (2026-11-26 Europe/Dublin)

| Fix | Status | Notes |
|-----|--------|-------|
| Ignore WP upgrade / upgrade-temp-backup | done | Filter `iworks_tools_backup_ignore_paths` |
| Empty backup folders never match | done | `backup_prefix` requires bytes > 0 |
| Version 1.1.2 + examples + tag | done | Tag v1.1.2; PR #4 merged |

## v1.1.1 (2026-11-26 Europe/Dublin)

| Fix | Status | Notes |
|-----|--------|-------|
| wp-header-end after header band | done | Notices land below band |
| Site Health badge = plugin name / blue | done | Unit test |
| Score ring by score bands | done | Unit test |
| Result match list + table title cells | done | For HC backup listing / relative paths |
| Bar chart outside + distinct colours | done | CSS |
| Version 1.1.1 + examples + tag | done | Tag v1.1.1; PR #2 merged; CI green |

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
