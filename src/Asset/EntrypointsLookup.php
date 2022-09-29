<?php

namespace Pentatrion\ViteBundle\Asset;

class EntrypointsLookup
{
    private $entriesData;
    private $fileExist = false;
    private $isProd;
    private $viteServer = null;

    public function __construct($entrypointsFilePath)
    {
        if (!file_exists($entrypointsFilePath)) {
            return;
        }

        $fileInfos = json_decode(file_get_contents($entrypointsFilePath), true);

        $requiredKeysDiff = array_diff(['isProd', 'entryPoints', 'viteServer'], array_keys($fileInfos));

        if (\count($requiredKeysDiff) > 0) {
            return;
        }

        $this->fileExist = true;

        $this->isProd = $fileInfos['isProd'];
        $this->entriesData = $fileInfos['entryPoints'];
        if (!$this->isProd) {
            $this->viteServer = $fileInfos['viteServer'];
        }
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
}
