<?php

namespace Pentatrion\ViteBundle\Asset;

use Symfony\Component\Routing\RouterInterface;

class EntrypointRenderer
{
    private EntrypointsLookupCollection $entrypointsLookupCollection;
    private TagRendererCollection $tagRendererCollection;
    private bool $useAbsoluteUrl;
    private RouterInterface $router;

    private $returnedViteClients = [];
    private $returnedReactRefresh = [];
    private $returnedPreloadedScripts = [];

    private $hasReturnedViteLegacyScripts = false;

    public function __construct(
        EntrypointsLookupCollection $entrypointsLookupCollection,
        TagRendererCollection $tagRendererCollection,
        bool $useAbsoluteUrl,
        RouterInterface $router = null
    ) {
        $this->entrypointsLookupCollection = $entrypointsLookupCollection;
        $this->tagRendererCollection = $tagRendererCollection;
        $this->useAbsoluteUrl = $useAbsoluteUrl;
        $this->router = $router;
    }

    private function getEntrypointsLookup(string $configName = null): EntrypointsLookup
    {
        return $this->entrypointsLookupCollection->getEntrypointsLookup($configName);
    }

    private function getTagRenderer(string $configName = null): TagRenderer
    {
        return $this->tagRendererCollection->getTagRenderer($configName);
    }

    public function renderScripts(string $entryName, array $options = [], $configName = null): string
    {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);
        $tagRenderer = $this->getTagRenderer($configName);

        if (!$entrypointsLookup->hasFile()) {
            return '';
        }

        $useAbsoluteUrl = $this->shouldUseAbsoluteURL($options, $configName);

        $content = [];
        $viteServer = $entrypointsLookup->getViteServer();
        $isBuild = $entrypointsLookup->isBuild();

        if (false !== $viteServer) {
            // vite server is active
            if (!isset($this->returnedViteClients[$configName])) {
                $content[] = $tagRenderer->renderTag('script', [
                    'type' => 'module',
                    'src' => $viteServer['origin'].$viteServer['base'].'@vite/client',
                ]);
                $this->returnedViteClients[$configName] = true;
            }
            if (!isset($this->returnedReactRefresh[$configName]) && isset($options['dependency']) && 'react' === $options['dependency']) {
                $content[] = $tagRenderer->renderReactRefreshInline($viteServer['origin'].$viteServer['base']);
                $this->returnedReactRefresh[$configName] = true;
            }
        } elseif (
            $entrypointsLookup->isLegacyPluginEnabled()
            && !$this->hasReturnedViteLegacyScripts
        ) {
            /* legacy section when vite server is inactive */

            /* set or not the __vite_is_modern_browser variable */
            $content[] = $tagRenderer::DETECT_MODERN_BROWSER_CODE;

            /* if your browser understands the modules but not dynamic import,
             * load the legacy entrypoints
             */
            $content[] = $tagRenderer::DYNAMIC_FALLBACK_INLINE_CODE;

            /* Safari 10.1 supports modules, but does not support the `nomodule` attribute
             *  it will load <script nomodule> anyway */
            $content[] = $tagRenderer::SAFARI10_NO_MODULE_FIX;

            foreach ($entrypointsLookup->getJSFiles('polyfills-legacy') as $fileWithHash) {
                // normally only one js file
                $content[] = $tagRenderer->renderTag('script', [
                    'nomodule' => true,
                    'crossorigin' => true,
                    'src' => $this->completeURL($fileWithHash['path'], $useAbsoluteUrl),
                    'id' => 'vite-legacy-polyfill',
                ]);
            }
            $this->hasReturnedViteLegacyScripts = true;
        }

        /* normal js scripts */
        foreach ($entrypointsLookup->getJSFiles($entryName) as $fileWithHash) {
            $attributes = array_merge([
                'type' => 'module',
                'src' => $this->completeURL($fileWithHash['path'], $useAbsoluteUrl),
                'integrity' => $fileWithHash['hash'],
            ], $options['attr'] ?? []);
            $content[] = $tagRenderer->renderScriptFile($attributes, '', $isBuild);
        }

        /* legacy js scripts */
        if ($entrypointsLookup->hasLegacy($entryName)) {
            $id = self::pascalToKebab("vite-legacy-entry-$entryName");

            $content[] = $tagRenderer->renderScriptFile([
                'nomodule' => true,
                'data-src' => $this->completeURL($entrypointsLookup->getLegacyJSFile($entryName), $useAbsoluteUrl),
                'id' => $id,
                'crossorigin' => true,
                'class' => 'vite-legacy-entry',
            ], $tagRenderer->getSystemJSInlineCode($id), $isBuild);
        }

        return implode(PHP_EOL, $content);
    }

    private function completeURL(string $path, $useAbsoluteUrl = false)
    {
        if (false === $useAbsoluteUrl || null === $this->router) {
            return $path;
        }

        return $this->router->getContext()->getScheme().'://'.$this->router->getContext()->getHost().$path;
    }

    private function shouldUseAbsoluteURL(array $options, $configName)
    {
        $viteServer = $this->getEntrypointsLookup($configName)->getViteServer($configName);

        return false === $viteServer && ($this->useAbsoluteUrl || (isset($options['absolute_url']) && true === $options['absolute_url']));
    }

    public function renderLinks(string $entryName, array $options = [], $configName = null): string
    {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);
        $tagRenderer = $this->getTagRenderer($configName);

        if (!$entrypointsLookup->hasFile($configName)) {
            return '';
        }

        $useAbsoluteUrl = $this->shouldUseAbsoluteURL($options, $configName);
        $isBuild = $entrypointsLookup->isBuild();

        $content = [];

        foreach ($entrypointsLookup->getCSSFiles($entryName) as $fileWithHash) {
            $content[] = $tagRenderer->renderLinkStylesheet($this->completeURL($fileWithHash['path'], $useAbsoluteUrl), array_merge([
                'integrity' => $fileWithHash['hash'],
            ], $options['attr'] ?? []), $isBuild);
        }

        if ($isBuild) {
            foreach ($entrypointsLookup->getJavascriptDependencies($entryName) as $fileWithHash) {
                if (false === \in_array($fileWithHash['path'], $this->returnedPreloadedScripts, true)) {
                    $content[] = $tagRenderer->renderLinkPreload($this->completeURL($fileWithHash['path'], $useAbsoluteUrl), [
                        'integrity' => $fileWithHash['hash'],
                    ], $isBuild);
                    $this->returnedPreloadedScripts[] = $fileWithHash['path'];
                }
            }
        }

        if ($isBuild && isset($options['preloadDynamicImports']) && true === $options['preloadDynamicImports']) {
            foreach ($entrypointsLookup->getJavascriptDynamicDependencies($entryName) as $fileWithHash) {
                if (false === \in_array($fileWithHash['path'], $this->returnedPreloadedScripts, true)) {
                    $content[] = $tagRenderer->renderLinkPreload($this->completeURL($fileWithHash['path'], $useAbsoluteUrl), [
                        'integrity' => $fileWithHash['hash'],
                    ], $isBuild);
                    $this->returnedPreloadedScripts[] = $fileWithHash['path'];
                }
            }
        }

        return implode(PHP_EOL, $content);
    }

    public function getMode(string $configName = null): ?string
    {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);

        if (!$entrypointsLookup->hasFile($configName)) {
            return null;
        }

        return $entrypointsLookup->isBuild() ? 'build' : 'dev';
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
