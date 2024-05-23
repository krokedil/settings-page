# Krokedil Settings Page
Adds functionality to extend a settings page in WordPress or WooCommerce with additional tabs for Support and Addons.

## Installation
1. Require the composer package by adding it in your composer.json file.
```json
{
    ...
    "require": {
        ...
        "krokedil/settings-page": "^1.0.0"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/krokedil/settings-page.git"
        }
    ]
    ...
}
```
2. Run `composer update` to install the package.

## Usage
To use this with your plugin, you first need to register the page with the `Krokedil\SettingsPage\SettingsPage` class by calling the `register` method.

To this method you need to pass a unique page id, and an array of arguments. The arguments array can contain the following keys:
- `support` (array) - An array with the configuration for the support page.
- `addons` (array) - An array with the configuration for the addons page.
- `sidebar` (array) - An array with the configuration for the sidebar.
- `general_content` (callable) - A callable function or method that will output the HTML for the general settings page you want to extend.

More information about the support and addons arrays can be found in the [Support](./docs/support.md), [Addons](./docs/addons.md) and [Sidebar](./docs/sidebar.md) documentation. If you do not pass anything to the support or addons keys, the tabs will not be added for them. Which can be useful if you do not wish to offer any addons for specific plugins.

```php
use Krokedil\SettingsPage\SettingsPage;

SettingsPage::get_instance()->register( 'your-page-id', $args );
```

This will register the settings page with the given id, and configure the support and addons tabs as well as the navigation between the different tabs. This will not output the tabs directly, and we need to then call the `output` method to output the tabs and the content for the current tab.

```php
SettingsPage::get_instance()->output('your-page-id');
```

To also output the navigation tabs for the different pages, you can call the method for that as well, and should be done either before the `output` method, or in the callable function for the `general_content` key.

```php
SettingsPage::get_instance()->navigation('your-page-id')->output();
SettingsPage::get_instance()->output('your-page-id');
```
or
```php
my_plugin_settings_content() {
    SettingsPage::get_instance()->navigation('your-page-id')->output();
    // Your settings page content
    ?>
    ...
    <?php
}

SettingsPage::get_instance()->register( 'your-page-id', [
    'general_content' => 'my_plugin_settings_content'
]);

SettingsPage::get_instance()->output('your-page-id');
```
