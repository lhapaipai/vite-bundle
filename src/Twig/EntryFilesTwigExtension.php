<?php

namespace Pentatrion\ViteBundle\Twig;

use Pentatrion\ViteBundle\Asset\EntrypointRenderer;
use Pentatrion\ViteBundle\Asset\EntrypointsLookup;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EntryFilesTwigExtension extends AbstractExtension
{
    private EntrypointRenderer $entrypointRenderer;
    private EntrypointsLookup $entrypointsLookup;

    public function __construct(EntrypointRenderer $entrypointRenderer, EntrypointsLookup $entrypointsLookup)
    {
        $this->entrypointRenderer = $entrypointRenderer;
        $this->entrypointsLookup = $entrypointsLookup;
    }

    public function getFunctions(): array
    {
        return [
          new TwigFunction('vite_entry_script_tags', [$this, 'renderViteScriptTags'], ['is_safe' => ['html']]),
          new TwigFunction('vite_entry_link_tags', [$this, 'renderViteLinkTags'], ['is_safe' => ['html']]),
          new TwigFunction('vite_entry_js_files', [$this, 'getJSFiles']),
          new TwigFunction('vite_entry_css_files', [$this, 'getCSSFiles']),
        ];
    }

    public function renderViteScriptTags(string $entryName, array $options = [], $buildName = null): string
    {
        return $this->entrypointRenderer->renderScripts($entryName, $options, $buildName);
    }

    public function renderViteLinkTags(string $entryName, array $options = [], $buildName = null): string
    {
        return $this->entrypointRenderer->renderLinks($entryName, $options, $buildName);
    }

    public function getJSFiles(string $entryName, ?string $buildName = null): array
    {
        return $this->entrypointsLookup->getJSFiles($entryName, $buildName);
    }

    public function getCSSFiles(string $entryName, ?string $buildName = null): array
    {
        return $this->entrypointsLookup->getCSSFiles($entryName, $buildName);
    }
}
