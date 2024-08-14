<?php

namespace Pentatrion\ViteBundle\CacheWarmer;

use Exception;
use Pentatrion\ViteBundle\Service\FileAccessor;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\AbstractPhpFileCacheWarmer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class EntrypointsCacheWarmer extends AbstractPhpFileCacheWarmer
{
    /**
     * @param array<string, array<mixed>> $configs
     */
    public function __construct(
        private string $publicPath,
        private array $configs,
        string $phpCacheFile)
    {
        parent::__construct($phpCacheFile);
    }

    protected function doWarmUp(string $cacheDir, ArrayAdapter $arrayAdapter, ?string $buildDir = null): bool
    {
        $fileAccessor = new FileAccessor($this->publicPath, $this->configs, $arrayAdapter);

        foreach ($this->configs as $configName => $config) {
            try {
                if ($fileAccessor->hasFile($configName, FileAccessor::ENTRYPOINTS)) {
                    $fileAccessor->getData($configName, FileAccessor::ENTRYPOINTS);
                }
            } catch (\Exception) {
                // ignore exception
            }

            try {
                if ($fileAccessor->hasFile($configName, FileAccessor::MANIFEST)) {
                    $fileAccessor->getData($configName, FileAccessor::MANIFEST);
                }
            } catch (\Exception) {
                // ignore exception
            }
        }

        return true;
    }
}
