<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Tests\Spreadsheet\Response;

use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Response\ExcelResponseFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelResponseFactoryTest extends TestCase
{
    private ExcelResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ExcelResponseFactory();
    }

    public function testStreamWorkbookReturnsStreamedResponse(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Test');

        $response = $this->factory->streamWorkbook($spreadsheet, 'test.xlsx');

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testResponseHasCorrectStatusCode(): void
    {
        $spreadsheet = new Spreadsheet();
        $response = $this->factory->streamWorkbook($spreadsheet, 'test.xlsx');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testResponseHasCorrectContentType(): void
    {
        $spreadsheet = new Spreadsheet();
        $response = $this->factory->streamWorkbook($spreadsheet, 'test.xlsx');

        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
    }

    public function testResponseHasContentDispositionHeader(): void
    {
        $spreadsheet = new Spreadsheet();
        $response = $this->factory->streamWorkbook($spreadsheet, 'myfile.xlsx');

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('myfile.xlsx', $contentDisposition);
    }

    public function testResponseEscapesFilenameWithSlashes(): void
    {
        $spreadsheet = new Spreadsheet();
        $response = $this->factory->streamWorkbook($spreadsheet, 'file"with"quotes.xlsx');

        $contentDisposition = $response->headers->get('Content-Disposition');
        // addslashes should escape the quotes
        $this->assertStringContainsString('file\\"with\\"quotes.xlsx', $contentDisposition);
    }

    public function testResponseHasCacheControlHeaders(): void
    {
        $spreadsheet = new Spreadsheet();
        $response = $this->factory->streamWorkbook($spreadsheet, 'test.xlsx');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    public function testResponseHasPragmaHeader(): void
    {
        $spreadsheet = new Spreadsheet();
        $response = $this->factory->streamWorkbook($spreadsheet, 'test.xlsx');

        $this->assertEquals('public', $response->headers->get('Pragma'));
    }

    public function testCallbackGeneratesValidExcelFile(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Hello');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'World');

        $response = $this->factory->streamWorkbook($spreadsheet, 'test.xlsx');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Check that content is not empty
        $this->assertNotEmpty($content);

        // Check for Excel file signature (PK for ZIP format)
        $this->assertStringStartsWith('PK', $content);
    }

    public function testDifferentFilenames(): void
    {
        $spreadsheet = new Spreadsheet();

        $filenames = ['test.xlsx', 'export-2024.xlsx', 'données.xlsx'];

        foreach ($filenames as $filename) {
            $response = $this->factory->streamWorkbook($spreadsheet, $filename);
            $contentDisposition = $response->headers->get('Content-Disposition');
            $this->assertStringContainsString($filename, $contentDisposition);
        }
    }

    public function testCsvFormat(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Test');

        $response = $this->factory->streamWorkbook($spreadsheet, 'test', 'csv');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('test.csv', $response->headers->get('Content-Disposition'));
    }

    public function testOdsFormat(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Test');

        $response = $this->factory->streamWorkbook($spreadsheet, 'test', 'ods');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('application/vnd.oasis.opendocument.spreadsheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('test.ods', $response->headers->get('Content-Disposition'));
    }

    public function testXlsxFormatExplicit(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Test');

        $response = $this->factory->streamWorkbook($spreadsheet, 'test', 'xlsx');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('test.xlsx', $response->headers->get('Content-Disposition'));
    }

    public function testFormatIsCaseInsensitive(): void
    {
        $spreadsheet = new Spreadsheet();

        $response = $this->factory->streamWorkbook($spreadsheet, 'test', 'CSV');
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));

        $response = $this->factory->streamWorkbook($spreadsheet, 'test', 'ODS');
        $this->assertEquals('application/vnd.oasis.opendocument.spreadsheet', $response->headers->get('Content-Type'));
    }

    public function testInvalidFormatThrowsException(): void
    {
        $spreadsheet = new Spreadsheet();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Format non supporté: pdf');

        $this->factory->streamWorkbook($spreadsheet, 'test', 'pdf');
    }

    public function testExtensionIsAutoAppended(): void
    {
        $spreadsheet = new Spreadsheet();

        $response = $this->factory->streamWorkbook($spreadsheet, 'myfile', 'xlsx');
        $this->assertStringContainsString('myfile.xlsx', $response->headers->get('Content-Disposition'));

        $response = $this->factory->streamWorkbook($spreadsheet, 'myfile', 'csv');
        $this->assertStringContainsString('myfile.csv', $response->headers->get('Content-Disposition'));

        $response = $this->factory->streamWorkbook($spreadsheet, 'myfile', 'ods');
        $this->assertStringContainsString('myfile.ods', $response->headers->get('Content-Disposition'));
    }

    public function testExtensionNotDuplicatedIfAlreadyPresent(): void
    {
        $spreadsheet = new Spreadsheet();

        $response = $this->factory->streamWorkbook($spreadsheet, 'myfile.xlsx', 'xlsx');
        $contentDisposition = $response->headers->get('Content-Disposition');
        // Should not become myfile.xlsx.xlsx
        $this->assertStringContainsString('myfile.xlsx', $contentDisposition);
        $this->assertStringNotContainsString('myfile.xlsx.xlsx', $contentDisposition);
    }

    public function testCsvContentGeneration(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Hello');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'World');

        $response = $this->factory->streamWorkbook($spreadsheet, 'test', 'csv');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Check that content is not empty and contains CSV data
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Hello', $content);
        $this->assertStringContainsString('World', $content);
    }
}
