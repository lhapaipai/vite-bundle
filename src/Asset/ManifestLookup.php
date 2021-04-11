<?php

namespace Lhapaipai\ViteBundle\Asset;

class ManifestLookup
{
  private $manifestPath;
  private $entriesData;
  private $isProd;
  private $publicPath;
  private $urlServer;
  private $hasReturnedViteClient = false;

  public function __construct($manifestPath, $publicPath, $urlServer)
  {
    $this->manifestPath = $manifestPath;
    $this->publicPath = $publicPath;
    $this->urlServer = $urlServer;

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
      return [$this->publicPath.$this->entriesData[$entryName]['file']];
    } else {
      if (!$this->hasReturnedViteClient) {
        $files = [$this->urlServer.$this->publicPath.'@vite/client'];
        $this->hasReturnedViteClient = true;
      } else {
        $files = [];
      }
      $files[] = $this->urlServer.$this->publicPath.$entryName;
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
      $files[] = $this->publicPath.$file;
    }
    return $files;
  }

  public function getJavascriptDependencies($entryName)
  {
    if (
      !$this->isProd
      || !isset($this->entriesData[$entryName])
      || !isset($this->entriesData[$entryName]['imports'])) {
      return [];
    }
    $files = [];
    foreach ($this->entriesData[$entryName]['imports'] as $key) {
      $files[] = $this->publicPath.$this->entriesData[$key]['file'];
    }
    return $files;
  }
}