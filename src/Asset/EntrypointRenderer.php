<?php

namespace Pentatrion\ViteBundle\Asset;

class EntrypointRenderer
{
    private $entrypointsLookup;
    private $tagRenderer;

    private $hasReturnedViteClient = false;
    private $hasReturnedViteLegacyScripts = false;

    public function __construct(EntrypointsLookup $entrypointsLookup, TagRenderer $tagRenderer)
    {
        $this->entrypointsLookup = $entrypointsLookup;
        $this->tagRenderer = $tagRenderer;
    }

    public function checkAndInsertLegacyPolyfill(&$content)
    {
        if (
            $this->entrypointsLookup->isProd()
            && $this->entrypointsLookup->isLegacyPluginEnabled()
            && !$this->hasReturnedViteLegacyScripts
        ) {
            $content[] = $this->tagRenderer->renderLegacyCheckInline();
            foreach ($this->entrypointsLookup->getJSFiles('polyfills-legacy') as $fileName) {
                $content[] = $this->tagRenderer->renderScriptFile([
                    'src' => $fileName,
                    'nomodule' => true,
                    'crossorigin' => true,
                    'id' => 'vite-legacy-polyfill',
                ], '', false);
            }
            $this->hasReturnedViteLegacyScripts = true;
        }
    }

    public function renderScripts(string $entryName, array $options = [])
    {
        if (!$this->entrypointsLookup->hasFile()) {
            return '';
        }

        $content = [];
        if (!$this->entrypointsLookup->isProd()) {
            $viteServer = $this->entrypointsLookup->getViteServer();

            if (!$this->hasReturnedViteClient) {
                $content[] = $this->tagRenderer->renderScriptFile([
                    'src' => $viteServer['origin'].$viteServer['base'].'@vite/client',
                    'type' => 'module',
                ]);
                if (isset($options['dependency']) && 'react' === $options['dependency']) {
                    $content[] = $this->tagRenderer->renderReactRefreshInline($viteServer['origin'].$viteServer['base']);
                }
                $this->hasReturnedViteClient = true;
            }
        }

        $this->checkAndInsertLegacyPolyfill($content);

        foreach ($this->entrypointsLookup->getJSFiles($entryName) as $fileName) {
            $content[] = $this->tagRenderer->renderScriptFile(array_merge([
                'src' => $fileName,
                'type' => 'module',
            ], $options['attr'] ?? []));
        }

        if ($this->entrypointsLookup->hasLegacy($entryName)) {
            $id = self::pascalToKebab("vite-legacy-entry-$entryName");

            $content[] = $this->tagRenderer->renderScriptFile([
                'data-src' => $this->entrypointsLookup->getLegacyJSFile($entryName),
                'id' => $id,
                'nomodule' => true,
                'crossorigin' => true,
                'class' => 'vite-legacy-entry',
            ], $this->tagRenderer->getSystemJSInlineCode($id));
        }

        return implode('', $content);
    }

    public function renderLinks(string $entryName, array $options = [])
    {
        if (!$this->entrypointsLookup->hasFile()) {
            return '';
        }

        $content = [];

        $this->checkAndInsertLegacyPolyfill($content);

        foreach ($this->entrypointsLookup->getCSSFiles($entryName) as $fileName) {
            $content[] = $this->tagRenderer->renderLinkStylesheet($fileName, $options['attr'] ?? []);
        }

        if ($this->entrypointsLookup->isProd()) {
            foreach ($this->entrypointsLookup->getJavascriptDependencies($entryName) as $fileName) {
                $content[] = $this->tagRenderer->renderLinkPreload($fileName);
            }
        }

        return implode('', $content);
    }

    public static function pascalToKebab(string $str)
    {
        return strtolower(preg_replace('/[A-Z]/', '-\\0', lcfirst($str)));
    }
}
