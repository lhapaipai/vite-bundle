<?php

namespace Pentatrion\ViteBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PentatrionViteExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $config = $this->processConfiguration(
            $this->getConfiguration($configs, $container),
            $configs
        );

        $base = $config['base'];
        $base = '/' !== substr($base, 0, 1) ? '/'.$base : $base;
        $base = '/' !== substr($base, -1) ? $base.'/' : $base;

        $container->setParameter('pentatrion_vite.base', $base);
        $container->setParameter('pentatrion_vite.public_dir', $config['public_dir']);

        $container->getDefinition('vite.tag_renderer')
            ->replaceArgument(0, $config['script_attributes'])
            ->replaceArgument(1, $config['link_attributes']);
    }
}
