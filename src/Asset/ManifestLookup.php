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
  }

  public function getCSSFiles($entryName)
  {
    if ($this->isProd && isset($this->entriesData[$entryName])) {
      $entry = $this->entriesData[$entryName];
      $files = [];
      if (isset($entry['css'])) {
        foreach ($entry['css'] as $file) {
          $files[] = $this->publicPath.$file;
        }
      }
      if (isset($entry['imports'])) {
        foreach($entry['imports'] as $importName) {
          $importEntry = $this->entriesData[$importName];
          if (isset($importEntry['css'])) {
            foreach ($importEntry['css'] as $file) {
              $files[] = $this->publicPath.$file;
            }
          }
        }
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