<?php

namespace Pentatrion\ViteBundle\CacheWarmer;

use Exception;
use Pentatrion\ViteBundle\Asset\EntrypointsLookup;
use Pentatrion\ViteBundle\Exception\EntrypointsFileNotFoundException;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\AbstractPhpFileCacheWarmer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class EntrypointsCacheWarmer extends AbstractPhpFileCacheWarmer
{
    private $basePaths;

    public function __construct(array $basePaths, string $phpCacheFile)
    {
        $this->basePaths = $basePaths;
        parent::__construct($phpCacheFile);
    }

    protected function doWarmUp(string $cacheDir, ArrayAdapter $arrayAdapter): bool
    {
        foreach ($this->basePaths as $basePath) {
            $entrypointsPath = $basePath.'entrypoints.json';

            if (!file_exists($entrypointsPath)) {
                continue;
            }

            $entrypointsLookup = new EntrypointsLookup($entrypointsPath);
            try {
                // any method that will call getFileContent and generate
                // the file in cache.
                $entrypointsLookup->getBase();
            } catch (EntrypointsFileNotFoundException $e) {
                // ignore exception
            }
        }

        return true;
    }
}
