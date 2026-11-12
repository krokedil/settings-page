# Store setup checks

Setup checks show the merchant a check list in a sidebar box: what is done, what has to be fixed, and what we recommend. The package runs the checks and renders the box, so it looks the same in every plugin.

The box is added after any boxes passed in the `sidebar` args, see [Sidebar](./sidebar.md). It shows wherever the sidebar shows, which is the Support and Addons tabs.

## Registering checks

```php
use Krokedil\SettingsPage\SettingsPage;
use Krokedil\SettingsPage\SetupChecks\Checks;
use Krokedil\SettingsPage\SetupChecks\SetupCheck;

SettingsPage::get_instance()
    ->set_plugin_name( 'My plugin' )
    ->register_page( 'my_gateway', $args, $gateway )
    ->add_setup_checks(
        'my_gateway',
        array(
            // Built-in checks.
            Checks::terms_page(),
            Checks::price_decimals( 2 ),
            Checks::credentials( array( 'merchant_id', 'shared_secret' ) ),

            // A check on a WordPress option.
            array(
                'id'       => 'prices_include_tax',
                'label'    => __( 'Prices entered with tax', 'my-plugin' ),
                'severity' => SetupCheck::SEVERITY_RECOMMENDED,
                'option'   => 'woocommerce_prices_include_tax',
                'valid'    => array( 'yes' ),
                'message'  => __( 'Enter prices including tax to avoid rounding differences.', 'my-plugin' ),
                'fix_url'  => admin_url( 'admin.php?page=wc-settings&tab=tax' ),
            ),

            // A custom check class.
            new My_Custom_Check(),
        )
    )
    ->output( 'my_gateway' );
```

`add_setup_checks( $page_id, $checks, $args )` can be called more than once for a page. The optional `$args` take a `title` for the box (defaults to "Store setup") and an extra CSS `class`.

## Severity

| Severity | Constant | Meaning |
| --- | --- | --- |
| `required` | `SetupCheck::SEVERITY_REQUIRED` | The plugin does not work until this is fixed. |
| `recommended` | `SetupCheck::SEVERITY_RECOMMENDED` | The plugin works, but we advise fixing it. |
| `info` | `SetupCheck::SEVERITY_INFO` | Neutral information, nothing has to be changed. |

The list is sorted so that failed required checks come first, then recommended, then info, and passed checks last.

## Array checks

| Key | Type | Description |
| --- | --- | --- |
| `id` | string | Required. Unique id of the check. |
| `label` | string | The label shown for the check. Defaults to the id. |
| `severity` | string | `required` (default), `recommended` or `info`. |
| `message` | string | Shown when the check fails. |
| `passed_message` | string | Optional, shown when the check passes. |
| `fix_url` | string | Optional URL where the merchant can fix a failed check. |
| `fix_label` | string | Optional link text for `fix_url`. Defaults to "Fix this". |

A check takes its outcome from one of these:

| Key | Type | Description |
| --- | --- | --- |
| `callback` | callable | Receives the page owner. Return `true` or `false`, a `CheckResult`, or `null` to leave the check out. |
| `option` | string | Name of a WordPress option to read. |
| `setting` | string | Key of a setting to read from the page owner (the third argument of `register_page`) through its `get_option()` method. |

A value read from `option` or `setting` is judged by the first of these that is set. Without any of them the check passes when the value is not empty.

| Key | Type | Description |
| --- | --- | --- |
| `compare` | callable | Receives the value and the page owner, returns whether the value is fine. |
| `valid` | array | The values that pass. |
| `invalid` | array | The values that fail. |

Values are compared as strings, so `2` matches the stored `'2'`.

## Custom checks

Implement `Krokedil\SettingsPage\SetupChecks\SetupCheck` when an array is not enough. A check only returns data, the package renders it.

```php
use Krokedil\SettingsPage\SetupChecks\CheckResult;
use Krokedil\SettingsPage\SetupChecks\SetupCheck;

class My_Custom_Check implements SetupCheck {
    public function get_id(): string {
        return 'my_custom_check';
    }

    public function get_label(): string {
        return __( 'Webhook registered', 'my-plugin' );
    }

    public function get_severity(): string {
        return self::SEVERITY_REQUIRED;
    }

    public function run(): CheckResult {
        if ( ! my_plugin_uses_webhooks() ) {
            return CheckResult::skipped();
        }

        return my_plugin_has_webhook()
            ? CheckResult::passed()
            : CheckResult::failed( __( 'Register the webhook.', 'my-plugin' ), admin_url( 'admin.php?page=my-plugin' ), __( 'Register', 'my-plugin' ) );
    }
}
```

The checks run every time the sidebar is shown. Keep them cheap, and do not call an external API from a check.

A check that throws is shown as "Unknown" and the `krokedil_settings_page_render_error` action is fired with the check id and the throwable. The rest of the list and the page render as usual.

## Built-in checks

All of them live in `Krokedil\SettingsPage\SetupChecks\Checks`. The last argument of every factory is an array that overrides the check arguments above, for example `Checks::price_decimals( 2, array( 'severity' => SetupCheck::SEVERITY_REQUIRED ) )`.

| Factory | Default severity | Passes when |
| --- | --- | --- |
| `terms_page()` | required | A published terms and conditions page is selected in WooCommerce. |
| `checkout_page()` | required | A published checkout page is selected in WooCommerce. |
| `https()` | required | The store address uses HTTPS. |
| `pretty_permalinks()` | required | The permalink structure is not "Plain". |
| `currency( $currencies )` | required | The store currency is in the list. |
| `base_country( $countries )` | required | The store country is in the list. |
| `credentials( $credentials )` | required | The callable returns true, or all the listed setting keys of the page owner are filled in. |
| `price_decimals( $decimals = 2 )` | recommended | WooCommerce prices use that number of decimals. |
| `enabled( $setting = 'enabled' )` | recommended | The setting of the page owner is `yes`. |
| `test_mode_off( $setting = 'testmode' )` | info | The setting of the page owner is not `yes`. |

## Reading the results

`SettingsPage::get_instance()->get_setup_check_results( $page_id )` returns the results as arrays, for example to add them to a system report. Each result has the keys `id`, `label`, `severity`, `status` (`passed`, `failed` or `error`), `message`, `fix_url` and `fix_label`. Checks that do not apply to the store are left out.

## Filter

`krokedil_settings_page_setup_checks` filters the checks of a page before they run. It receives the list of checks, the page id and the page owner.
