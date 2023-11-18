<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\Exception\EntrypointsFileNotFoundException;
use Pentatrion\ViteBundle\Exception\VersionMismatchException;
use Pentatrion\ViteBundle\PentatrionViteBundle;
use Psr\Cache\CacheItemPoolInterface;

class FileAccessor
{
    public const ENTRYPOINTS = 'entrypoints';
    public const MANIFEST = 'manifest';

    public const FILES = [
        self::ENTRYPOINTS => 'entrypoints.json',
        self::MANIFEST => 'manifest.json',
    ];

    private array $configs;
    private ?CacheItemPoolInterface $cache = null;
    private array $content;
    private string $publicPath;

    public function __construct(
        string $publicPath,
        array $configs,
        CacheItemPoolInterface $cache = null
    ) {
        $this->publicPath = $publicPath;
        $this->configs = $configs;
        $this->cache = $cache;
    }

    public function hasFile(string $configName, string $fileType): bool
    {
        $basePath = $this->publicPath.$this->configs[$configName]['base'];

        return file_exists($basePath.'.vite/'.self::FILES[$fileType]) || file_exists($basePath.self::FILES[$fileType]);
    }

    public function getData(string $configName, string $fileType): array
    {
        if (!isset($this->content[$configName][$fileType])) {
            if ($this->cache) {
                $cacheItem = $this->cache->getItem("$configName.$fileType");

                if ($cacheItem->isHit()) {
                    $this->content[$configName][$fileType] = $cacheItem->get();
                }
            }

            if (!isset($this->content[$configName][$fileType])) {
                $filePath = $this->publicPath.$this->configs[$configName]['base'].self::FILES[$fileType];
                $basePath = $this->publicPath.$this->configs[$configName]['base'];

                if (($scheme = parse_url($filePath, \PHP_URL_SCHEME)) && 0 === strpos($scheme, 'http')) {
                    throw new \Exception('You can\'t use a remote manifest with pentatrion/vite-bundle');
                }

                if (file_exists($basePath.'.vite/'.self::FILES[$fileType])) {
                    $filePath = $basePath.'.vite/'.self::FILES[$fileType];
                } elseif (file_exists($basePath.self::FILES[$fileType])) {
                    $filePath = $basePath.self::FILES[$fileType];
                } else {
                    throw new EntrypointsFileNotFoundException("$fileType not found at $basePath. Did you forget configure your `build_directory` in pentatrion_vite.yml");
                }

                $content = json_decode(file_get_contents($filePath), true);

                if (self::ENTRYPOINTS === $fileType) {
                    $pluginVersion = array_key_exists('version', $content) ? $content['version'] : null;
                    if (
                        is_null($pluginVersion)
                        // VERSION[1] => Major version number
                        || PentatrionViteBundle::VERSION[1] !== $pluginVersion[1]
                    ) {
                        throw new VersionMismatchException('your vite-plugin-symfony is outdated, run : npm install vite-plugin-symfony@^'.PentatrionViteBundle::VERSION[1]);
                    }
                }

                if (isset($cacheItem)) {
                    $this->cache->save($cacheItem->set($content));
                }

                $this->content[$configName][$fileType] = $content;
            }
        }

        return $this->content[$configName][$fileType];
    }
}
