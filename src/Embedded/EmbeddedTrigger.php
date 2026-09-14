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

namespace Alto\Code\Highlight\Embedded;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class EmbeddedTrigger
{
    /**
     * @param array<string, list<string>|null> $attributeConstraints
     * @param array<string, mixed>             $options
     */
    private function __construct(
        public readonly EmbeddedTriggerType $type,
        public readonly string $targetLanguage,
        private readonly array $attributeConstraints,
        private readonly array $options,
    ) {}

    /**
     * Create a trigger for HTML/SVG tags.
     *
     * @param array<string, list<string>|null> $attributeConstraints
     */
    public static function tag(string $tagName, string $targetLanguage, array $attributeConstraints = []): self
    {
        $options = [
            'tagName' => strtolower($tagName),
        ];

        return new self(EmbeddedTriggerType::Tag, strtolower($targetLanguage), self::normalizeAttributeConstraints($attributeConstraints), $options);
    }

    /**
     * Create a trigger for named blocks (e.g., Twig {% block foo %}).
     */
    public static function block(string $blockName, string $targetLanguage): self
    {
        $options = [
            'blockName' => strtolower($blockName),
        ];

        return new self(EmbeddedTriggerType::Block, strtolower($targetLanguage), [], $options);
    }

    /**
     * @return array<string, list<string>|null>
     */
    public function getAttributeConstraints(): array
    {
        return $this->attributeConstraints;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, string|true> $attributes
     */
    public function matchesTag(string $tagName, array $attributes = []): bool
    {
        if (EmbeddedTriggerType::Tag !== $this->type) {
            return false;
        }

        if (($this->options['tagName'] ?? null) !== strtolower($tagName)) {
            return false;
        }

        foreach ($this->attributeConstraints as $name => $allowedValues) {
            $attributeName = strtolower($name);
            if (!array_key_exists($attributeName, $attributes)) {
                return false;
            }

            if (null === $allowedValues) {
                continue; // presence-only constraint
            }

            $value = strtolower((string) $attributes[$attributeName]);
            $allowed = array_map('strtolower', $allowedValues);
            if (!in_array($value, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    public function matchesBlock(string $blockName): bool
    {
        if (EmbeddedTriggerType::Block !== $this->type) {
            return false;
        }

        return ($this->options['blockName'] ?? null) === strtolower($blockName);
    }

    /**
     * @param array<string, list<string>|null> $constraints
     *
     * @return array<string, list<string>|null>
     */
    private static function normalizeAttributeConstraints(array $constraints): array
    {
        $normalized = [];
        foreach ($constraints as $name => $allowedValues) {
            $key = strtolower($name);
            if (null === $allowedValues) {
                $normalized[$key] = null;

                continue;
            }

            $normalized[$key] = $allowedValues;
        }

        return $normalized;
    }
}
