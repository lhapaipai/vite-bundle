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

    public function checkAndInsertLegacyPolyfill(&$content, $buildName)
    {
        $viteServer = $this->entrypointsLookup->getViteServer($buildName);

        if (
            false === $viteServer
            && $this->entrypointsLookup->isLegacyPluginEnabled($buildName)
            && !$this->hasReturnedViteLegacyScripts
        ) {
            $content[] = $this->tagRenderer->renderLegacyCheckInline();
            foreach ($this->entrypointsLookup->getJSFiles('polyfills-legacy', $buildName) as $fileName) {
                $content[] = $this->tagRenderer->renderScriptFile([
                    'src' => $fileName,
                    'nomodule' => true,
                    'crossorigin' => true,
                    'id' => 'vite-legacy-polyfill',
                ], '', $buildName, false);
            }
            $this->hasReturnedViteLegacyScripts = true;
        }
    }

    public function renderScripts(string $entryName, array $options = [], $buildName = null)
    {
        if (!$this->entrypointsLookup->hasFile($buildName)) {
            return '';
        }

        $content = [];
        $viteServer = $this->entrypointsLookup->getViteServer($buildName);
        if (false !== $viteServer) {
            if (!isset($this->returnedViteClients[$buildName])) {
                $content[] = $this->tagRenderer->renderScriptFile([
                    'src' => $viteServer['origin'].$viteServer['base'].'@vite/client',
                    'type' => 'module',
                ], '', null, false);
                if (isset($options['dependency']) && 'react' === $options['dependency']) {
                    $content[] = $this->tagRenderer->renderReactRefreshInline($viteServer['origin'].$viteServer['base']);
                }
                $this->returnedViteClients[$buildName] = true;
            }
        }

        $this->checkAndInsertLegacyPolyfill($content, $buildName);

        foreach ($this->entrypointsLookup->getJSFiles($entryName, $buildName) as $fileName) {
            $content[] = $this->tagRenderer->renderScriptFile(array_merge([
                'src' => $fileName,
                'type' => 'module',
            ], $options['attr'] ?? []), '', $buildName, true);
        }

        if ($this->entrypointsLookup->hasLegacy($entryName, $buildName)) {
            $id = self::pascalToKebab("vite-legacy-entry-$entryName");

            $content[] = $this->tagRenderer->renderScriptFile([
                'data-src' => $this->entrypointsLookup->getLegacyJSFile($entryName, $buildName),
                'id' => $id,
                'nomodule' => true,
                'crossorigin' => true,
                'class' => 'vite-legacy-entry',
            ], $this->tagRenderer->getSystemJSInlineCode($id), $buildName);
        }

        return implode('', $content);
    }

    public function renderLinks(string $entryName, array $options = [], $buildName = null)
    {
        if (!$this->entrypointsLookup->hasFile($buildName)) {
            return '';
        }

        $content = [];

        $this->checkAndInsertLegacyPolyfill($content, $buildName);

        foreach ($this->entrypointsLookup->getCSSFiles($entryName, $buildName) as $fileName) {
            $content[] = $this->tagRenderer->renderLinkStylesheet($fileName, $options['attr'] ?? [], $buildName);
        }

        if ($this->entrypointsLookup->isProd($buildName)) {
            foreach ($this->entrypointsLookup->getJavascriptDependencies($entryName, $buildName) as $fileName) {
                $content[] = $this->tagRenderer->renderLinkPreload($fileName, $buildName);
            }
        }

        return implode('', $content);
    }

    public static function pascalToKebab(string $str)
    {
        return strtolower(preg_replace('/[A-Z]/', '-\\0', lcfirst($str)));
    }
}
