<?php

namespace Pentatrion\ViteBundle\Asset;

use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Symfony\Component\Asset\Exception\RuntimeException;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

class ViteAssetVersionStrategy implements VersionStrategyInterface
{
    private string $publicPath;
    private array $builds;

    private string $manifestPath;
    private string $entrypointsPath;
    private $manifestData = null;
    private $entrypointsData = null;
    private ?array $build = null;
    private bool $strictMode;

    public function __construct(string $publicPath, array $builds, string $defaultBuildName, bool $strictMode = true)
    {
        $this->publicPath = $publicPath;
        $this->builds = $builds;
        $this->strictMode = $strictMode;

        $this->setBuildName($defaultBuildName);

        if (($scheme = parse_url($this->manifestPath, \PHP_URL_SCHEME)) && 0 === strpos($scheme, 'http')) {
            throw new \Exception('You can\'t use a remote manifest with ViteAssetVersionStrategy');
        }
    }

    public function setBuildName(string $buildName): void
    {
        $this->build = $this->builds[$buildName];
        $this->manifestPath = $this->publicPath.$this->build['base'].'manifest.json';
        $this->entrypointsPath = $this->publicPath.$this->build['base'].'entrypoints.json';
    }

    /**
     * With a entrypoints, we don't really know or care about what
     * the version is. Instead, this returns the path to the
     * versioned file.
     */
    public function getVersion(string $path): string
    {
        return $this->applyVersion($path);
    }

    public function applyVersion(string $path): string
    {
        return $this->getassetsPath($path) ?: $path;
    }

    private function getassetsPath(string $path): ?string
    {
        if (null === $this->manifestData) {
            if (!is_file($this->entrypointsPath)) {
                throw new RuntimeException(sprintf('assets entrypoints file "%s" does not exist. Did you forget configure your base in pentatrion_vite.yml?', $this->manifestPath));
            }

            if (is_file($this->manifestPath)) {
                try {
                    $this->manifestData = json_decode(file_get_contents($this->manifestPath), true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new RuntimeException(sprintf('Error parsing JSON from entrypoints file "%s": ', $this->manifestPath).$e->getMessage(), 0, $e);
                }
            } else {
                $this->manifestData = false;
                try {
                    $this->entrypointsData = json_decode(file_get_contents($this->entrypointsPath), true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new RuntimeException(sprintf('Error parsing JSON from entrypoints file "%s": ', $this->manifestPath).$e->getMessage(), 0, $e);
                }
            }
        }

        if (false !== $this->manifestData) {
            if (isset($this->manifestData[$path])) {
                return $this->build['base'].$this->manifestData[$path]['file'];
            }
        } else {
            return $this->entrypointsData['viteServer']['origin'].$this->entrypointsData['viteServer']['base'].$path;
        }

        if ($this->strictMode) {
            $message = sprintf('assets "%s" not found in manifest file "%s".', $path, $this->manifestPath);
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
