<?php

namespace Pentatrion\ViteBundle\Asset;

use Exception;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;
use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Symfony\Component\Asset\Exception\RuntimeException;

class ViteAssetVersionStrategy implements VersionStrategyInterface
{
    private string $entrypointsPath;
    private array $entrypointsData;
    private ?array $assetsData = null;
    private bool $strictMode;

    /**
     * @param string $entrypointsPath Absolute path to the entrypoints file
     * @param bool   $strictMode   Throws an exception for unknown paths
     */
    public function __construct(string $entrypointsPath, bool $strictMode = true)
    {
        $this->entrypointsPath = $entrypointsPath;
        $this->strictMode = $strictMode;

        if (($scheme = parse_url($this->entrypointsPath, \PHP_URL_SCHEME)) && 0 === strpos($scheme, 'http')) {
            throw new Exception('You can\' use a remote entrypoints with ViteAssetVersionStrategy');
        }
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
        if (!isset($this->entrypointsData)) {
            if (!is_file($this->entrypointsPath)) {
                throw new RuntimeException(sprintf('assets entrypoints file "%s" does not exist. Did you forget to build the assetss with npm or yarn?', $this->entrypointsPath));
            }

            try {
                $this->entrypointsData = json_decode(file_get_contents($this->entrypointsPath), true, flags: \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new RuntimeException(sprintf('Error parsing JSON from entrypoints file "%s": ', $this->entrypointsPath) . $e->getMessage(), previous: $e);
            }
        }

        if ($this->entrypointsData['isProd']) {
            if (isset($this->entrypointsData['assets'][$path])) {
                return $this->entrypointsData['assets'][$path];
            }
        } else {
            return $this->entrypointsData['viteServer']['origin'] . $this->entrypointsData['viteServer']['base'] . $path;
        }

        if ($this->strictMode) {
            $message = sprintf('assets "%s" not found in assets file "%s".', $path, $this->assetsPath);
            $alternatives = $this->findAlternatives($path, $this->assetsData);
            if (\count($alternatives) > 0) {
                $message .= sprintf(' Did you mean one of these? "%s".', implode('", "', $alternatives));
            }

            throw new AssetNotFoundException($message, $alternatives);
        }

        return null;
    }

    private function findAlternatives(string $path, ?array $assetsData): array
    {
        $path = strtolower($path);
        $alternatives = [];

        if (is_null($assetsData)) {
            return  $alternatives;
        }

        foreach ($assetsData as $key => $value) {
            $lev = levenshtein($path, strtolower($key));
            if ($lev <= \strlen($path) / 3 || false !== stripos($key, $path)) {
                $alternatives[$key] = isset($alternatives[$key]) ? min($lev, $alternatives[$key]) : $lev;
            }

            $lev = levenshtein($path, strtolower($value));
            if ($lev <= \strlen($path) / 3 || false !== stripos($key, $path)) {
                $alternatives[$key] = isset($alternatives[$key]) ? min($lev, $alternatives[$key]) : $lev;
            }
        }

        asort($alternatives);

        return array_keys($alternatives);
    }
}
