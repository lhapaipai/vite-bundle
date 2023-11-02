<?php

namespace Pentatrion\ViteBundle\Asset;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Symfony\Component\Asset\Exception\RuntimeException;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;
use Symfony\Component\Routing\RouterInterface;

class ViteAssetVersionStrategy implements VersionStrategyInterface
{
    private string $publicPath;
    private array $configs;
    private string $configName;
    private $useAbsoluteUrl;
    private ?CacheItemPoolInterface $cache;
    private ?RouterInterface $router;
    private bool $strictMode;

    private ?string $viteMode = null;
    private string $basePath;
    private $manifestData;
    private $entrypointsData;

    public function __construct(
        string $publicPath,
        array $configs,
        string $defaultConfigName,
        bool $useAbsoluteUrl,
        CacheItemPoolInterface $cache = null,
        RouterInterface $router = null,
        bool $strictMode = true
    ) {
        $this->publicPath = $publicPath;
        $this->configs = $configs;
        $this->configName = $defaultConfigName;
        $this->useAbsoluteUrl = $useAbsoluteUrl;
        $this->cache = $cache;
        $this->router = $router;
        $this->strictMode = $strictMode;

        $this->setConfig($this->configName);

        if (($scheme = parse_url($this->basePath.'manifest.json', \PHP_URL_SCHEME)) && 0 === strpos($scheme, 'http')) {
            throw new \Exception('You can\'t use a remote manifest with ViteAssetVersionStrategy');
        }
    }

    public function setConfig(string $configName): void
    {
        $this->viteMode = null;
        $this->configName = $configName;
        $this->basePath = $this->publicPath.$this->configs[$configName]['base'];
    }

    /**
     * With a entrypoints, we don't really know or care about what
     * the version is. Instead, this returns the path to the
     * versioned file. as it contains a hashed and different path
     * with each new config, this is enough for us.
     */
    public function getVersion(string $path): string
    {
        return $this->applyVersion($path);
    }

    public function applyVersion(string $path): string
    {
        return $this->getassetsPath($path) ?: $path;
    }

    private function completeURL(string $path)
    {
        if (false === $this->useAbsoluteUrl || null === $this->router) {
            return $path;
        }

        return $this->router->getContext()->getScheme().'://'.$this->router->getContext()->getHost().$path;
    }

    private function getassetsPath(string $path): ?string
    {
        if (null === $this->viteMode) {
            $manifestPath = $this->basePath.'manifest.json';
            $entrypointsPath = $this->basePath.'entrypoints.json';

            $this->entrypointsData = null;
            $this->manifestData = null;

            $this->viteMode = is_file($manifestPath) ? 'build' : 'dev';

            if (!is_file($entrypointsPath)) {
                throw new RuntimeException(sprintf('assets entrypoints file "%s" does not exist. Did you forget configure your `build_dir` in pentatrion_vite.yml?', $entrypointsPath));
            }

            if ('build' === $this->viteMode && $this->cache) {
                $entrypointsCacheItem = $this->cache->getItem("{$this->configName}.entrypoints");
                $manifestCacheItem = $this->cache->getItem("{$this->configName}.manifest");

                if ($entrypointsCacheItem->isHit()) {
                    $this->entrypointsData = $entrypointsCacheItem->get();
                }
                if ($manifestCacheItem->isHit()) {
                    $this->manifestData = $manifestCacheItem->get();
                }
            }

            if ('build' === $this->viteMode && is_null($this->manifestData)) {
                try {
                    $this->manifestData = json_decode(file_get_contents($manifestPath), true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new RuntimeException(sprintf('Error parsing JSON from entrypoints file "%s": ', $manifestPath).$e->getMessage(), 0, $e);
                }
            }

            if (is_null($this->entrypointsData)) {
                try {
                    $this->entrypointsData = json_decode(file_get_contents($entrypointsPath), true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new RuntimeException(sprintf('Error parsing JSON from entrypoints file "%s": ', $manifestPath).$e->getMessage(), 0, $e);
                }
            }

            if (isset($entrypointsCacheItem)) {
                $this->cache->save($entrypointsCacheItem->set($this->entrypointsData));
            }
            if (isset($manifestCacheItem)) {
                $this->cache->save($manifestCacheItem->set($this->manifestData));
            }
        }

        if ('build' === $this->viteMode) {
            if (isset($this->manifestData[$path])) {
                return $this->completeURL($this->basePath.$this->manifestData[$path]['file']);
            }
        } else {
            return $this->entrypointsData['viteServer'].$this->entrypointsData['base'].$path;
        }

        if ($this->strictMode) {
            $message = sprintf('assets "%s" not found in manifest file "%s".', $path, $manifestPath);
            $alternatives = $this->findAlternatives($path, $this->manifestData);
            if (\count($alternatives) > 0) {
                $message .= sprintf(' Did you mean one of these? "%s".', implode('", "', $alternatives));
            }

            throw new AssetNotFoundException($message, $alternatives);
        }

        return null;
    }

    private function findAlternatives(string $path, ?array $manifestData): array
    {
        $path = strtolower($path);
        $alternatives = [];

        if (is_null($manifestData)) {
            return $alternatives;
        }

        foreach ($manifestData as $key => $value) {
            $lev = levenshtein($path, strtolower($key));
            if ($lev <= \strlen($path) / 3 || false !== stripos($key, $path)) {
                $alternatives[$key] = isset($alternatives[$key]) ? min($lev, $alternatives[$key]) : $lev;
            }
        }

        asort($alternatives);

        return array_keys($alternatives);
    }
}
