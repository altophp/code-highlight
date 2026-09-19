# Theme adapters

Theme adapters reuse styles from Highlight.js, Prism, or TextMate while Alto
continues to parse and render source code on the server. They do not load a
client-side highlighter and do not add languages.

Prefer local theme files in production. The `fromFile()` factories validate
that a path exists, refers to a regular file, and is readable.

## Highlight.js CSS

```php
<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Alto\Code\Highlight\Adapter\HighlightJsThemeAdapter;
use Alto\Code\Highlight\Highlighter;

$theme = HighlightJsThemeAdapter::fromFile(
    __DIR__.'/themes/github-dark.css',
    isDark: true,
);
$highlighter = new Highlighter($theme);

echo '<style>'.$theme->getStylesheet().'</style>';
echo $highlighter->highlight('<?php echo "Hello";', 'php');
```

The adapter maps Alto semantic scopes to `hljs-*` classes, adapts the CSS
selectors to Alto's markup, and adds the `hljs` class to the generated `<code>`
element.

## Prism CSS

```php
<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Alto\Code\Highlight\Adapter\PrismThemeAdapter;
use Alto\Code\Highlight\Highlighter;

$theme = PrismThemeAdapter::fromFile(
    __DIR__.'/themes/prism-tomorrow.css',
    isDark: true,
);
$highlighter = new Highlighter($theme);

echo '<style>'.$theme->getStylesheet().'</style>';
echo $highlighter->highlight('const answer = 42;', 'javascript');
```

The adapter maps semantic scopes to Prism's `token` classes. Alto emits
`language-*` classes on its `<pre>` and `<code>` elements, so normal Prism
theme selectors can apply without running Prism JavaScript.

## TextMate `.tmTheme`

```php
<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Alto\Code\Highlight\Adapter\TextMateThemeAdapter;
use Alto\Code\Highlight\Highlighter;

$theme = TextMateThemeAdapter::fromFile(
    __DIR__.'/themes/monokai.tmTheme',
    isDark: true,
);
$highlighter = new Highlighter($theme);

echo '<style>';
echo '.alto-highlight { padding: 1rem; overflow-x: auto; background: #272822; }';
echo $theme->getStylesheet();
echo '</style>';
echo $highlighter->highlight('SELECT * FROM users;', 'sql');
```

The adapter parses the PList XML, maps recognized TextMate scopes to Alto
scopes, and generates `alto-tm-*` classes. TextMate use requires PHP's
SimpleXML extension. Add your own `.alto-highlight` container rule when the
generated stylesheet does not provide background, spacing, or overflow.

## Dark-mode metadata

The `isDark` argument records theme metadata returned by
`ThemeInterface::isDark()`. It does not inspect the source file or alter its
colors. Pass the value that matches the chosen theme.

## Remote constructors

`HighlightJsThemeAdapter` and `PrismThemeAdapter` constructors can fetch named
themes from their configured CDN when no local CSS path is supplied. If a
fetch fails, their stylesheet falls back to a CSS `@import`. This makes output
dependent on network access, so `fromFile()` is the deterministic choice for
production and documentation builds.

## Styling boundaries

Adapters translate style classes only:

- parsing remains entirely in Alto;
- supported language identifiers remain those in the active highlighter;
- source escaping and output structure remain Alto's responsibility;
- line numbers still use `alto-line-number` and `alto-highlighted`.

See [Languages](../languages.md), [Getting started](../getting-started.md),
and [Creating a theme](creating.md) for those separate contracts.
