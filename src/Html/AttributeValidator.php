<?php
// src/Html/AttributeValidator.php
namespace Davidannebicque\HtmlToSpreadsheetBundle\Html;

final class AttributeValidator
{
    /** @var array<string,true> */
    private array $allowed;

    public function __construct(private readonly bool $strict = true)
    {
        // liste fermée d’attributs autorisés
        $allowed = [
            // table-level
            'data-xls-sheet'=>true,'data-xls-freeze'=>true,'data-xls-autosize'=>true,'data-xls-autofilter'=>true,
            'data-xls-default-col-width'=>true,'data-xls-zoom'=>true,'data-xls-print-orientation'=>true,
            'data-xls-print-fit'=>true,'data-xls-page-margins'=>true,'data-xls-tab-color'=>true,'data-xls-gridlines'=>true,
            // col
            'data-xls-width'=>true,'data-xls-hidden'=>true,'data-xls-apply'=>true,
            // tr
            'data-xls-height'=>true,
            // td/th
            'data-xls-format'=>true,'data-xls-align'=>true,'data-xls-valign'=>true,'data-xls-wrap'=>true,
            'data-xls-colspan'=>true,'data-xls-rowspan'=>true,'data-xls-formula'=>true,'data-xls-hyperlink'=>true,
            'data-xls-comment'=>true,'data-xls-dv-list'=>true,'data-xls-type'=>true,'data-xls-number-locale'=>true,
            'data-xls-image'=>true,'data-xls-img-width'=>true,'data-xls-img-height'=>true,
            // Priority 1 attributes
            'data-xls-bg-color'=>true,'data-xls-font-size'=>true,'data-xls-border'=>true,
            'data-xls-border-color'=>true,'data-xls-locked'=>true,
            // Priority 2 attributes - Font styling
            'data-xls-font-color'=>true,'data-xls-font-bold'=>true,'data-xls-font-italic'=>true,
            'data-xls-font-underline'=>true,'data-xls-font-name'=>true,
            // Priority 2 attributes - Conditional formatting
            'data-xls-conditional'=>true,
        ];
        $this->allowed = $allowed;
    }

    public function assertAllowed(string $attr, string $value): void
    {
        if (!$this->strict) return;
        if (!isset($this->allowed[$attr])) {
            throw new \InvalidArgumentException("Attribut non autorisé: $attr");
        }
        // validations minimales par attribut (exemples)
        if ($attr === 'data-xls-sheet' && $value === '') {
            throw new \InvalidArgumentException("data-xls-sheet ne peut pas être vide.");
        }
        if ($attr === 'data-xls-tab-color' && !preg_match('/^#?[0-9A-Fa-f]{6}$/', $value)) {
            throw new \InvalidArgumentException("Couleur d'onglet invalide: $value");
        }
        if ($attr === 'data-xls-print-orientation' && !in_array($value, ['portrait','landscape'], true)) {
            throw new \InvalidArgumentException("Orientation invalide: $value");
        }
        if ($attr === 'data-xls-gridlines' && !in_array($value, ['on','off'], true)) {
            throw new \InvalidArgumentException("Gridlines doit être 'on' ou 'off'.");
        }
        if (in_array($attr, ['data-xls-colspan','data-xls-rowspan','data-xls-default-col-width','data-xls-zoom','data-xls-width','data-xls-img-width','data-xls-img-height'], true)) {
            if (!ctype_digit((string)$value) || (int)$value < 1) {
                throw new \InvalidArgumentException("$attr doit être un entier positif.");
            }
        }
        if ($attr === 'data-xls-page-margins') {
            $parts = array_map('trim', explode(',', $value));
            if (count($parts) < 1 || count($parts) > 6) {
                throw new \InvalidArgumentException("data-xls-page-margins attend 1 à 6 nombres (top,right,bottom,left,header,footer).");
            }
            foreach ($parts as $p) {
                if (!is_numeric($p)) throw new \InvalidArgumentException("Marge invalide: $p");
            }
        }
        // New attributes validation
        if (in_array($attr, ['data-xls-bg-color', 'data-xls-border-color'], true)) {
            if (!preg_match('/^#?[0-9A-Fa-f]{6}$/', $value)) {
                throw new \InvalidArgumentException("Couleur invalide pour $attr: $value (attendu: format hex #RRGGBB)");
            }
        }
        if ($attr === 'data-xls-font-size' && (!is_numeric($value) || (float)$value <= 0)) {
            throw new \InvalidArgumentException("data-xls-font-size doit être un nombre positif.");
        }
        if ($attr === 'data-xls-border' && !in_array($value, ['thin', 'medium', 'thick', 'none'], true)) {
            throw new \InvalidArgumentException("data-xls-border doit être 'thin', 'medium', 'thick' ou 'none'.");
        }
        if ($attr === 'data-xls-locked' && !in_array($value, ['true', 'false'], true)) {
            throw new \InvalidArgumentException("data-xls-locked doit être 'true' ou 'false'.");
        }
        // Priority 2 attributes validation
        if ($attr === 'data-xls-font-color' && !preg_match('/^#?[0-9A-Fa-f]{6}$/', $value)) {
            throw new \InvalidArgumentException("Couleur de police invalide pour $attr: $value (attendu: format hex #RRGGBB)");
        }
        if (in_array($attr, ['data-xls-font-bold', 'data-xls-font-italic'], true) && !in_array($value, ['true', 'false'], true)) {
            throw new \InvalidArgumentException("$attr doit être 'true' ou 'false'.");
        }
        if ($attr === 'data-xls-font-underline' && !in_array($value, ['single', 'double', 'none'], true)) {
            throw new \InvalidArgumentException("data-xls-font-underline doit être 'single', 'double' ou 'none'.");
        }
        // Priority 3 attributes validation - Autosize
        if ($attr === 'data-xls-autosize') {
            // Validate autosize values: "true", "A", "A:D", "A,C,E"
            $isTrue = $value === 'true';
            $isSingleColumn = preg_match('/^[A-Z]+$/', $value);
            $isRange = preg_match('/^[A-Z]+:[A-Z]+$/', $value);
            $isList = preg_match('/^[A-Z]+(,[A-Z]+)*$/', $value);

            if (!$isTrue && !$isSingleColumn && !$isRange && !$isList) {
                throw new \InvalidArgumentException("data-xls-autosize doit être 'true', une colonne (A), une plage (A:D) ou une liste (A,C,E).");
            }
        }
    }
}
