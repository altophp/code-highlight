# Creating a theme

A theme implements `ThemeInterface`. It maps Alto's semantic scopes to CSS
class names and returns the stylesheet that defines those classes.

## Complete theme

The following theme covers every current `Scope`, styles the highlighter
container, and includes the structural line-number classes:

```php
<?php

declare(strict_types=1);

namespace App\Highlight;

use Alto\Code\Highlight\Scope;
use Alto\Code\Highlight\ThemeInterface;

final class OceanTheme implements ThemeInterface
{
    public function getName(): string
    {
        return 'Ocean Dark';
    }

    public function isDark(): bool
    {
        return true;
    }

    public function getCssClasses(): array
    {
        $classes = [];

        foreach (Scope::cases() as $scope) {
            $classes[$scope->value] = match ($scope) {
                Scope::Comment,
                Scope::CommentDocblock,
                Scope::CommentTask => 'ocean-comment',

                Scope::Keyword,
                Scope::KeywordDeclaration,
                Scope::KeywordOperator,
                Scope::KeywordControl,
                Scope::StorageModifier => 'ocean-keyword',

                Scope::String,
                Scope::StringInterpolated,
                Scope::StringTemplateExpression,
                Scope::RegExp,
                Scope::AttributeValue,
                Scope::TagAttributeValue => 'ocean-string',

                Scope::Number,
                Scope::Boolean,
                Scope::Null,
                Scope::Constant,
                Scope::BuiltInConstant,
                Scope::EnumCase,
                Scope::SupportConstant => 'ocean-literal',

                Scope::Variable,
                Scope::VariableParameter,
                Scope::VariableProperty,
                Scope::VariableThis => 'ocean-variable',

                Scope::Namespace,
                Scope::TypeDefinition,
                Scope::TypeReference,
                Scope::BuiltInType,
                Scope::SupportType => 'ocean-type',

                Scope::FunctionDefinition,
                Scope::FunctionCall,
                Scope::FunctionBuiltin,
                Scope::SupportFunction => 'ocean-function',

                Scope::AttributeName,
                Scope::TagAttributeName => 'ocean-attribute',

                Scope::TagName,
                Scope::SectionName => 'ocean-tag',

                Scope::DiffAdded => 'ocean-added',
                Scope::DiffRemoved,
                Scope::DiagnosticError => 'ocean-error',
                Scope::DiffChanged,
                Scope::DiagnosticWarning => 'ocean-warning',
                Scope::DiagnosticInfo => 'ocean-info',
                Scope::Meta => 'ocean-meta',
                Scope::Operator => 'ocean-operator',
                Scope::Punctuation => 'ocean-punctuation',
                Scope::MarkupText => 'ocean-markup',
                Scope::Whitespace => 'ocean-default',
            };
        }

        return $classes;
    }

    public function getStylesheet(): string
    {
        return <<<'CSS'
.alto-highlight {
    margin: 0;
    padding: 1rem;
    overflow-x: auto;
    border-radius: 0.5rem;
    background: #0b1724;
    color: #d8e6f3;
    font: 0.875rem/1.55 ui-monospace, SFMono-Regular, Menlo, monospace;
    tab-size: 4;
}

.alto-highlight code {
    font: inherit;
}

.alto-highlight .ocean-default,
.alto-highlight .ocean-punctuation,
.alto-highlight .ocean-markup { color: #d8e6f3; }
.alto-highlight .ocean-comment { color: #7890a6; font-style: italic; }
.alto-highlight .ocean-keyword { color: #c792ea; }
.alto-highlight .ocean-string { color: #addb67; }
.alto-highlight .ocean-literal { color: #f78c6c; }
.alto-highlight .ocean-variable { color: #82aaff; }
.alto-highlight .ocean-type { color: #ffcb6b; }
.alto-highlight .ocean-function { color: #7fdbca; }
.alto-highlight .ocean-attribute { color: #89ddff; }
.alto-highlight .ocean-tag { color: #f07178; }
.alto-highlight .ocean-added { color: #addb67; }
.alto-highlight .ocean-error { color: #ff5370; }
.alto-highlight .ocean-warning { color: #ffcb6b; }
.alto-highlight .ocean-info { color: #82aaff; }
.alto-highlight .ocean-meta { color: #c792ea; }
.alto-highlight .ocean-operator { color: #89ddff; }

.alto-highlight .alto-line-number {
    display: inline-block;
    width: 3rem;
    color: #60788e;
    user-select: none;
}

.alto-highlight .alto-line-number.alto-highlighted {
    color: #ffcb6b;
    font-weight: 700;
}
CSS;
    }
}
```

Use it like a built-in theme:

```php
use Alto\Code\Highlight\Highlighter;
use App\Highlight\OceanTheme;

$theme = new OceanTheme();
$highlighter = new Highlighter($theme);

echo '<style>'.$theme->getStylesheet().'</style>';
echo $highlighter->highlight($code, 'php', lineNumbers: true);
```

## Semantic scopes, not lexer tokens

A lexer token records an exact piece of source text. A semantic scope records
the role that text plays across languages. For example, PHP, Go, and Python
parsers can all emit `Scope::FunctionDefinition` even though their lexical
rules differ. Themes style the shared semantic role and do not need to know
which parser produced it.

Keep token class names theme-specific. A prefix such as `ocean-` avoids
collisions with application classes and lets two theme stylesheets coexist
without accidentally restyling generic names such as `.string` or `.keyword`.

## Verify the implementation

At minimum, test these contracts:

```php
$theme = new OceanTheme();
$classes = $theme->getCssClasses();

foreach (Scope::cases() as $scope) {
    assert(isset($classes[$scope->value]));
}

$highlighter = new Highlighter($theme);
$html = $highlighter->highlight(
    '<?php echo "<script>";',
    'php',
    lineNumbers: true,
    highlightLines: [1],
);

assert(str_contains($html, '&lt;?php'));
assert(str_contains($html, '&lt;script&gt;'));
assert(str_contains($html, 'alto-line-number'));
assert(str_contains($html, 'alto-highlighted'));
```

Also review the theme in a browser:

- confirm readable contrast for every semantic color;
- test long lines and `.alto-highlight` horizontal overflow;
- test line numbers and selected-line numbers;
- test markup, strings, comments, definitions, calls, and diff scopes;
- confirm that source text reconstructs exactly after stripping generated
  markup.

The canonical samples in [Examples](../examples.md) provide stable inputs for
visual review.

## Extension contract

Custom themes implement `ThemeInterface`. The `Scope` enum and its string
values form the semantic vocabulary supplied to themes. The built-in theme
classes and the Highlight.js, Prism, and TextMate adapters are supported
public implementations.

The interface requires `getName()`, `isDark()`, `getCssClasses()`, and
`getStylesheet()`. Every current `Scope` needs a class mapping; the stylesheet
then defines those classes for the generated HTML.

See [Compatibility](../compatibility.md) for the semantic-versioning boundary.
