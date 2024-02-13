<?php

namespace Pentatrion\ViteBundle\Asset;

use Pentatrion\ViteBundle\Service\FileAccessor;
use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ViteAssetVersionStrategy implements VersionStrategyInterface
{
    private FileAccessor $fileAccessor;
    private array $configs;
    private string $configName;
    private bool $useAbsoluteUrl;
    private ?RequestStack $requestStack;
    private bool $strictMode;

    private ?string $viteMode = null;
    private string $basePath;
    private ?array $manifestData = null;
    private array $entrypointsData = [];

    public function __construct(
        FileAccessor $fileAccessor,
        array $configs,
        string $defaultConfigName,
        bool $useAbsoluteUrl,
        ?RequestStack $requestStack = null,
        bool $strictMode = true
    ) {
        $this->fileAccessor = $fileAccessor;
        $this->configs = $configs;
        $this->configName = $defaultConfigName;
        $this->useAbsoluteUrl = $useAbsoluteUrl;
        $this->requestStack = $requestStack;
        $this->strictMode = $strictMode;

        $this->setConfig($this->configName);
    }

    public function setConfig(string $configName): void
    {
        $this->viteMode = null;
        $this->configName = $configName;
        $this->basePath = $this->configs[$configName]['base'];
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

    private function completeURL(string $path): string
    {
        if (str_starts_with($path, 'http') || false === $this->useAbsoluteUrl || null === $this->requestStack || null === $this->requestStack->getCurrentRequest()) {
            return $path;
        }

        return $this->requestStack->getCurrentRequest()->getUriForPath($path);
    }

    private function getassetsPath(string $path): ?string
    {
        if (null === $this->viteMode) {
            $this->viteMode = $this->fileAccessor->hasFile($this->configName, FileAccessor::MANIFEST) ? 'build' : 'dev';

            $this->manifestData = 'build' === $this->viteMode ? $this->fileAccessor->getData($this->configName, FileAccessor::MANIFEST) : null;
            $this->entrypointsData = $this->fileAccessor->getData($this->configName, FileAccessor::ENTRYPOINTS);
        }

        if ('build' === $this->viteMode) {
            if (isset($this->manifestData[$path])) {
                return $this->completeURL($this->basePath.$this->manifestData[$path]['file']);
            }
        } else {
            return $this->entrypointsData['viteServer'].$this->entrypointsData['base'].$path;
        }

        if ($this->strictMode) {
            $message = sprintf('assets "%s" not found in manifest file from config "%s".', $path, $this->configName);
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
