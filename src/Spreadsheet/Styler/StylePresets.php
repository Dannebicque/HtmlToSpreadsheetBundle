<?php
// src/Excel/Styler/StylePresets.php
namespace Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler;

/**
 * Presets de styles nommés, adaptés au format FR.
 * Chaque preset retourne un array compatible ->applyFromArray()
 * de PhpSpreadsheet (font, alignment, numberFormat, borders, fill, ...).
 */
final class StylePresets
{
    /** En-têtes de tableau */
    public static function header(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['argb' => 'FF333333']],
            'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFEEEEEE']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => ['bottom' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFCCCCCC']]],
        ];
    }

    /** Entier avec séparateur de milliers (espace insécable) */
    public static function int(): array
    {
        return [
            'alignment' => ['horizontal' => 'right'],
            'numberFormat' => ['formatCode' => '# ##0'], // espace insécable (U+00A0) ok aussi avec simple espace
        ];
    }

    /** Décimaux sur 2 */
    public static function float2(): array
    {
        return [
            'alignment' => ['horizontal' => 'right'],
            'numberFormat' => ['formatCode' => '# ##0,00'],
        ];
    }

    /** Décimaux sur 3 */
    public static function float3(): array
    {
        return [
            'alignment' => ['horizontal' => 'right'],
            'numberFormat' => ['formatCode' => '# ##0,000'],
        ];
    }

    /** Pourcentage 2 décimales */
    public static function percent2(): array
    {
        return [
            'alignment' => ['horizontal' => 'right'],
            'numberFormat' => ['formatCode' => '0,00%'],
        ];
    }

    /** Monnaie € (format standard) */
    public static function moneyEuro(): array
    {
        // [$€-fr-FR] force le symbole et les conventions FR
        return [
            'alignment' => ['horizontal' => 'right'],
            'numberFormat' => ['formatCode' => '# ##0,00 [$€-fr-FR]'],
        ];
    }

    /** Monnaie € (comptable, aligne les symboles) */
    public static function moneyEuroAccounting(): array
    {
        return [
            'alignment' => ['horizontal' => 'right'],
            'numberFormat' => ['formatCode' => '_-* # ##0,00 [$€-fr-FR]_-;\\-* # ##0,00 [$€-fr-FR]_-;_-* "-"?? [$€-fr-FR]_-;_-@_-'],
        ];
    }

    /** Date (jj/mm/aaaa) */
    public static function dateShort(): array
    {
        return [
            'alignment' => ['horizontal' => 'center'],
            'numberFormat' => ['formatCode' => 'dd/mm/yyyy'],
        ];
    }

    /** Date longue locale (vendredi 13 novembre 2025) */
    public static function dateLongFr(): array
    {
        // [$-fr-FR] pour localiser le nom du jour/mois
        return [
            'alignment' => ['horizontal' => 'left'],
            'numberFormat' => ['formatCode' => '[$-fr-FR]dddd d mmmm yyyy'],
        ];
    }

    /** Heure (HH:MM) */
    public static function timeShort(): array
    {
        return [
            'alignment' => ['horizontal' => 'center'],
            'numberFormat' => ['formatCode' => 'hh:mm'],
        ];
    }

    /** Date + heure (jj/mm/aaaa HH:MM) */
    public static function dateTime(): array
    {
        return [
            'alignment' => ['horizontal' => 'center'],
            'numberFormat' => ['formatCode' => 'dd/mm/yyyy hh:mm'],
        ];
    }

    /** Durée (cumule au-delà de 24h) */
    public static function duration(): array
    {
        // [h]:mm:ss permet 25h, 50h, etc.
        return [
            'alignment' => ['horizontal' => 'right'],
            'numberFormat' => ['formatCode' => '[h]:mm:ss'],
        ];
    }

    /** Booléen aligné centre */
    public static function boolCentered(): array
    {
        return [
            'alignment' => ['horizontal' => 'center'],
        ];
    }

    /** Texte multi-ligne */
    public static function textWrap(): array
    {
        return [
            'alignment' => ['vertical' => 'top', 'wrapText' => true],
        ];
    }

    /** Erreur / alerte (fond jaune) */
    public static function warning(): array
    {
        return [
            'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFFFFF99']],
        ];
    }
}
