<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Controller;

use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlToXlsxOptions;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\SpreadsheetRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Trait to simplify spreadsheet rendering in Symfony controllers.
 *
 * Usage:
 * ```php
 * use Davidannebicque\HtmlToSpreadsheetBundle\Controller\SpreadsheetTrait;
 *
 * class MyController extends AbstractController
 * {
 *     use SpreadsheetTrait;
 *
 *     public function export(): Response
 *     {
 *         return $this->renderSpreadsheet(
 *             'reports/export.html.twig',
 *             ['data' => $myData],
 *             'export.xlsx'
 *         );
 *     }
 * }
 * ```
 */
trait SpreadsheetTrait
{
    private ?SpreadsheetRenderer $spreadsheetRenderer = null;

    #[Required]
    public function setSpreadsheetRenderer(SpreadsheetRenderer $renderer): void
    {
        $this->spreadsheetRenderer = $renderer;
    }

    /**
     * Render a Twig template as a spreadsheet file.
     *
     * @param string $template Template path (e.g., 'reports/export.html.twig')
     * @param array<string, mixed> $context Template variables
     * @param string $filename Output filename with extension (xlsx, ods, or csv)
     * @param HtmlToXlsxOptions|null $options Conversion options
     * @return Response Spreadsheet file download response
     */
    protected function renderSpreadsheet(
        string $template,
        array $context = [],
        string $filename = 'export.xlsx',
        ?HtmlToXlsxOptions $options = null
    ): Response {
        if (!$this->spreadsheetRenderer) {
            throw new \LogicException('SpreadsheetRenderer is not available. Make sure SpreadsheetTrait is used in a service container context.');
        }

        return $this->spreadsheetRenderer->renderFromTemplate($template, $context, $filename, $options);
    }

    /**
     * Create a spreadsheet object from a Twig template (without streaming).
     *
     * @param string $template Template path
     * @param array<string, mixed> $context Template variables
     * @param HtmlToXlsxOptions|null $options Conversion options
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    protected function createSpreadsheet(
        string $template,
        array $context = [],
        ?HtmlToXlsxOptions $options = null
    ): \PhpOffice\PhpSpreadsheet\Spreadsheet {
        if (!$this->spreadsheetRenderer) {
            throw new \LogicException('SpreadsheetRenderer is not available. Make sure SpreadsheetTrait is used in a service container context.');
        }

        return $this->spreadsheetRenderer->createFromTemplate($template, $context, $options);
    }
}
