<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Tests\Html;

use Davidannebicque\HtmlToSpreadsheetBundle\Html\AttributeValidator;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlTableInterpreter;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlToXlsxOptions;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\SheetStyler;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\StyleRegistry;
use PHPUnit\Framework\TestCase;

class HtmlTableInterpreterTest extends TestCase
{
    private HtmlTableInterpreter $interpreter;
    private StyleRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new StyleRegistry([
            'bold' => ['font' => ['bold' => true]],
        ]);
        $styler = new SheetStyler($this->registry);
        $validator = new AttributeValidator(strict: false);
        $this->interpreter = new HtmlTableInterpreter($styler, $validator);
    }

    public function testFromHtmlThrowsExceptionWhenNoTableWithSheetAttribute(): void
    {
        $html = '<table><tr><td>Test</td></tr></table>';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Aucun <table data-xls-sheet> trouvé');

        $this->interpreter->fromHtml($html);
    }

    public function testFromHtmlCreatesBasicSpreadsheet(): void
    {
        $html = '<table data-xls-sheet="Sheet1">
            <tr><td>Hello</td><td>World</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);

        $this->assertCount(1, $spreadsheet->getAllSheets());
        $this->assertEquals('Sheet1', $spreadsheet->getActiveSheet()->getTitle());
        $this->assertEquals('Hello', $spreadsheet->getActiveSheet()->getCell('A1')->getValue());
        $this->assertEquals('World', $spreadsheet->getActiveSheet()->getCell('B1')->getValue());
    }

    public function testFromHtmlCreatesMultipleSheets(): void
    {
        $html = '
            <table data-xls-sheet="First"><tr><td>A</td></tr></table>
            <table data-xls-sheet="Second"><tr><td>B</td></tr></table>
        ';

        $spreadsheet = $this->interpreter->fromHtml($html);

        $this->assertCount(2, $spreadsheet->getAllSheets());
        $this->assertEquals('First', $spreadsheet->getSheet(0)->getTitle());
        $this->assertEquals('Second', $spreadsheet->getSheet(1)->getTitle());
        $this->assertEquals('A', $spreadsheet->getSheet(0)->getCell('A1')->getValue());
        $this->assertEquals('B', $spreadsheet->getSheet(1)->getCell('A1')->getValue());
    }

    public function testHandlesUtf8Content(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td>Français</td><td>日本語</td><td>العربية</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('Français', $sheet->getCell('A1')->getValue());
        $this->assertEquals('日本語', $sheet->getCell('B1')->getValue());
        $this->assertEquals('العربية', $sheet->getCell('C1')->getValue());
    }

    public function testHandlesTheadTbodyTfoot(): void
    {
        $html = '<table data-xls-sheet="Test">
            <thead><tr><td>Header</td></tr></thead>
            <tbody><tr><td>Body</td></tr></tbody>
            <tfoot><tr><td>Footer</td></tr></tfoot>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('Header', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Body', $sheet->getCell('A2')->getValue());
        $this->assertEquals('Footer', $sheet->getCell('A3')->getValue());
    }

    public function testCellMergingWithColspan(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-colspan="3">Merged</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('Merged', $sheet->getCell('A1')->getValue());
        // Check if A1:C1 is merged
        $this->assertTrue($sheet->getCell('A1')->isInMergeRange());
        $mergedCells = $sheet->getMergeCells();
        $this->assertContains('A1:C1', $mergedCells);
    }

    public function testCellMergingWithRowspan(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-rowspan="2">Merged</td><td>A</td></tr>
            <tr><td>B</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('Merged', $sheet->getCell('A1')->getValue());
        // Check if A1:A2 is merged
        $this->assertTrue($sheet->getCell('A1')->isInMergeRange());
        $mergedCells = $sheet->getMergeCells();
        $this->assertContains('A1:A2', $mergedCells);
    }

    public function testExcelFormula(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td>10</td><td>20</td><td data-xls-formula="=A1+B1"></td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('=A1+B1', $sheet->getCell('C1')->getValue());
    }

    public function testFormulaWithoutEqualSign(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-formula="SUM(A2:A5)">Text</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('=SUM(A2:A5)', $sheet->getCell('A1')->getValue());
    }

    public function testForcedTypeString(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-type="string">123</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('123', $sheet->getCell('A1')->getValue());
        $this->assertIsString($sheet->getCell('A1')->getValue());
    }

    public function testForcedTypeNumber(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-type="number">1234.56</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(1234.56, $sheet->getCell('A1')->getValue());
    }

    public function testForcedTypeBool(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr>
                <td data-xls-type="bool">true</td>
                <td data-xls-type="bool">false</td>
                <td data-xls-type="bool">1</td>
                <td data-xls-type="bool">oui</td>
            </tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertTrue($sheet->getCell('A1')->getValue());
        $this->assertFalse($sheet->getCell('B1')->getValue());
        $this->assertTrue($sheet->getCell('C1')->getValue());
        $this->assertTrue($sheet->getCell('D1')->getValue());
    }

    public function testForcedTypeNull(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-type="null">anything</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertNull($sheet->getCell('A1')->getValue());
    }

    public function testNumberParsingWithFrenchLocale(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-number-locale="fr-FR" data-xls-type="number">1 234,56</td></tr>
        </table>';

        $options = new HtmlToXlsxOptions(numberLocale: 'fr-FR');
        $spreadsheet = $this->interpreter->fromHtml($html, $options);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(1234.56, $sheet->getCell('A1')->getValue());
    }

    public function testHyperlink(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-hyperlink="https://example.com">Link</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('https://example.com', $sheet->getCell('A1')->getHyperlink()->getUrl());
    }

    public function testCellComment(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-comment="This is a comment">Data</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $comment = $sheet->getComment('A1')->getText()->getPlainText();
        $this->assertStringContainsString('This is a comment', $comment);
    }

    public function testColumnWidth(): void
    {
        $html = '<table data-xls-sheet="Test">
            <colgroup>
                <col data-xls-width="30">
                <col data-xls-width="50">
            </colgroup>
            <tr><td>A</td><td>B</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(30, $sheet->getColumnDimensionByColumn(1)->getWidth());
        $this->assertEquals(50, $sheet->getColumnDimensionByColumn(2)->getWidth());
    }

    public function testHiddenColumn(): void
    {
        $html = '<table data-xls-sheet="Test">
            <colgroup>
                <col data-xls-hidden="true">
                <col>
            </colgroup>
            <tr><td>Hidden</td><td>Visible</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertFalse($sheet->getColumnDimensionByColumn(1)->getVisible());
        $this->assertTrue($sheet->getColumnDimensionByColumn(2)->getVisible());
    }

    public function testRowHeight(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr data-xls-height="50"><td>Tall row</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(50, $sheet->getRowDimension(1)->getRowHeight());
    }

    public function testFreezePane(): void
    {
        $html = '<table data-xls-sheet="Test" data-xls-freeze="B2">
            <tr><td>A1</td><td>B1</td></tr>
            <tr><td>A2</td><td>B2</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('B2', $sheet->getFreezePane());
    }

    public function testZoomLevel(): void
    {
        $html = '<table data-xls-sheet="Test" data-xls-zoom="150">
            <tr><td>Zoomed</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(150, $sheet->getSheetView()->getZoomScale());
    }

    public function testTabColor(): void
    {
        $html = '<table data-xls-sheet="Test" data-xls-tab-color="FF0000">
            <tr><td>Red tab</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('FF0000', $sheet->getTabColor()->getRGB());
    }

    public function testGridlinesOn(): void
    {
        $html = '<table data-xls-sheet="Test" data-xls-gridlines="on">
            <tr><td>Grid</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertTrue($sheet->getShowGridLines());
    }

    public function testGridlinesOff(): void
    {
        $html = '<table data-xls-sheet="Test" data-xls-gridlines="off">
            <tr><td>No grid</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertFalse($sheet->getShowGridLines());
    }

    public function testSafeTabNameRemovesInvalidCharacters(): void
    {
        $html = '<table data-xls-sheet="Sheet:Name/With*Invalid?Chars">
            <tr><td>Test</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $title = $sheet->getTitle();
        $this->assertStringNotContainsString(':', $title);
        $this->assertStringNotContainsString('/', $title);
        $this->assertStringNotContainsString('*', $title);
        $this->assertStringNotContainsString('?', $title);
    }

    public function testSafeTabNameTruncatesTo31Characters(): void
    {
        $longName = str_repeat('A', 50);
        $html = '<table data-xls-sheet="'.$longName.'">
            <tr><td>Test</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(31, mb_strlen($sheet->getTitle()));
    }

    public function testDefaultColWidth(): void
    {
        $html = '<table data-xls-sheet="Test" data-xls-default-col-width="20">
            <tr><td>A</td><td>B</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals(20, $sheet->getDefaultColumnDimension()->getWidth());
    }

    public function testBackgroundColor(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-bg-color="#FF0000">Red cell</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $bgColor = $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB();
        $this->assertEquals('FF0000', $bgColor);
    }

    public function testBackgroundColorWithoutHash(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-bg-color="00FF00">Green cell</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $bgColor = $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB();
        $this->assertEquals('00FF00', $bgColor);
    }

    public function testFontSize(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-font-size="18">Large text</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $fontSize = $sheet->getStyle('A1')->getFont()->getSize();
        $this->assertEquals(18, $fontSize);
    }

    public function testBorderThin(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-border="thin">Bordered</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $borderStyle = $sheet->getStyle('A1')->getBorders()->getTop()->getBorderStyle();
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, $borderStyle);
    }

    public function testBorderMedium(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-border="medium">Bordered</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $borderStyle = $sheet->getStyle('A1')->getBorders()->getTop()->getBorderStyle();
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, $borderStyle);
    }

    public function testBorderWithColor(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-border="thin" data-xls-border-color="#FF0000">Red border</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $borderColor = $sheet->getStyle('A1')->getBorders()->getTop()->getColor()->getRGB();
        $this->assertEquals('FF0000', $borderColor);
    }

    public function testCellProtectionLocked(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-locked="true">Protected</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $isLocked = $sheet->getStyle('A1')->getProtection()->getLocked();
        // getLocked() returns '1' for protected and '' for unprotected
        $this->assertEquals('1', $isLocked);
    }

    public function testCellProtectionUnlocked(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-locked="false">Unlocked</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $isLocked = $sheet->getStyle('A1')->getProtection()->getLocked();
        // getLocked() returns '' for unprotected
        $this->assertEquals('', $isLocked);
    }

    public function testLocalImagePath(): void
    {
        // Create a temporary test image
        $tmpImage = tempnam(sys_get_temp_dir(), 'test_img_');
        $img = imagecreate(10, 10);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, 10, 10, $white);
        imagepng($img, $tmpImage);
        imagedestroy($img);

        try {
            $html = '<table data-xls-sheet="Test">
                <tr><td data-xls-image="'.$tmpImage.'">Image</td></tr>
            </table>';

            $spreadsheet = $this->interpreter->fromHtml($html);
            $sheet = $spreadsheet->getActiveSheet();

            // Check that the image was added
            $drawings = $sheet->getDrawingCollection();
            $this->assertCount(1, $drawings);
            $this->assertEquals('A1', $drawings[0]->getCoordinates());
        } finally {
            @unlink($tmpImage);
        }
    }

    public function testBase64ImageDataUri(): void
    {
        // Minimal 1x1 PNG in base64
        $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $dataUri = 'data:image/png;base64,' . $base64;

        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-image="'.$dataUri.'">Image</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        // Check that the image was added
        $drawings = $sheet->getDrawingCollection();
        $this->assertCount(1, $drawings);
        $this->assertEquals('A1', $drawings[0]->getCoordinates());
    }

    public function testImageWithDimensions(): void
    {
        // Create a temporary test image (50x25 to match 2:1 ratio)
        $tmpImage = tempnam(sys_get_temp_dir(), 'test_img_');
        $img = imagecreate(50, 25);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, 50, 25, $white);
        imagepng($img, $tmpImage);
        imagedestroy($img);

        try {
            $html = '<table data-xls-sheet="Test">
                <tr><td data-xls-image="'.$tmpImage.'" data-xls-img-width="100" data-xls-img-height="50">Image</td></tr>
            </table>';

            $spreadsheet = $this->interpreter->fromHtml($html);
            $sheet = $spreadsheet->getActiveSheet();

            // Check that the image was added with correct dimensions
            $drawings = $sheet->getDrawingCollection();
            $this->assertCount(1, $drawings);
            // PhpSpreadsheet may adjust dimensions to maintain aspect ratio
            $this->assertEquals(100, $drawings[0]->getWidth());
            $this->assertEquals(50, $drawings[0]->getHeight());
        } finally {
            @unlink($tmpImage);
        }
    }

    public function testInvalidImagePathThrowsException(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-image="/nonexistent/image.png">Image</td></tr>
        </table>';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Image introuvable');

        $this->interpreter->fromHtml($html);
    }

    // Priority 2: Font Styling Tests

    public function testFontColor(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-font-color="#FF0000">Red text</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $fontColor = $sheet->getStyle('A1')->getFont()->getColor()->getRGB();
        $this->assertEquals('FF0000', $fontColor);
    }

    public function testFontBold(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-font-bold="true">Bold text</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
    }

    public function testFontItalic(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-font-italic="true">Italic text</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertTrue($sheet->getStyle('A1')->getFont()->getItalic());
    }

    public function testFontUnderlineSingle(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-font-underline="single">Underlined text</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $underline = $sheet->getStyle('A1')->getFont()->getUnderline();
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE, $underline);
    }

    public function testFontUnderlineDouble(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-font-underline="double">Double underlined</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $underline = $sheet->getStyle('A1')->getFont()->getUnderline();
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_DOUBLE, $underline);
    }

    public function testFontName(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-font-name="Arial">Arial text</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $fontName = $sheet->getStyle('A1')->getFont()->getName();
        $this->assertEquals('Arial', $fontName);
    }

    public function testCombinedFontStyling(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-font-color="#0000FF"
                    data-xls-font-bold="true"
                    data-xls-font-italic="true"
                    data-xls-font-size="16"
                    data-xls-font-name="Times New Roman">Styled text</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $font = $sheet->getStyle('A1')->getFont();
        $this->assertEquals('0000FF', $font->getColor()->getRGB());
        $this->assertTrue($font->getBold());
        $this->assertTrue($font->getItalic());
        $this->assertEquals(16, $font->getSize());
        $this->assertEquals('Times New Roman', $font->getName());
    }

    // Priority 2: Conditional Formatting Tests

    public function testConditionalFormattingGreaterThan(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-conditional="value>100|bg:FF0000">150</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $conditionalStyles = $sheet->getStyle('A1')->getConditionalStyles();
        $this->assertCount(1, $conditionalStyles);

        $condition = $conditionalStyles[0];
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS, $condition->getConditionType());
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_GREATERTHAN, $condition->getOperatorType());
    }

    public function testConditionalFormattingLessThan(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-conditional="value<0|bg:FF0000">-10</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $conditionalStyles = $sheet->getStyle('A1')->getConditionalStyles();
        $this->assertCount(1, $conditionalStyles);

        $condition = $conditionalStyles[0];
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_LESSTHAN, $condition->getOperatorType());
    }

    public function testConditionalFormattingBetween(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-conditional="between:0:100|bg:00FF00">50</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $conditionalStyles = $sheet->getStyle('A1')->getConditionalStyles();
        $this->assertCount(1, $conditionalStyles);

        $condition = $conditionalStyles[0];
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_BETWEEN, $condition->getOperatorType());
        $this->assertEquals(['0', '100'], $condition->getConditions());
    }

    public function testConditionalFormattingMultipleStyles(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-conditional="value>100|bg:FF0000|font:FFFFFF|bold">150</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $conditionalStyles = $sheet->getStyle('A1')->getConditionalStyles();
        $this->assertCount(1, $conditionalStyles);

        $condition = $conditionalStyles[0];
        $style = $condition->getStyle();

        // Vérifier que le style contient bien un background et une font
        $this->assertNotNull($style);
    }

    // =============================
    // Cell Merging: Rowspan & Colspan
    // =============================

    public function testRowspanSimple(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-rowspan="3">A</td><td>B1</td></tr>
            <tr><td>B2</td></tr>
            <tr><td>B3</td></tr>
        </table>';

        $workbook = $this->interpreter->fromHtml($html);
        $sheet = $workbook->getActiveSheet();

        $this->assertEquals('A', $sheet->getCell('A1')->getValue());
        $this->assertTrue($sheet->getCell('A1')->isInMergeRange());
        $this->assertContains('A1:A3', $sheet->getMergeCells());
    }

    public function testColspanSimple(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-colspan="3">Header</td></tr>
            <tr><td>A</td><td>B</td><td>C</td></tr>
        </table>';

        $workbook = $this->interpreter->fromHtml($html);
        $sheet = $workbook->getActiveSheet();

        $this->assertEquals('Header', $sheet->getCell('A1')->getValue());
        $this->assertTrue($sheet->getCell('A1')->isInMergeRange());
        $this->assertContains('A1:C1', $sheet->getMergeCells());
    }

    public function testRowspanAndColspanCombined(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr>
                <td data-xls-rowspan="2" data-xls-colspan="2">Merged 2x2</td>
                <td>C1</td>
            </tr>
            <tr>
                <td>C2</td>
            </tr>
        </table>';

        $workbook = $this->interpreter->fromHtml($html);
        $sheet = $workbook->getActiveSheet();

        $this->assertEquals('Merged 2x2', $sheet->getCell('A1')->getValue());
        $this->assertTrue($sheet->getCell('A1')->isInMergeRange());
        $this->assertContains('A1:B2', $sheet->getMergeCells());
    }

    public function testComplexTableWithMergedCells(): void
    {
        $html = '<table data-xls-sheet="Test">
            <thead>
                <tr>
                    <th data-xls-colspan="2">Group 1</th>
                    <th data-xls-colspan="2">Group 2</th>
                </tr>
                <tr>
                    <th>A</th><th>B</th><th>C</th><th>D</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td data-xls-rowspan="2">Row 1-2</td>
                    <td>1</td>
                    <td>2</td>
                    <td>3</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>5</td>
                    <td>6</td>
                </tr>
            </tbody>
        </table>';

        $workbook = $this->interpreter->fromHtml($html);
        $sheet = $workbook->getActiveSheet();

        // Check colspan in header
        $this->assertContains('A1:B1', $sheet->getMergeCells());
        $this->assertContains('C1:D1', $sheet->getMergeCells());

        // Check rowspan in body
        $this->assertContains('A3:A4', $sheet->getMergeCells());
        $this->assertEquals('Row 1-2', $sheet->getCell('A3')->getValue());
    }

    public function testRowspanWithStyling(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr>
                <td data-xls-rowspan="2"
                    data-xls-bg-color="#FFCC00"
                    data-xls-font-bold="true"
                    data-xls-align="center"
                    data-xls-valign="center">
                    Merged & Styled
                </td>
                <td>A</td>
            </tr>
            <tr><td>B</td></tr>
        </table>';

        $workbook = $this->interpreter->fromHtml($html);
        $sheet = $workbook->getActiveSheet();

        $this->assertContains('A1:A2', $sheet->getMergeCells());

        // Check styling applied
        $style = $sheet->getStyle('A1');
        $this->assertEquals('FFCC00', $style->getFill()->getStartColor()->getRGB());
        $this->assertTrue($style->getFont()->getBold());
        $this->assertEquals('center', $style->getAlignment()->getHorizontal());
    // Priority 3: Hyperlinks
    // =============================

    public function testHyperlinkExternal(): void
    {
        $html = '<table data-xls-sheet="Test"><tr><td data-xls-link="https://example.com">Click here</td></tr></table>';
        $workbook = $this->interpreter->fromHtml($html);
        $sheet = $workbook->getActiveSheet();

        $hyperlink = $sheet->getCell('A1')->getHyperlink();
        $this->assertEquals('https://example.com', $hyperlink->getUrl());

        // Check default hyperlink style (blue, underlined)
        $font = $sheet->getStyle('A1')->getFont();
        $this->assertEquals('0563C1', $font->getColor()->getRGB());
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE, $font->getUnderline());
    }

    public function testHyperlinkInternal(): void
    {
        $html = '<table data-xls-sheet="Sheet1"><tr><td data-xls-link="#Sheet2!B5">Go to Sheet2</td></tr></table>';
        $workbook = $this->interpreter->fromHtml($html);
        $sheet = $workbook->getActiveSheet();

        $hyperlink = $sheet->getCell('A1')->getHyperlink();
        $this->assertEquals('#Sheet2!B5', $hyperlink->getUrl());
    }

    public function testHyperlinkEmail(): void
    {
        $html = '<table data-xls-sheet="Test"><tr><td data-xls-link="mailto:test@example.com">Send email</td></tr></table>';
        $workbook = $this->interpreter->fromHtml($html);
        $sheet = $workbook->getActiveSheet();

        $hyperlink = $sheet->getCell('A1')->getHyperlink();
        $this->assertEquals('mailto:test@example.com', $hyperlink->getUrl());
    }

    public function testHyperlinkWithTooltip(): void
    {
        $html = '<table data-xls-sheet="Test"><tr><td data-xls-link="https://example.com" data-xls-link-tooltip="Visit our website">Link</td></tr></table>';
        $workbook = $this->interpreter->fromHtml($html);
        $sheet = $workbook->getActiveSheet();

        $hyperlink = $sheet->getCell('A1')->getHyperlink();
        $this->assertEquals('https://example.com', $hyperlink->getUrl());
        $this->assertEquals('Visit our website', $hyperlink->getTooltip());
    }

    public function testHyperlinkInvalidUrlThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('data-xls-link doit être une URL valide');

        // Use strict validator for this test
    // Priority 3: Cell Comments Tests

    public function testBasicComment(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-comment="This is a comment">Cell with comment</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $comment = $sheet->getComment('A1');
        $this->assertEquals('This is a comment', $comment->getText()->getPlainText());
    }

    public function testCommentWithAuthor(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-comment="Review this cell" data-xls-comment-author="John Doe">Data</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $comment = $sheet->getComment('A1');
        $this->assertEquals('Review this cell', $comment->getText()->getPlainText());
        $this->assertEquals('John Doe', $comment->getAuthor());
    }

    public function testCommentWithDimensions(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-comment="Long comment text"
                    data-xls-comment-width="300"
                    data-xls-comment-height="100">Cell</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $comment = $sheet->getComment('A1');
        $this->assertEquals('300pt', $comment->getWidth());
        $this->assertEquals('100pt', $comment->getHeight());
    }

    public function testCommentVisible(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-comment="Always visible" data-xls-comment-visible="true">Cell</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $comment = $sheet->getComment('A1');
        $this->assertTrue($comment->getVisible());
    }

    public function testCommentWithAllAttributes(): void
    {
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-comment="Complete comment example"
                    data-xls-comment-author="Alice Smith"
                    data-xls-comment-width="250"
                    data-xls-comment-height="80"
                    data-xls-comment-visible="true">Important data</td></tr>
        </table>';

        $spreadsheet = $this->interpreter->fromHtml($html);
        $sheet = $spreadsheet->getActiveSheet();

        $comment = $sheet->getComment('A1');
        $this->assertEquals('Complete comment example', $comment->getText()->getPlainText());
        $this->assertEquals('Alice Smith', $comment->getAuthor());
        $this->assertEquals('250pt', $comment->getWidth());
        $this->assertEquals('80pt', $comment->getHeight());
        $this->assertTrue($comment->getVisible());
    }

    public function testCommentInvalidWidthThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('data-xls-comment-width doit être un nombre positif');

        $strictValidator = new AttributeValidator(strict: true);
        $styler = new SheetStyler($this->registry);
        $strictInterpreter = new HtmlTableInterpreter($styler, $strictValidator);

        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-comment="Test" data-xls-comment-width="invalid">Cell</td></tr>
        </table>';

        $strictInterpreter->fromHtml($html);
    }

    public function testCommentInvalidVisibleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("data-xls-comment-visible doit être 'true' ou 'false'");

        $strictValidator = new AttributeValidator(strict: true);
        $styler = new SheetStyler($this->registry);
        $strictInterpreter = new HtmlTableInterpreter($styler, $strictValidator);

        $html = '<table data-xls-sheet="Test"><tr><td data-xls-link="not-a-valid-url">Invalid</td></tr></table>';
        $html = '<table data-xls-sheet="Test">
            <tr><td data-xls-comment="Test" data-xls-comment-visible="yes">Cell</td></tr>
        </table>';

        $strictInterpreter->fromHtml($html);
    }
}
