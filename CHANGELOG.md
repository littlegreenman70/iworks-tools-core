# Changelog

## 1.0.0 — 2026-11-26

Initial release.

- `Core::boot()` entry point with suite registration, menu election, checks, jobs, assets, and reports.
- Shared admin menu `iworks-tools` with Overview card grid.
- Checks runner (sync + AJAX), Site Health bridge helper.
- Chunked Jobs manager with resume/cancel.
- `Filesystem\SizeScanner` job and `DeadWeight` heuristics (read-only).
- Markdown `Report` builder and download handler.
- Example plugins demonstrating Strauss prefixes and version election.
