<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\Exception\EntrypointNotFoundException;

class EntrypointsLookup
{
    private string $configName;
    private bool $throwOnMissingEntry;
    private FileAccessor $fileAccessor;

    private ?array $fileContent = null;

    public function __construct(
        FileAccessor $fileAccessor,
        string $configName, // for cache to retrieve content : configName is cache key
        bool $throwOnMissingEntry = false
    ) {
        $this->fileAccessor = $fileAccessor;
        $this->configName = $configName;
        $this->throwOnMissingEntry = $throwOnMissingEntry;
    }

    public function hasFile(): bool
    {
        return $this->fileAccessor->hasFile($this->configName, 'entrypoints');
    }

    private function getFileContent(): array
    {
        if (is_null($this->fileContent)) {
            $this->fileContent = $this->fileAccessor->getData($this->configName, 'entrypoints');

            if (!array_key_exists('entryPoints', $this->fileContent)
            || !array_key_exists('viteServer', $this->fileContent)
            || !array_key_exists('base', $this->fileContent)
            ) {
                throw new \Exception("$this->configName entrypoints.json : entryPoints, base or viteServer not exists");
            }
        }

        return $this->fileContent;
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

    public function getViteServer(): ?string
    {
        return $this->getFileContent()['viteServer'];
    }

    public function getBase(): string
    {
        return $this->getFileContent()['base'];
    }

    public function getJSFiles(string $entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['js'] ?? [];
    }

    public function getCSSFiles(string $entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['css'] ?? [];
    }

    public function getJavascriptDependencies(string $entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['preload'] ?? [];
    }

    public function getJavascriptDynamicDependencies(string $entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['dynamic'] ?? [];
    }

    public function hasLegacy(string $entryName): bool
    {
        $this->throwIfEntrypointIsMissing($entryName);

        $entryInfos = $this->getFileContent();

        return isset($entryInfos['entryPoints'][$entryName]['legacy']) && false !== $entryInfos['entryPoints'][$entryName]['legacy'];
    }

    public function getLegacyJSFile(string $entryName): string
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
