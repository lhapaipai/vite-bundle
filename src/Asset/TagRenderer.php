<?php

namespace Pentatrion\ViteBundle\Asset;

class TagRenderer
{
  private $manifestLookup;

  public function __construct($manifestLookup)
  {
    $this->manifestLookup = $manifestLookup;
  }

  public function renderViteScriptTags(string $entryName)
  {
    $scriptTags = [];
    foreach ($this->manifestLookup->getJavascriptFiles($entryName) as $filename) {
      $attributes = [
        'src' => $filename,
        'type' => 'module'
      ];
      $scriptTags[] = sprintf(
        '<script %s></script>',
        $this->convertArrayToAttributes($attributes)  
      );
    }
    return implode('', $scriptTags);
  }

  public function renderViteLinkTags(string $entryName)
  {
    $linkTags = [];
    foreach ($this->manifestLookup->getCSSFiles($entryName) as $filename) {
      $attributes = [
        'rel' => 'stylesheet',
        'href' => $filename
      ];
      $linkTags[] = sprintf(
        '<link %s>',
        $this->convertArrayToAttributes($attributes)  
      );
    }
    foreach ($this->manifestLookup->getJavascriptDependencies($entryName) as $filename) {
      $attributes = [
        'rel' => 'modulepreload',
        'href' => $filename
      ];
      $linkTags[] = sprintf(
        '<link %s>',
        $this->convertArrayToAttributes($attributes)  
      );
    }
    return implode('', $linkTags);
  }

  private function convertArrayToAttributes(array $attributes): string
  {
    return implode(' ', array_map(
      function ($key, $value) {
          return sprintf('%s="%s"', $key, htmlentities($value));
      },
      array_keys($attributes),
      $attributes
    ));
  }
}