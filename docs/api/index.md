# Public API

Alto Code Highlight follows semantic versioning for the supported entry points
and extension contracts described here. Patch and minor releases preserve
their documented signatures and behavior throughout the 1.x series.

## Parsing

`CodeParser` parses source into a `ParsedStream` without rendering it. Its
supported operations are:

- construction with an optional embedding registry and optional language list;
- `parse()` for semantically scoped tokens;
- `registerLanguage()` for adding or replacing a parser;
- `getEmbeddedRegistry()` for inspecting embedding plans;
- `setEmbeddingEnabled()` for toggling a configured host and target pair.

`parse()` preserves the source supplied by the caller. Concatenating the token
text, or calling `ParsedStream::toString()`, returns that source exactly.
Selecting `php` parses PHP from the first byte even when the opening tag is
omitted.

## HTML rendering

`Highlighter` renders the same parsed representation as escaped HTML. Its
supported operations are:

- construction with a `ThemeInterface`, optional embedding registry, and
  optional language list;
- `highlight()` for escaped HTML output;
- `getTheme()` for the configured theme;
- `registerLanguage()` for adding or replacing a parser;
- `getEmbeddedRegistry()` for inspecting embedding plans;
- `setEmbeddingEnabled()` for toggling a configured host and target pair.

`HighlighterInterface` defines the portable highlighting operation for code
that depends on an abstraction rather than the concrete facade.

## Theme extension contract

Custom themes implement `ThemeInterface`. The `Scope` enum and its string
values form the semantic vocabulary supplied to themes. The built-in theme
classes and the Highlight.js, Prism, and TextMate adapters are supported public
implementations.

See [Creating a theme](../theming/creating.md) and
[Theme adapters](../theming/adapters.md) for complete examples.

## Language extension contract

Custom parsers implement `LanguageInterface` and return a `ParsedStream` made
of `ParsedToken` values. `StreamBuilder`, `TokenType`, and `Scope` are supported
building blocks for those parsers.
`Languages::getDefaultLanguages()` returns the built-in registry.

Embedded parsers use `EmbeddedLanguageCapable`, `EmbeddedLanguageContext`, and
the types under `Alto\Code\Highlight\Embedded`. Their documented constructors
and public methods are covered by the same 1.x compatibility promise.

See [Languages](../languages/index.md) and
[Embedded languages](../languages/embedded.md)
for usage and behavior.

## Exceptions

`LanguageNotFoundException` reports an unknown language identifier.
`ParseException` reports source that a semantic parser cannot process. Both are
part of the supported exception contract.

## Compatibility boundary

The following details are not compatibility contracts:

- concrete lexer, semantic parser, state, and token classes inside a built-in
  language implementation;
- exact whitespace inside generated HTML;
- private methods and undocumented implementation details;
- test fixtures, documentation tooling, and generated showcase assets.

The generated element structure, documented CSS classes, source escaping,
language identifiers, semantic scope values, and public signatures are covered
by semantic versioning. Changes outside that boundary may occur in a minor or
patch release when documented behavior remains intact.
