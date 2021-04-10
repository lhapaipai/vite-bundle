<?php

namespace Pentatrion\ViteBundle\Twig;

use Pentatrion\ViteBundle\Asset\TagRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EntryFilesTwigExtension extends AbstractExtension
{
  private $tagRenderer;

  public function __construct(TagRenderer $tagRenderer)
  {
    $this->tagRenderer = $tagRenderer;
  }

  public function getFunctions()
  {
    return [
      new TwigFunction('vite_entry_script_tags', [$this, 'renderViteScriptTags'], ['is_safe' => ['html']]),
      new TwigFunction('vite_entry_link_tags', [$this, 'renderViteLinkTags'], ['is_safe' => ['html']]),
    ];
  }

  public function renderViteScriptTags(string $entryName): string
  {
    return $this->tagRenderer->renderViteScriptTags($entryName);
  }

  public function renderViteLinkTags(string $entryName): string
  {
    return $this->tagRenderer->renderViteLinkTags($entryName);
  }
}
