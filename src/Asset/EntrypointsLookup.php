<?php

namespace Pentatrion\ViteBundle\Asset;

class EntrypointsLookup
{
    private $entriesData;
    private $fileExist = false;
    private $isProd;
    private $viteServer = null;
    private $legacy = false;

    public function __construct($entrypointsFilePath)
    {
        if (!file_exists($entrypointsFilePath)) {
            return;
        }

        $fileInfos = json_decode(file_get_contents($entrypointsFilePath), true);

        if (!isset($fileInfos['isProd'], $fileInfos['entryPoints'], $fileInfos['viteServer'])) {
            return;
        }

        $this->fileExist = true;

        $this->isProd = $fileInfos['isProd'];
        $this->entriesData = $fileInfos['entryPoints'];
        if (!$this->isProd) {
            $this->viteServer = $fileInfos['viteServer'];
        } elseif (isset($fileInfos['legacy']) && $fileInfos['legacy']) { // only checked on prod.
            $this->legacy = true;
        }
    }

    public function isLegacyPluginEnabled()
    {
        return $this->legacy;
    }

    public function hasFile()
    {
        return $this->fileExist;
    }

    public function isProd()
    {
        return $this->isProd;
    }

    public function getViteServer()
    {
        return $this->viteServer;
    }

    public function getJSFiles($entryName)
    {
        return $this->entriesData[$entryName]['js'] ?? [];
    }

    public function getCSSFiles($entryName)
    {
        return $this->entriesData[$entryName]['css'] ?? [];
    }

    public function getJavascriptDependencies($entryName)
    {
        return $this->entriesData[$entryName]['preload'] ?? [];
    }

    public function hasLegacy($entryName)
    {
        return isset($this->entriesData[$entryName]['legacy']) && false !== $this->entriesData[$entryName]['legacy'];
    }

    public function getLegacyJSFile($entryName)
    {
        $legacyEntryName = $this->entriesData[$entryName]['legacy'];

        return $this->entriesData[$legacyEntryName]['js'][0];
    }
}
