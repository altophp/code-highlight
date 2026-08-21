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

use Alto\Code\Highlight\Embedded\EmbeddedLanguagePlan;
use Alto\Code\Highlight\Embedded\EmbeddedLanguageRegistry;
use Alto\Code\Highlight\Exception\LanguageNotFoundException;
use Alto\Code\Highlight\Language\EmbeddedLanguageCapable;
use Alto\Code\Highlight\Language\EmbeddedLanguageContext;
use Alto\Code\Highlight\Language\LanguageInterface;
use Alto\Code\Highlight\Language\Languages;
use Alto\Code\Highlight\Parser\ParsedStream;

/**
 * Parses source code into a stream of semantically scoped tokens.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CodeParser
{
    /**
     * @var array<string, LanguageInterface>
     */
    private array $languages = [];

    private EmbeddedLanguageRegistry $embeddedRegistry;

    /**
     * @var array<string, bool>
     */
    private array $embeddedLanguageToggles = [];

    /**
     * @param list<LanguageInterface>|null $languages
     */
    public function __construct(
        ?EmbeddedLanguageRegistry $embeddedRegistry = null,
        ?array $languages = null,
    ) {
        $this->embeddedRegistry = $embeddedRegistry ?? new EmbeddedLanguageRegistry();

        $languages ??= Languages::getDefaultLanguages();
        foreach ($languages as $language) {
            if (!$language instanceof LanguageInterface) {
                throw new \InvalidArgumentException('All languages must implement LanguageInterface.');
            }

            $this->registerLanguage($language);
        }
    }

    /**
     * Parse source code without rendering it.
     */
    public function parse(string $code, string $language): ParsedStream
    {
        $language = strtolower(trim($language));

        if ('php-snippet' === $language) {
            $language = 'php';
        }

        return $this->parseWithLanguage($this->getLanguage($language), $code);
    }

    /**
     * Register a language parser.
     */
    public function registerLanguage(LanguageInterface $language): void
    {
        $this->languages[$language->getIdentifier()] = $language;
    }

    public function getEmbeddedRegistry(): EmbeddedLanguageRegistry
    {
        return $this->embeddedRegistry;
    }

    /**
     * Enable or disable a specific embedded language.
     *
     * @param string $host   The host language (e.g., 'html')
     * @param string $target The embedded language to toggle (e.g., 'javascript')
     */
    public function setEmbeddingEnabled(string $host, string $target, bool $enabled): void
    {
        $this->embeddedLanguageToggles[strtolower($host) . ':' . strtolower($target)] = $enabled;
    }

    /**
     * @throws LanguageNotFoundException
     */
    private function getLanguage(string $identifier): LanguageInterface
    {
        if (!isset($this->languages[$identifier])) {
            throw new LanguageNotFoundException($identifier);
        }

        return $this->languages[$identifier];
    }

    private function parseWithLanguage(LanguageInterface $language, string $code): ParsedStream
    {
        if ($language instanceof EmbeddedLanguageCapable) {
            $plan = $this->embeddedRegistry->getPlan($language->getIdentifier());

            if (null !== $plan) {
                $host = $language->getIdentifier();
                $triggers = array_filter($plan->getTriggers(), function ($trigger) use ($host) {
                    $key = $host . ':' . $trigger->targetLanguage;

                    return $this->embeddedLanguageToggles[$key] ?? true;
                });
                $plan = EmbeddedLanguagePlan::forHost($host, array_values($triggers));
            }

            $context = EmbeddedLanguageContext::fromResolver(function (string $identifier, string $embeddedCode): ParsedStream {
                $embeddedLanguage = $this->getLanguage($identifier);

                return $this->parseWithLanguage($embeddedLanguage, $embeddedCode);
            }, $plan);

            return $language->parseWithEmbedding($code, $context);
        }

        return $language->parse($code);
    }
}
