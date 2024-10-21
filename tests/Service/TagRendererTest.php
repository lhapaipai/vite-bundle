<?php

namespace Pentatrion\ViteBundle\Tests\Service;

use Pentatrion\ViteBundle\Model\Tag;
use Pentatrion\ViteBundle\Service\TagRenderer;
use PHPUnit\Framework\TestCase;

class TagRendererTest extends TestCase
{
    public function tag1Provider()
    {
        return [
            [
                new Tag('script', ['src' => '/app.js']),
                '<script src="/app.js"></script>',
            ],
            [
                new Tag('script', ['src' => '/app.js', 'defer' => null]),
                '<script src="/app.js"></script>',
            ],
            [
                new Tag('script', ['src' => '/app.js', 'defer' => false]),
                '<script src="/app.js"></script>',
            ],
            [
                new Tag('script', ['src' => '/app.js', 'async' => true]),
                '<script src="/app.js" async></script>',
            ],
            [
                new Tag('script', ['src' => '/app.js'], '[js-content]'),
                '<script src="/app.js">[js-content]</script>',
            ],
            [
                new Tag('script', ['foo' => 'bar"baz']),
                '<script foo="bar&quot;baz"></script>',
            ],
        ];
    }

    /**
     * @dataProvider tag1Provider
     */
    public function testGenerateTagWithCustomAttributes(Tag $tag, string $expectedString)
    {
        $tagRenderer = new TagRenderer();
        $this->assertEquals($expectedString, $tagRenderer->generateTag($tag));
    }

    public function scriptProvider()
    {
        return [
            [
                ['src' => '/app.js'],
                '<script defer src="/app.js"></script>',
                'global attribute is added',
            ],
            [
                ['src' => '/app.js', 'defer' => true],
                '<script defer src="/app.js"></script>',
                'attributes is not repeated',
            ],
            [
                ['src' => '/app.js', 'defer' => false],
                '<script src="/app.js"></script>',
                'local attribute has priority',
            ],
        ];
    }

    /**
     * @dataProvider scriptProvider
     */
    public function testGenerateScript(array $attributes, string $expectedString, string $message)
    {
        $tagRenderer = new TagRenderer(
            [],
            ['defer' => true],
            ['dummy-1' => 'value'],
            ['dummy-2' => 'value'],
        );

        $tag = $tagRenderer->createScriptTag($attributes);

        $this->assertEquals($expectedString, $tagRenderer->generateTag($tag), $message);
    }

    public function linkStylesheetProvider()
    {
        return [
            [
                '/style.css',
                [],
                '<link referrerpolicy="origin" rel="stylesheet" href="/style.css">',
                'global attribute is added',
            ],
            [
                '/style.css',
                ['referrerpolicy' => 'no-referrer'],
                '<link referrerpolicy="no-referrer" rel="stylesheet" href="/style.css">',
                'local attribute has priority',
            ],
        ];
    }

    /**
     * @dataProvider linkStylesheetProvider
     */
    public function testGenerateLinkStylesheet(string $fileName, array $extraAttributes, string $expectedString, string $message)
    {
        $tagRenderer = new TagRenderer(
            [],
            ['dummy-1' => 'value'],
            ['referrerpolicy' => 'origin'],
            ['dummy-2' => 'value'],
        );

        $tag = $tagRenderer->createLinkStylesheetTag($fileName, $extraAttributes);

        $this->assertEquals($expectedString, $tagRenderer->generateTag($tag), $message);
    }

    public function linkPreloadProvider()
    {
        return [
            [
                '/dependency.js',
                [],
                '<link referrerpolicy="origin" rel="modulepreload" href="/dependency.js">',
                'global preload attributes are added',
            ],
        ];
    }

    /**
     * @dataProvider linkPreloadProvider
     */
    public function testGenerateLinkPreload(string $fileName, array $extraAttributes, string $expectedString, string $message)
    {
        $tagRenderer = new TagRenderer(
            [],
            ['dummy-1' => 'value'],
            ['dummy-2' => 'value'],
            ['referrerpolicy' => 'origin']
        );

        $tag = $tagRenderer->createModulePreloadLinkTag($fileName, $extraAttributes);

        $this->assertEquals($expectedString, $tagRenderer->generateTag($tag), $message);
    }

    public function testSpecialTag()
    {
        $tagRenderer = new TagRenderer(
            [],
            ['defer' => true],
            ['referrerpolicy' => 'origin']
        );

        $tag = $tagRenderer->createInternalScriptTag(['src' => '/internal.js']);
        $this->assertEquals(
            '<script src="/internal.js"></script>',
            $tagRenderer->generateTag($tag),
            'internal script tag has not global script tags'
        );

        $tag = $tagRenderer->createViteClientScript('http://127.0.0.1:5173/build/@vite/client');
        $this->assertEquals(
            '<script type="module" src="http://127.0.0.1:5173/build/@vite/client" crossorigin></script>',
            $tagRenderer->generateTag($tag)
        );
        $this->assertTrue($tag->isInternal());
        $this->assertEquals('', $tag->getOrigin());

        $tag = $tagRenderer->createViteClientScript('http://127.0.0.1:5173/build/@vite/client', 'app-foo');
        $this->assertEquals(
            '<script type="module" src="http://127.0.0.1:5173/build/@vite/client" crossorigin></script>',
            $tagRenderer->generateTag($tag)
        );
        $this->assertTrue($tag->isInternal());
        $this->assertEquals('app-foo', $tag->getOrigin());

        $tag = $tagRenderer->createReactRefreshScript('http://127.0.0.1:5173');
        $this->assertEquals(
            file_get_contents(__DIR__.'/../fixtures/react-refresh.html'),
            $tagRenderer->generateTag($tag)
        );

        $tag = $tagRenderer->createSafariNoModuleScript();
        $this->assertEquals(
            file_get_contents(__DIR__.'/../fixtures/safari-no-module.html'),
            $tagRenderer->generateTag($tag)
        );

        $tag = $tagRenderer->createDynamicFallbackScript();
        $this->assertEquals(
            file_get_contents(__DIR__.'/../fixtures/dynamic-fallback.html'),
            $tagRenderer->generateTag($tag)
        );

        $tag = $tagRenderer->createDetectModernBrowserScript();
        $this->assertEquals(
            file_get_contents(__DIR__.'/../fixtures/modern-browser.html'),
            $tagRenderer->generateTag($tag)
        );

        $tagRendererCrossOrigin = new TagRenderer(
            ['crossorigin' => 'anonymous'],
            ['defer' => true],
            ['referrerpolicy' => 'origin']
        );
        $tag = $tagRendererCrossOrigin->createInternalScriptTag(['src' => '/internal.js']);
        $this->assertEquals(
            '<script src="/internal.js"></script>',
            $tagRendererCrossOrigin->generateTag($tag),
            'internal script tag has not global default attributes'
        );
    }
}
