<?php

namespace Pentatrion\ViteBundle\Tests\Asset;

use Pentatrion\ViteBundle\Asset\EntrypointRenderer;
use Pentatrion\ViteBundle\Asset\EntrypointsLookup;
use Pentatrion\ViteBundle\Asset\EntrypointsLookupCollection;
use Pentatrion\ViteBundle\Asset\InlineContent;
use Pentatrion\ViteBundle\Asset\TagRenderer;
use Pentatrion\ViteBundle\Asset\TagRendererCollection;
use Pentatrion\ViteBundle\Event\RenderAssetTagEvent;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EntrypointRendererTest extends TestCase
{
    private function getBasicTagRendererCollection($scriptAttributes = [], $linkAttributes = []): TagRendererCollection
    {
        $tagRenderer = new TagRenderer($scriptAttributes, $linkAttributes);
        /**
         * @var TagRendererCollection|Stub $tagRendererCollection
         */
        $tagRendererCollection = $this->createStub(TagRendererCollection::class);
        $tagRendererCollection
            ->method('getTagRenderer')
            ->willReturn($tagRenderer);

        return $tagRendererCollection;
    }

    private function getEntrypointsLookupCollection(EntrypointsLookup $entrypointsLookup)
    {
        /**
         * @var EntrypointsLookupCollection|Stub $entrypointsLookupCollection
         */
        $entrypointsLookupCollection = $this->createStub(EntrypointsLookupCollection::class);
        $entrypointsLookupCollection
            ->method('getEntrypointsLookup')
            ->willReturn($entrypointsLookup);

        return $entrypointsLookupCollection;
    }

    private function getEntrypointsLookup($prefix)
    {
        return new EntrypointsLookup(
            __DIR__.'/../fixtures/entrypoints/'.$prefix.'/',
            '_default',
            true
        );
    }

    public function basicProvider()
    {
        return [
            [
                'basic-build',
                'app',
                [
                    'mode' => 'build',
                    'scripts' => '<script type="module" src="/build/assets/app.js"></script>',
                    'links' => '',
                ],
            ],
            [
                'basic-build',
                'theme',
                [
                    'mode' => 'build',
                    'scripts' => '',
                    'links' => '<link rel="stylesheet" href="/build/assets/theme.css">',
                ],
            ],
            [
                'basic-build',
                'with-dep',
                [
                    'mode' => 'build',
                    'scripts' => '<script type="module" src="/build/assets/main.js"></script>',
                    'links' => '<link rel="stylesheet" href="/build/assets/main.css">'
                        .'<link rel="modulepreload" href="/build/assets/vue.js">'
                        .'<link rel="modulepreload" href="/build/assets/react.js">',
                ],
            ],
            [
                'basic-build',
                'with-async',
                [
                    'mode' => 'build',
                    'scripts' => '<script type="module" src="/build/assets/main.js"></script>',
                    'links' => '<link rel="stylesheet" href="/build/assets/main.css">'
                        .'<link rel="modulepreload" href="/build/assets/vue.js">'
                        .'<link rel="modulepreload" href="/build/assets/react.js">',
                ],
            ],
            [
                'basic-dev',
                'app',
                [
                    'mode' => 'dev',
                    'scripts' => '<script type="module" src="http://127.0.0.1:5173/build/@vite/client"></script>'
                        .'<script type="module" src="http://127.0.0.1:5173/build/assets/app.js"></script>',
                    'links' => '',
                ],
            ],
            [
                'basic-dev',
                'theme',
                [
                    'mode' => 'dev',
                    'scripts' => '<script type="module" src="http://127.0.0.1:5173/build/@vite/client"></script>',
                    'links' => '<link rel="stylesheet" href="http://127.0.0.1:5173/build/assets/theme.scss">',
                ],
            ],
            [
                'legacy-build',
                'app',
                [
                    'mode' => 'build',
                    'scripts' => '<script type="module">'.InlineContent::DETECT_MODERN_BROWSER_INLINE_CODE.'</script>'
                    .'<script type="module">'.InlineContent::DYNAMIC_FALLBACK_INLINE_CODE.'</script>'
                    .'<script nomodule>'.InlineContent::SAFARI10_NO_MODULE_FIX_INLINE_CODE.'</script>'
                    .'<script nomodule crossorigin src="/build/assets/polyfills-legacy.js" id="vite-legacy-polyfill"></script>'
                    .'<script type="module" src="/build/assets/app.js"></script>'
                    .'<script nomodule data-src="/build/assets/app-legacy.js" id="vite-legacy-entry-app" crossorigin class="vite-legacy-entry">System.import(document.getElementById("vite-legacy-entry-app").getAttribute("data-src"))</script>',
                    'links' => '',
                ],
            ],
            [
                'legacy-build',
                'theme',
                [
                    'mode' => 'build',
                    'scripts' => '<script type="module">'.InlineContent::DETECT_MODERN_BROWSER_INLINE_CODE.'</script>'
                    .'<script type="module">'.InlineContent::DYNAMIC_FALLBACK_INLINE_CODE.'</script>'
                    .'<script nomodule>'.InlineContent::SAFARI10_NO_MODULE_FIX_INLINE_CODE.'</script>'
                    .'<script nomodule crossorigin src="/build/assets/polyfills-legacy.js" id="vite-legacy-polyfill"></script>'
                    .'<script nomodule data-src="/build/assets/theme-legacy.js" id="vite-legacy-entry-theme" crossorigin class="vite-legacy-entry">System.import(document.getElementById("vite-legacy-entry-theme").getAttribute("data-src"))</script>',
                    'links' => '<link rel="stylesheet" href="/build/assets/theme.css">',
                ],
            ],
            [
                'metadata-build',
                'app',
                [
                    'mode' => 'build',
                    'scripts' => '<script type="module" src="/build/assets/app.js" integrity="sha256-XYZ"></script>',
                    'links' => '',
                ],
            ],
        ];
    }

    /**
     * @dataProvider basicProvider
     */
    public function testBasic($config, $entryName, $expectedStrings)
    {
        $entrypointsLookup = $this->getEntrypointsLookup($config);

        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookup),
            $this->getBasicTagRendererCollection()
        );

        $this->assertEquals(
            $expectedStrings['mode'],
            $entrypointRenderer->getMode()
        );

        $this->assertEquals(
            $expectedStrings['scripts'],
            $entrypointRenderer->renderScripts($entryName)
        );

        $this->assertEquals(
            $expectedStrings['links'],
            $entrypointRenderer->renderLinks($entryName)
        );
    }

    public function testRenderOnlyOneViteClient()
    {
        $entrypointsLookup = $this->getEntrypointsLookup('duplication-dev');
        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookup),
            $this->getBasicTagRendererCollection()
        );

        $this->assertEquals(
            '<script type="module" src="http://127.0.0.1:5173/build/@vite/client"></script>'
            .'<script type="module" src="http://127.0.0.1:5173/build/assets/app.js"></script>'
            .'<script type="module" src="http://127.0.0.1:5173/build/assets/other-app.js"></script>',
            $entrypointRenderer->renderScripts('app').$entrypointRenderer->renderScripts('other-app')
        );
    }

    public function testRenderOnlyOneReactRefresh()
    {
        $entrypointsLookup = $this->getEntrypointsLookup('duplication-dev');
        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookup),
            $this->getBasicTagRendererCollection()
        );

        $this->assertEquals(
            '<script type="module" src="http://127.0.0.1:5173/build/@vite/client"></script>'
            .'<script type="module">'.InlineContent::getReactRefreshInlineCode('http://127.0.0.1:5173/build/').'</script>'
            .'<script type="module" src="http://127.0.0.1:5173/build/assets/app.js"></script>'
            .'<script type="module" src="http://127.0.0.1:5173/build/assets/other-app.js"></script>',
            $entrypointRenderer->renderScripts('app', ['dependency' => 'react']).$entrypointRenderer->renderScripts('other-app', ['dependency' => 'react'])
        );
    }

    public function testRenderOnlyOneFile()
    {
        $entrypointsLookup = $this->getEntrypointsLookup('duplication-build');
        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookup),
            $this->getBasicTagRendererCollection()
        );

        $expectedScripts = '<script type="module" src="/build/assets/app-1.js"></script>'
            .'<script type="module" src="/build/assets/app-2.js"></script>';
        $expectedLinks = '<link rel="modulepreload" href="/build/assets/vue.js">'
            .'<link rel="stylesheet" href="/build/assets/app-2.css">';

        $this->assertEquals(
            $expectedScripts,
            $entrypointRenderer->renderScripts('app-1').$entrypointRenderer->renderScripts('app-2')
        );

        $this->assertEquals(
            $expectedLinks,
            $entrypointRenderer->renderLinks('app-1').$entrypointRenderer->renderLinks('app-2'),
            'dont render twice vuejs dependency'
        );

        $this->assertEquals(
            '',
            $entrypointRenderer->renderScripts('app-1').$entrypointRenderer->renderScripts('app-2')
        );

        $this->assertEquals(
            '',
            $entrypointRenderer->renderLinks('app-1').$entrypointRenderer->renderLinks('app-2')
        );

        $entrypointRenderer->reset();

        $this->assertEquals(
            $expectedScripts,
            $entrypointRenderer->renderScripts('app-1').$entrypointRenderer->renderScripts('app-2')
        );

        $this->assertEquals(
            $expectedLinks,
            $entrypointRenderer->renderLinks('app-1').$entrypointRenderer->renderLinks('app-2'),
            'dont render twice vuejs dependency'
        );
    }

    public function testRenderOnlyOneLegacyInlineContent()
    {
        $entrypointsLookup = $this->getEntrypointsLookup('legacy-build');
        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookup),
            $this->getBasicTagRendererCollection()
        );

        $this->assertEquals(
            '<script type="module">'.InlineContent::DETECT_MODERN_BROWSER_INLINE_CODE.'</script>'
            .'<script type="module">'.InlineContent::DYNAMIC_FALLBACK_INLINE_CODE.'</script>'
            .'<script nomodule>'.InlineContent::SAFARI10_NO_MODULE_FIX_INLINE_CODE.'</script>'
            .'<script nomodule crossorigin src="/build/assets/polyfills-legacy.js" id="vite-legacy-polyfill"></script>'
            .'<script type="module" src="/build/assets/app.js"></script>'
            .'<script nomodule data-src="/build/assets/app-legacy.js" id="vite-legacy-entry-app" crossorigin class="vite-legacy-entry">System.import(document.getElementById("vite-legacy-entry-app").getAttribute("data-src"))</script>'
            .'<script nomodule data-src="/build/assets/theme-legacy.js" id="vite-legacy-entry-theme" crossorigin class="vite-legacy-entry">System.import(document.getElementById("vite-legacy-entry-theme").getAttribute("data-src"))</script>',
            $entrypointRenderer->renderScripts('app').$entrypointRenderer->renderScripts('theme')
        );

        $this->assertEquals(
            '<link rel="stylesheet" href="/build/assets/theme.css">',
            $entrypointRenderer->renderLinks('app').$entrypointRenderer->renderLinks('theme')
        );
    }

    public function testRenderWithAbsoluteUrl()
    {
        /**
         * @var Stub|RequestContext $context
         */
        $context = $this->createStub(RequestContext::class);
        $context
            ->method('getScheme')
            ->willReturn('http');

        $context
            ->method('getHost')
            ->willReturn('mydomain.local');
        /**
         * @var Stub|RouterInterface $router
         */
        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('getContext')
            ->willReturn($context);

        $entrypointsLookupBasicBuild = $this->getEntrypointsLookup('basic-build');
        $entrypointsLookupBasicDev = $this->getEntrypointsLookup('basic-dev');

        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookupBasicBuild),
            $this->getBasicTagRendererCollection(),
            true,
            'link-tag',
            $router,
            null,
        );
        $this->assertEquals(
            '<script type="module" src="http://mydomain.local/build/assets/app.js"></script>',
            $entrypointRenderer->renderScripts('app'),
            'render complete url when absolute_url defined globally'
        );

        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookupBasicBuild),
            $this->getBasicTagRendererCollection(),
            false,
            'link-tag',
            $router,
            null,
        );
        $this->assertEquals(
            '<script type="module" src="http://mydomain.local/build/assets/app.js"></script>',
            $entrypointRenderer->renderScripts('app', ['absolute_url' => true]),
            'render complete url when absolute_url defined locally'
        );

        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookupBasicDev),
            $this->getBasicTagRendererCollection(),
            true,
            'link-tag',
            $router,
            null,
        );
        $this->assertEquals(
            '<script type="module" src="http://127.0.0.1:5173/build/@vite/client">'
            .'</script><script type="module" src="http://127.0.0.1:5173/build/assets/app.js"></script>',
            $entrypointRenderer->renderScripts('app'),
            'render correct url when absolute_url defined and vite dev server is started'
        );
    }

    public function testRenderWithoutPreload()
    {
        $entrypointsLookup = $this->getEntrypointsLookup('basic-build');
        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookup),
            $this->getBasicTagRendererCollection(),
            false,
            'none'
        );

        $this->assertEquals(
            '<link rel="stylesheet" href="/build/assets/main.css">',
            $entrypointRenderer->renderLinks('with-async', [
                'preloadDynamicImports' => true,
            ]),
            'render only css files'
        );

        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookup),
            $this->getBasicTagRendererCollection(),
            false,
            'link-header'
        );

        $this->assertEquals(
            '<link rel="stylesheet" href="/build/assets/main.css">',
            $entrypointRenderer->renderLinks('with-async', [
                'preloadDynamicImports' => true,
            ]),
            'render only css files'
        );
    }

    public function testRenderAndPreloadDynamicImports()
    {
        $entrypointsLookup = $this->getEntrypointsLookup('basic-build');
        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookup),
            $this->getBasicTagRendererCollection()
        );

        $this->assertEquals(
            '<script type="module" src="/build/assets/main.js"></script>',
            $entrypointRenderer->renderScripts('with-async', [
                'preloadDynamicImports' => true,
            ])
        );

        $this->assertEquals(
            '<link rel="stylesheet" href="/build/assets/main.css">'
            .'<link rel="modulepreload" href="/build/assets/vue.js">'
            .'<link rel="modulepreload" href="/build/assets/react.js">'
            .'<link rel="modulepreload" href="/build/assets/async-script.js">',
            $entrypointRenderer->renderLinks('with-async', [
                'preloadDynamicImports' => true,
            ]),
            'render css files, preload preload&dynamic files'
        );
    }

    public function testRenderWithEvent()
    {
        /**
         * @var EventDispatcherInterface|Stub $dispatcher
         */
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher
            ->method('dispatch')
            ->willReturnCallback(function (RenderAssetTagEvent $evt) {
                $tag = $evt->getTag();
                if ($tag->isScriptTag()) {
                    $tag->setAttribute('src', $tag->getAttribute('src').'-modified');
                    $tag->setAttribute('nonce', 'custom-nonce');
                } elseif ($tag->isStylesheet()) {
                    $tag->removeAttribute('referrerpolicy');
                } elseif ($tag->isModulePreload()) {
                    $tag->setAttribute('data-foo', 'bar');
                }

                return $evt;
            });

        $entrypointsLookup = $this->getEntrypointsLookup('basic-build');
        $entrypointRenderer = new EntrypointRenderer(
            $this->getEntrypointsLookupCollection($entrypointsLookup),
            $this->getBasicTagRendererCollection(['defer' => true], ['referrerpolicy' => 'origin']),
            false,
            'link-tag',
            null,
            $dispatcher,
        );

        $this->assertSame(
            '<script defer type="module" src="/build/assets/app.js-modified" nonce="custom-nonce"></script>',
            $entrypointRenderer->renderScripts('app'),
            'filter script, add custom attribute, modify attributes in last'
        );
        $this->assertSame(
            '<link rel="stylesheet" href="/build/assets/theme.css">',
            $entrypointRenderer->renderLinks('theme'),
            'filter stylesheet, remove attribute'
        );
        $this->assertSame(
            '<link rel="stylesheet" href="/build/assets/main.css">'
            .'<link rel="modulepreload" href="/build/assets/vue.js" data-foo="bar">'
            .'<link rel="modulepreload" href="/build/assets/react.js" data-foo="bar">',
            $entrypointRenderer->renderLinks('with-dep'),
            'filter modulepreload, add custom attribute'
        );
    }

    public function testMultipleConfigInBuild()
    {
        $entrypointsLookupConfig1 = $this->getEntrypointsLookup('config1-build');
        $entrypointsLookupConfig2 = $this->getEntrypointsLookup('config2-build');

        /**
         * @var EntrypointsLookupCollection|Stub $entrypointsLookupCollection
         */
        $entrypointsLookupCollection = $this->createStub(EntrypointsLookupCollection::class);
        $entrypointsLookupCollection
            ->method('getEntrypointsLookup')
            ->will($this->returnValueMap([
                ['config1-dev', $entrypointsLookupConfig1],
                ['config2-dev', $entrypointsLookupConfig2],
            ]));

        $tagRendererConfig1 = new TagRenderer([], []);
        $tagRendererConfig2 = new TagRenderer(['defer' => true], ['referrerpolicy' => 'origin']);

        /**
         * @var TagRendererCollection|Stub $tagRendererCollection
         */
        $tagRendererCollection = $this->createStub(TagRendererCollection::class);
        $tagRendererCollection
            ->method('getTagRenderer')
            ->will($this->returnValueMap([
                ['config1-dev', $tagRendererConfig1],
                ['config2-dev', $tagRendererConfig2],
            ]));

        $entrypointRenderer = new EntrypointRenderer(
            $entrypointsLookupCollection,
            $tagRendererCollection
        );

        $this->assertEquals(
            '<script type="module" src="/build-config1/assets/app-1.js"></script>'
            .'<script defer type="module" src="/build-config2/assets/app-2.js"></script>',
            $entrypointRenderer->renderScripts('app-1', [], 'config1-dev')
            .$entrypointRenderer->renderScripts('app-2', [], 'config2-dev'),
            'render correct global script attributes'
        );

        $this->assertEquals(
            '<link rel="stylesheet" href="/build-config1/assets/theme-1.css">'
            .'<link referrerpolicy="origin" rel="stylesheet" href="/build-config2/assets/theme-2.css">',
            $entrypointRenderer->renderLinks('theme-1', [], 'config1-dev')
            .$entrypointRenderer->renderLinks('theme-2', [], 'config2-dev'),
            'render correct global link attributes'
        );
    }

    public function testMultipleConfigInDev()
    {
        $entrypointsLookupConfig1 = $this->getEntrypointsLookup('config1-dev');
        $entrypointsLookupConfig2 = $this->getEntrypointsLookup('config2-dev');

        /**
         * @var EntrypointsLookupCollection|Stub $entrypointsLookupCollection
         */
        $entrypointsLookupCollection = $this->createStub(EntrypointsLookupCollection::class);
        $entrypointsLookupCollection
            ->method('getEntrypointsLookup')
            ->will($this->returnValueMap([
                ['config1-dev', $entrypointsLookupConfig1],
                ['config2-dev', $entrypointsLookupConfig2],
            ]));

        $tagRendererConfig1 = new TagRenderer();
        $tagRendererConfig2 = new TagRenderer();

        /**
         * @var TagRendererCollection|Stub $tagRendererCollection
         */
        $tagRendererCollection = $this->createStub(TagRendererCollection::class);
        $tagRendererCollection
            ->method('getTagRenderer')
            ->will($this->returnValueMap([
                ['config1-dev', $tagRendererConfig1],
                ['config2-dev', $tagRendererConfig2],
            ]));

        $entrypointRenderer = new EntrypointRenderer(
            $entrypointsLookupCollection,
            $tagRendererCollection
        );

        $expectedScripts = '<script type="module" src="http://127.0.0.1:5173/build-config1/@vite/client"></script>'
        .'<script type="module" src="http://127.0.0.1:5173/build-config1/assets/app-1.js"></script>'
        .'<script type="module" src="http://127.0.0.1:5174/build-config2/@vite/client"></script>'
        .'<script type="module" src="http://127.0.0.1:5174/build-config2/assets/app-2.js"></script>';

        $this->assertEquals(
            $expectedScripts,
            $entrypointRenderer->renderScripts('app-1', [], 'config1-dev')
            .$entrypointRenderer->renderScripts('app-2', [], 'config2-dev'),
            'render multiple vite client'
        );

        $this->assertEquals(
            '',
            $entrypointRenderer->renderScripts('app-1', [], 'config1-dev')
            .$entrypointRenderer->renderScripts('app-2', [], 'config2-dev'),
            'render multiple vite client'
        );

        $entrypointRenderer->reset();

        $this->assertEquals(
            $expectedScripts,
            $entrypointRenderer->renderScripts('app-1', [], 'config1-dev')
            .$entrypointRenderer->renderScripts('app-2', [], 'config2-dev'),
            'render multiple vite client'
        );
    }
}
