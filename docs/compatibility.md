# Compatibility

Alto Code Highlight follows semantic versioning for its documented entry
points and extension contracts. Patch and minor releases preserve their
signatures and behavior throughout the 1.x series.

## Exceptions

`LanguageNotFoundException` reports an unknown language identifier.
`ParseException` reports source that a semantic parser cannot process. Both are
part of the supported exception contract.

Applications may catch those exceptions individually, or catch the package
exception interface when the same recovery applies to every highlighting
failure. Keep the original source visible when highlighting is optional.

## Supported boundary

The compatibility promise covers:

- generated element structure and documented CSS classes;
- source escaping;
- registered language identifiers;
- semantic `Scope` values;
- documented public signatures;
- the language and theme extension contracts.

The following details are implementation details:

- concrete lexer, parser, state, and token classes inside a built-in language;
- exact whitespace inside generated HTML;
- private methods and undocumented types;
- test fixtures, documentation tooling, and generated showcase assets.

Those details may change in a minor or patch release when documented behavior
stays compatible. Review the [Languages](languages.md) and
[Themes](themes.md) pages for the public extension contracts.
