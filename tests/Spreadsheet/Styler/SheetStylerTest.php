<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Tests\Spreadsheet\Styler;

use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\SheetStyler;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\StyleRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PHPUnit\Framework\TestCase;

class SheetStylerTest extends TestCase
{
    private StyleRegistry $registry;
    private SheetStyler $styler;
    private Spreadsheet $spreadsheet;

    protected function setUp(): void
    {
        $this->registry = new StyleRegistry([
            'bold' => ['font' => ['bold' => true]],
            'red' => ['font' => ['color' => ['argb' => 'FFFF0000']]],
        ]);
        $this->styler = new SheetStyler($this->registry);
        $this->spreadsheet = new Spreadsheet();
    }

    public function testApplyNamedStyleToCell(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Test');

        $this->styler->applyNamedStyle($sheet, 'A1', 'bold');

        $style = $sheet->getStyle('A1');
        $this->assertTrue($style->getFont()->getBold());
    }

    public function testApplyNamedStyleToRange(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Test1');
        $sheet->setCellValue('A2', 'Test2');

        $this->styler->applyNamedStyle($sheet, 'A1:A2', 'bold');

        $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
        $this->assertTrue($sheet->getStyle('A2')->getFont()->getBold());
    }

    public function testApplyNamedStyleToColumnByIndex(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Test1');
        $sheet->setCellValue('A2', 'Test2');

        // Column A is index 1
        $this->styler->applyNamedStyleToColumn($sheet, 1, 'bold');

        // When applying styles to a column range (A:A), the column style is set
        // Individual cells inherit this style
        $this->assertTrue($sheet->getStyle('A:A')->getFont()->getBold());
    }

    public function testApplyNumberFormat(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 1234.56);

        $this->styler->applyNumberFormat($sheet, 'A1', '0.00');

        $format = $sheet->getStyle('A1')->getNumberFormat()->getFormatCode();
        $this->assertEquals('0.00', $format);
    }

    public function testApplyAlignLeft(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->styler->applyAlign($sheet, 'A1', 'left');

        $alignment = $sheet->getStyle('A1')->getAlignment()->getHorizontal();
        $this->assertEquals(Alignment::HORIZONTAL_LEFT, $alignment);
    }

    public function testApplyAlignCenter(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->styler->applyAlign($sheet, 'A1', 'center');

        $alignment = $sheet->getStyle('A1')->getAlignment()->getHorizontal();
        $this->assertEquals(Alignment::HORIZONTAL_CENTER, $alignment);
    }

    public function testApplyAlignRight(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->styler->applyAlign($sheet, 'A1', 'right');

        $alignment = $sheet->getStyle('A1')->getAlignment()->getHorizontal();
        $this->assertEquals(Alignment::HORIZONTAL_RIGHT, $alignment);
    }

    public function testApplyAlignJustify(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->styler->applyAlign($sheet, 'A1', 'justify');

        $alignment = $sheet->getStyle('A1')->getAlignment()->getHorizontal();
        $this->assertEquals(Alignment::HORIZONTAL_JUSTIFY, $alignment);
    }

    public function testApplyAlignUnknownDefaultsToGeneral(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->styler->applyAlign($sheet, 'A1', 'unknown');

        $alignment = $sheet->getStyle('A1')->getAlignment()->getHorizontal();
        $this->assertEquals(Alignment::HORIZONTAL_GENERAL, $alignment);
    }

    public function testApplyVAlignTop(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->styler->applyVAlign($sheet, 'A1', 'top');

        $alignment = $sheet->getStyle('A1')->getAlignment()->getVertical();
        $this->assertEquals(Alignment::VERTICAL_TOP, $alignment);
    }

    public function testApplyVAlignMiddle(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->styler->applyVAlign($sheet, 'A1', 'middle');

        $alignment = $sheet->getStyle('A1')->getAlignment()->getVertical();
        $this->assertEquals(Alignment::VERTICAL_CENTER, $alignment);
    }

    public function testApplyVAlignBottom(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->styler->applyVAlign($sheet, 'A1', 'bottom');

        $alignment = $sheet->getStyle('A1')->getAlignment()->getVertical();
        $this->assertEquals(Alignment::VERTICAL_BOTTOM, $alignment);
    }

    public function testApplyWrapTextTrue(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->styler->applyWrap($sheet, 'A1', true);

        $this->assertTrue($sheet->getStyle('A1')->getAlignment()->getWrapText());
    }

    public function testApplyWrapTextFalse(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->styler->applyWrap($sheet, 'A1', false);

        $this->assertFalse($sheet->getStyle('A1')->getAlignment()->getWrapText());
    }

    public function testApplyListValidation(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $items = ['Option1', 'Option2', 'Option3'];

        $this->styler->applyListValidation($sheet, 'A1', $items);

        $validation = $sheet->getCell('A1')->getDataValidation();
        $this->assertEquals('list', $validation->getType());
        $this->assertTrue($validation->getAllowBlank());
        $this->assertTrue($validation->getShowDropDown());
        $this->assertEquals('"Option1,Option2,Option3"', $validation->getFormula1());
    }

    public function testApplyListValidationWithSpecialCharacters(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $items = ['Option "1"', 'Option "2"'];

        $this->styler->applyListValidation($sheet, 'A1', $items);

        $validation = $sheet->getCell('A1')->getDataValidation();
        // Double quotes should be escaped as ""
        $this->assertEquals('"Option ""1"",Option ""2"""', $validation->getFormula1());
    }

    public function testApplyNamedStyleThrowsExceptionForUnknownStyle(): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Style nommé inconnu: 'unknown'");

        $this->styler->applyNamedStyle($sheet, 'A1', 'unknown');
    }
}
