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

namespace Alto\Code\Highlight\Tests\Unit;

use Alto\Code\Highlight\CodeParser;
use Alto\Code\Highlight\Embedded\EmbeddedLanguageRegistry;
use Alto\Code\Highlight\Exception\LanguageNotFoundException;
use Alto\Code\Highlight\Language\LanguageInterface;
use Alto\Code\Highlight\Parser\ParsedStream;
use Alto\Code\Highlight\Parser\ParsedToken;
use Alto\Code\Highlight\Scope;
use Alto\Code\Highlight\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CodeParser::class)]
final class CodeParserTest extends TestCase
{
    public function testParsesPhpFragmentWithoutChangingTheSource(): void
    {
        $code = '$page->getByRole("button")->click();';

        $stream = (new CodeParser())->parse($code, 'php');

        self::assertSame($code, $stream->toString());
        self::assertContains(Scope::Variable, array_map(
            static fn(ParsedToken $token): Scope => $token->scope,
            $stream->tokens,
        ));
        self::assertContains(Scope::FunctionCall, array_map(
            static fn(ParsedToken $token): Scope => $token->scope,
            $stream->tokens,
        ));
    }

    public function testNormalizesLanguageIdentifierAndPhpSnippetAlias(): void
    {
        $parser = new CodeParser();
        $code = '$answer = 42;';

        self::assertSame($code, $parser->parse($code, ' PHP ')->toString());
        self::assertSame($code, $parser->parse($code, 'php-snippet')->toString());
    }

    public function testThrowsForUnknownLanguage(): void
    {
        $this->expectException(LanguageNotFoundException::class);

        (new CodeParser())->parse('code', 'unknown-language');
    }

    public function testCanRegisterAndParseACustomLanguage(): void
    {
        $language = self::customLanguage('custom');
        $parser = new CodeParser(languages: []);
        $parser->registerLanguage($language);

        $stream = $parser->parse('source', 'custom');

        self::assertSame('source', $stream->toString());
        self::assertSame(Scope::String, $stream->tokens[0]->scope);
    }

    public function testAcceptsLanguagesInConstructor(): void
    {
        $parser = new CodeParser(languages: [self::customLanguage('custom')]);

        self::assertSame('source', $parser->parse('source', 'custom')->toString());
    }

    public function testRejectsInvalidConstructorLanguage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All languages must implement LanguageInterface.');

        new CodeParser(languages: [new \stdClass()]);
    }

    public function testExposesInjectedEmbeddedLanguageRegistry(): void
    {
        $registry = new EmbeddedLanguageRegistry([]);

        $parser = new CodeParser($registry);

        self::assertSame($registry, $parser->getEmbeddedRegistry());
        self::assertSame(
            '<script>var answer = 42;</script>',
            $parser->parse('<script>var answer = 42;</script>', 'html')->toString(),
        );
    }

    public function testCanDisableAndReEnableEmbeddedLanguageParsing(): void
    {
        $parser = new CodeParser();
        $code = '<script>var answer = 42;</script>';

        self::assertTrue($this->hasToken($parser->parse($code, 'html'), 'var', Scope::KeywordDeclaration));

        $parser->setEmbeddingEnabled('HTML', 'JAVASCRIPT', false);
        self::assertFalse($this->hasToken($parser->parse($code, 'html'), 'var', Scope::KeywordDeclaration));

        $parser->setEmbeddingEnabled('html', 'javascript', true);
        self::assertTrue($this->hasToken($parser->parse($code, 'html'), 'var', Scope::KeywordDeclaration));
    }

    private static function customLanguage(string $identifier): LanguageInterface
    {
        return new class ($identifier) implements LanguageInterface {
            public function __construct(private readonly string $identifier) {}

            public function parse(string $code): ParsedStream
            {
                return new ParsedStream([new ParsedToken($code, Scope::String)]);
            }

            public function getIdentifier(): string
            {
                return $this->identifier;
            }
        };
    }

    private function hasToken(ParsedStream $stream, string $text, Scope $scope): bool
    {
        foreach ($stream as $token) {
            if ($text === $token->text && $scope === $token->scope) {
                return true;
            }
        }

        return false;
    }
}
