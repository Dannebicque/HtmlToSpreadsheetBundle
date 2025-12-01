<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Response;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExcelResponseFactory
{
    public function streamWorkbook(Spreadsheet $wb, string $filename): StreamedResponse
    {
        return new StreamedResponse(function () use ($wb) {
            $writer = new Xlsx($wb);
            $writer->setPreCalculateFormulas(false); // performance
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }
}
