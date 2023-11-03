<?php

namespace Pentatrion\ViteBundle\Asset;

use Pentatrion\ViteBundle\Exception\EntrypointsFileNotFoundException;
use Psr\Cache\CacheItemPoolInterface;

class FileAccessor
{
    public const FILES = [
        'entrypoints' => 'entrypoints.json',
        'manifest' => 'manifest.json',
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
        return file_exists($this->publicPath.$this->configs[$configName]['base'].self::FILES[$fileType]);
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

                if (($scheme = parse_url($filePath, \PHP_URL_SCHEME)) && 0 === strpos($scheme, 'http')) {
                    throw new \Exception('You can\'t use a remote manifest with pentatrion/vite-bundle');
                }

                if (!file_exists($filePath)) {
                    throw new EntrypointsFileNotFoundException("$fileType not found at $filePath. Did you forget configure your `build_directory` in pentatrion_vite.yml");
                }
                $content = json_decode(file_get_contents($filePath), true);

                if (isset($cacheItem)) {
                    $this->cache->save($cacheItem->set($content));
                }

                $this->content[$configName][$fileType] = $content;
            }
        }

        return $this->content[$configName][$fileType];
    }
}
