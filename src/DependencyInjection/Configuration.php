<?php
// src/DependencyInjection/Configuration.php
namespace Davidannebicque\HtmlToSpreadsheetBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('html_to_spreadsheet');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
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

        return $treeBuilder;
    }
}
