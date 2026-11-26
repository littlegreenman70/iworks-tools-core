# Changelog

## 1.1.1 — 2026-11-26

- **Admin notices:** output `<hr class="wp-header-end">` after the header band so WordPress relocates third-party notices below the title row, not between the `h1` and version pill.
- **Site Health badge:** use the plugin label prefix and a blue badge; status is already shown by the section.
- **Score ring:** colour by score (90+ green, 60-89 orange, under 60 red), not by whether any critical exists.
- **Result cards:** render optional match lists from result `data.matches` / `data.files`.
- **Disk bar chart:** distinct segment colours plus an `outside` segment class for non-`wp-content` remainder.
- **Tables:** cells may be `{ text, title }` so relative paths can keep the full path in `title`.

## 1.1.0 — 2026-11-26

- **Assets:** build URLs with `plugins_url()` so symlinked plugins load CSS/JS; register handles at `admin_enqueue_scripts` priority 5; show an admin notice (and `error_log` when `WP_DEBUG`) if assets cannot be resolved instead of failing silently.
- **Design:** Site Health inspired admin UI (header band, version pills, underline tabs, accordion results, score circle, filterable counts, empty state, progress %, overview cards).
- Unit test for `Assets::relative_to_plugin()`; integration test loading an example plugin through a symlink.

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
