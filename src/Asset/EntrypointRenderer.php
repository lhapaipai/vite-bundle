<?php

namespace Pentatrion\ViteBundle\Asset;

class EntrypointRenderer
{
    private $entrypointsLookup;
    private $tagRenderer;
    private $router;

    private $returnedViteClients = [];
    private $returnedReactRefresh = [];

    private $hasReturnedViteLegacyScripts = false;

    public function __construct(EntrypointsLookup $entrypointsLookup, TagRenderer $tagRenderer, ?\Symfony\Component\Routing\RouterInterface $router = null)
    {
        $this->entrypointsLookup = $entrypointsLookup;
        $this->tagRenderer = $tagRenderer;
        $this->router = $router;
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
                $this->returnedViteClients[$buildName] = true;
            }
            if (!isset($this->returnedReactRefresh[$buildName]) && isset($options['dependency']) && 'react' === $options['dependency']) {
                $content[] = $this->tagRenderer->renderReactRefreshInline($viteServer['origin'].$viteServer['base']);
                $this->returnedReactRefresh[$buildName] = true;
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

            foreach ($this->entrypointsLookup->getJSFiles('polyfills-legacy', $buildName) as $fileWithHash) {
                // normally only one js file
                $content[] = $this->tagRenderer->renderTag('script', [
                    'nomodule' => true,
                    'crossorigin' => true,
                    'src' => $fileWithHash['path'],
                    'id' => 'vite-legacy-polyfill',
                ]);
            }
            $this->hasReturnedViteLegacyScripts = true;
        }

        /* normal js scripts */
        foreach ($this->entrypointsLookup->getJSFiles($entryName, $buildName) as $fileWithHash) {
            $attributes = array_merge([
                'type' => 'module',
                'src' => $this->shouldUseAbsoluteURL($options, $buildName) ? $this->getAbsoluteURL($fileWithHash['path']) : $fileWithHash['path'],
                'integrity' => $fileWithHash['hash'],
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

    private function getAbsoluteURL(string $path)
    {
        if ($this->router === null) {
            return $path;
        }

        return $this->router->getContext()->getScheme() . '://' . $this->router->getContext()->getHost() . $path;
    }

    private function shouldUseAbsoluteURL(array $options, $buildName)
    {
        $viteServer = $this->entrypointsLookup->getViteServer($buildName);

        return false === $viteServer && isset($options['absoluteURL']) && true === $options['absoluteURL'];
    }

    public function renderLinks(string $entryName, array $options = [], $buildName = null): string
    {
        if (!$this->entrypointsLookup->hasFile($buildName)) {
            return '';
        }

        $viteServer = $this->entrypointsLookup->getViteServer($buildName);

        $content = [];

        foreach ($this->entrypointsLookup->getCSSFiles($entryName, $buildName) as $fileWithHash) {
            $content[] = $this->tagRenderer->renderLinkStylesheet($this->shouldUseAbsoluteURL($options, $buildName) ? $this->getAbsoluteURL($fileWithHash['path']) : $fileWithHash['path'], array_merge([
                'integrity' => $fileWithHash['hash'],
            ], $options['attr'] ?? []), $buildName);
        }

        if ($this->entrypointsLookup->isProd($buildName)) {
            foreach ($this->entrypointsLookup->getJavascriptDependencies($entryName, $buildName) as $fileWithHash) {
                $content[] = $this->tagRenderer->renderLinkPreload($this->shouldUseAbsoluteURL($options, $buildName) ? $this->getAbsoluteURL($fileWithHash['path']) : $fileWithHash['path'], [
                    'integrity' => $fileWithHash['hash'],
                ], $buildName);
            }
        }

        if ($this->entrypointsLookup->isProd($buildName) && isset($options['preloadDynamicImports']) && true === $options['preloadDynamicImports']) {
            foreach ($this->entrypointsLookup->getJavascriptDynamicDependencies($entryName, $buildName) as $fileWithHash) {
                $content[] = $this->tagRenderer->renderLinkPreload($this->shouldUseAbsoluteURL($options, $buildName) ? $this->getAbsoluteURL($fileWithHash['path']) : $fileWithHash['path'], [
                    'integrity' => $fileWithHash['hash'],
                ], $buildName);
            }
        }

        return implode(PHP_EOL, $content);
    }

    public function reset()
    {
        // resets the state of this service
        $this->returnedViteClients = [];
        $this->returnedReactRefresh = [];
    }

    public static function pascalToKebab(string $str): string
    {
        return strtolower(preg_replace('/[A-Z]/', '-\\0', lcfirst($str)));
    }
}
