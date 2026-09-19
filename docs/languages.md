# Languages

The default registry contains 27 languages. Pass the identifier in the
**Identifier** column to `Highlighter::highlight()`.

Identifiers are case-insensitive after trimming, but there are no short
aliases. Use `javascript`, not `js`; `typescript`, not `ts`; `bash`, not `sh`;
`yaml`, not `yml`; and `csharp`, not `cs`.

Read [Embedded languages](languages/embedded.md) for HTML, SVG, Markdown, and
Twig delegation.

## Default registry

| Language | Identifier | Category | Typical extension | Parsing focus | Example |
|---|---|---|---|---|---|
| Bash | `bash` | Shell | `.sh` | Shell keywords, built-ins, variables, strings, and here-docs | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/bash.sh) |
| C# | `csharp` | Programming | `.cs` | Two-pass definitions, calls, and type references | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/csharp.cs) |
| CSS | `css` | Stylesheet | `.css` | Selectors, declarations, values, comments, and at-rules | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/css.css) |
| Diff | `diff` | Change | `.diff` | File headers, hunks, added lines, and removed lines | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/diff.diff) |
| Dockerfile | `dockerfile` | Build | `Dockerfile` | Instructions, modifiers, variables, strings, and comments | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/dockerfile.Dockerfile) |
| dotenv | `dotenv` | Configuration | `.env` | Assignments, `export`, references, values, and comments | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/dotenv.env) |
| Go | `go` | Programming | `.go` | Two-pass functions, methods, receivers, and type references | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/go.go) |
| HTML | `html` | Markup | `.html` | Tags, attributes, text, and embedded CSS or JavaScript | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/html.html) |
| HTTP | `http` | Protocol | `.http` | Request or response lines, headers, and body text | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/http.http) |
| INI | `ini` | Configuration | `.ini` | Sections, assignments, typed values, references, and comments | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/ini.ini) |
| Java | `java` | Programming | `.java` | Two-pass definitions, calls, types, and brace context | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/java.java) |
| JavaScript | `javascript` | Programming | `.js` | Declarations, calls, literals, templates, and regular expressions | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/javascript.js) |
| JSON | `json` | Data | `.json` | Object keys, strings, numbers, booleans, and null | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/json.json) |
| Makefile | `makefile` | Build | `.mk` | Targets, dependencies, recipes, variables, and directives | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/makefile.mk) |
| Markdown | `markdown` | Markup | `.md` | Blocks, inline markup, and registered fenced languages | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/markdown.md) |
| PHP | `php` | Programming | `.php` | Two-pass semantic definitions, calls, types, and variables | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/php.php) |
| Python | `python` | Programming | `.py` | Two-pass function and class definitions, calls, and references | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/python.py) |
| Ruby | `ruby` | Programming | `.rb` | Two-pass definitions, calls, constants, and variables | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/ruby.rb) |
| Rust | `rust` | Programming | `.rs` | Two-pass definitions, calls, types, lifetimes, and macros | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/rust.rs) |
| SCSS | `scss` | Stylesheet | `.scss` | CSS plus variables, nesting, mixins, and interpolation | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/scss.scss) |
| SQL | `sql` | Data | `.sql` | Keywords, functions, identifiers, literals, and comments | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/sql.sql) |
| SVG | `svg` | Markup | `.svg` | XML-style markup plus embedded CSS or JavaScript | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/svg.svg) |
| Swift | `swift` | Programming | `.swift` | Two-pass definitions, calls, types, attributes, and variables | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/swift.swift) |
| Twig | `twig` | Templating | `.twig` | Twig expressions and tags with embedded HTML by default | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/twig.twig) |
| TypeScript | `typescript` | Programming | `.ts` | JavaScript plus types, interfaces, enums, and decorators | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/typescript.ts) |
| XML | `xml` | Markup | `.xml` | Tags, attributes, processing instructions, and CDATA | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/xml.xml) |
| YAML | `yaml` | Data | `.yaml` | Mappings, sequences, anchors, aliases, values, and comments | [Source](https://github.com/altophp/code-highlight/blob/main/examples/languages/yaml.yaml) |

The source files above are the canonical compact documentation examples.
See [Examples](examples.md) for generated previews.

## PHP snippets without an opening tag

Selecting `php` parses PHP from the first byte, including when the opening tag
is omitted:

```php
$html = $highlighter->highlight(
    '$total = array_sum($prices);',
    'php',
);
```

Any synthetic context required by PHP's native tokenizer is absent from the
returned stream, so text, byte offsets, lines, and columns describe the exact
source supplied by the caller.

The historical `php-snippet` identifier remains a compatibility alias for
`php`; it is not a 28th registered language. New code should use `php`.

## Register a custom language

Custom parsers implement `LanguageInterface` and return a `ParsedStream`:

```php
use Alto\Code\Highlight\Language\LanguageInterface;
use Alto\Code\Highlight\Parser\ParsedStream;
use Alto\Code\Highlight\Parser\ParsedToken;
use Alto\Code\Highlight\Scope;

final class MyLanguage implements LanguageInterface
{
    public function getIdentifier(): string
    {
        return 'my-language';
    }

    public function parse(string $code): ParsedStream
    {
        return new ParsedStream([
            new ParsedToken($code, Scope::MarkupText),
        ]);
    }
}

$highlighter->registerLanguage(new MyLanguage());
```

Registering an existing identifier replaces that parser on the highlighter
instance. Theme authors style the generic semantic scopes emitted by parsers;
see [Creating a theme](themes/creating.md).

## Extension contract

Custom parsers implement `LanguageInterface` and return a `ParsedStream` made
of `ParsedToken` values. `StreamBuilder`, `TokenType`, and `Scope` are
supported building blocks. `Languages::getDefaultLanguages()` returns the
built-in registry.

Embedded parsers use `EmbeddedLanguageCapable`, `EmbeddedLanguageContext`, and
the public types under `Alto\Code\Highlight\Embedded`. Their documented
constructors and methods follow the same compatibility promise described in
[Compatibility](compatibility.md).
