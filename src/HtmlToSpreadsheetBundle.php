<?php

namespace Davidannebicque\HtmlToSpreadsheetBundle;

use Davidannebicque\HtmlToSpreadsheetBundle\DependencyInjection\ConfigureServicesPass;
use Davidannebicque\HtmlToSpreadsheetBundle\Spreadsheet\Styler\StylePresets;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class HtmlToSpreadsheetBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
            ->children()
            ->scalarNode('temp_dir')
            ->defaultNull()
            ->info('Répertoire temporaire pour les fichiers')
            ->end()
            ->booleanNode('strict')
            ->defaultTrue()
            ->info('Activer le mode strict pour la validation HTML')
            ->end()
            ->booleanNode('include_builtins')
            ->defaultTrue()
            ->info('Inclure les styles prédéfinis du bundle')
            ->end()
            ->arrayNode('default_styles')
            ->info('Définir vos styles personnalisés')
            ->example([
                'custom_header' => [
                    'font' => ['bold' => true, 'color' => 'FFFFFF'],
                    'fill' => ['fillType' => 'solid', 'color' => '4472C4'],
                ]
            ])
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->variablePrototype()->end()
            ->end()
            ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('./Resources/config/services.yaml');

        $builtins = [
            'th' => StylePresets::header(),
            'int' => StylePresets::int(),
            'float2' => StylePresets::float2(),
            'float3' => StylePresets::float3(),
            'percent2' => StylePresets::percent2(),
            'money' => StylePresets::moneyEuro(),
            'money_accounting' => StylePresets::moneyEuroAccounting(),
            'date' => StylePresets::dateShort(),
            'date_long' => StylePresets::dateLongFr(),
            'time' => StylePresets::timeShort(),
            'datetime' => StylePresets::dateTime(),
            'duration' => StylePresets::duration(),
            'bool_center' => StylePresets::boolCentered(),
            'text_wrap' => StylePresets::textWrap(),
            'warn' => StylePresets::warning(),
        ];

        $includeBuiltins = $config['include_builtins'] ?? true;
        $base = $includeBuiltins ? $builtins : [];
        $defaultStyles = array_replace($base, $config['default_styles'] ?? []);

        $container->parameters()
            ->set('html_to_spreadsheet.temp_dir', $config['temp_dir'] ?? null)
            ->set('html_to_spreadsheet.strict', $config['strict'] ?? true)
            ->set('html_to_spreadsheet.default_styles', $defaultStyles);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ConfigureServicesPass());
    }
}
