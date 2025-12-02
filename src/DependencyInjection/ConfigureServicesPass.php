<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\DependencyInjection;

use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\StyleRegistry;
use Davidannebicque\HtmlToSpreadsheetBundle\Html\AttributeValidator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigureServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('html_to_spreadsheet.default_styles')) {
            return;
        }

        // Configure StyleRegistry
        if ($container->hasDefinition(StyleRegistry::class)) {
            $definition = $container->getDefinition(StyleRegistry::class);
            $definition->setArgument(0, $container->getParameter('html_to_spreadsheet.default_styles'));
        }

        // Configure AttributeValidator
        if ($container->hasDefinition(AttributeValidator::class)) {
            $definition = $container->getDefinition(AttributeValidator::class);
            $definition->setArgument(0, $container->getParameter('html_to_spreadsheet.strict'));
        }
    }
}
