<?php
namespace Lhapaipai\ViteBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class LhapaipaiViteExtension extends Extension
{
  public function load(array $configs, ContainerBuilder $container)
  {
    $loader = new XmlFileLoader($container, new FileLocator((__DIR__.'/../Resources/config')));
    $loader->load('services.xml');

    $config = $this->processConfiguration(
      $this->getConfiguration($configs, $container),
      $configs
    );

    $container->setParameter('lhapaipai_vite.base', $config['base']);
    $server = ($config['server']['https'] ? 'https://' : 'http://').$config['server']['host'].':'.$config['server']['port'];
    $container->setParameter('lhapaipai_vite.server', $server);
  }
}