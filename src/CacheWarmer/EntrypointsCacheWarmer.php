<?php

namespace Pentatrion\ViteBundle\CacheWarmer;

use Exception;
use Pentatrion\ViteBundle\Service\FileAccessor;
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

    protected function doWarmUp(string $cacheDir, ArrayAdapter $arrayAdapter, ?string $buildDir = null): bool
    {
        $fileAccessor = new FileAccessor($this->publicPath, $this->configs, $arrayAdapter);

        foreach ($this->configs as $configName => $config) {
            try {
                if ($fileAccessor->hasFile($configName, 'entrypoints')) {
                    $fileAccessor->getData($configName, 'entrypoints');
                }
            } catch (\Exception) {
                // ignore exception
            }

            try {
                if ($fileAccessor->hasFile($configName, 'manifest')) {
                    $fileAccessor->getData($configName, 'manifest');
                }
            } catch (\Exception) {
                // ignore exception
            }
        }

        return true;
    }
}
