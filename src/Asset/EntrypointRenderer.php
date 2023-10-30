<?php

namespace Pentatrion\ViteBundle\Asset;

use Pentatrion\ViteBundle\Event\RenderAssetTagEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EntrypointRenderer
{
    private EntrypointsLookupCollection $entrypointsLookupCollection;
    private TagRendererCollection $tagRendererCollection;
    private bool $useAbsoluteUrl;
    private RouterInterface $router;
    private EventDispatcherInterface $eventDispatcher;

    private $returnedViteClient = false;
    private $returnedReactRefresh = false;
    private $returnedPreloadedScripts = [];

    private $hasReturnedViteLegacyScripts = false;

    public function __construct(
        EntrypointsLookupCollection $entrypointsLookupCollection,
        TagRendererCollection $tagRendererCollection,
        bool $useAbsoluteUrl,
        RouterInterface $router = null,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->entrypointsLookupCollection = $entrypointsLookupCollection;
        $this->tagRendererCollection = $tagRendererCollection;
        $this->useAbsoluteUrl = $useAbsoluteUrl;
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    private function getEntrypointsLookup(string $configName = null): EntrypointsLookup
    {
        return $this->entrypointsLookupCollection->getEntrypointsLookup($configName);
    }

    private function getTagRenderer(string $configName = null): TagRenderer
    {
        return $this->tagRendererCollection->getTagRenderer($configName);
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
        $this->returnedViteClient = false;
        $this->returnedReactRefresh = false;
    }

    public function renderScripts(
        string $entryName,
        array $options = [],
        $configName = null,
        $toString = true
    ): string {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);
        $tagRenderer = $this->getTagRenderer($configName);

        if (!$entrypointsLookup->hasFile()) {
            return '';
        }

        $useAbsoluteUrl = $this->shouldUseAbsoluteURL($options, $configName);

        $tags = [];
        $viteServer = $entrypointsLookup->getViteServer();
        $isBuild = $entrypointsLookup->isBuild();

        if (false !== $viteServer) {
            // vite server is active
            if (!$this->returnedViteClient) {
                $tags[] = $tagRenderer->createViteClientScript($viteServer['origin'].$viteServer['base'].'@vite/client');
                $this->returnedViteClient = true;
            }

            if (
                !$this->returnedReactRefresh
                && isset($options['dependency']) && 'react' === $options['dependency']
            ) {
                $tags[] = $tagRenderer->createReactRefreshScript($viteServer['origin'].$viteServer['base']);

                $this->$this->returnedReactRefresh = true;
            }
        } elseif (
            $entrypointsLookup->isLegacyPluginEnabled()
            && !$this->hasReturnedViteLegacyScripts
        ) {
            /* legacy section when vite server is inactive */
            $tags[] = $tagRenderer->createDetectModernBrowserScript();
            $tags[] = $tagRenderer->createDynamicFallbackScript();
            $tags[] = $tagRenderer->createSafariNoModuleScript();

            foreach ($entrypointsLookup->getJSFiles('polyfills-legacy') as $filePath) {
                // normally only one js file
                $tags[] = $tagRenderer->createScriptTag(
                    [
                        'nomodule' => true,
                        'crossorigin' => true,
                        'src' => $this->completeURL($filePath, $useAbsoluteUrl),
                        'id' => 'vite-legacy-polyfill',
                    ]
                );
            }
            $this->hasReturnedViteLegacyScripts = true;
        }

        /* normal js scripts */
        foreach ($entrypointsLookup->getJSFiles($entryName) as $filePath) {
            $tags[] = $tagRenderer->createScriptTag(
                array_merge(
                    [
                        'type' => 'module',
                        'src' => $this->completeURL($filePath, $useAbsoluteUrl),
                        'integrity' => $entrypointsLookup->getFileHash($filePath),
                    ],
                    $options['attr'] ?? []
                )
            );
        }

        /* legacy js scripts */
        if ($entrypointsLookup->hasLegacy($entryName)) {
            $id = self::pascalToKebab("vite-legacy-entry-$entryName");

            $file = $entrypointsLookup->getLegacyJSFile($entryName);
            $tags[] = $tagRenderer->createScriptTag(
                [
                    'nomodule' => true,
                    'data-src' => $this->completeURL($file, $useAbsoluteUrl),
                    'id' => $id,
                    'crossorigin' => true,
                    'class' => 'vite-legacy-entry',
                    'integrity' => $entrypointsLookup->getFileHash($file),
                ],
                InlineContent::getSystemJSInlineCode($id)
            );
        }

        return $this->renderTags($tags, $isBuild, $toString);
    }

    public function renderLinks(
        string $entryName,
        array $options = [],
        $configName = null,
        $toString = true
    ): string {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);
        $tagRenderer = $this->getTagRenderer($configName);

        if (!$entrypointsLookup->hasFile($configName)) {
            return '';
        }

        $useAbsoluteUrl = $this->shouldUseAbsoluteURL($options, $configName);
        $isBuild = $entrypointsLookup->isBuild();

        $tags = [];

        foreach ($entrypointsLookup->getCSSFiles($entryName) as $filePath) {
            $tags[] = $tagRenderer->createLinkStylesheetTag(
                $this->completeURL($filePath, $useAbsoluteUrl),
                array_merge(['integrity' => $entrypointsLookup->getFileHash($filePath)], $options['attr'] ?? [])
            );
        }

        if ($isBuild) {
            foreach ($entrypointsLookup->getJavascriptDependencies($entryName) as $filePath) {
                if (false === \in_array($filePath, $this->returnedPreloadedScripts, true)) {
                    $tags[] = $tagRenderer->createModulePreloadLinkTag(
                        $this->completeURL($filePath, $useAbsoluteUrl),
                        ['integrity' => $entrypointsLookup->getFileHash($filePath)]
                    );
                    $this->returnedPreloadedScripts[] = $filePath;
                }
            }
        }

        if ($isBuild && isset($options['preloadDynamicImports']) && true === $options['preloadDynamicImports']) {
            foreach ($entrypointsLookup->getJavascriptDynamicDependencies($entryName) as $filePath) {
                if (false === \in_array($filePath, $this->returnedPreloadedScripts, true)) {
                    $tags[] = $tagRenderer->createModulePreloadLinkTag(
                        $this->completeURL($filePath, $useAbsoluteUrl),
                        ['integrity' => $entrypointsLookup->getFileHash($filePath)]
                    );
                    $this->returnedPreloadedScripts[] = $filePath;
                }
            }
        }

        return $this->renderTags($tags, $isBuild, $toString);
    }

    public function renderTags(array $tags, $isBuild, $toString)
    {
        if (null !== $this->eventDispatcher) {
            foreach ($tags as $tag) {
                $this->eventDispatcher->dispatch(new RenderAssetTagEvent($isBuild, $tag));
            }
        }

        return $toString
        ? implode(PHP_EOL, array_map(function ($tagEvent) {
            return TagRenderer::generateTag($tagEvent);
        }, $tags))
        : $tags;
    }

    public static function pascalToKebab(string $str): string
    {
        return strtolower(preg_replace('/[A-Z]/', '-\\0', lcfirst($str)));
    }
}
