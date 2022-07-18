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
        ->scalarNode('base')->defaultValue('/build/')->end()
        ->scalarNode('public_dir')->defaultValue('/public')->end()
        ->arrayNode('server')
          ->addDefaultsIfNotSet()
          ->children()
            ->scalarNode('host')->defaultValue('localhost')->end()
            ->integerNode('port')->defaultValue(5173)->end()
            ->booleanNode('https')->defaultFalse()->end()
          ->end()          
      ->end()
    ;

    return $treeBuilder;
  }
}