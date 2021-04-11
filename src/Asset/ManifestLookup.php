<?php

namespace Pentatrion\ViteBundle\Asset;

class ManifestLookup
{
  private $manifestPath;
  private $publicPath;

  private $entriesData;
  private $isProd;

  public function __construct($manifestPath, $publicPath)
  {
    $this->manifestPath = $manifestPath;
    $this->publicPath = $publicPath;

    $this->isProd = file_exists($this->manifestPath);
    if ($this->isProd) {
      $this->entriesData = json_decode(file_get_contents($this->manifestPath), true);
    }
  }

  public function isProd() {
    return $this->isProd;
  }

  public function getJSFiles($entryName)
  {
    if ($this->isProd && isset($this->entriesData[$entryName])) {
      return [$this->publicPath.$this->entriesData[$entryName]['file']];
    } else {
      return [];
    }

    // if ($this->isProd && !isset($this->entriesData[$entryName])) {
    //   return [];
    // }
    // if ($this->isProd) {
    //   return [$this->entriesData[$entryName]['file']];
    // } else {
    //   if (!$this->hasReturnedViteClient) {
    //     $files = [$this->urlServer.'@vite/client'];
    //     $this->hasReturnedViteClient = true;
    //   } else {
    //     $files = [];
    //   }
    //   $files[] = $this->urlServer.$entryName;
    //   return $files;
    // }
  }

  public function getCSSFiles($entryName)
  {
    if ($this->isProd && isset($this->entriesData[$entryName])) {
      $files = [];
      foreach ($this->entriesData[$entryName]['css'] as $file) {
        $files[] = $this->publicPath.$file;
      }
      return $files;
    } else {
      return [];
    }
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