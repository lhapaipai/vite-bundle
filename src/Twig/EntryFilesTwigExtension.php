<?php

namespace Pentatrion\ViteBundle\Twig;

use Pentatrion\ViteBundle\Asset\EntrypointRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EntryFilesTwigExtension extends AbstractExtension
{
    private $entrypointRenderer;

    public function __construct(EntrypointRenderer $entrypointRenderer)
    {
        $this->entrypointRenderer = $entrypointRenderer;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_entry_script_tags', [$this, 'renderViteScriptTags'], ['is_safe' => ['html']]),
            new TwigFunction('vite_entry_link_tags', [$this, 'renderViteLinkTags'], ['is_safe' => ['html']]),
            new TwigFunction('vite_mode', [$this, 'getViteMode']),
        ];
    }

    public function getViteMode(string $buildName = null): ?string
    {
        return $this->entrypointRenderer->getMode($buildName);
    }

    public function renderViteScriptTags(string $entryName, array $options = [], $buildName = null): string
    {
        return $this->entrypointRenderer->renderScripts($entryName, $options, $buildName);
    }

    public function renderViteLinkTags(string $entryName, array $options = [], $buildName = null): string
    {
        return $this->entrypointRenderer->renderLinks($entryName, $options, $buildName);
    }
}
