<?php

namespace Glorpen\Propel\PropelBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('glorpen_propel');
        
        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('glorpen_propel');
        }

        $rootNode
        ->children()
            ->arrayNode("extended_models")
                ->defaultValue(array())
                ->useAttributeAsKey('key')
                ->prototype('scalar')->end()
            ->end()
        ->end()
        ;
        
        return $treeBuilder;
    }
}
