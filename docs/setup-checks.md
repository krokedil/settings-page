# Store setup checks

Setup checks tell the merchant which store settings have to be fixed and which we recommend changing. The package runs the checks and renders them, so they look the same in every plugin. They are shown in four places:

- A **Store setup check** box in the sidebar. It has a summary banner (all passed, required checks fail, or recommended checks fail) and lists only the checks that did not pass. The box is added after any boxes passed in the `sidebar` args, see [Sidebar](./sidebar.md), and shows wherever the sidebar shows, which is the Support and Addons tabs.
- A section in the WooCommerce system report (WooCommerce → Status) with every check. The box links to it with "View full report", and it is part of the system report that the support form attaches.
- An admin notice on every admin page, listing the failed required checks. It only shows while the payment or shipping method is enabled, and it cannot be dismissed. It is left out on the Support and Addons tabs of the plugin's own settings page, where the box already shows the same checks. Recommended and info checks never cause a notice.
- The same notice above the list of payment methods (WooCommerce → Settings → Payments), for checks that belong to a payment gateway. Here it also shows while the gateway is disabled, since this is where it gets enabled. WooCommerce removes the admin notices of other plugins on that page and has no way to add content to the row of a payment method, so the notice is printed above the list.

## Registering checks

Register the checks early, for example on `admin_init`, and not when the settings page is rendered. Otherwise they are missing from the system report.

```php
use Krokedil\SettingsPage\SettingsPage;
use Krokedil\SettingsPage\SetupChecks\Checks;
use Krokedil\SettingsPage\SetupChecks\SetupCheck;

add_action(
    'admin_init',
    function () {
        SettingsPage::get_instance()
            ->set_plugin_name( 'My plugin' )
            ->add_setup_checks(
                'my_gateway',
                array(
                    // Built-in checks.
                    Checks::terms_page(),
                    Checks::price_decimals( 2 ),
                    Checks::credentials( array( 'merchant_id', 'shared_secret' ) ),

                    // A check on a WordPress option.
                    array(
                        'id'          => 'prices_include_tax',
                        'label'       => __( 'Prices entered with tax', 'my-plugin' ),
                        'description' => __( 'Prices without tax can cause rounding differences.', 'my-plugin' ),
                        'severity'    => SetupCheck::SEVERITY_RECOMMENDED,
                        'option'      => 'woocommerce_prices_include_tax',
                        'valid'       => array( 'yes' ),
                        'message'     => __( 'Prices are entered excluding tax.', 'my-plugin' ),
                        'fix_url'     => admin_url( 'admin.php?page=wc-settings&tab=tax' ),
                        'fix_label'   => __( 'Change the tax settings', 'my-plugin' ),
                    ),

                    // A custom check class.
                    new My_Custom_Check(),
                ),
                array(
                    'docs_url' => 'https://docs.krokedil.com/my-plugin/get-started/',
                    'owner'    => function () {
                        return WC()->payment_gateways()->payment_gateways()['my_gateway'] ?? null;
                    },
                )
            );
    }
);
```

The page id is the same id that is passed to `register_page()`. `add_setup_checks( $page_id, $checks, $args )` can be called more than once for a page. The optional `$args` are:

| Key | Type | Description |
| --- | --- | --- |
| `owner` | object or Closure | The object that `setting` checks read from, e.g. the payment gateway. A Closure is called when the checks run, so the gateway does not have to be loaded on every admin request. Defaults to the owner passed to `register_page()`, which is only known on the settings page itself. |
| `docs_url` | string | Documentation link for the checks that do not have their own `docs_url`. |
| `plugin_name` | string | Used in the box and as the title of the system report section. Defaults to the name set with `set_plugin_name()`. |
| `title` | string | Title of the box and the system report section. Defaults to "Store setup check". |
| `enabled` | bool or callable | Whether the payment or shipping method is enabled, which decides if the admin notice shows. Defaults to the `enabled` property of the owner. Pass a callable that reads the setting directly to keep the owner from being loaded on every admin page. |
| `notice` | bool | Set to `false` to turn the notices off. |
| `section` | string | The `section` of the plugin's WooCommerce settings page, used to leave the notice out where the box is shown. Only needed when it differs from the page id. |

## Severity

| Severity | Constant | Meaning |
| --- | --- | --- |
| `required` | `SetupCheck::SEVERITY_REQUIRED` | The plugin does not work until this is fixed. |
| `recommended` | `SetupCheck::SEVERITY_RECOMMENDED` | The plugin works, but we advise fixing it. |
| `info` | `SetupCheck::SEVERITY_INFO` | Neutral information, nothing has to be changed. |

The box lists failed required checks first, then recommended, then info. An `info` check is listed when it does not pass, but it never turns the summary banner red or yellow.

## Array checks

| Key | Type | Description |
| --- | --- | --- |
| `id` | string | Required. Unique id of the check. |
| `label` | string | The label shown for the check, worded as the wanted outcome, e.g. "Terms and conditions page set". The package adds "(required)" or "(recommended)". Defaults to the id. |
| `description` | string | Optional help text shown in the help tip of the check, explaining why it matters. |
| `severity` | string | `required` (default), `recommended` or `info`. |
| `message` | string | Shown when the check fails. Links (`<a>`), `<strong>`, `<em>` and `<code>` are allowed. |
| `passed_message` | string | Optional, shown when the check passes. |
| `fix_url` | string | Optional URL where the merchant can fix a failed check. |
| `fix_label` | string | Optional link text for `fix_url`. Say what the link does, e.g. "Change the permalink structure". Defaults to "Fix this". |
| `docs_url` | string | Optional documentation link. The package adds "Read more about this requirement in the plugin docs." after the message. |

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

Implement `Krokedil\SettingsPage\SetupChecks\SetupCheck` when an array is not enough. A check only returns data, the package renders it. Implement `DetailedSetupCheck` instead to also give the check a help text (`get_description()`) and its own documentation link (`get_docs_url()`).

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

The checks run every time the sidebar or the system report is shown. Keep them cheap, and do not call an external API from a check.

A check that throws is listed with "This check could not be run." and the `krokedil_settings_page_render_error` action is fired with the check id and the throwable. The rest of the list and the page render as usual.

## Built-in checks

All of them live in `Krokedil\SettingsPage\SetupChecks\Checks`. The last argument of every factory is an array that overrides the check arguments above, for example `Checks::price_decimals( 2, array( 'severity' => SetupCheck::SEVERITY_REQUIRED ) )`.

| Factory | Default severity | Passes when |
| --- | --- | --- |
| `terms_page()` | required | A published terms and conditions page is selected in WooCommerce. A selected page that is not published gets its own message with a link to edit the page. |
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

`SettingsPage::get_instance()->get_setup_check_results( $page_id )` returns the result of every check as an array, in the order the checks were added. Checks that do not apply to the store are left out. Each result has these keys:

| Key | Description |
| --- | --- |
| `id`, `label`, `severity`, `description`, `docs_url` | As registered. |
| `status` | `passed`, `failed`, or `error` when the check could not be run. |
| `message` | The message for the status. |
| `fix_url`, `fix_label` | The fix link of a failed check. |

`SettingsPage::get_instance()->setup_checks( $page_id )->get_failed( SetupCheck::SEVERITY_REQUIRED )` returns only the failed required checks, for example to show a notice or to stop the payment method from being enabled.

## Filter

`krokedil_settings_page_setup_checks` filters the checks of a page before they run. It receives the list of checks, the page id and the page owner.
