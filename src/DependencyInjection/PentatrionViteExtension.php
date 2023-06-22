<?php

namespace Pentatrion\ViteBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PentatrionViteExtension extends Extension
{
    public static function prepareBase($base)
    {
        $base = '/' !== substr($base, 0, 1) ? '/'.$base : $base;
        $base = '/' !== substr($base, -1) ? $base.'/' : $base;

        return $base;
    }

    public static function preparePublicDirectory($publicDir)
    {
        $publicDir = '/' !== substr($publicDir, 0, 1) ? '/'.$publicDir : $publicDir;
        $publicDir = rtrim($publicDir, '/');

        return $publicDir;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $config = $this->processConfiguration(
            $this->getConfiguration($configs, $container),
            $configs
        );

        if (
            count($config['builds']) > 0) {
            if (is_null($config['default_build']) || !isset($config['builds'][$config['default_build']])) {
                throw new \Exception('Invalid default_build, choose between : '.join(', ', array_keys($config['builds'])));
            }
            $defaultBuild = $config['default_build'];
            $builds = [];
            foreach ($config['builds'] as $buildName => $build) {
                if ('build' === $build['build_directory'] && '/build/' !== $build['base']) {
                    $build['build_directory'] = $build['base'];
                }

                $builds[$buildName] = [
                    'base' => self::prepareBase($build['build_directory']),
                    'script_attributes' => $build['script_attributes'],
                    'link_attributes' => $build['link_attributes'],
                ];
            }
        } else {
            $defaultBuild = 'default';

            if ('build' === $config['build_directory'] && '/build/' !== $config['base']) {
                $config['build_directory'] = $config['base'];
            }

            $builds = [
                'default' => [
                    'base' => self::prepareBase($config['build_directory']),
                    'script_attributes' => $config['script_attributes'],
                    'link_attributes' => $config['link_attributes'],
                ],
            ];
        }

        // if deprecated option is set and not `public_directory`.
        if ('public' === $config['public_directory'] && 'public' !== $config['public_dir']) {
            $config['public_directory'] = $config['public_dir'];
        }

        $container->setParameter('pentatrion_vite.public_directory', self::preparePublicDirectory($config['public_directory']));

        $container->setParameter('pentatrion_vite.default_build', $defaultBuild);
        $container->setParameter('pentatrion_vite.builds', $builds);

        $container->setParameter('pentatrion_vite.proxy_origin', $config['proxy_origin']);
        $container->setParameter('pentatrion_vite.throw_on_missing_entry', $config['throw_on_missing_entry']);

        $container->getDefinition('vite.tag_renderer')
            ->replaceArgument(0, $defaultBuild)
            ->replaceArgument(1, $builds);
    }
}
