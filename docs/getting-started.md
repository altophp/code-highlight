# Getting started

This guide renders a complete HTML page with one highlighted PHP example.

Save the script below as `highlight.php` beside `vendor`, then run
`php highlight.php > highlight.html` and open the result in a browser.

## Render a code block

```php
<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Alto\Code\Highlight\Highlighter;
use Alto\Code\Highlight\Theme\AltoTheme;

$theme = new AltoTheme();
$highlighter = new Highlighter($theme);

$code = <<<'PHP'
<?php

final class Greeting
{
    public function for(string $name): string
    {
        return "Hello, {$name}";
    }
}
PHP;

$highlightedCode = $highlighter->highlight($code, 'php');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Alto example</title>
    <style><?= $theme->getStylesheet() ?></style>
</head>
<body>
    <?= $highlightedCode ?>
</body>
</html>
```

The highlighter escapes source text before it creates HTML. Insert its return
value as trusted generated markup; escaping that value again would display the
`<pre>`, `<code>`, and `<span>` tags as text.

The generated page contains a `<pre class="alto-highlight language-php">`
block and the bundled Alto stylesheet. Compare its rendered colors with the
checked-in [PHP previews](examples.md#php).

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

`parse()` returns a `ParsedStream` and preserves the source exactly:
`$stream->toString()` equals the original code. The parser also resolves
configured embedded languages without requiring a theme or choosing an output
format.

`CodeParser` accepts an optional embedding registry and language list. It also
exposes `registerLanguage()`, `getEmbeddedRegistry()`, and
`setEmbeddingEnabled()` for the same parser configuration used by
`Highlighter`.

## Construct a highlighter

The concrete constructor accepts a theme and two optional custom registries:

```php
$highlighter = new Highlighter(
    theme: $theme,
    embeddedRegistry: null,
    languages: null,
);
```

Passing `null` uses the built-in embedding plans and all 27 default languages.
The `languages` argument accepts a list of `LanguageInterface`
implementations. It replaces the default list rather than extending it; call
`registerLanguage()` after construction when you only need to add or replace
one parser.

## `Highlighter::highlight()`

The method accepts four arguments:

```php
interface HighlighterInterface
{
    public function highlight(
        string $code,
        string $language,
        bool $lineNumbers = false,
        array $highlightLines = [],
    ): string;
}
```

- `$code` is the source text.
- `$language` is an exact [registered identifier](languages.md).
- `$lineNumbers` adds a numbered span at the start of every line.
- `$highlightLines` is a list of 1-indexed line numbers. Highlighted numbers
  receive the `alto-highlighted` class.

Use named arguments when enabling the optional features:

```php
$html = $highlighter->highlight(
    $code,
    'php',
    lineNumbers: true,
    highlightLines: [3, 6],
);
```

Built-in themes color syntax tokens but do not prescribe line-number
presentation. Add application CSS for the two structural classes:

```css
.alto-line-number {
    display: inline-block;
    width: 3rem;
    color: color-mix(in srgb, currentColor 55%, transparent);
    user-select: none;
}

.alto-line-number.alto-highlighted {
    color: inherit;
    font-weight: 700;
}
```

`alto-highlighted` is applied to the line-number span, not to a wrapper around
the full source line.

## Highlight PHP without an opening tag

Selecting `php` is authoritative: the source is parsed as PHP from its first
byte even when it is a single line without `<?php`:

```php
$html = $highlighter->highlight(
    '$page->getByRole("button")->click();',
    'php',
    lineNumbers: true,
    highlightLines: [1],
);
```

The parser may add an opening tag internally for PHP's native tokenizer, but
that context never appears in the output. Text, line numbers, highlighted
lines, byte offsets, and columns remain relative to the supplied source.

## Handle an unknown language

Unknown identifiers throw `LanguageNotFoundException`:

```php
use Alto\Code\Highlight\Exception\LanguageNotFoundException;

try {
    $html = $highlighter->highlight($code, $requestedLanguage);
} catch (LanguageNotFoundException $exception) {
    $html = '<pre><code>'.
        htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').
        '</code></pre>';
}
```

The highlighter lowercases and trims the identifier. It does not infer a
language from a filename and does not expand aliases.

## Emit theme CSS once

`getTheme()` returns the same theme instance passed to the constructor:

```php
$stylesheet = $highlighter->getTheme()->getStylesheet();
```

Place that stylesheet once in the document `<head>` or in a cached CSS asset.
Do not emit it for every code block. The same `Highlighter` instance can render
multiple blocks with the selected theme.

To switch themes, create the requested theme and a corresponding highlighter
before rendering the page. See the [built-in theme variants](themes.md).

## Other public operations

`Highlighter` also exposes:

- `registerLanguage()` to add or replace a parser by its identifier;
- `getEmbeddedRegistry()` to inspect the active embedding plans;
- `setEmbeddingEnabled()` to toggle a configured host/target pair.

See [Embedded languages](languages/embedded.md) for the embedding contracts
and [Compatibility](compatibility.md) for supported public boundaries.
