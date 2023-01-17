<?php

namespace Pentatrion\ViteBundle\Asset;

class EntrypointsLookup
{
    private $defaultBuild;
    private $buildsInfos = [];

    public function __construct($publicPath, $defaultBuild, $builds)
    {
        $this->defaultBuild = $defaultBuild;

        foreach ($builds as $buildName => $build) {
            $this->buildsInfos[$buildName] = [
                'entryPointsPath' => $publicPath.$build['base'].'entrypoints.json',
                'infos' => null,
            ];
        }
    }

    private function getInfos($buildName = null)
    {
        if (is_null($buildName)) {
            $buildName = $this->defaultBuild;
        }

        if (!isset($this->buildsInfos[$buildName]['infos'])) {
            $entrypointsFilePath = $this->buildsInfos[$buildName]['entryPointsPath'];
            if (!file_exists($entrypointsFilePath)) {
                throw new \Exception($entrypointsFilePath.' not exists');
            }
            $fileInfos = json_decode(file_get_contents($entrypointsFilePath), true);
            if (!isset($fileInfos['isProd'], $fileInfos['entryPoints'], $fileInfos['viteServer'])) {
                throw new \Exception($entrypointsFilePath.' : isProd, entryPoints or viteServer not exists');
            }

            $this->buildsInfos[$buildName]['infos'] = $fileInfos;
        }

        return $this->buildsInfos[$buildName]['infos'];
    }

    public function isLegacyPluginEnabled($buildName = null)
    {
        return $this->getInfos($buildName)['legacy'];
    }

    public function hasFile($buildName = null)
    {
        if (is_null($buildName)) {
            $buildName = $this->defaultBuild;
        }

        return file_exists($this->buildsInfos[$buildName]['entryPointsPath']);
    }

    public function isProd($buildName = null)
    {
        return $this->getInfos($buildName)['isProd'];
    }

    public function getViteServer($buildName = null)
    {
        return $this->getInfos($buildName)['viteServer'];
    }

    public function getJSFiles($entryName, $buildName = null)
    {
        return $this->getInfos($buildName)['entryPoints'][$entryName]['js'] ?? [];
    }

    public function getCSSFiles($entryName, $buildName = null)
    {
        return $this->getInfos($buildName)['entryPoints'][$entryName]['css'] ?? [];
    }

    public function getJavascriptDependencies($entryName, $buildName = null)
    {
        return $this->getInfos($buildName)['entryPoints'][$entryName]['preload'] ?? [];
    }

    public function hasLegacy($entryName, $buildName = null)
    {
        $entryInfos = $this->getInfos($buildName);

        return isset($entryInfos['entryPoints'][$entryName]['legacy']) && false !== $entryInfos['entryPoints'][$entryName]['legacy'];
    }

    public function getLegacyJSFile($entryName, $buildName = null)
    {
        $entryInfos = $this->getInfos($buildName);

        $legacyEntryName = $entryInfos['entryPoints'][$entryName]['legacy'];

        return $entryInfos['entryPoints'][$legacyEntryName]['js'][0];
    }
}
