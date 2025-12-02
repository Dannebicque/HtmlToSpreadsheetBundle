<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\Html;

use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\SheetStyler;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class HtmlTableInterpreter
{
    public function __construct(
        private readonly SheetStyler $styler,
        private readonly AttributeValidator $validator,
    ) {}

    public function fromHtml(string $html, HtmlToXlsxOptions $options = new HtmlToXlsxOptions()): Spreadsheet
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        if (!mb_detect_encoding($html, 'UTF-8', true)) {
            $html = mb_convert_encoding($html, 'UTF-8');
        }

        // 2) On “annonce” clairement l'UTF-8 au parser HTML
        $htmlWithMeta = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>'
            .$html.
            '</body></html>';

        @$dom->loadHTML($htmlWithMeta, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);

        $xp = new \DOMXPath($dom);

        $tables = $xp->query('//table[@data-xls-sheet]');
        if (!$tables || $tables->length === 0) {
            throw new \InvalidArgumentException('Aucun <table data-xls-sheet> trouvé.');
        }

        $wb = new Spreadsheet();
        // Supprimer la feuille par défaut
        $wb->removeSheetByIndex(0);

        foreach ($tables as $idx => $tbl) {

            $sheetName = $tbl->getAttribute('data-xls-sheet');
            $sheetName = $this->safeTabName($sheetName);
            $sheet = new Worksheet($wb, $sheetName);
            $wb->addSheet($sheet, $idx);

            // table-level
            $this->applyTableLevel($sheet, $tbl);

            // <colgroup>/<col>
            $this->applyColgroup($sheet, $xp, $tbl);

            // rows & cells
            $rowIndex = 1;
            foreach ($xp->query('./thead/tr|./tbody/tr|./tfoot/tr|./tr', $tbl) as $tr) {
                $this->applyRow($sheet, $tr, $rowIndex, $options);
                $rowIndex++;
            }

            // autosize/autofilter gérés après remplissage (si déclarés)
            $this->postProcessTable($sheet, $tbl);
        }

        return $wb;
    }

    private function applyTableLevel(Worksheet $sheet, \DOMElement $tbl): void
    {
        $map = [
            'data-xls-freeze'       => fn($v) => $sheet->freezePane($v),
            'data-xls-autofilter'   => fn($v) => $sheet->setAutoFilter($v),
            'data-xls-default-col-width' => fn($v) => $sheet->getDefaultColumnDimension()->setWidth((float)$v),
            'data-xls-zoom'         => fn($v) => $sheet->getSheetView()->setZoomScale((int)$v),
            'data-xls-tab-color'    => fn($v) => $sheet->getTabColor()->setRGB(ltrim($v, '#')),
            'data-xls-gridlines'    => fn($v) => $sheet->setShowGridLines($v === 'on'),
            'data-xls-print-orientation' => function ($v) use ($sheet) {
                $o = $sheet->getPageSetup();
                $o->setOrientation($v === 'landscape'
                    ? PageSetup::ORIENTATION_LANDSCAPE
                    : PageSetup::ORIENTATION_PORTRAIT);
            },
            'data-xls-print-fit' => function ($v) use ($sheet) {
                $ps = $sheet->getPageSetup();
                if ($v === 'width')  $ps->setFitToWidth(1) && $ps->setFitToHeight(0);
                if ($v === 'height') $ps->setFitToWidth(0) && $ps->setFitToHeight(1);
                if ($v === 'page')   $ps->setFitToPage(true);
            },
            'data-xls-page-margins' => function ($v) use ($sheet) {
                $m = array_map('floatval', explode(',', $v));
                [$top,$right,$bottom,$left,$header,$footer] = array_pad($m, 6, 0.5);
                $pm = $sheet->getPageMargins();
                $pm->setTop($top); $pm->setRight($right); $pm->setBottom($bottom);
                $pm->setLeft($left); $pm->setHeader($header); $pm->setFooter($footer);
            },
        ];
        foreach ($map as $attr => $apply) {
            if ($tbl->hasAttribute($attr)) {
                $this->validator->assertAllowed($attr, $tbl->getAttribute($attr));
                $apply($tbl->getAttribute($attr));
            }
        }
    }

    private function applyColgroup(Worksheet $sheet, \DOMXPath $xp, \DOMElement $tbl): void
    {
        $cols = $xp->query('./colgroup/col', $tbl);
        if (!$cols || $cols->length === 0) return;

        foreach ($cols as $i => $col) {
            $colIndex = $i + 1;
            $dim = $sheet->getColumnDimensionByColumn($colIndex);
            if ($w = $col->getAttribute('data-xls-width'))  $dim->setWidth((float)$w);
            if ($col->getAttribute('data-xls-hidden') === 'true') $dim->setVisible(false);
            if ($style = $col->getAttribute('data-xls-apply')) {
                $this->styler->applyNamedStyleToColumn($sheet, $colIndex, $style);
            }
        }
    }

    private function applyRow(Worksheet $sheet, \DOMElement $tr, int &$rowIndex, HtmlToXlsxOptions $opt): void
    {
        $rowDim = $sheet->getRowDimension($rowIndex);
        if ($h = $tr->getAttribute('data-xls-height')) $rowDim->setRowHeight((float)$h);

        $applyRowStyle = $tr->getAttribute('data-xls-apply') ?: null;

        $colIndex = 1;
        foreach ($tr->childNodes as $cellNode) {
            if (!$cellNode instanceof \DOMElement) continue;
            if (!in_array($cellNode->tagName, ['td','th'], true)) continue;

            // Fusion
            $colspan = max(1, (int)($cellNode->getAttribute('data-xls-colspan') ?: 1));
            $rowspan = max(1, (int)($cellNode->getAttribute('data-xls-rowspan') ?: 1));
            $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex).$rowIndex;

            // Valeur / type / format
            $value = trim($cellNode->textContent);
            $forcedType = $cellNode->getAttribute('data-xls-type') ?: null;
            $numberLocale = $cellNode->getAttribute('data-xls-number-locale') ?: $opt->numberLocale;

            $formula = $cellNode->getAttribute('data-xls-formula');
            $img = $cellNode->getAttribute('data-xls-image');

            if ($formula !== '' && $cellNode->hasAttribute('data-xls-formula')) {
                if ($formula[0] !== '=') $formula = "=".$formula;
                $sheet->setCellValue($coord, $formula);
            } elseif ($img !== '' && $cellNode->hasAttribute('data-xls-image')) {
                $this->insertImage($sheet, $coord, $img,
                    (int)($cellNode->getAttribute('data-xls-img-width') ?: 0),
                    (int)($cellNode->getAttribute('data-xls-img-height') ?: 0)
                );
            } else {
                $typed = $this->castValue($value, $forcedType, $numberLocale);
                // Si le type est forcé à 'string', utiliser setCellValueExplicit pour éviter la conversion automatique
                if ($forcedType === 'string') {
                    $sheet->setCellValueExplicit($coord, $typed, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($coord, $typed);
                }
            }

            // Style
            if ($applyRowStyle) $this->styler->applyNamedStyle($sheet, $coord, $applyRowStyle);
            if ($apply = $cellNode->getAttribute('data-xls-apply')) $this->styler->applyNamedStyle($sheet, $coord, $apply);

            // Format/alignements
            if ($fmt = $cellNode->getAttribute('data-xls-format')) $this->styler->applyNumberFormat($sheet, $coord, $fmt);
            if ($al  = $cellNode->getAttribute('data-xls-align')) $this->styler->applyAlign($sheet, $coord, $al);
            if ($val = $cellNode->getAttribute('data-xls-valign')) $this->styler->applyVAlign($sheet, $coord, $val);
            if ($wrap = $cellNode->getAttribute('data-xls-wrap')) $this->styler->applyWrap($sheet, $coord, $wrap === 'true');

            // Hyperlien / commentaire / validation liste
            if ($hl = $cellNode->getAttribute('data-xls-hyperlink')) $sheet->getCell($coord)->getHyperlink()->setUrl($hl);
            if ($cm = $cellNode->getAttribute('data-xls-comment')) $sheet->getComment($coord)->getText()->createTextRun($cm);
            if ($dv = $cellNode->getAttribute('data-xls-dv-list')) {
                $this->styler->applyListValidation($sheet, $coord, explode('|', $dv));
            }

            // Fusionner si besoin
            if ($colspan > 1 || $rowspan > 1) {
                $endCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + $colspan - 1);
                $endRow = $rowIndex + $rowspan - 1;
                $sheet->mergeCells("$coord:$endCol$endRow");
            }

            $colIndex += $colspan;
        }
    }

    private function insertImage(Worksheet $sheet, string $coord, string $src, int $w, int $h): void
    {
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setCoordinates($coord);
        $drawing->setPath($this->resolveImagePath($src)); // copy/stream if data-uri
        if ($w>0) $drawing->setWidth($w);
        if ($h>0) $drawing->setHeight($h);
        $drawing->setWorksheet($sheet);
    }

    private function castValue(string $raw, ?string $forcedType, ?string $locale): mixed
    {
        if ($raw === '' || $forcedType === 'null') return null;
        switch ($forcedType) {
            case 'string': return $raw;
            case 'number': return $this->parseNumber($raw, $locale);
            case 'bool':   return in_array(strtolower($raw), ['1','true','vrai','yes','oui'], true);
            case 'date':   return \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime($raw));
        }
        // heuristique simple
        if (is_numeric(str_replace([' ', ' '], '', $raw))) return $this->parseNumber($raw, $locale);
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) return \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime($raw));
        return $raw;
    }

    private function parseNumber(string $v, ?string $locale): float
    {
        // FR: "1 234,56" | EN: "1,234.56"
        $v = trim($v);
        if ($locale === 'fr-FR') {
            $v = str_replace(array(' ', ' ', ','), array('', '', '.'), $v);
            return (float)$v;
        }
        // défaut EN-like
        $v = str_replace(',', '', $v);
        return (float)$v;
    }

    private function postProcessTable(Worksheet $sheet, \DOMElement $tbl): void
    {
        if ($tbl->hasAttribute('data-xls-autosize')) {
            foreach (explode(':', $tbl->getAttribute('data-xls-autosize')) as $range) {
                [$start, $end] = explode('A', str_replace([':', ' '], [' A', ''], $range)) + [1 => null];
                // simplifié: autosize par lettres A..D
                foreach (range($range[0], $range[-1]) as $colLetter) {
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }
            }
        }
    }

    /**
     * Résout le chemin de l'image. Supporte les chemins de fichiers locaux.
     * Pour les data-URI, on pourrait les gérer en créant un fichier temporaire.
     */
    private function resolveImagePath(string $src): string
    {
        // Si c'est un chemin absolu qui existe, on le retourne directement
        if (file_exists($src)) {
            return $src;
        }

        // Si c'est une data-URI (data:image/png;base64,...)
        if (str_starts_with($src, 'data:')) {
            // Extraire le type et les données
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $src, $matches)) {
                $extension = $matches[1];
                $data = base64_decode($matches[2]);

                // Créer un fichier temporaire
                $tmpFile = tempnam(sys_get_temp_dir(), 'xls_img_') . '.' . $extension;
                file_put_contents($tmpFile, $data);

                return $tmpFile;
            }
        }

        // Si le chemin n'existe pas, on lance une exception
        throw new \InvalidArgumentException("Image introuvable: $src");
    }

    /**
     * @param $sheetName
     * @return string
     */
    private function safeTabName($sheetName): string
    {
        $sheetName = html_entity_decode($sheetName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (extension_loaded('intl') && class_exists(\Normalizer::class)) {
            $sheetName = \Normalizer::normalize($sheetName, \Normalizer::FORM_C);
        }
        $sheetName = str_replace([':', '\\', '/', '?', '*', '[', ']'], '', $sheetName);
        $sheetName = trim($sheetName) ?: 'Sheet';
        $sheetName = mb_substr($sheetName, 0, 31, 'UTF-8');
        return $sheetName;
    }
}
