<?php

namespace SteppingHat\BackblazeB2\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class BackblazeB2Extension extends Extension {

    public function load(array $configs, ContainerBuilder $container) {

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition('backblazeb2.client');
        $definition->setArgument(1, $config['account_id']);
        $definition->setArgument(2, $config['application_id']);
        $definition->setArgument(3, $config['application_secret']);
        $definition->setArgument(4, $config['token_cache_directory']);

    }
}