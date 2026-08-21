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

namespace Alto\Code\Highlight;

use Alto\Code\Highlight\Embedded\EmbeddedLanguageRegistry;
use Alto\Code\Highlight\Language\LanguageInterface;
use Alto\Code\Highlight\Parser\ParsedStream;

/**
 * The main highlighter class - the public API entry point.
 *
 * This is the facade that users interact with to highlight code.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class Highlighter implements HighlighterInterface
{
    private CodeParser $parser;

    /**
     * @param list<LanguageInterface>|null $languages
     */
    public function __construct(
        private readonly ThemeInterface $theme,
        ?EmbeddedLanguageRegistry $embeddedRegistry = null,
        ?array $languages = null,
    ) {
        $this->parser = new CodeParser($embeddedRegistry, $languages);
    }

    /**
     * Highlight source code and return HTML.
     *
     * @param string    $code           The source code to highlight
     * @param string    $language       The language identifier ('php', 'html', etc.)
     * @param bool      $lineNumbers    Whether to include line numbers
     * @param list<int> $highlightLines Line numbers to highlight (1-indexed)
     *
     * @return string The highlighted HTML output
     */
    public function highlight(
        string $code,
        string $language,
        bool $lineNumbers = false,
        array $highlightLines = [],
    ): string {
        // Normalize language identifier
        $language = strtolower(trim($language));

        // Keep the historical convenience identifier as a PHP alias.
        if ('php-snippet' === $language) {
            $language = 'php';
        }

        $parsedStream = $this->parser->parse($code, $language);

        // Format the output
        return $this->format($parsedStream, $language, $lineNumbers, $highlightLines);
    }

    /**
     * Register a language parser.
     */
    public function registerLanguage(LanguageInterface $language): void
    {
        $this->parser->registerLanguage($language);
    }

    /**
     * Get the theme being used.
     */
    public function getTheme(): ThemeInterface
    {
        return $this->theme;
    }

    public function getEmbeddedRegistry(): EmbeddedLanguageRegistry
    {
        return $this->parser->getEmbeddedRegistry();
    }

    /**
     * Enable or disable a specific embedded language.
     *
     * @param string $host   The host language (e.g., 'html')
     * @param string $target The embedded language to toggle (e.g., 'javascript')
     */
    public function setEmbeddingEnabled(string $host, string $target, bool $enabled): void
    {
        $this->parser->setEmbeddingEnabled($host, $target, $enabled);
    }

    /**
     * Format the parsed stream into HTML.
     *
     * @param list<int> $highlightLines
     */
    private function format(
        ParsedStream $parsedStream,
        string $language,
        bool $lineNumbers,
        array $highlightLines,
    ): string {
        $cssClasses = $this->theme->getCssClasses();
        $languageClass = $this->detectLanguageClass($language);
        $preClasses = ['alto-highlight'];
        $codeClasses = [];

        if (null !== $languageClass) {
            $preClasses[] = $languageClass;
            $codeClasses[] = $languageClass;
        }

        if ($vendorClass = $this->detectVendorContainerClass()) {
            $codeClasses[] = $vendorClass;
        }

        $html = sprintf(
            '<pre class="%s"><code%s>',
            $this->joinCssClasses($preClasses),
            $this->formatClassAttribute($codeClasses),
        );

        $currentLine = 1;

        // Add the line number for the very first line
        if ($lineNumbers) {
            $html .= $this->formatLineNumber($currentLine, $highlightLines);
        }

        foreach ($parsedStream->getTokens() as $token) {
            // We must explode EVERY token by newline, because tokens like T_OPEN_TAG (<?php\n)
            // or multi-line comments/strings contain newlines that affect line numbering.
            $lines = explode("\n", $token->getText());

            foreach ($lines as $i => $lineContent) {
                // If we are on the second element (index 1) or later, it means
                // we have crossed a newline character in the original string.
                if ($i > 0) {
                    $html .= "\n";
                    ++$currentLine;

                    if ($lineNumbers) {
                        $html .= $this->formatLineNumber($currentLine, $highlightLines);
                    }
                }

                // If this segment has content, output it
                if ('' !== $lineContent) {
                    $text = htmlspecialchars($lineContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $scopeValue = $token->getScope()->value;
                    $cssClass = $cssClasses[$scopeValue] ?? 'alto-default';

                    // Skip whitespace scope wrapping to keep HTML cleaner
                    if (Scope::Whitespace === $token->getScope()) {
                        $html .= $text;
                    } else {
                        $html .= sprintf('<span class="%s">%s</span>', $cssClass, $text);
                    }
                }
            }
        }

        $html .= '</code></pre>';

        return $html;
    }

    private function detectLanguageClass(string $language): ?string
    {
        $language = preg_replace('/[^a-z0-9\-]+/', '-', strtolower($language));
        $language = trim((string) $language, '-');

        if ('' === $language) {
            return null;
        }

        return 'language-' . $language;
    }

    private function detectVendorContainerClass(): ?string
    {
        $name = strtolower($this->theme->getName());

        if (str_starts_with($name, 'hljs-')) {
            return 'hljs';
        }

        return null;
    }

    /**
     * @param list<string> $classes
     */
    private function joinCssClasses(array $classes): string
    {
        $classes = array_values(array_filter(array_map(trim(...), $classes)));

        return implode(' ', $classes);
    }

    /**
     * @param list<string> $classes
     */
    private function formatClassAttribute(array $classes): string
    {
        $class = $this->joinCssClasses($classes);

        return '' === $class ? '' : ' class="' . $class . '"';
    }

    /**
     * Format a line number.
     *
     * @param list<int> $highlightLines
     */
    private function formatLineNumber(int $lineNumber, array $highlightLines): string
    {
        $isHighlighted = in_array($lineNumber, $highlightLines, true);
        $class = $isHighlighted ? 'alto-line-number alto-highlighted' : 'alto-line-number';

        return sprintf('<span class="%s">%4d</span> ', $class, $lineNumber);
    }
}
