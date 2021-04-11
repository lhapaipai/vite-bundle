<?php

namespace Pentatrion\ViteBundle\Asset;

class EntrypointRenderer
{
  private $manifestLookup;
  private $tagRenderer;

  private $publicPath;
  private $urlServer;
  private $hasReturnedViteClient = false;

  public function __construct(ManifestLookup $manifestLookup, TagRenderer $tagRenderer, $publicPath, $urlServer)
  {
    $this->manifestLookup = $manifestLookup;
    $this->tagRenderer = $tagRenderer;
    $this->publicPath = $publicPath;
    $this->urlServer = $urlServer;
  }

  public function renderScripts(string $entryName, array $options = [])
  {
    $scriptTags = [];
    if ($this->manifestLookup->isProd()) {
      foreach ($this->manifestLookup->getJSFiles($entryName) as $fileName) {
        $scriptTags[] = $this->tagRenderer->renderScriptFile($fileName);
      }
    } else {
      if (!$this->hasReturnedViteClient) {
        $scriptTags[] = $this->tagRenderer->renderScriptFile($this->urlServer.$this->publicPath.'@vite/client');
        if (isset($options['dependency']) && $options['dependency'] === 'react') {
          $scriptTags[] = $this->tagRenderer->renderReactRefreshInline();
        }
        $this->hasReturnedViteClient = true;
      }
      $scriptTags[] = $this->tagRenderer->renderScriptFile($this->urlServer.$this->publicPath.$entryName);
    }
    return implode('', $scriptTags);
  }

  public function renderLinks(string $entryName)
  {
    if (!$this->manifestLookup->isProd()) {
      return '';
    }

    $linkTags = [];
    foreach ($this->manifestLookup->getCSSFiles($entryName) as $fileName) {
      $linkTags[] = $this->tagRenderer->renderLinkStylesheet($fileName);
    }
    foreach ($this->manifestLookup->getJavascriptDependencies($entryName) as $fileName) {
      $linkTags[] = $this->tagRenderer->renderLinkPreload($fileName);
    }
    return implode('', $linkTags);
  }

}