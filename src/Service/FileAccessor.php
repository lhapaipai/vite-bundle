<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\Exception\EntrypointsFileNotFoundException;
use Pentatrion\ViteBundle\Exception\VersionMismatchException;
use Pentatrion\ViteBundle\PentatrionViteBundle;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @phpstan-type EntryPoint array{
 *  assets?: array<string>,
 *  js?: array<string>,
 *  css?: array<string>,
 *  preload?: array<string>,
 *  dynamic?: array<string>,
 *  legacy: false|string,
 * }
 * @phpstan-type FileMetadatas array{
 *  hash: string|null
 * }
 * @phpstan-type EntryPointsFile array{
 *  base: string,
 *  entryPoints: array<string, EntryPoint>,
 *  legacy: bool,
 *  metadatas: array<string, FileMetadatas>,
 *  version: array{0: string, 1: int, 2: int, 3: int},
 *  viteServer: string|null
 * }
 * @phpstan-type ManifestEntry array{
 *  file: string,
 *  src?: string,
 *  isDynamicEntry?: bool,
 *  isEntry?: bool,
 *  imports?: array<string>,
 *  css?: array<string>
 * }
 * @phpstan-type ManifestFile array<string, ManifestEntry>
 */
class FileAccessor
{
    public const ENTRYPOINTS = 'entrypoints';
    public const MANIFEST = 'manifest';

    public const FILES = [
        self::ENTRYPOINTS => 'entrypoints.json',
        self::MANIFEST => 'manifest.json',
    ];

    /** @var array<string, array<string, EntryPointsFile|ManifestFile>> */
    private array $content;

    /** @param array<string, array<mixed>> $configs */
    public function __construct(
        private string $publicPath,
        private array $configs,
        private ?CacheItemPoolInterface $cache = null,
    ) {
    }

    public function hasFile(string $configName, string $fileType): bool
    {
        $basePath = $this->publicPath.$this->configs[$configName]['base'];

        return file_exists($basePath.'.vite/'.self::FILES[$fileType]) || file_exists($basePath.self::FILES[$fileType]);
    }

    /**
     * @param key-of<FileAccessor::FILES> $fileType
     *
     * @phpstan-return ($fileType is 'entrypoints' ? EntryPointsFile : ManifestFile)
     */
    public function getData(string $configName, string $fileType): array
    {
        $cacheItem = null;
        if (!isset($this->content[$configName][$fileType])) {
            if ($this->cache) {
                $cacheItem = $this->cache->getItem("$configName.$fileType");

                if ($cacheItem->isHit()) {
                    /** @var EntryPointsFile|ManifestFile $data */
                    $data = $cacheItem->get();
                    $this->content[$configName][$fileType] = $data;
                }
            }

            if (!isset($this->content[$configName][$fileType])) {
                $filePath = $this->publicPath.$this->configs[$configName]['base'].self::FILES[$fileType];
                $basePath = $this->publicPath.$this->configs[$configName]['base'];

                if (($scheme = parse_url($filePath, \PHP_URL_SCHEME)) && str_starts_with($scheme, 'http')) {
                    throw new \Exception('You can\'t use a remote manifest with pentatrion/vite-bundle');
                }

                if (file_exists($basePath.'.vite/'.self::FILES[$fileType])) {
                    $filePath = $basePath.'.vite/'.self::FILES[$fileType];
                } elseif (file_exists($basePath.self::FILES[$fileType])) {
                    $filePath = $basePath.self::FILES[$fileType];
                } else {
                    throw new EntrypointsFileNotFoundException("$fileType not found at $basePath. Did you forget configure your `build_directory` in pentatrion_vite.yml");
                }

                /** @var EntryPointsFile|ManifestFile $content */
                $content = json_decode((string) file_get_contents($filePath), true, flags: \JSON_THROW_ON_ERROR);

                if (self::ENTRYPOINTS === $fileType) {
                    /** @var EntryPointsFile $content */
                    $pluginVersion = $content['version'];
                    // VERSION[1] => Major version number
                    if (PentatrionViteBundle::VERSION[1] !== $pluginVersion[1]) {
                        throw new VersionMismatchException('your vite-plugin-symfony is outdated, run : npm install vite-plugin-symfony@^'.PentatrionViteBundle::VERSION[1]);
                    }
                }

                if ($this->cache && null !== $cacheItem) {
                    $this->cache->save($cacheItem->set($content));
                }

                $this->content[$configName][$fileType] = $content;
            }
        }

        return $this->content[$configName][$fileType];
    }
}
