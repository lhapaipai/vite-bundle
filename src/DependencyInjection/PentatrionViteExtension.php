<?php

namespace Pentatrion\ViteBundle\DependencyInjection;

use Pentatrion\ViteBundle\Asset\EntrypointsLookup;
use Pentatrion\ViteBundle\Asset\TagRenderer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PentatrionViteExtension extends Extension
{
    public function load(array $bundleConfigs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yaml');

        $bundleConfig = $this->processConfiguration(
            $this->getConfiguration($bundleConfigs, $container),
            $bundleConfigs
        );

        if (isset($bundleConfig['builds']) && !isset($bundleConfig['configs'])) {
            $bundleConfig['configs'] = $bundleConfig['builds'];
        }
        if (isset($bundleConfig['default_build']) && !isset($bundleConfig['default_config'])) {
            $bundleConfig['default_config'] = $bundleConfig['default_build'];
        }

        if (
            count($bundleConfig['configs']) > 0) {
            if (is_null($bundleConfig['default_config']) || !isset($bundleConfig['configs'][$bundleConfig['default_config']])) {
                throw new \Exception('Invalid default_config, choose between : '.join(', ', array_keys($bundleConfig['configs'])));
            }
            $defaultConfigName = $bundleConfig['default_config'];
            $lookupFactories = [];
            $tagRendererFactories = [];
            $configs = [];

            foreach ($bundleConfig['configs'] as $configName => $config) {
                $configs[$configName] = $configPrepared = self::prepareConfig($config);
                $lookupFactories[$configName] = $this->entrypointsLookupFactory($container, $configName, $configPrepared);
                $tagRendererFactories[$configName] = $this->tagRendererFactory($container, $configName, $configPrepared);
            }
        } else {
            $defaultConfigName = '_default';
            $configs[$defaultConfigName] = $configPrepared = self::prepareConfig($bundleConfig);

            $lookupFactories = [
                '_default' => $this->entrypointsLookupFactory($container, $defaultConfigName, $configPrepared),
            ];
            $tagRendererFactories = [
                '_default' => $this->tagRendererFactory($container, $defaultConfigName, $configPrepared),
            ];
        }

        $container->setParameter('pentatrion_vite.public_directory', self::preparePublicDirectory($bundleConfig['public_directory']));
        $container->setParameter('pentatrion_vite.default_config', $defaultConfigName);
        $container->setParameter('pentatrion_vite.configs', $configs);

        $container->setParameter('pentatrion_vite.absolute_url', $bundleConfig['absolute_url']);
        $container->setParameter('pentatrion_vite.proxy_origin', $bundleConfig['proxy_origin']);
        $container->setParameter('pentatrion_vite.throw_on_missing_entry', $bundleConfig['throw_on_missing_entry']);

        $container->getDefinition('pentatrion_vite.entrypoints_lookup_collection')
            ->addArgument(ServiceLocatorTagPass::register($container, $lookupFactories))
            ->addArgument($defaultConfigName);

        $container->getDefinition('pentatrion_vite.tag_renderer_collection')
            ->addArgument(ServiceLocatorTagPass::register($container, $tagRendererFactories))
            ->addArgument($defaultConfigName);

        // $container->getDefinition('pentatrion_vite.tag_renderer')
        //     ->replaceArgument(0, $defaultConfigName)
        //     ->replaceArgument(1, $configs);
    }

    private function entrypointsLookupFactory(
        ContainerBuilder $container,
        string $configName,
        array $config
    ): Reference {
        $id = $this->getServiceId('entrypoints_lookup', $configName);
        $arguments = [
            '%kernel.project_dir%%pentatrion_vite.public_directory%',
            $config,
            '%pentatrion_vite.throw_on_missing_entry%',
        ];
        $definition = new Definition(EntrypointsLookup::class, $arguments);
        $container->setDefinition($id, $definition);

        return new Reference($id);
    }

    private function tagRendererFactory(
        ContainerBuilder $container,
        string $configName,
        array $config
    ): Reference {
        $id = $this->getServiceId('tag_renderer', $configName);
        $arguments = [
            $config['script_attributes'],
            $config['link_attributes'],
        ];
        $definition = new Definition(TagRenderer::class, $arguments);
        $container->setDefinition($id, $definition);

        return new Reference($id);
    }

    private function getServiceId(string $prefix, string $configName): string
    {
        return sprintf('pentatrion_vite.%s[%s]', $prefix, $configName);
    }

    public static function prepareConfig(array $config): array
    {
        $base = $config['build_directory'];
        $base = '/' !== substr($base, 0, 1) ? '/'.$base : $base;
        $base = '/' !== substr($base, -1) ? $base.'/' : $base;

        return [
            'base' => $base,
            'script_attributes' => $config['script_attributes'],
            'link_attributes' => $config['link_attributes'],
        ];
    }

    public static function preparePublicDirectory($publicDir)
    {
        $publicDir = '/' !== substr($publicDir, 0, 1) ? '/'.$publicDir : $publicDir;
        $publicDir = rtrim($publicDir, '/');

        return $publicDir;
    }
}
