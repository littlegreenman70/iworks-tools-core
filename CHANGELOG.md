# Changelog

## 1.0.1 — 2026-11-26

- **SizeScanner:** store directory aggregates only (no per-file nodes); capped top-50 files, dead-weight files (500), and unreadable paths (100); drop working `nodes`/`stack` when finished.
- **Jobs Manager:** AJAX responses return a public view only (`job_id`, progress, message, done, cancelled, stale, exists, and `result` when done); unknown cancel ids return 404; start params pass through `map_deep( …, sanitize_text_field )`.
- **SizeScanner roots:** `root` must resolve inside `ABSPATH` / `WP_CONTENT_DIR` (filter `iworks_tools_scan_roots`); otherwise falls back to `ABSPATH`.
- **Report download:** only serves single-use, user-tied transients from `store_for_download()`; request body/query report text removed.
- **DeadWeight `backup_prefix`:** directories whose basename starts with `backup` only; never under `wp-content/plugins` or `wp-content/themes`.
- **i18n:** scan messages and die strings moved to `Strings` (plugin text domain).
- Example-two VERSION bump script now derives the next minor from `Core::VERSION`.

## 1.0.0 — 2026-11-26

Initial release.

- `Core::boot()` entry point with suite registration, menu election, checks, jobs, assets, and reports.
- Shared admin menu `iworks-tools` with Overview card grid.
- Checks runner (sync + AJAX), Site Health bridge helper.
- Chunked Jobs manager with resume/cancel.
- `Filesystem\SizeScanner` job and `DeadWeight` heuristics (read-only).
- Markdown `Report` builder and download handler.
- Example plugins demonstrating Strauss prefixes and version election.
