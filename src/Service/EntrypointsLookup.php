<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\Exception\EntrypointNotFoundException;

/**
 * @phpstan-import-type EntryPointsFile from FileAccessor
 */
class EntrypointsLookup
{
    /** @var EntryPointsFile|null */
    private ?array $fileContent = null;

    public function __construct(
        private FileAccessor $fileAccessor,
        private string $configName,
        // for cache to retrieve content : configName is cache key
        private bool $throwOnMissingEntry = false,
    ) {
    }

    public function hasFile(): bool
    {
        return $this->fileAccessor->hasFile($this->configName, FileAccessor::ENTRYPOINTS);
    }

    /**
     * @phpstan-return EntryPointsFile
     */
    private function getFileContent(): array
    {
        if (is_null($this->fileContent)) {
            $this->fileContent = $this->fileAccessor->getData($this->configName, FileAccessor::ENTRYPOINTS);

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

        /* @phpstan-ignore-next-line always evaluate to false but can be possible with legacy vite-plugin-symfony versions */
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

    public function hasModernPolyfillsEntry(): bool
    {
        return isset($this->getFileContent()['entryPoints']['polyfills']);
    }

    /**
     * @return array<string>
     */
    public function getJSFiles(string $entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['js'] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getCSSFiles(string $entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['css'] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getJavascriptDependencies(string $entryName): array
    {
        $this->throwIfEntrypointIsMissing($entryName);

        return $this->getFileContent()['entryPoints'][$entryName]['preload'] ?? [];
    }

    /**
     * @return array<string>
     */
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

        if (!is_string($legacyEntryName)) {
            throw new \Exception("Entrypoint doesn't have legacy entrypoint");
        }

        $legacyEntry = $entryInfos['entryPoints'][$legacyEntryName];

        if (!isset($legacyEntry['js'][0])) {
            throw new \Exception("Entrypoint legacy doesn't have js script");
        }

        return $legacyEntry['js'][0];
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
