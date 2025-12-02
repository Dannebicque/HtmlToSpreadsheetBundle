<?php
// src/Excel/Styler/SheetStyler.php
namespace Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

final class SheetStyler
{
    public function __construct(private readonly StyleRegistry $registry) {}

    public function applyNamedStyle(Worksheet $sheet, string $coord, string $name): void
    {
        $arr = $this->registry->get($name);
        $sheet->getStyle($coord)->applyFromArray($arr);
    }

    public function applyNamedStyleToColumn(Worksheet $sheet, int $colIndex, string $name): void
    {
        $arr = $this->registry->get($name);
        $letter = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->getStyle($letter.':'.$letter)->applyFromArray($arr);
    }

    public function applyNumberFormat(Worksheet $sheet, string $coord, string $format): void
    {
        $sheet->getStyle($coord)->getNumberFormat()->setFormatCode($format);
    }

    public function applyAlign(Worksheet $sheet, string $coord, string $h): void
    {
        $map = [
            'left' => Alignment::HORIZONTAL_LEFT,
            'center' => Alignment::HORIZONTAL_CENTER,
            'right' => Alignment::HORIZONTAL_RIGHT,
            'justify' => Alignment::HORIZONTAL_JUSTIFY,
        ];
        $sheet->getStyle($coord)->getAlignment()->setHorizontal($map[$h] ?? Alignment::HORIZONTAL_GENERAL);
    }

    public function applyVAlign(Worksheet $sheet, string $coord, string $v): void
    {
        $map = [
            'top' => Alignment::VERTICAL_TOP,
            'middle' => Alignment::VERTICAL_CENTER,
            'bottom' => Alignment::VERTICAL_BOTTOM,
        ];
        $sheet->getStyle($coord)->getAlignment()->setVertical($map[$v] ?? Alignment::VERTICAL_BOTTOM);
    }

    public function applyWrap(Worksheet $sheet, string $coord, bool $wrap): void
    {
        $sheet->getStyle($coord)->getAlignment()->setWrapText($wrap);
    }

    /** Validation liste simple (inline) */
    public function applyListValidation(Worksheet $sheet, string $coord, array $items): void
    {
        $dv = $sheet->getCell($coord)->getDataValidation();
        $dv->setType(DataValidation::TYPE_LIST);
        $dv->setErrorStyle(DataValidation::STYLE_STOP);
        $dv->setAllowBlank(true);
        $dv->setShowDropDown(true);
        $escaped = array_map(fn($s)=>str_replace('"','""',$s), $items);
        $dv->setFormula1('"'.implode(',', $escaped).'"');
    }
}
