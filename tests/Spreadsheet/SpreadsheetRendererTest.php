<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Tests\Spreadsheet;

use Davidannebicque\HtmlToSpreadsheetBundle\Html\AttributeValidator;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlTableInterpreter;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlToXlsxOptions;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Response\ExcelResponseFactory;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\SpreadsheetRenderer;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\SheetStyler;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\StyleRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class SpreadsheetRendererTest extends TestCase
{
    private SpreadsheetRenderer $renderer;
    private Environment $twig;

    protected function setUp(): void
    {
        // Setup Twig with array loader for testing
        $loader = new ArrayLoader([
            'test.html.twig' => '<table data-xls-sheet="Test"><tr><td>{{ value }}</td></tr></table>',
            'multisheet.html.twig' => '<table data-xls-sheet="Sheet1"><tr><td>{{ data.a }}</td></tr></table>' .
                                      '<table data-xls-sheet="Sheet2"><tr><td>{{ data.b }}</td></tr></table>',
        ]);
        $this->twig = new Environment($loader);

        // Setup dependencies
        $registry = new StyleRegistry([]);
        $styler = new SheetStyler($registry);
        $validator = new AttributeValidator(strict: false);
        $interpreter = new HtmlTableInterpreter($styler, $validator);
        $factory = new ExcelResponseFactory();

        $this->renderer = new SpreadsheetRenderer($this->twig, $interpreter, $factory);
    }

    public function testRenderFromTemplate(): void
    {
        $response = $this->renderer->renderFromTemplate(
            'test.html.twig',
            ['value' => 'Hello World'],
            'test.xlsx'
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
        $this->assertStringContainsString('test.xlsx', $response->headers->get('Content-Disposition') ?? '');
    }

    public function testRenderFromTemplateWithContext(): void
    {
        $workbook = $this->renderer->createFromTemplate(
            'test.html.twig',
            ['value' => 'Custom Value']
        );

        $sheet = $workbook->getActiveSheet();
        $this->assertEquals('Custom Value', $sheet->getCell('A1')->getValue());
    }

    public function testRenderFromTemplateWithMultipleSheets(): void
    {
        $workbook = $this->renderer->createFromTemplate(
            'multisheet.html.twig',
            ['data' => ['a' => 'First', 'b' => 'Second']]
        );

        $this->assertEquals(2, $workbook->getSheetCount());
        $this->assertEquals('Sheet1', $workbook->getSheet(0)->getTitle());
        $this->assertEquals('Sheet2', $workbook->getSheet(1)->getTitle());
        $this->assertEquals('First', $workbook->getSheet(0)->getCell('A1')->getValue());
        $this->assertEquals('Second', $workbook->getSheet(1)->getCell('A1')->getValue());
    }

    public function testRenderFromTemplateWithOdsFormat(): void
    {
        $response = $this->renderer->renderFromTemplate(
            'test.html.twig',
            ['value' => 'Test'],
            'export.ods'
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString('export.ods', $response->headers->get('Content-Disposition') ?? '');
    }

    public function testRenderFromTemplateWithCsvFormat(): void
    {
        $response = $this->renderer->renderFromTemplate(
            'test.html.twig',
            ['value' => 'Test'],
            'export.csv'
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString('export.csv', $response->headers->get('Content-Disposition') ?? '');
    }

    public function testRenderFromTemplateWithCustomOptions(): void
    {
        $options = new HtmlToXlsxOptions(strict: false, numberLocale: 'en-US');
        $workbook = $this->renderer->createFromTemplate(
            'test.html.twig',
            ['value' => '123'],
            $options
        );

        $this->assertNotNull($workbook);
        $this->assertEquals('123', $workbook->getActiveSheet()->getCell('A1')->getValue());
    }

    public function testCreateFromTemplate(): void
    {
        $workbook = $this->renderer->createFromTemplate(
            'test.html.twig',
            ['value' => 'Test Data']
        );

        $this->assertInstanceOf(\PhpOffice\PhpSpreadsheet\Spreadsheet::class, $workbook);
        $this->assertEquals('Test Data', $workbook->getActiveSheet()->getCell('A1')->getValue());
    }

    public function testRenderFromTemplateWithEmptyContext(): void
    {
        $response = $this->renderer->renderFromTemplate('test.html.twig');

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }
}
