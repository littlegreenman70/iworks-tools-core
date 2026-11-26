# Progress

Repo path: `/opt/homebrew/var/www/iworks-tools-core`

| Step | Status | Notes |
|------|--------|-------|
| 1. Repo setup | done | composer, phpcs, phpstan, gitignore, gitattributes |
| 2. Modules | done | Core, Suite, Admin, UI, Checks, Jobs, Filesystem, Report, Assets, Strings |
| 3. Example plugins | done | example-one (core 1.0.0) + example-two (core VERSION bumped to 1.0.1) |
| 4. Tests | done | unit 15 OK; integration 3 OK |
| 5. CI | done | `.github/workflows/ci.yml` (PHP 7.4/8.3, integration, plugin-check) |
| 6. Docs | done | README, CHANGELOG, DECISIONS, LICENSE |
| 7. GitHub | done | `littlegreenman70/iworks-tools-core` private, tag `v1.0.0` |

Manual verify on https://www.iworks.new (Europe/Dublin 2026-11-26): one iWorks Tools menu owned by example-two; Overview lists both; deactivate two → one owns menu; check/job classes run; debug.log clean under WP_DEBUG.

Started: 2026-11-26 (Europe/Dublin)
Finished: 2026-11-26 (Europe/Dublin)
