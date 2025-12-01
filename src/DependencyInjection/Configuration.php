<?php
// src/DependencyInjection/Configuration.php
namespace Davidannebicque\HtmlToSpreadsheetBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tb = new TreeBuilder('html_to_spreadsheet');

        $tb->getRootNode()
            ->children()
            ->scalarNode('temp_dir')->defaultNull()->end()
            ->booleanNode('strict')->defaultTrue()->end()
            ->arrayNode('default_styles')
            ->useAttributeAsKey('name')
            ->arrayPrototype()->normalizeKeys(false)->end()
            ->end()
            ->end();

        return $tb;
    }
}
