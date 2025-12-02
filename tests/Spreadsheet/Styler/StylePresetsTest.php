<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Tests\Spreadsheet\Styler;

use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\StylePresets;
use PHPUnit\Framework\TestCase;

class StylePresetsTest extends TestCase
{
    public function testHeaderReturnsValidStyle(): void
    {
        $style = StylePresets::header();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('font', $style);
        $this->assertArrayHasKey('fill', $style);
        $this->assertArrayHasKey('alignment', $style);
        $this->assertArrayHasKey('borders', $style);
        $this->assertTrue($style['font']['bold']);
    }

    public function testIntReturnsValidStyle(): void
    {
        $style = StylePresets::int();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('alignment', $style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertEquals('right', $style['alignment']['horizontal']);
        $this->assertEquals('# ##0', $style['numberFormat']['formatCode']);
    }

    public function testFloat2ReturnsValidStyle(): void
    {
        $style = StylePresets::float2();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertEquals('# ##0,00', $style['numberFormat']['formatCode']);
    }

    public function testFloat3ReturnsValidStyle(): void
    {
        $style = StylePresets::float3();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertEquals('# ##0,000', $style['numberFormat']['formatCode']);
    }

    public function testPercent2ReturnsValidStyle(): void
    {
        $style = StylePresets::percent2();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertEquals('0,00%', $style['numberFormat']['formatCode']);
    }

    public function testMoneyEuroReturnsValidStyle(): void
    {
        $style = StylePresets::moneyEuro();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertArrayHasKey('alignment', $style);
        $this->assertStringContainsString('€', $style['numberFormat']['formatCode']);
        $this->assertEquals('right', $style['alignment']['horizontal']);
    }

    public function testMoneyEuroAccountingReturnsValidStyle(): void
    {
        $style = StylePresets::moneyEuroAccounting();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertStringContainsString('€', $style['numberFormat']['formatCode']);
    }

    public function testDateShortReturnsValidStyle(): void
    {
        $style = StylePresets::dateShort();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertEquals('dd/mm/yyyy', $style['numberFormat']['formatCode']);
        $this->assertEquals('center', $style['alignment']['horizontal']);
    }

    public function testDateLongFrReturnsValidStyle(): void
    {
        $style = StylePresets::dateLongFr();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertStringContainsString('fr-FR', $style['numberFormat']['formatCode']);
        $this->assertEquals('left', $style['alignment']['horizontal']);
    }

    public function testTimeShortReturnsValidStyle(): void
    {
        $style = StylePresets::timeShort();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertEquals('hh:mm', $style['numberFormat']['formatCode']);
    }

    public function testDateTimeReturnsValidStyle(): void
    {
        $style = StylePresets::dateTime();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertEquals('dd/mm/yyyy hh:mm', $style['numberFormat']['formatCode']);
    }

    public function testDurationReturnsValidStyle(): void
    {
        $style = StylePresets::duration();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('numberFormat', $style);
        $this->assertEquals('[h]:mm:ss', $style['numberFormat']['formatCode']);
    }

    public function testBoolCenteredReturnsValidStyle(): void
    {
        $style = StylePresets::boolCentered();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('alignment', $style);
        $this->assertEquals('center', $style['alignment']['horizontal']);
    }

    public function testTextWrapReturnsValidStyle(): void
    {
        $style = StylePresets::textWrap();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('alignment', $style);
        $this->assertTrue($style['alignment']['wrapText']);
        $this->assertEquals('top', $style['alignment']['vertical']);
    }

    public function testWarningReturnsValidStyle(): void
    {
        $style = StylePresets::warning();

        $this->assertIsArray($style);
        $this->assertArrayHasKey('fill', $style);
        $this->assertEquals('solid', $style['fill']['fillType']);
        $this->assertEquals('FFFFFF99', $style['fill']['color']['argb']);
    }

    public function testAllPresetsReturnArrays(): void
    {
        $methods = [
            'header', 'int', 'float2', 'float3', 'percent2',
            'moneyEuro', 'moneyEuroAccounting', 'dateShort', 'dateLongFr',
            'timeShort', 'dateTime', 'duration', 'boolCentered', 'textWrap', 'warning'
        ];

        foreach ($methods as $method) {
            $style = StylePresets::$method();
            $this->assertIsArray($style, "Method $method should return an array");
            $this->assertNotEmpty($style, "Method $method should not return an empty array");
        }
    }
}
