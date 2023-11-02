<?php

namespace Pentatrion\ViteBundle\CacheWarmer;

use Exception;
use Pentatrion\ViteBundle\Asset\EntrypointsLookup;
use Pentatrion\ViteBundle\Asset\ViteAssetVersionStrategy;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\AbstractPhpFileCacheWarmer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class EntrypointsCacheWarmer extends AbstractPhpFileCacheWarmer
{
    private string $publicPath;
    private array $configs;

    public function __construct(
        string $publicPath,
        array $configs,
        string $phpCacheFile)
    {
        $this->publicPath = $publicPath;
        $this->configs = $configs;
        parent::__construct($phpCacheFile);
    }

    protected function doWarmUp(string $cacheDir, ArrayAdapter $arrayAdapter): bool
    {
        foreach ($this->configs as $configName => $config) {
            $entrypointsPath = $this->publicPath.$this->configs[$configName]['base'].'entrypoints.json';

            if (!file_exists($entrypointsPath)) {
                continue;
            }

            $viteAssetVersionStrategy = new ViteAssetVersionStrategy(
                $this->publicPath,
                $this->configs,
                $configName,
                false,
                $arrayAdapter,
                null,
                false
            );
            // $entrypointsLookup = new EntrypointsLookup($basePath, $configName, false, $arrayAdapter);
            try {
                // any method that will call getFileContent and generate
                // the file in cache.
                // $entrypointsLookup->getBase();
                $viteAssetVersionStrategy->applyVersion('/some-dummy-path');
            } catch (\Exception $e) {
                // ignore exception
            }
        }

        return true;
    }
}
