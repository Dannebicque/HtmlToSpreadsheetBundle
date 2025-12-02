# Symfony Flex Integration Guide

This document explains how to integrate HtmlToSpreadsheetBundle with Symfony Flex for automatic installation and configuration.

## What is Symfony Flex?

Symfony Flex is a Composer plugin that automates the installation and configuration of Symfony bundles. When you run `composer require`, Flex can:

- Automatically register bundles in `config/bundles.php`
- Create configuration files in `config/packages/`
- Update environment variables in `.env`
- Display helpful post-install messages

## Current Status

✅ **Bundle is Flex-ready!**

The HtmlToSpreadsheetBundle already extends `AbstractBundle`, which provides full Symfony Flex compatibility.

## Installation (Once Published)

Once the bundle is published on Packagist and the Flex recipe is merged into [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib), users will be able to install it with a single command:

```bash
composer require davidannebicque/html-to-spreadsheet-bundle
```

Flex will automatically:
1. Register the bundle in `config/bundles.php`
2. Create `config/packages/html_to_spreadsheet.yaml` with default configuration
3. Display a welcome message with quick start instructions

## Testing Flex Integration Locally

To test the Flex recipe before submitting to recipes-contrib:

### Option 1: Using Local Flex Endpoint

1. In a test Symfony project, modify `composer.json`:

```json
{
    "extra": {
        "symfony": {
            "allow-contrib": true,
            "endpoint": [
                "file:///path/to/HtmlToSpreadsheetBundle/flex-recipe",
                "flex://defaults"
            ]
        }
    }
}
```

2. Install the bundle:

```bash
composer require davidannebicque/html-to-spreadsheet-bundle
```

### Option 2: Manual Installation (Current Method)

Until the Flex recipe is published, install manually:

1. **Install via Composer:**

```bash
composer require davidannebicque/html-to-spreadsheet-bundle
```

2. **Register the bundle** in `config/bundles.php`:

```php
return [
    // ...
    Davidannebicque\HtmlToSpreadsheetBundle\HtmlToSpreadsheetBundle::class => ['all' => true],
];
```

3. **Create configuration file** `config/packages/html_to_spreadsheet.yaml`:

```yaml
html_to_spreadsheet:
    strict: true
    include_builtins: true
    # default_styles:
    #     custom_header:
    #         font:
    #             bold: true
    #             color: 'FFFFFF'
```

## Flex Recipe Contents

The Flex recipe is located in `flex-recipe/1.0/` and contains:

### 1. `manifest.json`

Tells Flex what to do during installation:

```json
{
    "bundles": {
        "Davidannebicque\\HtmlToSpreadsheetBundle\\HtmlToSpreadsheetBundle": ["all"]
    },
    "copy-from-recipe": {
        "config/": "%CONFIG_DIR%/"
    }
}
```

### 2. `config/packages/html_to_spreadsheet.yaml`

Default configuration file with:
- Strict mode enabled
- Built-in style presets enabled
- Commented examples

### 3. `post-install.txt`

Welcome message with:
- Quick start steps
- Documentation links
- Feature highlights

## Publishing the Recipe

To make the Flex recipe available to all Symfony users:

### 1. Publish on Packagist

First, ensure the bundle is available on Packagist:

1. Create a GitHub release (e.g., v1.0.0)
2. Packagist will automatically pick it up (if webhook is configured)
3. Or manually submit on https://packagist.org/packages/submit

### 2. Submit Recipe to symfony/recipes-contrib

1. **Fork the repository:**
   ```bash
   git clone https://github.com/symfony/recipes-contrib
   cd recipes-contrib
   ```

2. **Create recipe directory:**
   ```bash
   mkdir -p davidannebicque/html-to-spreadsheet-bundle/1.0
   ```

3. **Copy recipe files:**
   ```bash
   cp -r /path/to/HtmlToSpreadsheetBundle/flex-recipe/1.0/* \
         davidannebicque/html-to-spreadsheet-bundle/1.0/
   ```

4. **Create Pull Request:**
   ```bash
   git checkout -b recipe-html-to-spreadsheet
   git add davidannebicque/html-to-spreadsheet-bundle
   git commit -m "Add recipe for davidannebicque/html-to-spreadsheet-bundle 1.0"
   git push origin recipe-html-to-spreadsheet
   ```

5. **Open PR on GitHub** with:
   - Title: `Add recipe for davidannebicque/html-to-spreadsheet-bundle`
   - Description of what the bundle does
   - Link to bundle repository
   - Confirmation of local testing

### 3. Recipe Review

The Symfony Flex team will review the recipe:
- ✅ Check manifest.json format
- ✅ Verify configuration file quality
- ✅ Ensure post-install message is helpful
- ✅ Confirm bundle follows Symfony best practices

## Configuration Options

Once installed, users can configure the bundle in `config/packages/html_to_spreadsheet.yaml`:

```yaml
html_to_spreadsheet:
    # Enable strict mode (throws exceptions for invalid HTML attributes)
    strict: true

    # Include built-in style presets (th, money, date, etc.)
    include_builtins: true

    # Temporary directory for file operations (optional)
    temp_dir: '%kernel.project_dir%/var/tmp'

    # Define custom styles
    default_styles:
        my_header:
            font:
                bold: true
                color: 'FFFFFF'
                size: 14
            fill:
                fillType: 'solid'
                startColor:
                    rgb: '4472C4'
            alignment:
                horizontal: 'center'
                vertical: 'center'

        money_red:
            numberFormat:
                formatCode: '#,##0.00 €'
            font:
                color: 'FF0000'
```

## Bundle Features Available via Flex

Once installed, all features are immediately available:

### 1. **Service Autowiring**

```php
use Davidannebicque\HtmlToSpreadsheetBundle\Html\HtmlTableInterpreter;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Response\ExcelResponseFactory;

class ExportController extends AbstractController
{
    public function __construct(
        private HtmlTableInterpreter $interpreter,
        private ExcelResponseFactory $factory
    ) {}

    #[Route('/export')]
    public function export(Environment $twig): Response
    {
        $html = $twig->render('export/template.html.twig', []);
        $spreadsheet = $this->interpreter->fromHtml($html);
        return $this->factory->streamWorkbook($spreadsheet, 'export.xlsx');
    }
}
```

### 2. **Twig Integration**

Create templates with `data-xls-*` attributes:

```twig
<table data-xls-sheet="Report" data-xls-freeze="A2">
    <thead>
        <tr data-xls-apply="th">
            <th>Product</th>
            <th>Price</th>
        </tr>
    </thead>
    <tbody>
        {% for product in products %}
        <tr>
            <td>{{ product.name }}</td>
            <td data-xls-type="number" data-xls-apply="money">
                {{ product.price }}
            </td>
        </tr>
        {% endfor %}
    </tbody>
</table>
```

### 3. **Style Presets**

Use built-in styles via `data-xls-apply`:

- `th` - Table headers (bold, centered)
- `money` - Currency format (€)
- `date` - Short date format
- `percent2` - Percentage with 2 decimals
- `float2` - Float with 2 decimals
- And many more...

## Resources

- **Bundle Repository:** https://github.com/Davidannebicque/HtmlToSpreadsheetBundle
- **Symfony Flex Docs:** https://symfony.com/doc/current/setup/flex.html
- **Recipe Format:** https://github.com/symfony/recipes/blob/main/README.rst
- **Recipes Contrib:** https://github.com/symfony/recipes-contrib

## Support

For issues related to:
- **Bundle functionality:** Open an issue on the bundle repository
- **Flex recipe:** Comment on the recipe PR or open an issue in recipes-contrib
- **General Symfony Flex:** Ask on Stack Overflow or Symfony Slack (#flex channel)
