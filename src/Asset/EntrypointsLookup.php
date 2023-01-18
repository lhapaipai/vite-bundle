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
            $entryPointsPath = $publicPath.$build['base'].'entrypoints.json';
            $this->buildsInfos[$buildName] = [
                'entryPointsPath' => $entryPointsPath,
                'infos' => null,
                'fileExists' => file_exists($entryPointsPath),
            ];
        }
    }

    private function getInfos($buildName = null)
    {
        if (is_null($buildName)) {
            $buildName = $this->defaultBuild;
        }

        if (!isset($this->buildsInfos[$buildName]['infos'])) {
            if (!$this->buildsInfos[$buildName]['fileExists']) {
                throw new \Exception('entrypoints.json for '.$buildName.' not exists');
            }
            $entrypointsFilePath = $this->buildsInfos[$buildName]['entryPointsPath'];
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
        if (!isset($this->buildsInfos[$buildName])) {
            return false;
        }

        return $this->buildsInfos[$buildName]['fileExists'];
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
