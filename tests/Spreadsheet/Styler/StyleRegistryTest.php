<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Tests\Spreadsheet\Styler;

use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\StyleRegistry;
use PHPUnit\Framework\TestCase;

class StyleRegistryTest extends TestCase
{
    public function testConstructorWithDefaultStyles(): void
    {
        $defaultStyles = [
            'header' => ['font' => ['bold' => true]],
            'number' => ['numberFormat' => ['formatCode' => '0.00']],
        ];

        $registry = new StyleRegistry($defaultStyles);

        $this->assertTrue($registry->has('header'));
        $this->assertTrue($registry->has('number'));
        $this->assertEquals(['font' => ['bold' => true]], $registry->get('header'));
    }

    public function testConstructorWithEmptyStyles(): void
    {
        $registry = new StyleRegistry([]);

        $this->assertFalse($registry->has('header'));
        $this->assertEquals([], $registry->all());
    }

    public function testRegisterNewStyle(): void
    {
        $registry = new StyleRegistry([]);

        $style = ['font' => ['color' => 'FF0000']];
        $registry->register('custom', $style);

        $this->assertTrue($registry->has('custom'));
        $this->assertEquals($style, $registry->get('custom'));
    }

    public function testRegisterOverwritesExistingStyle(): void
    {
        $registry = new StyleRegistry(['existing' => ['old' => 'style']]);

        $newStyle = ['new' => 'style'];
        $registry->register('existing', $newStyle);

        $this->assertEquals($newStyle, $registry->get('existing'));
    }

    public function testHasReturnsTrueForExistingStyle(): void
    {
        $registry = new StyleRegistry(['myStyle' => ['font' => ['bold' => true]]]);

        $this->assertTrue($registry->has('myStyle'));
    }

    public function testHasReturnsFalseForNonExistingStyle(): void
    {
        $registry = new StyleRegistry([]);

        $this->assertFalse($registry->has('nonExisting'));
    }

    public function testGetReturnsCorrectStyle(): void
    {
        $style = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => 'center'],
        ];
        $registry = new StyleRegistry(['title' => $style]);

        $this->assertEquals($style, $registry->get('title'));
    }

    public function testGetThrowsExceptionForUnknownStyle(): void
    {
        $registry = new StyleRegistry(['style1' => [], 'style2' => []]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Style nommé inconnu: 'unknown'. Styles connus: [style1, style2]");

        $registry->get('unknown');
    }

    public function testAllReturnsAllStyles(): void
    {
        $styles = [
            'style1' => ['font' => ['bold' => true]],
            'style2' => ['font' => ['italic' => true]],
            'style3' => ['font' => ['underline' => true]],
        ];
        $registry = new StyleRegistry($styles);

        $this->assertEquals($styles, $registry->all());
    }

    public function testAllReturnsEmptyArrayWhenNoStyles(): void
    {
        $registry = new StyleRegistry([]);

        $this->assertEquals([], $registry->all());
    }

    public function testMultipleRegistrations(): void
    {
        $registry = new StyleRegistry([]);

        $registry->register('style1', ['a' => 1]);
        $registry->register('style2', ['b' => 2]);
        $registry->register('style3', ['c' => 3]);

        $this->assertCount(3, $registry->all());
        $this->assertTrue($registry->has('style1'));
        $this->assertTrue($registry->has('style2'));
        $this->assertTrue($registry->has('style3'));
    }
}
