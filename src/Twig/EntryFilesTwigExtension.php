<?php

namespace Pentatrion\ViteBundle\Twig;

use Pentatrion\ViteBundle\Service\EntrypointRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @phpstan-type ViteEntryScriptTagsOptions array{
 *  absolute_url?: bool,
 *  attr?: array<string, bool|string|null>,
 *  dependency?: "react"|null
 * }
 * @phpstan-type ViteEntryLinkTagsOptions array{
 *  absolute_url?: bool,
 *  attr?: array<string, bool|string|null>,
 *  preloadDynamicImports?: bool
 * }
 */
class EntryFilesTwigExtension extends AbstractExtension
{
    public function __construct(private EntrypointRenderer $entrypointRenderer)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_entry_script_tags', [$this, 'renderViteScriptTags'], ['is_safe' => ['html']]),
            new TwigFunction('vite_entry_link_tags', [$this, 'renderViteLinkTags'], ['is_safe' => ['html']]),
            new TwigFunction('vite_mode', [$this, 'getViteMode']),
        ];
    }

    public function getViteMode(?string $configName = null): ?string
    {
        return $this->entrypointRenderer->getMode($configName);
    }

    /**
     * @param ViteEntryScriptTagsOptions $options
     */
    public function renderViteScriptTags(string $entryName, array $options = [], ?string $configName = null): string
    {
        return $this->entrypointRenderer->renderScripts($entryName, $options, $configName);
    }

    /**
     * @param ViteEntryLinkTagsOptions $options
     */
    public function renderViteLinkTags(string $entryName, array $options = [], ?string $configName = null): string
    {
        return $this->entrypointRenderer->renderLinks($entryName, $options, $configName);
    }
}
