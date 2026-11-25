# Support Tab Configuration
The support tab outputs content items, optionally grouped in sections, and a button that opens a support ticket with Krokedil.

The support configuration is an array with these keys:
- `content` (array) - Content items, output in order. See [Content items](#content-items).
- `sections` (array) - Sections, output in order after `content`. See [Sections](#sections).
- `use_helpscout` (`yes`|`no`) - Whether to show the support ticket button. Defaults to `yes`.

Set at least one of `content` and `sections`, otherwise the support page only shows the sidebar.

## Sections
Each section has:
- `title` (string|array) - The section heading. A string or an array with 2 letter ISO 639-1 language codes as keys, `en` is the fallback.
- `description` (string|array) - Optional text under the heading, in the same format as `title`.
- `content` (array) - The content items of the section.

When the page is registered with `styled_output`, every section is a box that can be collapsed, with the same look as the sections of the General tab. The sections are open by default. Without `styled_output`, a section is a plain heading followed by its content.

The support ticket button is output at the end of the last section. Without sections, it is output after `content`.

```php
'support' => array(
    'sections' => array(
        array(
            'title'       => array( 'en' => 'Documentation', 'sv' => 'Dokumentation' ),
            'description' => array( 'en' => 'Guides for the plugin.' ),
            'content'     => array(
                array(
                    'type'   => 'link',
                    'text'   => 'Get started',
                    'target' => '_blank',
                    'href'   => 'https://docs.krokedil.com/',
                ),
            ),
        ),
        array(
            'title'   => 'Support',
            'content' => array(
                array( 'type' => 'text', 'en' => 'Contact us using the button below.' ),
            ),
        ),
    ),
),
```

## Content items
Every item has a `type`.

### text
A text, keyed by language code. HTML is allowed.
```php
array( 'type' => 'text', 'en' => 'Your text', 'sv' => 'Din text' )
```

### link
- `text` (string|array) - The link text.
- `href` (string|array) - The URL, or an array with language codes as keys. Always pass `en`, it is the fallback.
- `target` (string) - For example `_blank` or `_self`. `_blank` links get an icon that shows they open in a new tab.
- `class` (string) - Extra classes. `no-external-icon` removes the external link icon.
```php
array(
    'type'   => 'link',
    'text'   => 'Your link title',
    'target' => '_blank',
    'class'  => 'my-link-class',
    'href'   => array(
        'en' => 'https://example.com',
        'sv' => 'https://example.se',
    ),
)
```

### link_text
A text with one link in it. `text` is keyed by language code and contains one `%s`, which is replaced by `link`, a link in the format above.
```php
array(
    'type' => 'link_text',
    'text' => array(
        'en' => 'Your english text that should contain a link %s.',
        'sv' => 'Your swedish text that should contain a link %s.',
    ),
    'link' => array(
        'text'   => 'Your link title',
        'target' => '_blank',
        'class'  => 'no-external-icon',
        'href'   => array( 'en' => 'https://example.com' ),
    ),
)
```

### list
A bulleted list. `items` holds content items.
```php
array(
    'type'  => 'list',
    'items' => array(
        array( 'type' => 'text', 'en' => 'First item' ),
    ),
)
```

### spacer
Vertical space.
```php
array( 'type' => 'spacer' )
```
