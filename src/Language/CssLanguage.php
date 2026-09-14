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
 * CSS language parser.
 *
 * Handles parsing and semantic analysis of CSS code.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class CssLanguage implements LanguageInterface
{
    public function getIdentifier(): string
    {
        return 'css';
    }

    public function parse(string $code): ParsedStream
    {
        $tokens = [];
        $position = 0;
        $length = strlen($code);
        $inBlock = false;
        $inSelector = true;

        while ($position < $length) {
            $char = $code[$position];

            // Skip whitespace
            if (preg_match('/\s/', $char)) {
                $ws = '';
                while ($position < $length && preg_match('/\s/', $code[$position])) {
                    $ws .= $code[$position];
                    ++$position;
                }
                $tokens[] = new ParsedToken($ws, Scope::Whitespace);

                continue;
            }

            // Comments
            if ($position + 1 < $length && '/' === $code[$position] && '*' === $code[$position + 1]) {
                $comment = $this->parseComment($code, $position);
                $tokens[] = new ParsedToken($comment, Scope::Comment);
                $position += strlen($comment);

                continue;
            }

            // At-rules (@media, @import, @keyframes, etc.)
            if ('@' === $char) {
                $atRule = $this->parseAtRule($code, $position);
                $tokens[] = new ParsedToken($atRule, Scope::Keyword);
                $position += strlen($atRule);

                continue;
            }

            // Opening brace - entering property block
            if ('{' === $char) {
                $tokens[] = new ParsedToken($char, Scope::Punctuation);
                $inBlock = true;
                $inSelector = false;
                ++$position;

                continue;
            }

            // Closing brace - exiting property block
            if ('}' === $char) {
                $tokens[] = new ParsedToken($char, Scope::Punctuation);
                $inBlock = false;
                $inSelector = true;
                ++$position;

                continue;
            }

            // Semicolon
            if (';' === $char) {
                $tokens[] = new ParsedToken($char, Scope::Punctuation);
                ++$position;

                continue;
            }

            // Colon (property: value separator)
            if (':' === $char && $inBlock) {
                // Check if it's a pseudo-class/element or property separator
                $beforeColon = $this->getTextBefore($tokens);
                if ($this->isPseudoClass($beforeColon)) {
                    // Already handled as part of selector
                    $tokens[] = new ParsedToken($char, Scope::Punctuation);
                } else {
                    $tokens[] = new ParsedToken($char, Scope::Punctuation);
                }
                ++$position;

                continue;
            }

            // In selector context
            if ($inSelector || !$inBlock) {
                $selector = $this->parseSelector($code, $position);
                if ('' !== $selector) {
                    $scope = $this->determineSelectorScope($selector);
                    $tokens[] = new ParsedToken($selector, $scope);
                    $position += strlen($selector);

                    continue;
                }
            }

            // Strings (quoted values)
            if ('"' === $char || "'" === $char) {
                $str = $this->parseQuotedString($code, $position);
                $tokens[] = new ParsedToken($str, Scope::String);
                $position += strlen($str);

                continue;
            }

            // In property block - parse property or value
            if ($inBlock) {
                // Check if we're at a property name
                if ($this->isAtPropertyName($code, $position)) {
                    $property = $this->parseProperty($code, $position);
                    $tokens[] = new ParsedToken($property, Scope::AttributeName);
                    $position += strlen($property);

                    continue;
                }

                // CSS function call: url(...), calc(...), rgb(...), etc.
                if (preg_match('/\G[a-z-]+\(/i', $code, $m, 0, $position)) {
                    $tokens[] = new ParsedToken($m[0], Scope::FunctionCall);
                    $position += strlen($m[0]);
                    $this->parseFunctionArgs($code, $position, $length, $tokens);

                    continue;
                }

                // Parse value
                $value = $this->parseValue($code, $position);
                if ('' !== $value) {
                    $scope = $this->determineValueScope($value);
                    $tokens[] = new ParsedToken($value, $scope);
                    $position += strlen($value);

                    continue;
                }
            }

            // Punctuation
            if (in_array($char, ['(', ')', ',', '>', '+', '~', '*', '[', ']'], true)) {
                $tokens[] = new ParsedToken($char, Scope::Punctuation);
                ++$position;

                continue;
            }

            // Unknown - skip
            ++$position;
        }

        return new ParsedStream($tokens);
    }

    protected function parseComment(string $code, int $position): string
    {
        $comment = '/*';
        $position += 2;
        $length = strlen($code);

        while ($position < $length) {
            if ($position + 1 < $length && '*' === $code[$position] && '/' === $code[$position + 1]) {
                $comment .= '*/';

                return $comment;
            }
            $comment .= $code[$position];
            ++$position;
        }

        return $comment;
    }

    protected function parseAtRule(string $code, int $position): string
    {
        $atRule = '@';
        ++$position;
        $length = strlen($code);

        while ($position < $length && preg_match('/[a-zA-Z-]/', $code[$position])) {
            $atRule .= $code[$position];
            ++$position;
        }

        return $atRule;
    }

    protected function parseSelector(string $code, int $position): string
    {
        $selector = '';
        $length = strlen($code);

        while ($position < $length) {
            $char = $code[$position];

            // Stop at block start or other terminators
            if (in_array($char, ['{', '}', ';'], true)) {
                break;
            }

            // Handle pseudo-classes and pseudo-elements
            if (':' === $char) {
                $selector .= $char;
                ++$position;

                // Check for ::pseudo-element
                if ($position < $length && ':' === $code[$position]) {
                    $selector .= $code[$position];
                    ++$position;
                }

                // Get the pseudo name
                while ($position < $length && preg_match('/[a-zA-Z-]/', $code[$position])) {
                    $selector .= $code[$position];
                    ++$position;
                }

                continue;
            }

            if (preg_match('/\s/', $char) || in_array($char, [',', '>', '+', '~'], true)) {
                break;
            }

            $selector .= $char;
            ++$position;
        }

        return $selector;
    }

    protected function determineSelectorScope(string $selector): Scope
    {
        // Pseudo-elements (::before, ::after)
        if (str_contains($selector, '::')) {
            return Scope::AttributeName;
        }

        // Pseudo-classes (:hover, :focus, etc.)
        if (str_contains($selector, ':')) {
            return Scope::AttributeName;
        }

        return Scope::TagName;
    }

    protected function parseProperty(string $code, int $position): string
    {
        $property = '';
        $length = strlen($code);

        while ($position < $length && preg_match('/[a-zA-Z-]/', $code[$position])) {
            $property .= $code[$position];
            ++$position;
        }

        return $property;
    }

    protected function parseValue(string $code, int $position): string
    {
        $value = '';
        $depth = 0;
        $length = strlen($code);

        while ($position < $length) {
            $char = $code[$position];

            if (in_array($char, [';', '}'], true) && 0 === $depth) {
                break;
            }

            // Only break on whitespace at the top level (not inside parens)
            if (0 === $depth && preg_match('/\s/', $char)) {
                break;
            }

            if ('(' === $char) {
                ++$depth;
            } elseif (')' === $char) {
                --$depth;
            }

            $value .= $char;
            ++$position;
        }

        return $value;
    }

    protected function determineValueScope(string $value): Scope
    {
        // !important
        if ('!important' === $value) {
            return Scope::Keyword;
        }

        // Functions: var(), calc(), clamp(), rgb(), etc.
        if (preg_match('/^[a-z-]+\(/', $value)) {
            return Scope::FunctionCall;
        }

        // Units (px, em, rem, %, etc.)
        if (preg_match('/\d+(px|em|rem|%|vh|vw|pt|cm|mm|in|pc)$/', $value)) {
            return Scope::Constant;
        }

        // Colors
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)) {
            return Scope::BuiltInConstant;
        }

        return Scope::AttributeValue;
    }

    protected function isAtPropertyName(string $code, int $position): bool
    {
        $length = strlen($code);

        // Look ahead to find a colon
        $lookahead = $position;
        while ($lookahead < $length) {
            if (':' === $code[$lookahead]) {
                return true;
            }
            if (in_array($code[$lookahead], [';', '}', '{'], true)) {
                return false;
            }
            ++$lookahead;
        }

        return false;
    }

    /**
     * @param list<ParsedToken> $tokens
     */
    protected function getTextBefore(array $tokens): string
    {
        if (empty($tokens)) {
            return '';
        }

        return $tokens[count($tokens) - 1]->getText();
    }

    protected function isPseudoClass(string $text): bool
    {
        return str_contains($text, ':');
    }

    /**
     * Parse function arguments between ( and ), emitting tokens for each argument.
     *
     * @param list<ParsedToken> $tokens
     */
    protected function parseFunctionArgs(string $code, int &$position, int $length, array &$tokens): void
    {
        $depth = 1;

        while ($position < $length) {
            $char = $code[$position];

            if (')' === $char) {
                --$depth;
                if (0 === $depth) {
                    $tokens[] = new ParsedToken(')', Scope::Punctuation);
                    ++$position;

                    return;
                }
            }

            if ('(' === $char) {
                ++$depth;
            }

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

            // Quoted strings
            if ('"' === $char || "'" === $char) {
                $str = $this->parseQuotedString($code, $position);
                $tokens[] = new ParsedToken($str, Scope::String);
                $position += strlen($str);

                continue;
            }

            // Comma separator
            if (',' === $char) {
                $tokens[] = new ParsedToken(',', Scope::Punctuation);
                ++$position;

                continue;
            }

            // Nested function call
            if (preg_match('/\G[a-z-]+\(/i', $code, $m, 0, $position)) {
                $tokens[] = new ParsedToken($m[0], Scope::FunctionCall);
                $position += strlen($m[0]);
                ++$depth;
                $this->parseFunctionArgs($code, $position, $length, $tokens);
                --$depth;

                continue;
            }

            // Other argument content (numbers, identifiers, etc.)
            $arg = '';
            while ($position < $length) {
                $c = $code[$position];
                if (')' === $c || ',' === $c || '(' === $c || '"' === $c || "'" === $c || preg_match('/\s/', $c)) {
                    break;
                }
                $arg .= $c;
                ++$position;
            }
            if ('' !== $arg) {
                $tokens[] = new ParsedToken($arg, $this->determineValueScope($arg));
            }
        }
    }

    protected function parseQuotedString(string $code, int $position): string
    {
        $quote = $code[$position];
        $str = $quote;
        ++$position;
        $length = strlen($code);

        while ($position < $length) {
            $char = $code[$position];
            $str .= $char;
            ++$position;

            if ('\\' === $char && $position < $length) {
                $str .= $code[$position];
                ++$position;

                continue;
            }

            if ($char === $quote) {
                return $str;
            }
        }

        return $str;
    }
}
