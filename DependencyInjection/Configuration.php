<?php

namespace PawelLen\DataTablesListing\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('data_tables_listing');
        $rootNode
            ->children()
                ->arrayNode('include_assets')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('datatables_js')
                            ->defaultValue('//cdn.datatables.net/1.10.4/js/jquery.dataTables.min.js')
                        ->end()
                        ->scalarNode('datatables_css')
                            ->defaultValue('//cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css')
                        ->end()
                        ->booleanNode('include_jquery')
                            ->defaultValue(false)
                        ->end()
                        ->scalarNode('jquery_js')
                            ->defaultValue('//code.jquery.com/jquery-2.1.3.min.js')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_template')
                    ->defaultValue('DataTablesListingBundle::listing_div_layout.html.twig')
                ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
