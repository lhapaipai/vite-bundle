<?php

namespace Pentatrion\ViteBundle\Asset;

class EntrypointRenderer
{
    private $entrypointsLookup;
    private $tagRenderer;

    private $returnedViteClients = [];
    private $hasReturnedViteLegacyScripts = false;

    public function __construct(EntrypointsLookup $entrypointsLookup, TagRenderer $tagRenderer)
    {
        $this->entrypointsLookup = $entrypointsLookup;
        $this->tagRenderer = $tagRenderer;
    }

    public function renderScripts(string $entryName, array $options = [], $buildName = null): string
    {
        if (!$this->entrypointsLookup->hasFile($buildName)) {
            return '';
        }

        $content = [];
        $viteServer = $this->entrypointsLookup->getViteServer($buildName);

        if (false !== $viteServer) {
            // vite server is active
            if (!isset($this->returnedViteClients[$buildName])) {
                $content[] = $this->tagRenderer->renderTag('script', [
                    'type' => 'module',
                    'src' => $viteServer['origin'].$viteServer['base'].'@vite/client',
                ]);
                if (isset($options['dependency']) && 'react' === $options['dependency']) {
                    $content[] = $this->tagRenderer->renderReactRefreshInline($viteServer['origin'].$viteServer['base']);
                }
                $this->returnedViteClients[$buildName] = true;
            }
        } elseif (
            $this->entrypointsLookup->isLegacyPluginEnabled($buildName)
            && !$this->hasReturnedViteLegacyScripts
        ) {
            /* legacy section when vite server is inactive */

            /* set or not the __vite_is_modern_browser variable */
            $content[] = $this->tagRenderer::DETECT_MODERN_BROWSER_CODE;

            /* if your browser understands the modules but not dynamic import,
             * load the legacy entrypoints
             */
            $content[] = $this->tagRenderer::DYNAMIC_FALLBACK_INLINE_CODE;

            /* Safari 10.1 supports modules, but does not support the `nomodule` attribute
             *  it will load <script nomodule> anyway */
            $content[] = $this->tagRenderer::SAFARI10_NO_MODULE_FIX;

            foreach ($this->entrypointsLookup->getJSFiles('polyfills-legacy', $buildName) as $fileName) {
                // normally only one js file
                $content[] = $this->tagRenderer->renderTag('script', [
                    'nomodule' => true,
                    'crossorigin' => true,
                    'src' => $fileName,
                    'id' => 'vite-legacy-polyfill',
                ]);
            }
            $this->hasReturnedViteLegacyScripts = true;
        }

        /* normal js scripts */
        foreach ($this->entrypointsLookup->getJSFiles($entryName, $buildName) as $fileName) {
            $attributes = array_merge([
                'type' => 'module',
                'src' => $fileName,
            ], $options['attr'] ?? []);
            $content[] = $this->tagRenderer->renderScriptFile($attributes, '', $buildName);
        }

        /* legacy js scripts */
        if ($this->entrypointsLookup->hasLegacy($entryName, $buildName)) {
            $id = self::pascalToKebab("vite-legacy-entry-$entryName");

            $content[] = $this->tagRenderer->renderScriptFile([
                'nomodule' => true,
                'data-src' => $this->entrypointsLookup->getLegacyJSFile($entryName, $buildName),
                'id' => $id,
                'crossorigin' => true,
                'class' => 'vite-legacy-entry',
            ], $this->tagRenderer->getSystemJSInlineCode($id), $buildName);
        }

        return implode(PHP_EOL, $content);
    }

    public function renderLinks(string $entryName, array $options = [], $buildName = null): string
    {
        if (!$this->entrypointsLookup->hasFile($buildName)) {
            return '';
        }

        $content = [];

        foreach ($this->entrypointsLookup->getCSSFiles($entryName, $buildName) as $fileName) {
            $content[] = $this->tagRenderer->renderLinkStylesheet($fileName, $options['attr'] ?? [], $buildName);
        }

        if ($this->entrypointsLookup->isProd($buildName)) {
            foreach ($this->entrypointsLookup->getJavascriptDependencies($entryName, $buildName) as $fileName) {
                $content[] = $this->tagRenderer->renderLinkPreload($fileName, $buildName);
            }
        }

        return implode(PHP_EOL, $content);
    }

    public static function pascalToKebab(string $str): string
    {
        return strtolower(preg_replace('/[A-Z]/', '-\\0', lcfirst($str)));
    }
}
