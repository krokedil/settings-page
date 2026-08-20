# Sidebar configuration
A Sidebar will be added to both the support and addons page if the sidebar configuration is passed to the `register_page` method. The sidebar configuration should be an array with the following keys:
- `plugin_resources` (array) - An array with the configuration for the plugin resources.
- `additional_resources` (array) - An array with the configuration for additional resources.
- `boxes` (array) - An array of custom sidebar boxes, see [Sidebar boxes](#sidebar-boxes).

Both of the resources keys take a `links` array with the exact same link configuration as the [support](./support.md) page, and will output the links in the order they are passed in the array.

```php
array(
    'plugin_resources' => array(
        'links' => array(
            array(
                'text'   => 'Your link title',
                'target' => '_blank',
                'class'  => 'my-link-class my-other-link-class',
                'href'   => array(
                    'en' => 'https://example.com',
                    'sv' => 'https://example.se',
                ),
            ),
        ),
    ),
    'additional_resources' => array(
        'links' => array(
            array(
                'text'   => 'Your link title',
                'target' => '_blank',
                'class'  => 'my-link-class my-other-link-class',
                'href'   => array(
                    'en' => 'https://example.com',
                    'sv' => 'https://example.se',
                ),
            ),
        ),
    ),
)
```

The plugin resources is used for links to the plugin documentation, support, and other resources related to the plugin. The additional resources is used for links to other resources that can be useful for the user, but is not directly related to the plugin. For example the blog page, or general FAQ page.

## Sidebar boxes

Custom boxes render as separate sidebar cards, above the resources card, in the order they are passed. A box supports the following keys:

- `id` (string) - A unique id for the box. Used to identify the box if its rendering fails.
- `title` (string|array) - The box heading. Takes a plain string, or a locale array like the link `href` above.
- `content` (string|callable) - The box body. A callable prints its own output; a string is run through `wp_kses_post`.
- `class` (string) - Optional extra CSS class added to the box element.

```php
array(
    'sidebar' => array(
        'boxes' => array(
            array(
                'id'      => 'store_setup_check',
                'title'   => 'Store setup check',
                'content' => array( $my_checks, 'output_sidebar_box_content' ),
                'class'   => 'my-plugin-status-box',
            ),
        ),
        'plugin_resources' => array( /* ... */ ),
    ),
)
```

If a box `content` callable throws, the box is skipped, the rest of the page renders as usual, and the `krokedil_settings_page_render_error` action is fired with the box id, the throwable, and the box configuration.
