# Symfony Flex Recipe for HtmlToSpreadsheetBundle

This directory contains the Symfony Flex recipe for automatic installation and configuration of the HtmlToSpreadsheetBundle.

## What is a Flex Recipe?

A Symfony Flex recipe is a set of instructions that tells Symfony how to automatically configure a bundle when it's installed via Composer. This provides a better developer experience by:

- Auto-registering the bundle in `config/bundles.php`
- Creating default configuration files in `config/packages/`
- Displaying helpful post-install messages
- Setting up the bundle with sensible defaults

## Recipe Structure

```
flex-recipe/1.0/
├── manifest.json                     # Recipe instructions for Flex
├── config/
│   └── packages/
│       └── html_to_spreadsheet.yaml  # Default configuration file
├── post-install.txt                  # Message displayed after installation
└── README.md                         # This file
```

## Files Description

### manifest.json

Defines what Flex should do during installation:
- `bundles`: Registers the bundle automatically
- `copy-from-recipe`: Copies configuration files to the project

### config/packages/html_to_spreadsheet.yaml

Default configuration file that will be created in the user's project. Includes:
- Strict mode enabled by default
- Built-in presets enabled
- Commented examples of custom styles

### post-install.txt

A friendly message displayed after installation with:
- Quick start steps
- Links to documentation
- Feature highlights

## Testing the Recipe Locally

To test this recipe in a local Symfony project before submitting to recipes-contrib:

1. In your Symfony project, add this to `composer.json`:

```json
{
    "extra": {
        "symfony": {
            "allow-contrib": true,
            "endpoint": [
                "https://api.github.com/repos/symfony/recipes-contrib/contents/index.json",
                "flex://defaults"
            ]
        }
    }
}
```

2. Create a local Flex server or use the `symfony/flex` configurator directly

3. Install the bundle:

```bash
composer require davidannebicque/html-to-spreadsheet-bundle
```

## Submitting to symfony/recipes-contrib

Once tested, this recipe can be submitted to the official Symfony recipes-contrib repository:

### Prerequisites

1. The package must be published on Packagist
2. The package must have a stable release (1.0.0 or higher)
3. The recipe must follow Symfony Flex guidelines

### Submission Process

1. Fork the repository: https://github.com/symfony/recipes-contrib

2. Create the recipe in the correct location:
   ```
   recipes-contrib/davidannebicque/html-to-spreadsheet-bundle/1.0/
   ```

3. Copy the files from this directory to that location

4. Create a Pull Request with:
   - Clear title: "Add recipe for davidannebicque/html-to-spreadsheet-bundle"
   - Description explaining what the bundle does
   - Link to the bundle's repository
   - Confirmation that you've tested the recipe

5. Wait for review and approval from Symfony Flex maintainers

### Guidelines

- Recipe version (1.0) should match the major version of the bundle
- Configuration files should have sensible defaults
- Post-install messages should be concise and helpful
- Follow the existing recipe patterns in recipes-contrib

## Resources

- [Symfony Flex Documentation](https://symfony.com/doc/current/setup/flex.html)
- [Recipe Format Reference](https://github.com/symfony/recipes/blob/main/README.rst)
- [Symfony Recipes Contrib Repository](https://github.com/symfony/recipes-contrib)
- [Recipe Submission Guidelines](https://github.com/symfony/recipes-contrib/blob/main/CONTRIBUTING.md)

## Support

If you encounter issues with this recipe:
- Open an issue on the bundle's GitHub repository
- Check the Symfony Flex documentation
- Ask in the #flex channel on Symfony Slack
