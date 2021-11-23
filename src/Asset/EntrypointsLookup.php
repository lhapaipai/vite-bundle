<?php

namespace Pentatrion\ViteBundle\Asset;

class EntrypointsLookup
{

  private $entriesData;
  private $isProd;
  private $viteServer = null;

  public function __construct($entrypointsFilePath)
  {
    $fileInfos = json_decode(file_get_contents($entrypointsFilePath), true);

    $this->isProd = $fileInfos['isProd'];
    $this->entriesData = $fileInfos['entryPoints'];
    if (!$this->isProd) {
      $this->viteServer = $fileInfos['viteServer'];
    }
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
    if (isset($this->entriesData[$entryName])) {
      return $this->entriesData[$entryName]['js'];
    } else {
      return [];
    }
  }

  public function getCSSFiles($entryName)
  {
    if (isset($this->entriesData[$entryName])) {
      return $this->entriesData[$entryName]['css'];
    } else {
      return [];
    }
  }

  public function getJavascriptDependencies($entryName)
  {
    if (isset($this->entriesData[$entryName])) {
      return $this->entriesData[$entryName]['preload'];
    } else {
      return [];
    }
  }
}
