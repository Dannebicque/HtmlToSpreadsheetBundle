<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Tests\Html;

use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlToXlsxOptions;
use PHPUnit\Framework\TestCase;

class HtmlToXlsxOptionsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $options = new HtmlToXlsxOptions();

        $this->assertTrue($options->strict);
        $this->assertNull($options->numberLocale);
    }

    public function testConstructorWithStrictMode(): void
    {
        $options = new HtmlToXlsxOptions(strict: true);

        $this->assertTrue($options->strict);
    }

    public function testConstructorWithNonStrictMode(): void
    {
        $options = new HtmlToXlsxOptions(strict: false);

        $this->assertFalse($options->strict);
    }

    public function testConstructorWithNumberLocale(): void
    {
        $options = new HtmlToXlsxOptions(numberLocale: 'fr-FR');

        $this->assertEquals('fr-FR', $options->numberLocale);
    }

    public function testConstructorWithAllParameters(): void
    {
        $options = new HtmlToXlsxOptions(
            strict: false,
            numberLocale: 'en-US'
        );

        $this->assertFalse($options->strict);
        $this->assertEquals('en-US', $options->numberLocale);
    }

    public function testPublicPropertiesAreAccessible(): void
    {
        $options = new HtmlToXlsxOptions(strict: true, numberLocale: 'de-DE');

        $this->assertTrue(property_exists($options, 'strict'));
        $this->assertTrue(property_exists($options, 'numberLocale'));
        $this->assertTrue($options->strict);
        $this->assertEquals('de-DE', $options->numberLocale);
    }
}
