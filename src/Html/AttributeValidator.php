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
        // etc. Ajoute au besoin d'autres contraintes.
    }
}
