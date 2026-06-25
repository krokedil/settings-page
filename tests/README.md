# Tests

Integration tests for the settings-page package, built with
[Codeception](https://codeception.com/) and
[lucatume/wp-browser](https://wpbrowser.wptesting.tools/).

The suite boots a real WordPress + WooCommerce instance (via the `WPLoader`
module) on a file-based **SQLite** database, so no MySQL server is required.

## What is covered

The `Integration` suite tests the WooCommerce-facing settings pages:

- `WcSettingsPage` – defaults, the full page wrapper, the graceful render-error
  fallback (`output_subsection_safe`), and the static field HTML helpers
  (section start/end, button, radio, divider).
- `Gateway` – id / title / description derivation from a `WC_Payment_Gateway`
  and the rendered gateway settings.
- `Shipping` – the same, for a `WC_Shipping_Method`.

Shared test doubles (a stub gateway and shipping method) live in
[`Integration/_fixtures.php`](Integration/_fixtures.php) and are loaded by the
suite bootstrap.

## Running the tests

```bash
composer test          # run the Integration suite
composer test:debug    # same, with Codeception --debug output
composer test:reset-db # delete the SQLite test DB (forces a clean reinstall next run)
```

Or directly:

```bash
vendor/bin/codecept run Integration
```

> **About the SQLite database:** the suite uses `WPLoader` with `skipInstall: true`
> (see [`Integration.suite.yml`](Integration.suite.yml)). The first run on an
> empty database installs WordPress + WooCommerce from scratch (the clean
> `CREATE TABLE` path); subsequent runs skip the install/activation cycle and just
> load the plugins. This avoids WooCommerce / ActionScheduler re-running its
> `ALTER TABLE ... MODIFY COLUMN` upgrade step, which the SQLite drop-in cannot
> translate. If you upgrade WooCommerce or change the schema, run
> `composer test:reset-db` to force a clean reinstall.

## Fresh checkout / CI bootstrap

The following are generated artifacts and are **git-ignored** (see
[`.gitignore`](../.gitignore)):

- `tests/_wordpress/` – the downloaded WordPress core + the SQLite drop-in
  (`wp-content/db.php`, `wp-content/mu-plugins/sqlite-database-integration`).
- `tests/_plugins/`, `tests/_themes/` – WooCommerce and Storefront, installed by
  Composer into these paths (see the `extra.installer-paths` config).
- `tests/_wordpress/data/db.sqlite` – the test database.

On a fresh clone:

1. `composer install` – installs wp-browser and drops WooCommerce / Storefront
   into `tests/_plugins` / `tests/_themes`.
2. Provision WordPress core into `tests/_wordpress` and the SQLite drop-in. This
   was originally scaffolded with wp-browser's setup (`vendor/bin/codecept init
   wpbrowser`). If `tests/_wordpress` is missing, re-run that setup (it is
   interactive and will regenerate the config files in this folder).
