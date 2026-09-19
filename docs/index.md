# Alto Code Highlight

Alto Code Highlight parses source code and renders escaped, theme-ready HTML
entirely in PHP. Semantic scopes distinguish language concepts across 27
built-in languages, including embedded CSS, JavaScript, PHP, and markup.

```php
use Alto\Code\Highlight\Highlighter;
use Alto\Code\Highlight\Theme\AltoTheme;

$highlighter = new Highlighter(new AltoTheme());
$html = $highlighter->highlight('<?php echo "Hello";', 'php');
```

The result is escaped semantic HTML ready for the selected theme stylesheet:

```html
<pre class="alto-highlight language-php"><code class="language-php"><span class="alto-punctuation">&lt;?php </span><span class="alto-keyword">echo</span> <span class="alto-string">&quot;Hello&quot;</span><span class="alto-punctuation">;</span></code></pre>
```

The package needs no browser-side highlighter, Node.js process, external
service, or third-party PHP runtime package. It also adapts Highlight.js,
Prism, and TextMate themes without handing parsing to those tools.

## Documentation

- [Installation](installation.md)
- [Getting started](getting-started.md)
- [Examples](examples.md)
- [Languages](languages.md)
- [Themes](themes.md)
- [Compatibility](compatibility.md)
