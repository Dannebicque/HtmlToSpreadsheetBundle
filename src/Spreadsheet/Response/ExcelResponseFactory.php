<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Response;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExcelResponseFactory
{
    /**
     * Génère une réponse HTTP streamée contenant le fichier Excel.
     *
     * @param Spreadsheet $wb Le classeur à exporter
     * @param string $filename Le nom du fichier (sans extension)
     * @param string $format Le format de sortie : 'xlsx' (défaut), 'csv' ou 'ods'
     * @return StreamedResponse
     */
    public function streamWorkbook(Spreadsheet $wb, string $filename, string $format = 'xlsx'): StreamedResponse
    {
        $format = strtolower($format);

        // Vérifier le format demandé
        if (!in_array($format, ['xlsx', 'csv', 'ods'], true)) {
            throw new \InvalidArgumentException("Format non supporté: $format. Utilisez 'xlsx', 'csv' ou 'ods'.");
        }

        // Déterminer le writer et les headers en fonction du format
        [$writer, $contentType, $extension] = match($format) {
            'csv' => [
                new Csv($wb),
                'text/csv',
                '.csv'
            ],
            'ods' => [
                new Ods($wb),
                'application/vnd.oasis.opendocument.spreadsheet',
                '.ods'
            ],
            default => [
                new Xlsx($wb),
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                '.xlsx'
            ],
        };

        // Configuration du writer
        if ($writer instanceof Xlsx) {
            $writer->setPreCalculateFormulas(false); // performance
        } elseif ($writer instanceof Csv) {
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setSheetIndex(0); // CSV n'exporte qu'une seule feuille
        }

        // Ajouter l'extension au nom de fichier si nécessaire
        if (!str_ends_with($filename, $extension)) {
            $filename .= $extension;
        }

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }
}
