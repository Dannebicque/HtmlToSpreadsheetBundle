<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet;

use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlTableInterpreter;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlToXlsxOptions;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Response\ExcelResponseFactory;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class SpreadsheetRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly HtmlTableInterpreter $interpreter,
        private readonly ExcelResponseFactory $factory
    ) {}

    /**
     * Render a Twig template and convert it to a spreadsheet file in one call.
     *
     * @param string $template Template path (e.g., 'reports/export.html.twig')
     * @param array<string, mixed> $context Template variables
     * @param string $filename Output filename (extension will determine format)
     * @param HtmlToXlsxOptions|null $options Conversion options (default: strict mode enabled)
     * @return Response Spreadsheet file download response
     */
    public function renderFromTemplate(
        string $template,
        array $context = [],
        string $filename = 'export.xlsx',
        ?HtmlToXlsxOptions $options = null
    ): Response {
        // 1) Render the Twig template
        $html = $this->twig->render($template, $context);

        // 2) Convert to spreadsheet
        $options ??= new HtmlToXlsxOptions(strict: true);
        $workbook = $this->interpreter->fromHtml($html, $options);

        // 3) Stream as file download
        return $this->factory->streamWorkbook($workbook, $filename);
    }

    /**
     * Render a Twig template and get the spreadsheet object (without streaming).
     *
     * @param string $template Template path
     * @param array<string, mixed> $context Template variables
     * @param HtmlToXlsxOptions|null $options Conversion options
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public function createFromTemplate(
        string $template,
        array $context = [],
        ?HtmlToXlsxOptions $options = null
    ): \PhpOffice\PhpSpreadsheet\Spreadsheet {
        $html = $this->twig->render($template, $context);
        $options ??= new HtmlToXlsxOptions(strict: true);
        return $this->interpreter->fromHtml($html, $options);
    }
}
