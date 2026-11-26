# iworks-tools-core

Shared PHP library for the free **iWorks.ie Tools** WordPress plugin suite (`iworksie-*`).

Each plugin Composer-requires this package and runs [Strauss](https://github.com/BrianHenryIE/strauss) at build time so the copy lands in that plugin’s `vendor-prefixed/` under a **plugin-specific namespace**. Two plugins can therefore ship different core versions without a “class already declared” fatal. Copies never talk through PHP classes; they cooperate only through the WordPress hooks below.

- **Requires:** PHP 7.4+, WordPress 6.5+
- **License:** GPL-2.0-or-later
- **Version:** 1.0.0 (`IWorksIE\Core\Core::VERSION`)

## Install (consuming plugin)

### GitHub (VCS)

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/littlegreenman70/iworks-tools-core"
    }
  ],
  "require": {
    "iworksie/tools-core": "^1.0"
  },
  "extra": {
    "strauss": {
      "target_directory": "vendor-prefixed",
      "namespace_prefix": "IWorksIE\\YourPlugin\\Vendor\\",
      "classmap_prefix": "IWorksIE_YourPlugin_",
      "constant_prefix": "IWIYP_",
      "packages": ["iworksie/tools-core"]
    }
  },
  "scripts": {
    "prefix-namespaces": [
      "@php vendor/bin/strauss"
    ],
    "post-install-cmd": ["@prefix-namespaces"],
    "post-update-cmd": ["@prefix-namespaces"]
  }
}
```

Copy `assets/css` and `assets/js` into `vendor-prefixed/assets/` after Strauss (see `bin/copy-core-assets.php` in this repo).

### Local path (dev)

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../iworks-tools-core",
      "options": { "symlink": true }
    }
  ]
}
```

## Boot

```php
$core = \IWorksIE\YourPlugin\Vendor\IWorksIE\Core\Core::boot( array(
	'plugin_file' => __FILE__,
	'slug'        => 'iworksie-health-check',
	'prefix'      => 'iwiehc',
	'name'        => __( 'Health Check', 'iworksie-health-check' ),
	'version'     => '1.0.0',
	'text_domain' => 'iworksie-health-check',
	'capability'  => 'manage_options',
	'wporg_url'   => 'https://wordpress.org/plugins/iworksie-health-check/',
) );

$core->checks()->add( new MyCheck() );
$core->jobs()->add( new MyJob() );
$core->menu()->add_submenu( 'Title', 'Menu', 'iworksie-health-check', $render_callback );
```

### i18n

Core has no `.pot`. Pass your plugin `text_domain` to `Core::boot()`. `Strings::get()` uses `translate( $english, $text_domain )`. Include `vendor-prefixed/` in `wp i18n make-pot` so Strauss-copied English strings are picked up under your domain.

## Hook contract (`iworks_tools_*`)

Public, versioned. **Add fields; never rename or remove.**

| Hook | Type | Payload |
|------|------|---------|
| `iworks_tools_register_core` | filter | `array[]` of `{ version, render_overview, register_menu, slug }` |
| `iworks_tools_plugins` | filter | `array[]` of `{ slug, name, version, description, admin_url, wporg_url, icon }` |
| `iworks_tools_catalogue` | filter | `array[]` of `{ label, url }` (default empty; no remote calls) |
| `iworks_tools_dead_weight_rules` | filter | Rule list for `Filesystem\DeadWeight` |
| `iworks_tools_backup_ignore_paths` | filter | Relative paths never counted as backup dead weight (defaults: `wp-content/upgrade`, `wp-content/upgrade-temp-backup`) |
| `iworks_tools_scan_roots` | filter | Allowed SizeScanner roots (defaults: `ABSPATH`, `WP_CONTENT_DIR`) |
| `iworks_tools_core_asset_url` | filter | Override asset base URL |

**Version history**

- **1.0.0** — Initial contract as above. Menu slug `iworks-tools`. Overview owned by highest `Core::VERSION` (`version_compare`; tie → first registered).

## Adding a check

Implement `Checks\Check` (`id`, `label`, `group`, `run(): Result`) and `$core->checks()->add( $check )`. AJAX action `{prefix}_run_check` (nonce `{prefix}_checks`) runs one check per request.

## Adding a job

Implement `Jobs\Job` (`start`, `step`, `finish`) and `$core->jobs()->add( $job )`. Endpoints: `{prefix}_job_start|step|cancel|status` (nonce `{prefix}_jobs`).

## Examples

`examples/iworksie-example-one` and `examples/iworksie-example-two` prove multi-copy coexistence. Example two rewrites its prefixed `Core::VERSION` to `1.0.1` after Strauss so it wins menu election.

```bash
composer install
composer install --working-dir=examples/iworksie-example-one
composer install --working-dir=examples/iworksie-example-two
bin/link-examples.sh   # symlinks into www.iworks.new by default
```

## Development

```bash
composer install
composer lint
composer analyse
composer test:unit
bin/install-wp-tests.sh   # once; needs MySQL database iworks_tests
composer test:integration
```

## Release

1. Bump `Core::VERSION` and `CHANGELOG.md`.
2. Tag `vX.Y.Z` and push.
3. Consumers require `^X.Y` and rebuild Strauss.

## License

GPL-2.0-or-later.
