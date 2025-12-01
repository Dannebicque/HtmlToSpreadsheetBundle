<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle\DependencyInjection;

use Davidannebicque\HtmlToSpreadsheetBundle\Html\AttributeValidator;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\StylePresets;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\StyleRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class HtmlToSpreadsheetExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // 1) Lire la config du bundle (temp_dir, strict, default_styles)
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // 2) Construire la liste complète des styles par défaut
        $defaultStyles = array_merge([
            'th'               => StylePresets::header(),
            'int'              => StylePresets::int(),
            'float2'           => StylePresets::float2(),
            'float3'           => StylePresets::float3(),
            'percent2'         => StylePresets::percent2(),
            'money'            => StylePresets::moneyEuro(),
            'money_accounting' => StylePresets::moneyEuroAccounting(),
            'date'             => StylePresets::dateShort(),
            'date_long'        => StylePresets::dateLongFr(),
            'time'             => StylePresets::timeShort(),
            'datetime'         => StylePresets::dateTime(),
            'duration'         => StylePresets::duration(),
            'bool_center'      => StylePresets::boolCentered(),
            'text_wrap'        => StylePresets::textWrap(),
            'warn'             => StylePresets::warning(),
        ], $config['default_styles'] ?? []);

        // 3) Enregistrer les paramètres du bundle
        $container->setParameter('html_to_spreadsheet.temp_dir', $config['temp_dir'] ?? null);
        $container->setParameter('html_to_spreadsheet.strict', $config['strict'] ?? true);
        $container->setParameter('html_to_spreadsheet.default_styles', $defaultStyles);

        // 4) Charger les services du bundle
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        // 5) S’assurer que StyleRegistry et AttributeValidator reçoivent les bons arguments

        // StyleRegistry
        if ($container->hasDefinition(StyleRegistry::class)) {
            $container->getDefinition(StyleRegistry::class)
                ->setArgument(0, '%html_to_spreadsheet.default_styles%');
        }

        // AttributeValidator
        if ($container->hasDefinition(AttributeValidator::class)) {
            $container->getDefinition(AttributeValidator::class)
                ->setArgument(0, '%html_to_spreadsheet.strict%');
        }
    }

    public function getAlias(): string
    {
        return 'html_to_spreadsheet';
    }
}
