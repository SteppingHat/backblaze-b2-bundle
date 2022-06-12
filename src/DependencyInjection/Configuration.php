<?php

namespace SteppingHat\BackblazeB2\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {

    public function getConfigTreeBuilder(): TreeBuilder {
        $treeBuilder = new TreeBuilder('backblaze_b2');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('account_id')->end()
            ->scalarNode('application_id')->end()
            ->scalarNode('application_secret')->end()
            ->scalarNode('token_cache_directory')
                ->defaultNull()
            ->end()
        ->end();

        return $treeBuilder;
    }

}