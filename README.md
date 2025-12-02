# HtmlToSpreadsheetBundle

[![Latest Version](https://img.shields.io/packagist/v/davidannebicque/html-to-spreadsheet-bundle.svg)](https://packagist.org/packages/davidannebicque/html-to-spreadsheet-bundle)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Symfony](https://img.shields.io/badge/Symfony-7.x/8.x-black.svg)](https://symfony.com)
[![PhpSpreadsheet](https://img.shields.io/badge/PhpSpreadsheet-4.x/5.x-yellow.svg)](https://github.com/PHPOffice/PhpSpreadsheet)

## Warning

First draft of a Symfony bundle to convert HTML tables into Excel (.xlsx), OpenDocument (.ods) or CSV. WIP. 

---

Convert **annotated HTML tables** into **Excel (.xlsx), OpenDocument (.ods)** or **CSV** using a simple, declarative syntax.

This bundle is ideal for developers who want to use **Twig + HTML** as a DSL to build spreadsheets, without directly using the PhpSpreadsheet API.

---

## Requirements

- PHP 8.2+
- Symfony 7.3+
- PhpSpreadsheet 4.x|5.x (maybe 3  also compatible, not tested)

## ✨ Features

- ✔️ Write spreadsheets using **HTML tables** and `data-xls-*` attributes
- ✔️ Auto-generation of **Excel sheets**, **styles**, **formulas**, **freezepanes**, **column widths**, **images**, etc.
- ✔️ Built-in **French-oriented presets** (`money`, `date`, `float2`, `percent2`, etc.)
- ✔️ Generate **XLSX**, **ODS**, **CSV** with multi-format export support
- ✔️ **Cell styling** (background colors, font sizes, borders, protection)
- ✔️ **Image support** (local files, data-URI, remote HTTP/HTTPS URLs)
- ✔️ Multiple sheets from multiple `<table data-xls-sheet="...">` tags
- ✔️ Strict mode (invalid attributes → exception)
- ✔️ Fully extensible (register custom styles, validators, etc.)

---

## 🎨 Cell Styling Attributes

### Background & Font
- `data-xls-bg-color="#FF0000"` - Set cell background color (hex format)
- `data-xls-font-size="14"` - Set font size (numeric value)

### Borders
- `data-xls-border="thin"` - Set border style (`thin`, `medium`, `thick`, `none`)
- `data-xls-border-color="#000000"` - Set border color (hex format)

### Cell Protection
- `data-xls-locked="true"` - Lock cell (requires sheet protection)
- `data-xls-locked="false"` - Unlock cell for editing

### Example
```html
<td data-xls-bg-color="#E7E6E6"
    data-xls-font-size="16"
    data-xls-border="medium"
    data-xls-border-color="FF0000"
    data-xls-locked="true">
    Protected Cell
</td>
```

## ✍️ Font Styling

Control font appearance with these attributes:

### Font Color & Weight
- `data-xls-font-color="#FF0000"` - Set font color (hex format)
- `data-xls-font-bold="true"` - Make text bold (`true` or `false`)
- `data-xls-font-italic="true"` - Make text italic (`true` or `false`)

### Font Decoration & Family
- `data-xls-font-underline="single"` - Underline text (`single`, `double`, or `none`)
- `data-xls-font-name="Arial"` - Set font family (e.g., "Arial", "Times New Roman", "Calibri")

### Example
```html
<td data-xls-font-color="#0000FF"
    data-xls-font-bold="true"
    data-xls-font-italic="true"
    data-xls-font-underline="single"
    data-xls-font-name="Arial">
    Styled Text
</td>
```

## 🎯 Conditional Formatting

Apply dynamic formatting based on cell values using a simple syntax:

**Syntax:** `data-xls-conditional="condition|style1|style2..."`

### Available Conditions
- `value>X` - Greater than
- `value<X` - Less than
- `value>=X` - Greater than or equal
- `value<=X` - Less than or equal
- `value==X` - Equal to
- `value!=X` - Not equal to
- `between:min:max` - Value between min and max

### Available Styles
- `bg:RRGGBB` - Background color (hex without #)
- `font:RRGGBB` - Font color (hex without #)
- `bold` - Bold text

### Examples
```html
<!-- Highlight negative values in red -->
<td data-xls-type="number"
    data-xls-conditional="value<0|bg:FFCCCC|font:FF0000">
    -150.50
</td>

<!-- Highlight high values with bold green -->
<td data-xls-type="number"
    data-xls-conditional="value>1000|bg:CCFFCC|bold">
    1500.00
</td>

<!-- Highlight values in a range -->
<td data-xls-type="number"
    data-xls-conditional="between:100:500|bg:FFFFCC">
    250.00
</td>
```

## 📊 Multi-Format Export

Export your spreadsheets in multiple formats:

```php
// XLSX (default)
return $factory->streamWorkbook($workbook, 'export.xlsx');
// or explicitly
return $factory->streamWorkbook($workbook, 'export', 'xlsx');

// CSV (exports first sheet only)
return $factory->streamWorkbook($workbook, 'export', 'csv');

// ODS (OpenDocument - LibreOffice compatible)
return $factory->streamWorkbook($workbook, 'export', 'ods');
```

The file extension is automatically added if not present.

## 🖼️ Image Support

Insert images in cells using various sources:

```html
<!-- Local file -->
<td data-xls-image="/path/to/image.png"
    data-xls-img-width="100"
    data-xls-img-height="50">
</td>

<!-- Remote URL (HTTP/HTTPS) -->
<td data-xls-image="https://example.com/logo.png">
</td>

<!-- Base64 Data-URI -->
<td data-xls-image="data:image/png;base64,iVBORw0KGgo...">
</td>
```

---

## Installation

```bash
composer require davidannebicque/html-to-spreadsheet-bundle
```

## Configuration

Create the file `config/packages/html_to_spreadsheet.yaml` :
```yaml
html_to_spreadsheet:
    strict: true
    include_builtins: true
```

A complete example file with all available styles is provided in:
`vendor/davidannebicque/html-to-spreadsheet-bundle/src/Resources/config/html_to_spreadsheet.yaml.dist`

### Styles prédéfinis disponibles

- `th` : En-tête de tableau (gras, centré)
- `money` : Format monétaire Euro
- `date` : Format date courte
- `int` : Nombre entier
- `percent2` : Pourcentage à 2 décimales
- ... (voir la documentation complète)

### Créer des styles personnalisés
```yaml
html_to_spreadsheet:
    default_styles:
        mon_style:
            font:
                bold: true
                color: 'FF0000'
            alignment:
                horizontal: 'center'
```


## Example

### Contrôleur

```php
<?php

namespace App\Controller;

use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlTableInterpreter;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Response\ExcelResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlToXlsxOptions;
use Twig\Environment;

final class DemoController extends AbstractController
{
    #[Route('/demo', name: 'app_demo')]
     public function export(
        Environment $twig,
        HtmlTableInterpreter $interpreter,
        ExcelResponseFactory $factory
    ): Response {
        // Données d’exemple
        $lines = [
            ['nom' => 'Doe',   'prenom' => 'John',  'montant' => 1234.56, 'date' => new \DateTime('2024-02-01')],
            ['nom' => 'Smith', 'prenom' => 'Anna',  'montant' => 987.45,  'date' => new \DateTime('2024-02-02')],
            ['nom' => 'Lemaire','prenom' => 'Lucie','montant' => 150.00,  'date' => new \DateTime('2024-02-03')],
        ];

        // 1) Rend la vue HTML annotée
        $html = $twig->render('demo/index.html.twig', [
            'lignes' => $lines,
        ]);

        // 2) Convertit en Spreadsheet
        $workbook = $interpreter->fromHtml(
            $html,
            new HtmlToXlsxOptions(strict: true) // strict = validation des attributes
        );

        // 3) Renvoie le fichier Excel
        return $factory->streamWorkbook($workbook, 'export-test.xlsx');
    }
}
```

### La vue (twig)

```twig
{# templates/test/export.html.twig #}
<div id="workbook">

  {# --- Feuille 1 --- #}
  <table data-xls-sheet="Résumé"
         data-xls-freeze="A2"
         data-xls-autofilter="A1:D1"
         data-xls-default-col-width="20"
         data-xls-gridlines="on">

    <colgroup>
      <col data-xls-width="25">
      <col data-xls-width="20">
      <col data-xls-width="20">
      <col data-xls-width="18" data-xls-apply="money">
    </colgroup>

    <thead>
      <tr data-xls-apply="th">
        <th>Nom</th>
        <th>Prénom</th>
        <th>Date</th>
        <th>Montant (€)</th>
      </tr>
    </thead>

    <tbody>
      {% for l in lignes %}
        <tr>
          <td>{{ l.nom }}</td>
          <td>{{ l.prenom }}</td>

          {# Date formatée #}
          <td data-xls-apply="date" data-xls-type="date">
            {{ l.date|date('Y-m-d') }}
          </td>

          {# Montant format monétaire #}
          <td data-xls-apply="money" data-xls-type="number">
            {{ l.montant }}
          </td>
        </tr>
      {% endfor %}

      {# Total via formule #}
      <tr>
        <td data-xls-colspan="3" data-xls-align="right"><strong>Total :</strong></td>
        <td data-xls-formula="SUM(D2:D{{ 1 + lignes|length }})"
            data-xls-apply="money"></td>
      </tr>
    </tbody>
  </table>


  {# --- Feuille 2 --- #}
  <table data-xls-sheet="Brut"
         data-xls-default-col-width="15">

    <thead>
      <tr data-xls-apply="th">
        <th>#</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Date brute</th>
        <th>Montant brut</th>
      </tr>
    </thead>

    <tbody>
      {% for l in lignes %}
        <tr>
          <td>{{ loop.index }}</td>
          <td>{{ l.nom }}</td>
          <td>{{ l.prenom }}</td>

          <td data-xls-type="date">{{ l.date|date('Y-m-d H:i') }}</td>
          <td data-xls-type="number">{{ l.montant }}</td>
        </tr>
      {% endfor %}
    </tbody>
  </table>
</div>
```
