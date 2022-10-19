<?php

namespace Pentatrion\ViteBundle\Asset;

class EntrypointRenderer
{
    private $entrypointsLookup;
    private $tagRenderer;

    private $hasReturnedViteClient = false;

    public function __construct(EntrypointsLookup $entrypointsLookup, TagRenderer $tagRenderer)
    {
        $this->entrypointsLookup = $entrypointsLookup;
        $this->tagRenderer = $tagRenderer;
    }

    public function renderScripts(string $entryName, array $options = [])
    {
        if (!$this->entrypointsLookup->hasFile()) {
            return '';
        }

        $scriptTags = [];
        if (!$this->entrypointsLookup->isProd()) {
            $viteServer = $this->entrypointsLookup->getViteServer();

            if (!$this->hasReturnedViteClient) {
                $scriptTags[] = $this->tagRenderer->renderScriptFile($viteServer['origin'].$viteServer['base'].'@vite/client', [], false);
                if (isset($options['dependency']) && 'react' === $options['dependency']) {
                    $scriptTags[] = $this->tagRenderer->renderReactRefreshInline($viteServer['origin'].$viteServer['base']);
                }
                $this->hasReturnedViteClient = true;
            }
        }
        foreach ($this->entrypointsLookup->getJSFiles($entryName) as $fileName) {
            $scriptTags[] = $this->tagRenderer->renderScriptFile($fileName, $options['attr'] ?? []);
        }

        return implode('', $scriptTags);
    }

    public function renderLinks(string $entryName, array $options = [])
    {
        if (!$this->entrypointsLookup->hasFile()) {
            return '';
        }

        $linkTags = [];
        foreach ($this->entrypointsLookup->getCSSFiles($entryName) as $fileName) {
            $linkTags[] = $this->tagRenderer->renderLinkStylesheet($fileName, $options['attr'] ?? []);
        }

        if ($this->entrypointsLookup->isProd()) {
            foreach ($this->entrypointsLookup->getJavascriptDependencies($entryName) as $fileName) {
                $linkTags[] = $this->tagRenderer->renderLinkPreload($fileName);
            }
        }

        return implode('', $linkTags);
    }
}
