<?php

namespace Pentatrion\ViteBundle\Asset;

use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Symfony\Component\Asset\Exception\RuntimeException;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;
use Symfony\Component\Routing\RouterInterface;

class ViteAssetVersionStrategy implements VersionStrategyInterface
{
    private string $publicPath;
    private array $configs;
    private $useAbsoluteUrl;
    private $router;

    private string $manifestPath;
    private string $entrypointsPath;
    private $manifestData;
    private $entrypointsData;
    private ?array $config = null;
    private bool $strictMode;
    private ?string $mode = null;

    public function __construct(
        string $publicPath,
        array $configs,
        string $defaultConfigName,
        bool $useAbsoluteUrl,
        RouterInterface $router = null,
        bool $strictMode = true
    ) {
        $this->publicPath = $publicPath;
        $this->configs = $configs;
        $this->strictMode = $strictMode;
        $this->useAbsoluteUrl = $useAbsoluteUrl;
        $this->router = $router;

        $this->setConfig($defaultConfigName);

        if (($scheme = parse_url($this->manifestPath, \PHP_URL_SCHEME)) && 0 === strpos($scheme, 'http')) {
            throw new \Exception('You can\'t use a remote manifest with ViteAssetVersionStrategy');
        }
    }

    public function setConfig(string $configName): void
    {
        $this->mode = null;
        $this->config = $this->configs[$configName];
        $this->manifestPath = $this->publicPath.$this->config['base'].'manifest.json';
        $this->entrypointsPath = $this->publicPath.$this->config['base'].'entrypoints.json';
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
        if (null === $this->mode) {
            if (!is_file($this->entrypointsPath)) {
                throw new RuntimeException(sprintf('assets entrypoints file "%s" does not exist. Did you forget configure your `build_dir` in pentatrion_vite.yml?', $this->entrypointsPath));
            }

            if (is_file($this->manifestPath)) {
                // when vite server is running manifest file doesn't exists
                $this->mode = 'build';
                try {
                    $this->manifestData = json_decode(file_get_contents($this->manifestPath), true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new RuntimeException(sprintf('Error parsing JSON from entrypoints file "%s": ', $this->manifestPath).$e->getMessage(), 0, $e);
                }
            } else {
                $this->mode = 'dev';
                try {
                    $this->entrypointsData = json_decode(file_get_contents($this->entrypointsPath), true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new RuntimeException(sprintf('Error parsing JSON from entrypoints file "%s": ', $this->manifestPath).$e->getMessage(), 0, $e);
                }
            }
        }

        if ('build' === $this->mode) {
            if (isset($this->manifestData[$path])) {
                return $this->completeURL($this->config['base'].$this->manifestData[$path]['file']);
            }
        } else {
            return $this->entrypointsData['viteServer'].$this->entrypointsData['base'].$path;
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
