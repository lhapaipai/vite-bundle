<?php

namespace Pentatrion\ViteBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PentatrionViteExtension extends Extension
{
  public function load(array $configs, ContainerBuilder $container): void
  {
    $loader = new YamlFileLoader($container, new FileLocator((__DIR__ . '/../Resources/config')));
    $loader->load('services.yaml');

    $config = $this->processConfiguration(
      $this->getConfiguration($configs, $container),
      $configs
    );

    $container->setParameter('pentatrion_vite.base', $config['base']);
    $container->setParameter('pentatrion_vite.public_dir', $config['public_dir']);
    $server = ($config['server']['https'] ? 'https://' : 'http://') . $config['server']['host'] . ':' . $config['server']['port'];
    $container->setParameter('pentatrion_vite.server', $server);
  }
}
