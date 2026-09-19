# Installation

Install Alto Code Highlight in a PHP 8.4 or later application and verify the runtime before adding it to a page.

## Requirements

Alto Code Highlight requires:

- PHP 8.4 or later;
- the `mbstring` extension;
- the `tokenizer` extension;
- Composer.

The package has no PHP package dependencies at runtime. It also needs no
Node.js process or client-side syntax highlighter.

## Install with Composer

Run this command in your project:

```bash
composer require alto/code-highlight
```

Framework applications normally load Composer's autoloader for you. In a
standalone PHP script, load it explicitly:

```php
require __DIR__.'/vendor/autoload.php';
```

## Smoke test

Save this as `highlight.php` in the project root:

```php
<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Alto\Code\Highlight\Highlighter;
use Alto\Code\Highlight\Theme\AltoTheme;

$highlighter = new Highlighter(new AltoTheme());
$code = '<?php echo "Alto is ready";';

echo "<style>\n".$highlighter->getTheme()->getStylesheet()."\n</style>\n";
echo $highlighter->highlight($code, 'php');
```

Run it and write the resulting HTML to a file:

```bash
php highlight.php > highlight.html
```

Open `highlight.html` in a browser. It should contain a dark Alto code block
with highlighted PHP.

## Common installation failures

### Composer rejects the PHP version

Check the CLI version used by Composer:

```bash
php --version
composer check-platform-reqs
```

The package requires PHP 8.4 or later.

### A required extension is missing

Check both extensions in the same PHP runtime used by Composer:

```bash
php --ri mbstring
php --ri tokenizer
```

Install or enable the missing extension, then rerun Composer.

### The autoloader cannot be found

Run `composer install` in the project and make the `require` path relative to
the script that executes it. A framework bootstrap usually already includes
`vendor/autoload.php`.

### A language is reported as unsupported

Use an exact identifier from the [language reference](languages.md). The
highlighter normalizes case and surrounding whitespace, but does not provide
aliases such as `js`, `ts`, `sh`, `yml`, or `cs`.

## Next step

Continue with [Getting started](getting-started.md) for a complete page,
line-number options, and error handling.
