# HtmlToSpreadsheetBundle

[![Latest Version](https://img.shields.io/packagist/v/davidannebicque/html-to-spreadsheet-bundle.svg)](https://packagist.org/packages/davidannebicque/html-to-spreadsheet-bundle)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Symfony](https://img.shields.io/badge/Symfony-6.4/7.x/8.x-black.svg)](https://symfony.com)
[![PhpSpreadsheet](https://img.shields.io/badge/PhpSpreadsheet-4.x/5.x-yellow.svg)](https://github.com/PHPOffice/PhpSpreadsheet)

## Warning

First draft of a Symfony bundle to convert HTML tables into Excel (.xlsx), OpenDocument (.ods) or CSV. WIP. 

---

Convert **annotated HTML tables** into **Excel (.xlsx), OpenDocument (.ods)** or **CSV** using a simple, declarative syntax.

This bundle is ideal for developers who want to use **Twig + HTML** as a DSL to build spreadsheets, without directly using the PhpSpreadsheet API.

---

## Requirements

- PHP 8.2+
- Symfony 6.4+
- PhpSpreadsheet 4.x|5.x (maybe 3  also compatible, not tested)

## Features

- Write spreadsheets using **HTML tables** and `data-xls-*` attributes
- **One-liner rendering** with `SpreadsheetRenderer` service or `SpreadsheetTrait` for controllers
- Auto-generation of **Excel sheets**, **styles**, **formulas**, **freezepanes**, **column widths**, **images**, etc.
- Built-in **French-oriented presets** (`money`, `date`, `float2`, `percent2`, etc.)
- Generate **XLSX**, **ODS**, **CSV** with multi-format export support
- **Cell styling** (background colors, font sizes, borders, protection)
- **Image support** (local files, data-URI, remote HTTP/HTTPS URLs)
- Multiple sheets from multiple `<table data-xls-sheet="...">` tags
- Strict mode (invalid attributes → exception)
- Fully extensible (register custom styles, validators, etc.)

---

## Cell Styling Attributes

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

## Cell Merging

Merge cells horizontally (colspan) or vertically (rowspan), or both simultaneously.

### Attributes

- `data-xls-colspan="N"` - Merge N columns horizontally
- `data-xls-rowspan="N"` - Merge N rows vertically

### Examples

**Horizontal Merging (Colspan):**
```html
<!-- Merge 3 columns -->
<tr>
    <td data-xls-colspan="3">Merged Header</td>
</tr>
<tr>
    <td>A</td>
    <td>B</td>
    <td>C</td>
</tr>
```

**Vertical Merging (Rowspan):**
```html
<!-- Merge 2 rows -->
<tr>
    <td data-xls-rowspan="2">Category</td>
    <td>Item 1</td>
</tr>
<tr>
    <td>Item 2</td>
</tr>
```

**Combined Merging:**
```html
<!-- Merge 2 columns and 2 rows (2x2 block) -->
<tr>
    <td data-xls-rowspan="2" data-xls-colspan="2">
        Large Block
    </td>
    <td>C1</td>
</tr>
<tr>
    <td>C2</td>
</tr>
```

**Complex Table Example:**
```html
<table data-xls-sheet="Report">
    <thead>
        <tr>
            <th data-xls-colspan="2" data-xls-bg-color="#4472C4">Q1 Results</th>
            <th data-xls-colspan="2" data-xls-bg-color="#70AD47">Q2 Results</th>
        </tr>
        <tr>
            <th>Revenue</th><th>Profit</th>
            <th>Revenue</th><th>Profit</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td data-xls-rowspan="2" data-xls-align="center">North</td>
            <td>100K</td><td>20K</td>
            <td>120K</td><td>25K</td>
        </tr>
        <tr>
            <td>110K</td><td>22K</td>
            <td>130K</td><td>28K</td>
        </tr>
    </tbody>
</table>
```

### Combining with Styling

Merged cells can be styled like any other cell:

```html
<td data-xls-rowspan="3"
    data-xls-bg-color="#FFCC00"
    data-xls-font-bold="true"
    data-xls-align="center">
    Styled Merged Cell
</td>
```

## Font Styling

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

## Conditional Formatting

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

## Auto-size Columns

Automatically adjust column widths to fit content. This feature uses PhpSpreadsheet's auto-sizing capability to calculate optimal column widths based on cell content.

### Attributes

**Table-level:**
- `data-xls-autosize="true"` - Auto-size all columns in the table
- `data-xls-autosize="A"` - Auto-size a single column (A)
- `data-xls-autosize="A:D"` - Auto-size a range of columns (A through D)
- `data-xls-autosize="A,C,E"` - Auto-size specific columns (A, C, and E)

**Column-level (in `<colgroup>`):**
- `data-xls-autosize="true"` - Auto-size this specific column

### Examples

**Auto-size all columns:**
```html
<table data-xls-sheet="Products" data-xls-autosize="true">
    <tr>
        <td>Product A</td>
        <td>This is a much longer description</td>
        <td>$99.99</td>
    </tr>
</table>
```

**Auto-size specific columns:**
```html
<table data-xls-sheet="Report" data-xls-autosize="A,C">
    <tr>
        <td>Name</td>
        <td>Fixed width column</td>
        <td>Long description text</td>
    </tr>
</table>
```

**Auto-size columns by range:**
```html
<table data-xls-sheet="Data" data-xls-autosize="B:D">
    <tr>
        <td>ID</td>
        <td>Name</td>
        <td>Email</td>
        <td>Phone</td>
        <td>Notes</td>
    </tr>
</table>
```

**Column-level auto-size with colgroup:**
```html
<table data-xls-sheet="Mixed">
    <colgroup>
        <col data-xls-width="10">
        <col data-xls-autosize="true">
        <col data-xls-width="30">
        <col data-xls-autosize="true">
    </colgroup>
    <tr>
        <td>ID</td>
        <td>Variable width name</td>
        <td>Fixed</td>
        <td>Variable width description</td>
## Cell Comments

Add comments (notes) to cells to provide additional information, instructions, or context. Comments appear as small indicators in cells and display when hovering over them in Excel/LibreOffice.

### Attributes

- `data-xls-comment="text"` - Comment text (required)
- `data-xls-comment-author="name"` - Comment author name (optional)
- `data-xls-comment-width="pixels"` - Comment box width in pixels (optional)
- `data-xls-comment-height="pixels"` - Comment box height in pixels (optional)
- `data-xls-comment-visible="true"` - Make comment always visible (optional, default: false)

### Examples

**Basic comment:**
```html
<td data-xls-comment="This cell needs review">
    Important data
</td>
```

**Comment with author:**
```html
<td data-xls-comment="Please verify this value"
    data-xls-comment-author="John Doe">
    1,234.56
</td>
```

**Comment with custom dimensions:**
```html
<td data-xls-comment="This is a longer comment that requires more space to display properly"
    data-xls-comment-width="300"
    data-xls-comment-height="100">
    Complex data
</td>
```

**Always visible comment:**
```html
<td data-xls-comment="IMPORTANT: Read this first"
    data-xls-comment-visible="true">
    Critical value
</td>
```

**Complete example with all attributes:**
```html
<table data-xls-sheet="Review">
    <tr>
        <td data-xls-comment="This formula needs to be updated for Q2"
            data-xls-comment-author="Finance Team"
            data-xls-comment-width="250"
            data-xls-comment-height="80"
            data-xls-comment-visible="true"
            data-xls-formula="=SUM(A1:A10)">
        </td>
    </tr>
</table>
```

**Note:** Auto-size calculates width based on content after all data is loaded. If both `data-xls-width` and `data-xls-autosize` are specified, autosize takes precedence.
**Use cases:**
- Provide instructions for data entry
- Add review notes or approvals
- Document formula logic
- Flag cells requiring attention
- Add contextual information for collaborators

## Multi-Format Export

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

## Image Support

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

## Hyperlinks

Add clickable links to cells with automatic styling. Links are rendered as blue, underlined text in Excel/ODS files.

### Attributes

- `data-xls-link="URL"` - Hyperlink URL (required)
- `data-xls-link-tooltip="text"` - Tooltip displayed on hover (optional)

### Supported Link Types

**External URLs:**
```html
<td data-xls-link="https://example.com">Visit our website</td>
<td data-xls-link="https://github.com/user/repo">GitHub Repository</td>
```

**Internal References (to another sheet/cell):**
```html
<!-- Link to another sheet -->
<td data-xls-link="#Sheet2!A1">Go to Sheet2, cell A1</td>

<!-- Link to cell in same sheet -->
<td data-xls-link="#B10">Jump to B10</td>

<!-- Link with spaces in sheet name -->
<td data-xls-link="#'My Data'!C5">Link to "My Data" sheet</td>
```

**Email Addresses:**
```html
<!-- Simple email -->
<td data-xls-link="mailto:contact@example.com">Send email</td>

<!-- Email with subject -->
<td data-xls-link="mailto:support@example.com?subject=Help Request">Contact Support</td>
```

**With Tooltips:**
```html
<td data-xls-link="https://symfony.com/doc"
    data-xls-link-tooltip="Click to view Symfony documentation">
    Symfony Docs
</td>
```

### Combining with Other Styles

Hyperlinks can be combined with other cell styling:

```html
<td data-xls-link="https://example.com"
    data-xls-bg-color="#FFFFCC"
    data-xls-font-bold="true"
    data-xls-link-tooltip="Important link">
    Highlighted Link
</td>
```

### Automatic Styling

Hyperlinks are automatically styled with:
- **Font color:** Blue (#0563C1)
- **Text decoration:** Underlined

These default styles can be overridden using `data-xls-font-color` and `data-xls-font-underline` attributes.

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


## Quick Start

### Simple Usage (Recommended)

Use the `SpreadsheetRenderer` service or `SpreadsheetTrait` for a one-liner solution:

```php
<?php

namespace App\Controller;

use Davidannebicque\HtmlToSpreadsheetBundle\Controller\SpreadsheetTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReportController extends AbstractController
{
    use SpreadsheetTrait;

    #[Route('/export', name: 'app_export')]
    public function export(): Response
    {
        $data = [
            ['name' => 'John Doe', 'amount' => 1234.56, 'date' => new \DateTime('2024-02-01')],
            ['name' => 'Anna Smith', 'amount' => 987.45, 'date' => new \DateTime('2024-02-02')],
        ];

        // One-liner: render template + convert + stream
        return $this->renderSpreadsheet(
            'reports/export.html.twig',
            ['lines' => $data],
            'export.xlsx'
        );
    }
}
```

### Advanced Usage

For more control, use the services directly:

```php
<?php

namespace App\Controller;

use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlTableInterpreter;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlToXlsxOptions;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Response\ExcelResponseFactory;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\SpreadsheetRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final class AdvancedController extends AbstractController
{
    #[Route('/export-advanced', name: 'app_export_advanced')]
    public function exportWithRenderer(SpreadsheetRenderer $renderer): Response
    {
        $data = [
            ['nom' => 'Doe', 'prenom' => 'John', 'montant' => 1234.56, 'date' => new \DateTime('2024-02-01')],
            ['nom' => 'Smith', 'prenom' => 'Anna', 'montant' => 987.45, 'date' => new \DateTime('2024-02-02')],
        ];

        // Using the renderer service
        return $renderer->renderFromTemplate(
            'demo/index.html.twig',
            ['lignes' => $data],
            'export-test.xlsx'
        );
    }

    #[Route('/export-manual', name: 'app_export_manual')]
    public function exportManual(
        Environment $twig,
        HtmlTableInterpreter $interpreter,
        ExcelResponseFactory $factory
    ): Response {
        $lines = [
            ['nom' => 'Doe', 'prenom' => 'John', 'montant' => 1234.56, 'date' => new \DateTime('2024-02-01')],
            ['nom' => 'Smith', 'prenom' => 'Anna', 'montant' => 987.45, 'date' => new \DateTime('2024-02-02')],
            ['nom' => 'Lemaire', 'prenom' => 'Lucie', 'montant' => 150.00, 'date' => new \DateTime('2024-02-03')],
        ];

        // 1) Render HTML template
        $html = $twig->render('demo/index.html.twig', ['lignes' => $lines]);

        // 2) Convert to Spreadsheet
        $workbook = $interpreter->fromHtml(
            $html,
            new HtmlToXlsxOptions(strict: true)
        );

        // 3) Stream as file download
        return $factory->streamWorkbook($workbook, 'export-test.xlsx');
    }

    #[Route('/create-workbook', name: 'app_create_workbook')]
    public function createWorkbook(SpreadsheetRenderer $renderer): Response
    {
        // Get the workbook object without streaming (for further manipulation)
        $workbook = $renderer->createFromTemplate(
            'demo/index.html.twig',
            ['lignes' => [/* data */]]
        );

        // Manipulate the workbook...
        $workbook->getActiveSheet()->getCell('A1')->setValue('Modified');

        // Then stream it manually
        $factory = new ExcelResponseFactory();
        return $factory->streamWorkbook($workbook, 'custom.xlsx');
    }
}
```

### Export Formats

```php
// XLSX (default)
return $this->renderSpreadsheet('template.html.twig', $data, 'export.xlsx');

// ODS (LibreOffice)
return $this->renderSpreadsheet('template.html.twig', $data, 'export.ods');

// CSV (first sheet only)
return $this->renderSpreadsheet('template.html.twig', $data, 'export.csv');
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
