<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\Event\RenderAssetTagEvent;
use Pentatrion\ViteBundle\Model\Tag;
use Pentatrion\ViteBundle\Util\InlineContent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

class EntrypointRenderer implements ResetInterface
{
    private EntrypointsLookupCollection $entrypointsLookupCollection;
    private TagRendererCollection $tagRendererCollection;
    private bool $useAbsoluteUrl;
    private string $preload;
    private ?RequestStack $requestStack;
    private ?EventDispatcherInterface $eventDispatcher;

    private $returnedViteClients = [];
    private $returnedReactRefresh = [];
    private $returnedViteLegacyScripts = [];

    private $renderedFiles = [
        'scripts' => [],
        'styles' => [],
    ];

    public function __construct(
        EntrypointsLookupCollection $entrypointsLookupCollection,
        TagRendererCollection $tagRendererCollection,
        bool $useAbsoluteUrl = false,
        string $preload = 'link-tag',
        RequestStack $requestStack = null,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->entrypointsLookupCollection = $entrypointsLookupCollection;
        $this->tagRendererCollection = $tagRendererCollection;
        $this->useAbsoluteUrl = $useAbsoluteUrl;
        $this->preload = $preload;
        $this->requestStack = $requestStack;
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

    private function completeURL(string $path, bool $useAbsoluteUrl = false): string
    {
        if (0 === strpos($path, 'http') || false === $useAbsoluteUrl || null === $this->requestStack || null === $this->requestStack->getCurrentRequest()) {
            return $path;
        }

        return $this->requestStack->getCurrentRequest()->getUriForPath($path);
    }

    private function shouldUseAbsoluteURL(array $options, string $configName = null): bool
    {
        $viteServer = $this->getEntrypointsLookup($configName)->getViteServer($configName);

        return is_null($viteServer) && ($this->useAbsoluteUrl || (isset($options['absolute_url']) && true === $options['absolute_url']));
    }

    public function getMode(string $configName = null): ?string
    {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);

        if (!$entrypointsLookup->hasFile($configName)) {
            return null;
        }

        return $entrypointsLookup->isBuild() ? 'build' : 'dev';
    }

    public function reset(): void
    {
        $this->returnedViteClients = [];
        $this->returnedReactRefresh = [];
        $this->returnedViteLegacyScripts = [];
        $this->renderedFiles = [
            'scripts' => [],
            'styles' => [],
        ];
    }

    public function getRenderedScripts(): array
    {
        return $this->renderedFiles['scripts'];
    }

    public function getRenderedStyles(): array
    {
        return $this->renderedFiles['styles'];
    }

    /**
     * @return string|array
     */
    public function renderScripts(
        string $entryName,
        array $options = [],
        string $configName = null,
        bool $toString = true
    ) {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);
        $tagRenderer = $this->getTagRenderer($configName);

        if (!$entrypointsLookup->hasFile()) {
            return '';
        }

        $useAbsoluteUrl = $this->shouldUseAbsoluteURL($options, $configName);

        $tags = [];
        $viteServer = $entrypointsLookup->getViteServer();
        $isBuild = $entrypointsLookup->isBuild();
        $base = $entrypointsLookup->getBase();

        if (!is_null($viteServer)) {
            // vite server is active
            if (!isset($this->returnedViteClients[$configName])) {
                $tags[] = $tagRenderer->createViteClientScript($viteServer.$base.'@vite/client');

                $this->returnedViteClients[$configName] = true;
            }

            if (
                !isset($this->returnedReactRefresh[$configName])
                && isset($options['dependency']) && 'react' === $options['dependency']
            ) {
                $tags[] = $tagRenderer->createReactRefreshScript($viteServer.$base);

                $this->returnedReactRefresh[$configName] = true;
            }
        } elseif (
            $entrypointsLookup->isLegacyPluginEnabled()
            && !isset($this->returnedViteLegacyScripts[$configName])
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

            $this->returnedViteLegacyScripts[$configName] = true;
        }

        /* normal js scripts */
        foreach ($entrypointsLookup->getJSFiles($entryName) as $filePath) {
            if (false === \in_array($filePath, $this->renderedFiles['scripts'], true)) {
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

                $this->renderedFiles['scripts'][] = $filePath;
            }
        }

        /* legacy js scripts */
        if ($entrypointsLookup->hasLegacy($entryName)) {
            $id = self::pascalToKebab("vite-legacy-entry-$entryName");

            $filePath = $entrypointsLookup->getLegacyJSFile($entryName);
            if (false === \in_array($filePath, $this->renderedFiles['scripts'], true)) {
                $tags[] = $tagRenderer->createScriptTag(
                    [
                        'nomodule' => true,
                        'data-src' => $this->completeURL($filePath, $useAbsoluteUrl),
                        'id' => $id,
                        'crossorigin' => true,
                        'class' => 'vite-legacy-entry',
                        'integrity' => $entrypointsLookup->getFileHash($filePath),
                    ],
                    InlineContent::getSystemJSInlineCode($id)
                );

                $this->renderedFiles['scripts'][] = $filePath;
            }
        }

        return $this->renderTags($tags, $isBuild, $toString);
    }

    /**
     * @return string|array
     */
    public function renderLinks(
        string $entryName,
        array $options = [],
        string $configName = null,
        bool $toString = true
    ) {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);
        $tagRenderer = $this->getTagRenderer($configName);

        if (!$entrypointsLookup->hasFile()) {
            return '';
        }

        $useAbsoluteUrl = $this->shouldUseAbsoluteURL($options, $configName);
        $isBuild = $entrypointsLookup->isBuild();

        $tags = [];

        foreach ($entrypointsLookup->getCSSFiles($entryName) as $filePath) {
            if (false === \in_array($filePath, $this->renderedFiles['styles'], true)) {
                $tags[] = $tagRenderer->createLinkStylesheetTag(
                    $this->completeURL($filePath, $useAbsoluteUrl),
                    array_merge(['integrity' => $entrypointsLookup->getFileHash($filePath)], $options['attr'] ?? [])
                );
                $this->renderedFiles['styles'][] = $filePath;
            }
        }

        if ($isBuild) {
            foreach ($entrypointsLookup->getJavascriptDependencies($entryName) as $filePath) {
                if (false === \in_array($filePath, $this->renderedFiles['scripts'], true)) {
                    $tags[] = $tagRenderer->createModulePreloadLinkTag(
                        $this->completeURL($filePath, $useAbsoluteUrl),
                        ['integrity' => $entrypointsLookup->getFileHash($filePath)]
                    );
                    $this->renderedFiles['scripts'][] = $filePath;
                }
            }
        }

        if ($isBuild && isset($options['preloadDynamicImports']) && true === $options['preloadDynamicImports']) {
            foreach ($entrypointsLookup->getJavascriptDynamicDependencies($entryName) as $filePath) {
                if (false === \in_array($filePath, $this->renderedFiles['scripts'], true)) {
                    $tags[] = $tagRenderer->createModulePreloadLinkTag(
                        $this->completeURL($filePath, $useAbsoluteUrl),
                        ['integrity' => $entrypointsLookup->getFileHash($filePath)]
                    );
                    $this->renderedFiles['scripts'][] = $filePath;
                }
            }
        }

        return $this->renderTags($tags, $isBuild, $toString);
    }

    /**
     * @return string|array
     */
    public function renderTags(array $tags, bool $isBuild, bool $toString)
    {
        if (null !== $this->eventDispatcher) {
            foreach ($tags as $tag) {
                $this->eventDispatcher->dispatch(new RenderAssetTagEvent($isBuild, $tag));
            }
        }

        if ('link-tag' !== $this->preload) {
            $tags = array_filter($tags, function (Tag $tag) {
                return !$tag->isModulePreload();
            });
        }

        return $toString
        ? implode('', array_map(function ($tagEvent) {
            return TagRenderer::generateTag($tagEvent);
        }, $tags))
        : $tags;
    }

    public static function pascalToKebab(string $str): string
    {
        return strtolower(preg_replace('/[A-Z]/', '-\\0', lcfirst($str)));
    }
}
