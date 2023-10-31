<?php

namespace Pentatrion\ViteBundle\Asset;

use Pentatrion\ViteBundle\Exception\EntrypointNotFoundException;

class EntrypointsLookup
{
    private bool $throwOnMissingEntry;
    private array $fileInfos;

    public function __construct(string $publicPath, array $config, bool $throwOnMissingEntry)
    {
        $this->throwOnMissingEntry = $throwOnMissingEntry;
        $entrypointsPath = $publicPath.$config['base'].'entrypoints.json';

        $this->fileInfos = [
            'entrypointsPath' => $entrypointsPath,
            'content' => null,
            'fileExists' => file_exists($entrypointsPath),
        ];
    }

    public function hasFile(): bool
    {
        if (!isset($this->fileInfos)) {
            return false;
        }

        return $this->fileInfos['fileExists'];
    }

    private function getFileContent(): array
    {
        if (!isset($this->fileInfos['content'])) {
            if (!$this->fileInfos['fileExists']) {
                throw new \Exception('entrypoints.json not found at '.$this->fileInfos['entrypointsPath']);
            }
            $content = json_decode(file_get_contents($this->fileInfos['entrypointsPath']), true);
            if (!array_key_exists('entryPoints', $content)
             || !array_key_exists('viteServer', $content)
             || !array_key_exists('base', $content)
            ) {
                throw new \Exception($this->fileInfos['entrypointsPath'].' : entryPoints, base or viteServer not exists');
            }

            $this->fileInfos['content'] = $content;
        }

        return $this->fileInfos['content'];
    }

    public function getFileHash(string $filePath): ?string
    {
        $infos = $this->getFileContent();

        if (is_null($infos['metadatas']) || !array_key_exists($filePath, $infos['metadatas'])) {
            return null;
        }

        return $infos['metadatas'][$filePath]['hash'];
    }

    public function isLegacyPluginEnabled(): bool
    {
        $infos = $this->getFileContent();

        return array_key_exists('legacy', $infos) && true === $infos['legacy'];
    }

    public function isBuild(): bool
    {
        return null === $this->getFileContent()['viteServer'];
    }

    public function getViteServer()
    {
        return $this->getFileContent()['viteServer'];
    }

    public function getBase()
    {
        return $this->getFileContent()['base'];
    }

    public function getJSFiles($entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['js'] ?? [];
    }

    public function getCSSFiles($entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['css'] ?? [];
    }

    public function getJavascriptDependencies($entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['preload'] ?? [];
    }

    public function getJavascriptDynamicDependencies($entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['dynamic'] ?? [];
    }

    public function hasLegacy($entryName): bool
    {
        $this->throwIfEntrypointIsMissing($entryName);

        $entryInfos = $this->getFileContent();

        return isset($entryInfos['entryPoints'][$entryName]['legacy']) && false !== $entryInfos['entryPoints'][$entryName]['legacy'];
    }

    public function getLegacyJSFile($entryName): string
    {
        $this->throwIfEntrypointIsMissing($entryName);

        $entryInfos = $this->getFileContent();

        $legacyEntryName = $entryInfos['entryPoints'][$entryName]['legacy'];

        return $entryInfos['entryPoints'][$legacyEntryName]['js'][0];
    }

    private function throwIfEntrypointIsMissing(string $entryName): void
    {
        if (!$this->throwOnMissingEntry) {
            return;
        }

        if (!array_key_exists($entryName, $this->getFileContent()['entryPoints'])) {
            $keys = array_keys($this->getFileContent()['entryPoints']);
            $entryPointKeys = join(', ', array_map(function ($key) { return "'$key'"; }, $keys));
            throw new EntrypointNotFoundException(sprintf("Entry '%s' not present in the entrypoints file. Defined entrypoints are %s", $entryName, $entryPointKeys));
        }
    }
}
