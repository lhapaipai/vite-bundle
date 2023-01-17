<?php

namespace Pentatrion\ViteBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pentatrion_vite');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('public_dir')
                    ->defaultValue('/public')
                ->end()
                ->scalarNode('base')
                    ->defaultValue('/build/')
                ->end()
                ->arrayNode('script_attributes')
                    ->info('Key/value pair of attributes to render on all script tags')
                    ->example('{ defer: true, referrerpolicy: "origin" }')
                    ->normalizeKeys(false)
                    ->scalarPrototype()
                    ->end()
                ->end()
                ->arrayNode('link_attributes')
                    ->info('Key/value pair of attributes to render on all CSS link tags')
                    ->example('{ referrerpolicy: "origin" }')
                    ->normalizeKeys(false)
                    ->scalarPrototype()
                    ->end()
                ->end()
                ->scalarNode('default_build')
                    ->defaultValue(null)
                ->end()
                ->arrayNode('builds')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('base')
                                ->defaultValue('/build/')
                            ->end()
                            ->arrayNode('script_attributes')
                                ->info('Key/value pair of attributes to render on all script tags')
                                ->example('{ defer: true, referrerpolicy: "origin" }')
                                ->normalizeKeys(false)
                                ->scalarPrototype()
                                ->end()
                            ->end()
                            ->arrayNode('link_attributes')
                                ->info('Key/value pair of attributes to render on all CSS link tags')
                                ->example('{ referrerpolicy: "origin" }')
                                ->normalizeKeys(false)
                                ->scalarPrototype()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
