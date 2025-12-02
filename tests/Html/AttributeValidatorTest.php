<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Tests\Html;

use Davidannebicque\HtmlToSpreadsheetBundle\Html\AttributeValidator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AttributeValidatorTest extends TestCase
{
    public function testStrictModeBlocksUnknownAttribute(): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Attribut non autorisé: data-xls-unknown');

        $validator->assertAllowed('data-xls-unknown', 'value');
    }

    public function testNonStrictModeAllowsUnknownAttribute(): void
    {
        $validator = new AttributeValidator(strict: false);

        // Should not throw
        $validator->assertAllowed('data-xls-unknown', 'value');

        $this->assertTrue(true); // If we get here, test passes
    }

    public function testEmptySheetNameThrowsException(): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('data-xls-sheet ne peut pas être vide');

        $validator->assertAllowed('data-xls-sheet', '');
    }

    public function testValidSheetName(): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed('data-xls-sheet', 'MySheet');

        $this->assertTrue(true);
    }

    #[DataProvider('invalidTabColorProvider')]
    public function testInvalidTabColorThrowsException(string $color): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Couleur d'onglet invalide: $color");

        $validator->assertAllowed('data-xls-tab-color', $color);
    }

    public static function invalidTabColorProvider(): array
    {
        return [
            ['invalid'],
            ['#GGG'],
            ['12345'],
            ['#12345'],
            ['#1234567'],
        ];
    }

    #[DataProvider('validTabColorProvider')]
    public function testValidTabColor(string $color): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed('data-xls-tab-color', $color);

        $this->assertTrue(true);
    }

    public static function validTabColorProvider(): array
    {
        return [
            ['FF0000'],
            ['#FF0000'],
            ['ff0000'],
            ['#ff0000'],
            ['ABC123'],
            ['#abc123'],
        ];
    }

    #[DataProvider('invalidOrientationProvider')]
    public function testInvalidPrintOrientation(string $orientation): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Orientation invalide: $orientation");

        $validator->assertAllowed('data-xls-print-orientation', $orientation);
    }

    public static function invalidOrientationProvider(): array
    {
        return [
            ['horizontal'],
            ['vertical'],
            ['Portrait'],
            ['LANDSCAPE'],
        ];
    }

    #[DataProvider('validOrientationProvider')]
    public function testValidPrintOrientation(string $orientation): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed('data-xls-print-orientation', $orientation);

        $this->assertTrue(true);
    }

    public static function validOrientationProvider(): array
    {
        return [
            ['portrait'],
            ['landscape'],
        ];
    }

    #[DataProvider('invalidGridlinesProvider')]
    public function testInvalidGridlines(string $value): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Gridlines doit être 'on' ou 'off'");

        $validator->assertAllowed('data-xls-gridlines', $value);
    }

    public static function invalidGridlinesProvider(): array
    {
        return [
            ['true'],
            ['false'],
            ['1'],
            ['0'],
            ['yes'],
            ['no'],
        ];
    }

    #[DataProvider('validGridlinesProvider')]
    public function testValidGridlines(string $value): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed('data-xls-gridlines', $value);

        $this->assertTrue(true);
    }

    public static function validGridlinesProvider(): array
    {
        return [
            ['on'],
            ['off'],
        ];
    }

    #[DataProvider('invalidPositiveIntegerProvider')]
    public function testInvalidPositiveIntegerAttributes(string $attribute, string $value): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("$attribute doit être un entier positif");

        $validator->assertAllowed($attribute, $value);
    }

    public static function invalidPositiveIntegerProvider(): array
    {
        return [
            ['data-xls-colspan', '0'],
            ['data-xls-colspan', '-1'],
            ['data-xls-colspan', 'abc'],
            ['data-xls-colspan', '1.5'],
            ['data-xls-rowspan', '0'],
            ['data-xls-zoom', '-10'],
            ['data-xls-width', '0'],
            ['data-xls-default-col-width', 'invalid'],
            ['data-xls-img-width', '0'],
            ['data-xls-img-height', '-5'],
        ];
    }

    #[DataProvider('validPositiveIntegerProvider')]
    public function testValidPositiveIntegerAttributes(string $attribute, string $value): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed($attribute, $value);

        $this->assertTrue(true);
    }

    public static function validPositiveIntegerProvider(): array
    {
        return [
            ['data-xls-colspan', '1'],
            ['data-xls-colspan', '10'],
            ['data-xls-rowspan', '5'],
            ['data-xls-zoom', '100'],
            ['data-xls-width', '50'],
            ['data-xls-default-col-width', '15'],
            ['data-xls-img-width', '200'],
            ['data-xls-img-height', '150'],
        ];
    }

    #[DataProvider('invalidPageMarginsProvider')]
    public function testInvalidPageMargins(string $value, string $expectedMessage): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $validator->assertAllowed('data-xls-page-margins', $value);
    }

    public static function invalidPageMarginsProvider(): array
    {
        return [
            // Empty string produces a single empty part which is not numeric
            ['', 'Marge invalide'],
            ['1,2,3,4,5,6,7', 'data-xls-page-margins attend 1 à 6 nombres'],
            ['1,abc', 'Marge invalide: abc'],
            ['top,right', 'Marge invalide: top'],
        ];
    }

    #[DataProvider('validPageMarginsProvider')]
    public function testValidPageMargins(string $value): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed('data-xls-page-margins', $value);

        $this->assertTrue(true);
    }

    public static function validPageMarginsProvider(): array
    {
        return [
            ['1'],
            ['0.5'],
            ['1, 2'],
            ['1, 2, 3'],
            ['1, 2, 3, 4'],
            ['1.5, 2.5, 1.5, 2.5'],
            ['1, 2, 3, 4, 0.5, 0.5'],
        ];
    }

    public function testAllTableLevelAttributesAreAllowed(): void
    {
        $validator = new AttributeValidator(strict: true);

        $tableAttributes = [
            'data-xls-sheet' => 'Sheet1',
            'data-xls-freeze' => 'A2',
            'data-xls-autosize' => 'true',
            'data-xls-autofilter' => 'A1:D1',
            'data-xls-default-col-width' => '15',
            'data-xls-zoom' => '100',
            'data-xls-print-orientation' => 'portrait',
            'data-xls-print-fit' => '1',
            'data-xls-page-margins' => '1,2,3,4',
            'data-xls-tab-color' => 'FF0000',
            'data-xls-gridlines' => 'on',
        ];

        foreach ($tableAttributes as $attr => $value) {
            // Should not throw
            $validator->assertAllowed($attr, $value);
        }

        $this->assertTrue(true);
    }

    public function testAllCellAttributesAreAllowed(): void
    {
        $validator = new AttributeValidator(strict: true);

        $cellAttributes = [
            'data-xls-format' => '0.00',
            'data-xls-align' => 'center',
            'data-xls-valign' => 'middle',
            'data-xls-wrap' => 'true',
            'data-xls-colspan' => '2',
            'data-xls-rowspan' => '3',
            'data-xls-formula' => 'SUM(A1:A10)',
            'data-xls-hyperlink' => 'https://example.com',
            'data-xls-comment' => 'This is a comment',
            'data-xls-dv-list' => 'Option1|Option2|Option3',
            'data-xls-type' => 'number',
            'data-xls-number-locale' => 'fr-FR',
            'data-xls-image' => '/path/to/image.png',
            'data-xls-img-width' => '100',
            'data-xls-img-height' => '100',
            'data-xls-bg-color' => '#FF0000',
            'data-xls-font-size' => '14',
            'data-xls-border' => 'thin',
            'data-xls-border-color' => '#000000',
            'data-xls-locked' => 'true',
        ];

        foreach ($cellAttributes as $attr => $value) {
            // Should not throw
            $validator->assertAllowed($attr, $value);
        }

        $this->assertTrue(true);
    }

    #[DataProvider('invalidColorProvider')]
    public function testInvalidBackgroundColor(string $color): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Couleur invalide pour data-xls-bg-color: $color");

        $validator->assertAllowed('data-xls-bg-color', $color);
    }

    public static function invalidColorProvider(): array
    {
        return [
            ['red'],
            ['#GGG'],
            ['12345'],
            ['#12345'],
            ['#1234567'],
            ['rgb(255,0,0)'],
        ];
    }

    #[DataProvider('validColorProvider')]
    public function testValidBackgroundColor(string $color): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed('data-xls-bg-color', $color);

        $this->assertTrue(true);
    }

    public static function validColorProvider(): array
    {
        return [
            ['FF0000'],
            ['#FF0000'],
            ['ff0000'],
            ['#ff0000'],
            ['ABC123'],
            ['#abc123'],
        ];
    }

    #[DataProvider('invalidColorProvider')]
    public function testInvalidBorderColor(string $color): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Couleur invalide pour data-xls-border-color: $color");

        $validator->assertAllowed('data-xls-border-color', $color);
    }

    #[DataProvider('validColorProvider')]
    public function testValidBorderColor(string $color): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed('data-xls-border-color', $color);

        $this->assertTrue(true);
    }

    #[DataProvider('invalidFontSizeProvider')]
    public function testInvalidFontSize(string $size): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("data-xls-font-size doit être un nombre positif");

        $validator->assertAllowed('data-xls-font-size', $size);
    }

    public static function invalidFontSizeProvider(): array
    {
        return [
            ['0'],
            ['-5'],
            ['abc'],
            [''],
        ];
    }

    #[DataProvider('validFontSizeProvider')]
    public function testValidFontSize(string $size): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed('data-xls-font-size', $size);

        $this->assertTrue(true);
    }

    public static function validFontSizeProvider(): array
    {
        return [
            ['10'],
            ['12'],
            ['18'],
            ['14.5'],
        ];
    }

    #[DataProvider('invalidBorderStyleProvider')]
    public function testInvalidBorderStyle(string $style): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("data-xls-border doit être 'thin', 'medium', 'thick' ou 'none'");

        $validator->assertAllowed('data-xls-border', $style);
    }

    public static function invalidBorderStyleProvider(): array
    {
        return [
            ['solid'],
            ['dashed'],
            ['dotted'],
            ['Thin'],
            ['MEDIUM'],
        ];
    }

    #[DataProvider('validBorderStyleProvider')]
    public function testValidBorderStyle(string $style): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed('data-xls-border', $style);

        $this->assertTrue(true);
    }

    public static function validBorderStyleProvider(): array
    {
        return [
            ['thin'],
            ['medium'],
            ['thick'],
            ['none'],
        ];
    }

    #[DataProvider('invalidLockedValueProvider')]
    public function testInvalidLockedValue(string $value): void
    {
        $validator = new AttributeValidator(strict: true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("data-xls-locked doit être 'true' ou 'false'");

        $validator->assertAllowed('data-xls-locked', $value);
    }

    public static function invalidLockedValueProvider(): array
    {
        return [
            ['1'],
            ['0'],
            ['yes'],
            ['no'],
            ['True'],
            ['FALSE'],
        ];
    }

    #[DataProvider('validLockedValueProvider')]
    public function testValidLockedValue(string $value): void
    {
        $validator = new AttributeValidator(strict: true);

        // Should not throw
        $validator->assertAllowed('data-xls-locked', $value);

        $this->assertTrue(true);
    }

    public static function validLockedValueProvider(): array
    {
        return [
            ['true'],
            ['false'],
        ];
    }
}
