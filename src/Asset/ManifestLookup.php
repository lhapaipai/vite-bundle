<?php

namespace Pentatrion\ViteBundle\Asset;

class ManifestLookup
{
  private $manifestPath;
  private $entriesData;
  private $isProd;
  private $assetsWebPath;
  private $hasReturnedViteClient = false;

  public function __construct($manifestPath, $assetsWebPath)
  {
    $this->manifestPath = $manifestPath;
    $this->assetsWebPath = $assetsWebPath;

    $this->isProd = file_exists($this->manifestPath);
    if ($this->isProd) {
      $this->entriesData = json_decode(file_get_contents($this->manifestPath), true);
    }
  }

  public function getJavascriptFiles($entryName)
  {
    if ($this->isProd && !isset($this->entriesData[$entryName])) {
      return [];
    }
    if ($this->isProd) {
      return [$this->assetsWebPath.$this->entriesData[$entryName]['file']];
    } else {
      if (!$this->hasReturnedViteClient) {
        $files = ['http://localhost:3000/assets/@vite/client'];
        $this->hasReturnedViteClient = true;
      } else {
        $files = [];
      }
      $files[] = 'http://localhost:3000/assets/'.$entryName;
      return $files;
    }
  }

  public function getCSSFiles($entryName)
  {
    if (!$this->isProd || !isset($this->entriesData[$entryName])) {
      return [];
    }

    $files = [];
    foreach ($this->entriesData[$entryName]['css'] as $file) {
      $files[] = $this->assetsWebPath.$file;
    }
    return $files;
  }

  public function getJavascriptDependencies($entryName)
  {
    if (!$this->isProd || !isset($this->entriesData[$entryName])) {
      return [];
    }
    $files = [];
    foreach ($this->entriesData[$entryName]['imports'] as $key) {
      $files[] = $this->assetsWebPath.$this->entriesData[$key]['file'];
    }
    return $files;
  }
}