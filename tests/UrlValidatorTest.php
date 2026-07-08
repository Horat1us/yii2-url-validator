<?php

declare(strict_types=1);

namespace Horat1us\Yii\Tests;

use Horat1us\Yii\UrlValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;
use yii\validators\UrlValidator as YiiUrlValidator;

class UrlValidatorTest extends TestCase
{
    public static function stockValidUrlProvider(): array
    {
        return [
            'apex domain with slash' => ['http://example.com/',],
            'apex domain https' => ['https://example.com',],
            'ipv4 with port' => ['http://127.0.0.1:8080/',],
            'subdomain with query and fragment' => ['https://www.example.com/path?q=1#f',],
        ];
    }

    public static function singleLabelHostProvider(): array
    {
        return [
            'localhost with port and slash' => ['http://localhost:8000/',],
            'localhost bare' => ['http://localhost',],
            'localhost with slash' => ['http://localhost/',],
            'localhost with path, query and fragment' => ['http://localhost:8000/path?q=1#f',],
            'hyphenated host' => ['http://my-host:3000/',],
            'intranet' => ['http://intranet',],
            'single character host' => ['http://a',],
        ];
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'no host' => ['http://',],
            'empty host' => ['http:///',],
            'space in host' => ['http://loc alhost/',],
            'unlisted scheme' => ['ftp://localhost/',],
            'ipv6 literal' => ['http://[::1]/',],
            'userinfo' => ['http://user@localhost/',],
            'port out of range' => ['http://localhost:999999/',],
            'leading hyphen label' => ['http://-bad/',],
            'leading underscore label' => ['http://_x/',],
            'no scheme' => ['localhost:8000/',],
        ];
    }

    public static function invalidValueProvider(): array
    {
        return [
            'null' => [null,],
            'int' => [123,],
            'array' => [['http://localhost',],],
            'too long' => ['http://localhost/' . str_repeat('a', 2000),],
        ];
    }

    #[DataProvider('stockValidUrlProvider')]
    public function testStockValidUrlIsAcceptedRegardlessOfFlag(string $url): void
    {
        self::assertTrue((new UrlValidator())->validate($url));
        self::assertTrue((new UrlValidator(['allowSingleLabelHost' => true,]))->validate($url));
    }

    #[DataProvider('singleLabelHostProvider')]
    public function testSingleLabelHostIsRejectedByDefault(string $url): void
    {
        self::assertFalse((new UrlValidator())->validate($url));
    }

    #[DataProvider('singleLabelHostProvider')]
    public function testSingleLabelHostIsAcceptedWhenEnabled(string $url): void
    {
        self::assertTrue((new UrlValidator(['allowSingleLabelHost' => true,]))->validate($url));
    }

    #[DataProvider('invalidUrlProvider')]
    public function testInvalidUrlIsRejectedRegardlessOfFlag(string $url): void
    {
        self::assertFalse((new UrlValidator())->validate($url));
        self::assertFalse((new UrlValidator(['allowSingleLabelHost' => true,]))->validate($url));
    }

    #[DataProvider('invalidValueProvider')]
    public function testInvalidValueIsRejected(mixed $value): void
    {
        self::assertFalse((new UrlValidator(['allowSingleLabelHost' => true,]))->validate($value));
    }

    public function testValidSchemesAreRespected(): void
    {
        $validator = new UrlValidator([
            'allowSingleLabelHost' => true,
            'validSchemes' => ['http', 'ftp',],
        ]);

        self::assertTrue($validator->validate('ftp://localhost/'));
        self::assertTrue($validator->validate('ftp://example.com/'));
        self::assertTrue($validator->validate('http://localhost/'));
        self::assertFalse($validator->validate('https://localhost/'));
    }

    public function testDefaultSchemeIsPrepended(): void
    {
        $validator = new UrlValidator([
            'allowSingleLabelHost' => true,
            'defaultScheme' => 'http',
        ]);

        self::assertTrue($validator->validate('localhost'));
        self::assertTrue($validator->validate('localhost:8000/'));
        self::assertTrue($validator->validate('example.com'));
    }

    public function testDefaultSchemeDoesNotAllowSingleLabelHostByItself(): void
    {
        $validator = new UrlValidator(['defaultScheme' => 'http',]);

        self::assertFalse($validator->validate('localhost'));
        self::assertTrue($validator->validate('example.com'));
    }

    public function testDefaultSchemeIsAssignedToModelAttribute(): void
    {
        $model = new UrlModel();
        $model->url = 'localhost:8000/';

        $validator = new UrlValidator([
            'allowSingleLabelHost' => true,
            'defaultScheme' => 'http',
        ]);
        $validator->validateAttribute($model, 'url');

        self::assertFalse($model->hasErrors('url'));
        self::assertSame('http://localhost:8000/', $model->url);
    }

    public function testPatternIsUntouchedWhenFlagIsDisabled(): void
    {
        $defaults = (new \ReflectionClass(YiiUrlValidator::class))->getDefaultProperties();

        self::assertSame($defaults['pattern'], (new UrlValidator())->pattern);
    }

    public function testPatternIsRelaxedWhenFlagIsEnabled(): void
    {
        $pattern = (new UrlValidator(['allowSingleLabelHost' => true,]))->pattern;

        self::assertStringContainsString(UrlValidator::SINGLE_LABEL_HOST, $pattern);
        self::assertStringNotContainsString(UrlValidator::MULTI_LABEL_HOST, $pattern);
    }

    public function testCustomPatternIsRespected(): void
    {
        $pattern = '/^{schemes}:\/\/example\.test$/i';
        $validator = new UrlValidator([
            'allowSingleLabelHost' => true,
            'pattern' => $pattern,
        ]);

        self::assertSame($pattern, $validator->pattern);
        self::assertTrue($validator->validate('http://example.test'));
        self::assertFalse($validator->validate('http://localhost'));
        self::assertFalse($validator->validate('http://example.com'));
    }

    public function testRelaxedPatternIsIdempotent(): void
    {
        $relaxed = (new UrlValidator(['allowSingleLabelHost' => true,]))->pattern;
        $validator = new UrlValidator([
            'allowSingleLabelHost' => true,
            'pattern' => $relaxed,
        ]);

        self::assertSame($relaxed, $validator->pattern);
        self::assertTrue($validator->validate('http://localhost:8000/'));
    }

    public function testUnrecognizedYiiPatternIsRejected(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('unrecognized default UrlValidator::$pattern');

        new UnrecognizedPatternValidator(['allowSingleLabelHost' => true,]);
    }

    public function testUnrecognizedYiiPatternIsIgnoredWhenFlagIsDisabled(): void
    {
        self::assertTrue((new UnrecognizedPatternValidator())->validate('http://nope'));
    }

    public function testClientOptionsUseRelaxedPattern(): void
    {
        $validator = new UrlValidator(['allowSingleLabelHost' => true,]);
        $pattern = (string)$validator->getClientOptions(new UrlModel(), 'url')['pattern'];

        self::assertStringContainsString(UrlValidator::SINGLE_LABEL_HOST, $pattern);
        self::assertStringNotContainsString('{schemes}', $pattern);
        self::assertStringContainsString('(http|https)', $pattern);
        self::assertSame(1, preg_match($pattern, 'http://localhost:8000/'));
    }

    public function testClientOptionsUseStockPatternWhenFlagIsDisabled(): void
    {
        $validator = new UrlValidator();
        $pattern = (string)$validator->getClientOptions(new UrlModel(), 'url')['pattern'];

        self::assertStringContainsString(UrlValidator::MULTI_LABEL_HOST, $pattern);
        self::assertSame(0, preg_match($pattern, 'http://localhost:8000/'));
    }
}
