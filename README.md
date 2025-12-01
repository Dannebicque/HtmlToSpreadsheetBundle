# HtmlToSpreadsheetBundle

[![Latest Version](https://img.shields.io/packagist/v/davidannebicque/html-to-spreadsheet-bundle.svg)](https://packagist.org/packages/davidannebicque/html-to-spreadsheet-bundle)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Symfony](https://img.shields.io/badge/Symfony-7.3/8.x-black.svg)](https://symfony.com)
[![PhpSpreadsheet](https://img.shields.io/badge/PhpSpreadsheet-5.x-yellow.svg)](https://github.com/PHPOffice/PhpSpreadsheet)

## Warning

First draft of a Symfony bundle to convert HTML tables into Excel (.xlsx), OpenDocument (.ods) or CSV. WIP. 

---

Convert **annotated HTML tables** into **Excel (.xlsx), OpenDocument (.ods)** or **CSV** using a simple, declarative syntax.

This bundle is ideal for developers who want to use **Twig + HTML** as a DSL to build spreadsheets, without directly using the PhpSpreadsheet API.

---

## Requirements

- PHP 8.2+
- Symfony 7.3+
- PhpSpreadsheet 5.x (maybe 3 or 4 also compatible, not tested)

## ✨ Features

- ✔️ Write spreadsheets using **HTML tables** and `data-xls-*` attributes
- ✔️ Auto-generation of **Excel sheets**, **styles**, **formulas**, **freezepanes**, **column widths**, **images**, etc.
- ✔️ Built-in **French-oriented presets** (`money`, `date`, `float2`, `percent2`, etc.)
- ✔️ Generate XLSX, ODS, CSV
- ✔️ Multiple sheets from multiple `<table data-xls-sheet="...">` tags
- ✔️ Strict mode (invalid attributes → exception)
- ✔️ Fully extensible (register custom styles, validators, etc.)

---

## Installation

```bash
composer require davidannebicque/html-to-spreadsheet-bundle
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
