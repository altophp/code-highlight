# ALTO Code Highlight

Server-side syntax highlighting for PHP applications, with semantic scopes,
embedded languages, and no third-party PHP package dependencies at runtime.

&nbsp; ![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-00B7FF?logoColor=00B7FF&labelColor=050608)
&nbsp; ![CI](https://img.shields.io/github/actions/workflow/status/altophp/code-highlight/CI.yml?branch=main&label=Tests&labelColor=050608&color=00B7FF)
&nbsp; [![Packagist](https://img.shields.io/packagist/v/alto/code-highlight?label=Packagist&labelColor=050608&color=00B7FF)](https://packagist.org/packages/alto/code-highlight)
&nbsp; ![License](https://img.shields.io/github/license/altophp/code-highlight?label=License&labelColor=050608&color=00B7FF)
&nbsp; [![GitHub Sponsors](https://img.shields.io/github/sponsors/smnandre?logo=githubsponsors&logoColor=00B7FF&label=%20Sponsor&labelColor=050608&color=00B7FF)](https://github.com/sponsors/smnandre)

![PHP highlighted with the Alto Dark theme](docs/assets/examples/alto-dark/php.png)

Core highlighting runs entirely in PHP. It needs no browser runtime, Node.js
process, or external service. Its parsers assign semantic scopes, so themes can
distinguish a function definition from a call or a type definition from a
reference.

## Installation

Install ALTO Code Highlight with Composer:

```bash
composer require alto/code-highlight
```

ALTO Code Highlight requires PHP 8.4 or later, Mbstring, and Tokenizer.
Tokenizer is included with PHP; Mbstring is available in most PHP distributions
but must be enabled.

See the [installation guide](docs/installation.md) for verification and
troubleshooting.

## Quick Start

```php
<?php

use Alto\Code\Highlight\Highlighter;
use Alto\Code\Highlight\Theme\AltoTheme;

$theme = new AltoTheme();
$highlighter = new Highlighter($theme);
$code = '<?php echo "Hello, Alto!";';

echo '<style>'.$theme->getStylesheet().'</style>';
echo $highlighter->highlight($code, 'php');
```

`highlight()` returns escaped HTML inside
`<pre class="alto-highlight"><code>…</code></pre>`. Emit a theme stylesheet
once per page, then reuse the highlighter for every code block.

## Parse without rendering

Use `CodeParser` when another component needs semantic tokens instead of HTML:

```php
use Alto\Code\Highlight\CodeParser;

$stream = (new CodeParser())->parse(
    '$total = array_sum($prices);',
    'php',
);

foreach ($stream as $token) {
    echo $token->text.' '.$token->scope->value.PHP_EOL;
}
```

`parse()` returns a `ParsedStream` and preserves the caller's source exactly:
`$stream->toString()` is the original code. It also resolves configured
embedded languages, without requiring a theme or choosing an output format.

## What it covers

- **27 languages:** the PHP web stack plus common programming, markup, data,
  configuration, and query languages.
- **Semantic highlighting:** context-aware scopes for definitions, calls,
  types, variables, constants, and other language concepts.
- **Embedded languages:** CSS and JavaScript in HTML/SVG, fenced code in
  Markdown, and language blocks in Twig.
- **Line controls:** optional line numbers and selected-line emphasis.
- **12 built-in variants:** seven theme families, including Alto, GitHub,
  Dracula, Polar, Cupertino, Noctis, and Solar.
- **Theme compatibility:** adapters for Highlight.js CSS, Prism CSS, and
  TextMate `.tmTheme` files.

## Documentation

| Guide | Contents |
|---|---|
| [Documentation index](docs/index.md) | Choose the right guide |
| [Getting started](docs/getting-started.md) | Complete rendering, line numbers, and errors |
| [Languages](docs/languages/index.md) | Exact identifiers and language capabilities |
| [Themes](docs/theming/index.md) | Built-in variants and visual examples |
| [Create a theme](docs/theming/creating.md) | Implement `ThemeInterface` |
| [Embedded languages](docs/languages/embedded.md) | HTML, SVG, Markdown, and Twig |
| [Theme adapters](docs/theming/adapters.md) | Highlight.js, Prism, and TextMate |
| [Public API](docs/api/index.md) | Parsing, rendering, and extension contracts |
| [Examples](docs/examples.md) | Compact examples and generated previews |

The complete source examples are available in [`examples/languages/`](examples/languages/).

## Languages

Use the lowercase identifier in the second argument to `highlight()`:

```text
bash        csharp       css          diff         dockerfile
dotenv      go           html         http         ini
java        javascript   json         makefile     markdown
php         python       ruby         rust         scss
sql         svg          swift        twig         typescript
xml         yaml
```

Choosing `php` parses the source as PHP from its first byte. An opening tag is
optional:

```php
$html = $highlighter->highlight(
    '$total = array_sum($prices);',
    'php',
);
```

The returned text and token positions are relative to the caller's source. The
legacy `php-snippet` identifier remains an alias for `php`.

## Line numbers and highlighted lines

```php
$html = $highlighter->highlight(
    code: $code,
    language: 'php',
    lineNumbers: true,
    highlightLines: [2, 3],
);
```

## Choose a theme

```php
use Alto\Code\Highlight\Theme\GitHubTheme;

$light = new GitHubTheme(dark: false);
$dark = new GitHubTheme();
```

Browse the [built-in theme matrix](docs/theming/index.md), learn how to
[create a theme](docs/theming/creating.md), or reuse an existing stylesheet
through a [theme adapter](docs/theming/adapters.md).

## Integrations

[alto/twig-code-highlight](https://github.com/altophp/twig-code-highlight)
adds blocks and filters for Twig applications. The core package remains
framework-independent and can be used in Symfony controllers, Laravel views,
static generators, or any PHP rendering pipeline.

## Contributing

Contributions of all kinds are welcome. Visit the
[project on GitHub](https://github.com/altophp/code-highlight) to
[report a bug](https://github.com/altophp/code-highlight/issues/new),
[suggest a feature](https://github.com/altophp/code-highlight/issues/new), or
[open a pull request](https://github.com/altophp/code-highlight/pulls).

Before submitting code, run:

```bash
# Runs PHP CS Fixer, PHPStan, and PHPUnit
composer qa
```

Language parsers use fixtures under `tests/Language/`. Public showcase examples
live separately under `examples/languages/`; they are short documentation
samples rather than exhaustive parser tests.

## Support

ALTO Code Highlight is open source. You can support its continued development through
[GitHub Sponsors](https://github.com/sponsors/smnandre).

Sharing this package with others or
[starring it on GitHub](https://github.com/altophp/code-highlight) is also much
appreciated.

## License

ALTO Code Highlight is released by [ALTO PHP](https://altophp.com) under the
[MIT License](LICENSE).
