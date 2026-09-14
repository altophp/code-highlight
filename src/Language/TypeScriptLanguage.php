<?php

declare(strict_types=1);

/*
 * This file is part of the ALTO library.
 *
 * © 2026-present Simon André
 *
 * For full copyright and license information, please see
 * the LICENSE file distributed with this source code.
 */

namespace Alto\Code\Highlight\Language;

use Alto\Code\Highlight\Parser\ParsedStream;
use Alto\Code\Highlight\Parser\ParsedToken;
use Alto\Code\Highlight\Scope;

/**
 * TypeScript language parser extending JavaScript.
 *
 * Handles TypeScript-specific syntax including type annotations, interfaces,
 * enums, generics, decorators, and access modifiers.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class TypeScriptLanguage extends JavaScriptLanguage
{
    private const TS_KEYWORDS = [
        'interface', 'type', 'enum', 'namespace', 'module', 'declare',
        'abstract', 'implements', 'readonly', 'keyof', 'infer', 'is',
        'asserts', 'any', 'unknown', 'never', 'void', 'bigint', 'symbol',
    ];

    private const JS_KEYWORDS = [
        'break', 'case', 'catch', 'class', 'const', 'continue', 'debugger', 'default', 'delete',
        'do', 'else', 'export', 'extends', 'finally', 'for', 'function', 'if', 'import', 'in',
        'instanceof', 'let', 'new', 'return', 'super', 'switch', 'this', 'throw', 'try',
        'typeof', 'var', 'void', 'while', 'with', 'yield', 'async', 'await', 'from',
    ];

    private const ACCESS_MODIFIERS = ['public', 'private', 'protected'];

    private bool $expectingTypeDefinitionName = false;

    private bool $expectingNamespaceName = false;

    private bool $inTypeAnnotation = false;

    private bool $pendingTypeAlias = false;

    private bool $heritageClauseActive = false;

    public function getIdentifier(): string
    {
        return 'typescript';
    }

    public function parse(string $code): ParsedStream
    {
        /** @var list<ParsedToken> $tokens */
        $tokens = [];
        $position = 0;
        $length = strlen($code);

        $this->resetTypeScriptState();

        while ($position < $length) {
            $char = $code[$position];

            // Whitespace
            if (preg_match('/\s/', $char)) {
                $ws = '';
                while ($position < $length && preg_match('/\s/', $code[$position])) {
                    $ws .= $code[$position];
                    ++$position;
                }
                $tokens[] = new ParsedToken($ws, Scope::Whitespace);

                continue;
            }

            // Single-line comment
            if ('/' === $char && $position + 1 < $length && '/' === $code[$position + 1]) {
                $comment = '//';
                $position += 2;
                while ($position < $length && "\n" !== $code[$position]) {
                    $comment .= $code[$position];
                    ++$position;
                }
                $tokens[] = new ParsedToken($comment, Scope::Comment);

                continue;
            }

            // Multi-line comment
            if ('/' === $char && $position + 1 < $length && '*' === $code[$position + 1]) {
                $comment = '/*';
                $position += 2;
                while ($position < $length - 1) {
                    $comment .= $code[$position];
                    if ('*' === $code[$position] && '/' === $code[$position + 1]) {
                        $comment .= '/';
                        $position += 2;
                        break;
                    }
                    ++$position;
                }
                $tokens[] = new ParsedToken($comment, Scope::Comment);

                continue;
            }

            // Decorator @
            if ('@' === $char && $position + 1 < $length && preg_match('/[a-zA-Z_]/', $code[$position + 1])) {
                $decorator = '@';
                ++$position;
                while ($position < $length && preg_match('/[a-zA-Z0-9_]/', $code[$position])) {
                    $decorator .= $code[$position];
                    ++$position;
                }
                $tokens[] = new ParsedToken($decorator, Scope::AttributeName);

                continue;
            }

            // Template literal
            if ('`' === $char) {
                $this->parseTemplateLiteral($code, $position, $tokens);

                continue;
            }

            // String (single or double quotes)
            if ('"' === $char || "'" === $char) {
                $string = $this->parseString($code, $position, $char);
                $tokens[] = new ParsedToken($string, Scope::String);
                $position += strlen($string);

                continue;
            }

            // Type assertion with angle brackets <Type>
            if ('<' === $char && $this->isTypeAssertion($code, $position, $tokens)) {
                $assertion = $this->parseTypeAssertion($code, $position);
                if (null !== $assertion) {
                    $tokens[] = new ParsedToken($assertion, Scope::Meta);
                    $position += strlen($assertion);

                    continue;
                }
            }

            // Regex literal
            if ('/' === $char && $this->isRegexContext($tokens)) {
                $regex = $this->parseRegex($code, $position);
                if (null !== $regex) {
                    $tokens[] = new ParsedToken($regex, Scope::RegExp);
                    $position += strlen($regex);

                    continue;
                }
            }

            // Number
            if (ctype_digit($char) || ('.' === $char && $position + 1 < $length && ctype_digit($code[$position + 1]))) {
                $number = $this->parseNumber($code, $position);
                $tokens[] = new ParsedToken($number, Scope::Number);
                $position += strlen($number);

                continue;
            }

            // Arrow function =>
            if ('=' === $char && $position + 1 < $length && '>' === $code[$position + 1]) {
                $tokens[] = new ParsedToken('=>', Scope::Operator);
                $position += 2;

                continue;
            }

            // Spread operator ...
            if ('.' === $char && $position + 2 < $length && '.' === $code[$position + 1] && '.' === $code[$position + 2]) {
                $tokens[] = new ParsedToken('...', Scope::Operator);
                $position += 3;

                continue;
            }

            // Type annotation colon :
            if (':' === $char && $this->isTypeAnnotation($code, $position, $tokens)) {
                $tokens[] = new ParsedToken(':', Scope::Punctuation);
                ++$position;
                $this->inTypeAnnotation = true;

                continue;
            }

            // Operators
            if (preg_match('/[+\-*\/%=<>!&|^~?:]/', $char)) {
                $operator = $this->parseOperator($code, $position);
                $tokens[] = new ParsedToken($operator, Scope::Operator);
                $position += strlen($operator);

                if ('=' === $operator) {
                    if ($this->pendingTypeAlias) {
                        $this->pendingTypeAlias = false;
                        $this->inTypeAnnotation = true;
                    } elseif ($this->inTypeAnnotation) {
                        $this->inTypeAnnotation = false;
                    }
                } elseif ('=>' === $operator) {
                    $this->inTypeAnnotation = false;
                }

                continue;
            }

            // Generics and angle brackets
            if ('<' === $char || '>' === $char) {
                $tokens[] = new ParsedToken($char, Scope::Punctuation);
                ++$position;

                continue;
            }

            // Punctuation
            if (preg_match('/[(){}\[\];,.]/', $char)) {
                $tokens[] = new ParsedToken($char, Scope::Punctuation);
                ++$position;

                $this->handlePunctuationTypeContext($char);

                continue;
            }

            // Identifiers and keywords
            if (preg_match('/[a-zA-Z_$]/', $char)) {
                $identifier = $this->parseIdentifier($code, $position);
                $scope = $this->determineTypeScriptIdentifierScope($identifier, $code, $position, $tokens);
                $tokens[] = new ParsedToken($identifier, $scope);
                $position += strlen($identifier);

                continue;
            }

            // Unknown character
            ++$position;
        }

        return new ParsedStream($tokens);
    }

    /**
     * @param list<ParsedToken> $tokens
     */
    private function isTypeAssertion(string $code, int $position, array $tokens): bool
    {
        // Check previous token to avoid confusing generics with assertions
        $prevToken = null;
        for ($i = count($tokens) - 1; $i >= 0; --$i) {
            if (Scope::Whitespace !== $tokens[$i]->getScope() && Scope::Comment !== $tokens[$i]->getScope()) {
                $prevToken = $tokens[$i];
                break;
            }
        }

        if (null !== $prevToken) {
            $scope = $prevToken->getScope();

            $disallowedScopes = [
                Scope::Variable,
                Scope::FunctionCall,
                Scope::TypeReference,
                Scope::TypeDefinition,
                Scope::String,
                Scope::Number,
            ];

            // If preceded by identifier, it's likely a generic (e.g., func<T>) or comparison
            if (
                in_array($scope, $disallowedScopes, true)
                || ')' === $prevToken->getText()
                || ']' === $prevToken->getText()
            ) {
                return false;
            }
        }

        // Simple heuristic: look for <TypeName> pattern
        $nextPos = $position + 1;
        $length = strlen($code);

        // Skip whitespace
        while ($nextPos < $length && preg_match('/\s/', $code[$nextPos])) {
            ++$nextPos;
        }

        // Should be followed by a type name (allow lower/upper-case identifiers)
        return $nextPos < $length && preg_match('/[A-Za-z_]/', $code[$nextPos]);
    }

    /**
     * Parse a TypeScript type assertion (angle bracket syntax).
     *
     * Handles nested generic types like <Array<string>>.
     *
     * @return string|null The type assertion including angle brackets, or null if incomplete
     */
    private function parseTypeAssertion(string $code, int $position): ?string
    {
        $assertion = '<';
        ++$position;
        $length = strlen($code);
        $depth = 1;

        while ($position < $length) {
            $char = $code[$position];

            if ('<' === $char) {
                ++$depth;
            } elseif ('>' === $char) {
                --$depth;
            }

            $assertion .= $char;
            ++$position;

            if (0 === $depth) {
                return $assertion;
            }
        }

        return null;
    }

    /**
     * @param list<ParsedToken> $tokens
     */
    private function isTypeAnnotation(string $code, int $position, array $tokens): bool
    {
        // Simple heuristic: colon in parameter/variable context is likely a type annotation
        // This is a simplified check - a full implementation would need better context awareness

        // Find previous non-whitespace token
        for ($i = count($tokens) - 1; $i >= 0; --$i) {
            $candidate = $tokens[$i];
            if (Scope::Whitespace === $candidate->getScope()) {
                continue;
            }

            if ('?' === $candidate->getText()) {
                continue;
            }

            $prevToken = $candidate;
            break;
        }

        if (!isset($prevToken)) {
            return false;
        }

        // If previous token is an identifier or closing paren, likely a type annotation
        return Scope::Variable === $prevToken->getScope()
               || ')' === $prevToken->getText()
               || ']' === $prevToken->getText();
    }

    private function handlePunctuationTypeContext(string $char): void
    {
        if (';' === $char) {
            $this->pendingTypeAlias = false;
        }

        if ('{' === $char && $this->heritageClauseActive) {
            $this->heritageClauseActive = false;
            $this->inTypeAnnotation = false;

            return;
        }

        if (!$this->inTypeAnnotation) {
            return;
        }

        if (in_array($char, [';', ')', ','], true)) {
            $this->inTypeAnnotation = false;
            $this->heritageClauseActive = false;
        }
    }

    /**
     * @param list<ParsedToken> $tokens
     */
    private function determineTypeScriptIdentifierScope(string $identifier, string $code, int $position, array $tokens): Scope
    {
        $lower = strtolower($identifier);

        if ($this->expectingTypeDefinitionName) {
            $this->expectingTypeDefinitionName = false;

            return Scope::TypeDefinition;
        }

        if ($this->expectingNamespaceName) {
            $this->expectingNamespaceName = false;

            return Scope::Namespace;
        }

        if (in_array($lower, self::ACCESS_MODIFIERS, true)) {
            return Scope::StorageModifier;
        }

        if (in_array($lower, ['interface', 'class', 'enum'], true)) {
            $this->expectingTypeDefinitionName = true;

            return Scope::KeywordDeclaration;
        }

        if ('type' === $lower) {
            $this->expectingTypeDefinitionName = true;
            $this->pendingTypeAlias = true;

            return Scope::KeywordDeclaration;
        }

        if (in_array($lower, ['namespace', 'module'], true)) {
            $this->expectingNamespaceName = true;

            return Scope::KeywordDeclaration;
        }

        if (in_array($lower, ['extends', 'implements', 'as', 'keyof', 'infer', 'satisfies'], true)) {
            $this->inTypeAnnotation = true;
            if (in_array($lower, ['extends', 'implements'], true)) {
                $this->heritageClauseActive = true;
            }

            return Scope::Keyword;
        }

        if (in_array($lower, self::TS_KEYWORDS, true)) {
            return Scope::Keyword;
        }

        if (in_array($lower, self::JS_KEYWORDS, true)) {
            return Scope::Keyword;
        }

        if ($this->inTypeAnnotation) {
            if ($this->isBuiltInTypeLiteral($lower)) {
                return Scope::BuiltInType;
            }

            return Scope::TypeReference;
        }

        if ($this->isPossiblyGeneric($tokens)) {
            return Scope::TypeReference;
        }

        // Fallback: Check if it's a function call (followed by opening paren)
        $nextPosition = $position + strlen($identifier);
        while ($nextPosition < strlen($code) && preg_match('/\s/', $code[$nextPosition])) {
            ++$nextPosition;
        }

        if ($nextPosition < strlen($code) && '(' === $code[$nextPosition]) {
            return Scope::FunctionCall;
        }

        // Default to variable for identifiers
        return Scope::Variable;
    }

    private function isBuiltInTypeLiteral(string $identifier): bool
    {
        return in_array($identifier, [
            'string',
            'number',
            'boolean',
            'object',
            'any',
            'unknown',
            'never',
            'void',
            'undefined',
            'symbol',
            'bigint',
        ], true);
    }

    /**
     * @param list<ParsedToken> $tokens
     */
    private function isPossiblyGeneric(array $tokens): bool
    {        // Look for previous < or , suggesting generic context
        for ($i = count($tokens) - 1; $i >= 0; --$i) {
            if (Scope::Whitespace === $tokens[$i]->getScope()) {
                continue;
            }

            $content = $tokens[$i]->getText();
            if ('<' === $content || ',' === $content) {
                return true;
            }

            // Stop looking if we hit something else
            break;
        }

        return false;
    }

    /**
     * Parse an identifier starting at the given position.
     *
     * Identifiers consist of letters, digits, underscores, and dollar signs.
     */
    private function parseIdentifier(string $code, int $position): string
    {
        $identifier = '';
        $length = strlen($code);

        while ($position < $length && preg_match('/[a-zA-Z0-9_$]/', $code[$position])) {
            $identifier .= $code[$position];
            ++$position;
        }

        return $identifier;
    }

    /**
     * Parse an operator starting at the given position.
     *
     * Handles single, double, and triple character operators.
     */
    private function parseOperator(string $code, int $position): string
    {
        $length = strlen($code);
        $char = $code[$position];

        // Three-character operators
        if ($position + 2 < $length) {
            $three = substr($code, $position, 3);
            if (in_array($three, ['===', '!==', '>>>', '**=', '<<=', '>>='], true)) {
                return $three;
            }
        }

        // Two-character operators
        if ($position + 1 < $length) {
            $two = substr($code, $position, 2);
            if (in_array($two, ['==', '!=', '<=', '>=', '&&', '||', '++', '--', '<<', '>>', '**', '+=', '-=', '*=', '/=', '%=', '&=', '|=', '^=', '??', '?.'], true)) {
                return $two;
            }
        }

        // Single-character operator
        return $char;
    }

    /**
     * Parse a numeric literal starting at the given position.
     *
     * Supports hexadecimal (0x), binary (0b), octal (0o), decimal, and scientific notation.
     */
    private function parseNumber(string $code, int $position): string
    {
        $number = '';
        $length = strlen($code);

        // Hex: 0x...
        if ($position + 1 < $length && '0' === $code[$position] && 'x' === strtolower($code[$position + 1])) {
            $number = '0x';
            $position += 2;
            while ($position < $length && ctype_xdigit($code[$position])) {
                $number .= $code[$position];
                ++$position;
            }

            return $number;
        }

        // Binary: 0b...
        if ($position + 1 < $length && '0' === $code[$position] && 'b' === strtolower($code[$position + 1])) {
            $number = '0b';
            $position += 2;
            while ($position < $length && in_array($code[$position], ['0', '1'], true)) {
                $number .= $code[$position];
                ++$position;
            }

            return $number;
        }

        // Octal: 0o...
        if ($position + 1 < $length && '0' === $code[$position] && 'o' === strtolower($code[$position + 1])) {
            $number = '0o';
            $position += 2;
            while ($position < $length && $code[$position] >= '0' && $code[$position] <= '7') {
                $number .= $code[$position];
                ++$position;
            }

            return $number;
        }

        // Integer and decimal
        while ($position < $length && ctype_digit($code[$position])) {
            $number .= $code[$position];
            ++$position;
        }

        // Decimal part
        if ($position < $length && '.' === $code[$position]) {
            $number .= '.';
            ++$position;
            while ($position < $length && ctype_digit($code[$position])) {
                $number .= $code[$position];
                ++$position;
            }
        }

        // Exponent
        if ($position < $length && in_array(strtolower($code[$position]), ['e'], true)) {
            $number .= $code[$position];
            ++$position;
            if ($position < $length && in_array($code[$position], ['+', '-'], true)) {
                $number .= $code[$position];
                ++$position;
            }
            while ($position < $length && ctype_digit($code[$position])) {
                $number .= $code[$position];
                ++$position;
            }
        }

        return $number;
    }

    /**
     * @param list<ParsedToken> $tokens
     */
    private function isRegexContext(array $tokens): bool
    {
        if ([] === $tokens) {
            return true;
        }

        $lastToken = $tokens[count($tokens) - 1];

        if (Scope::Whitespace === $lastToken->getScope()) {
            for ($i = count($tokens) - 1; $i >= 0; --$i) {
                if (Scope::Whitespace !== $tokens[$i]->getScope()) {
                    $lastToken = $tokens[$i];
                    break;
                }
            }
        }

        if (Scope::Whitespace === $lastToken->getScope()) {
            return true;
        }

        return in_array($lastToken->getText(), ['=', '(', '[', ',', ':', 'return', '{', ';'], true)
            || Scope::Operator === $lastToken->getScope();
    }

    /**
     * Parse a string literal with the given quote character.
     *
     * Handles escape sequences within the string.
     *
     * @param string $quote The quote character (' or ")
     */
    private function parseString(string $code, int $position, string $quote): string
    {
        $string = $quote;
        ++$position;
        $length = strlen($code);
        $escaped = false;

        while ($position < $length) {
            $char = $code[$position];
            $string .= $char;

            if ($escaped) {
                $escaped = false;
                ++$position;

                continue;
            }

            if ('\\' === $char) {
                $escaped = true;
                ++$position;

                continue;
            }

            if ($char === $quote) {
                break;
            }

            ++$position;
        }

        return $string;
    }

    /**
     * Parse a regular expression literal starting at the given position.
     *
     * Handles escape sequences, character classes, and regex flags.
     *
     * @return string|null The regex including delimiters and flags, or null if invalid
     */
    private function parseRegex(string $code, int $position): ?string
    {
        $regex = '/';
        ++$position;
        $length = strlen($code);
        $escaped = false;
        $inCharClass = false;

        while ($position < $length) {
            $char = $code[$position];

            if ($escaped) {
                $regex .= $char;
                $escaped = false;
                ++$position;

                continue;
            }

            if ('\\' === $char) {
                $regex .= $char;
                $escaped = true;
                ++$position;

                continue;
            }

            if ('[' === $char) {
                $inCharClass = true;
                $regex .= $char;
                ++$position;

                continue;
            }

            if (']' === $char && $inCharClass) {
                $inCharClass = false;
                $regex .= $char;
                ++$position;

                continue;
            }

            if ('/' === $char && !$inCharClass) {
                $regex .= $char;
                ++$position;

                // Parse flags
                while ($position < $length && preg_match('/[gimsuvy]/', $code[$position])) {
                    $regex .= $code[$position];
                    ++$position;
                }

                return $regex;
            }

            if ("\n" === $char) {
                // Invalid regex (newline not allowed)
                return null;
            }

            $regex .= $char;
            ++$position;
        }

        // Unclosed regex
        return null;
    }

    /**
     * @param list<ParsedToken> $tokens
     */
    private function parseTemplateLiteral(string $code, int &$position, array &$tokens): void
    {
        $literal = '`';
        ++$position;
        $length = strlen($code);

        while ($position < $length) {
            $char = $code[$position];

            if ('\\' === $char) {
                $literal .= $char;
                ++$position;
                if ($position < $length) {
                    $literal .= $code[$position];
                    ++$position;
                }

                continue;
            }

            if ('$' === $char && $position + 1 < $length && '{' === $code[$position + 1]) {
                // End current literal part
                if (strlen($literal) > 1) {
                    $tokens[] = new ParsedToken($literal, Scope::String);
                }

                // Parse expression
                $expr = '${';
                $position += 2;
                $braceCount = 1;

                while ($position < $length && $braceCount > 0) {
                    if ('{' === $code[$position]) {
                        ++$braceCount;
                    } elseif ('}' === $code[$position]) {
                        --$braceCount;
                    }
                    $expr .= $code[$position];
                    ++$position;
                }

                $tokens[] = new ParsedToken($expr, Scope::StringTemplateExpression);
                $literal = '';

                continue;
            }

            if ('`' === $char) {
                $literal .= $char;
                ++$position;
                $tokens[] = new ParsedToken($literal, Scope::String);

                return;
            }

            $literal .= $char;
            ++$position;
        }

        // Unclosed template literal
        if ('' !== $literal) {
            $tokens[] = new ParsedToken($literal, Scope::String);
        }
    }

    /**
     * Reset TypeScript-specific parser state flags.
     *
     * Called at the start of each parse operation to ensure clean state.
     */
    private function resetTypeScriptState(): void
    {
        $this->expectingTypeDefinitionName = false;
        $this->expectingNamespaceName = false;
        $this->inTypeAnnotation = false;
        $this->pendingTypeAlias = false;
        $this->heritageClauseActive = false;
    }
}
