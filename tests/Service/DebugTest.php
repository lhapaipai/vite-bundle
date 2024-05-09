<?php

namespace Pentatrion\ViteBundle\Tests\Service;

use Pentatrion\ViteBundle\DependencyInjection\PentatrionViteExtension;
use Pentatrion\ViteBundle\Service\Debug;
use Pentatrion\ViteBundle\Service\EntrypointsLookup;
use Pentatrion\ViteBundle\Service\EntrypointsLookupCollection;
use Pentatrion\ViteBundle\Service\FileAccessor;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @phpstan-import-type ResolvedConfig from PentatrionViteExtension
 */
class DebugTest extends TestCase
{
    public function testGetInfoUrl(): void
    {
        $url = Debug::getInfoUrl('http://127.0.0.1:5173', '/build/');
        $this->assertEquals($url, 'http://127.0.0.1:5173/build/@vite/info');
    }

    /**
     * @return array<array{mixed, string}>
     */
    public function scalarInputProvider(): array
    {
        return [
            [true, 'true'],
            [9, '9'],
            [3.14, '3.14'],
            ['Hello', 'Hello'],
        ];
    }

    /**
     * @dataProvider scalarInputProvider
     */
    public function testStringifyWithScalarTypes(mixed $input, string $expectedString): void
    {
        $output = Debug::stringifyScalar($input);
        $this->assertEquals($expectedString, $output);
    }

    /**
     * @return array<array{mixed, string}>
     */
    public function mixedInputProvider(): array
    {
        return [
            [null, '<i>null</i>'],
            [[], '[]'],
            [['hello' => 'world'], '<ul><li>hello: world</li></ul>'],
            [['hello' => 'world', 'foo' => 'bar'], '<ul><li>hello: world</li><li>foo: bar</li></ul>'],
            [['hello', 'world'], '<ul><li>hello</li><li>world</li></ul>'],

            [['nested' => ['foo' => 'bar']], '<ul><li>nested: <ul><li>foo: bar</li></ul></li></ul>'],

            [(object) ['foo' => 'bar'], '<pre>stdClass Object
(
    [foo] => bar
)
</pre>'],
        ];
    }

    /**
     * @dataProvider mixedInputProvider
     */
    public function testStringifyWithAllTypes(mixed $input, string $expectedString): void
    {
        $output = Debug::stringify($input);
        $this->assertEquals($expectedString, $output);
    }

    private function getEntrypointsLookupCollection(string $prefix): EntrypointsLookupCollection
    {
        /**
         * @var Stub $fileAccessorSub
         */
        $fileAccessorSub = $this->createStub(FileAccessor::class);

        $fileAccessorSub
            ->method('getData')
            ->willReturnCallback(function ($prefix, $fileType) {
                return self::getJsonFixtureContent('entrypoints/'.$prefix.'/'.$fileType.'.json');
            });

        $fileAccessorSub
            ->method('hasFile')
            ->willReturnCallback(function ($prefix, $fileType) {
                $path = __DIR__.'/../fixtures/entrypoints/'.$prefix.'/'.$fileType.'.json';

                return file_exists($path);
            });

        /**
         * @var FileAccessor $fileAccessor
         */
        $fileAccessor = $fileAccessorSub;

        $entrypointsLookup = new EntrypointsLookup(
            $fileAccessor,
            $prefix,
            true
        );

        $mockEntrypointsLookupCollectionStub = $this->createStub(EntrypointsLookupCollection::class);
        $mockEntrypointsLookupCollectionStub->method('getEntrypointsLookup')
            ->willReturn($entrypointsLookup);

        /**
         * @var EntrypointsLookupCollection $entrypointsLookupCollection
         */
        $entrypointsLookupCollection = $mockEntrypointsLookupCollectionStub;

        return $entrypointsLookupCollection;
    }

    private static function getJsonFixtureContent(string $path): mixed
    {
        $path = __DIR__.'/../fixtures/'.$path;
        $content = file_get_contents($path);
        if (!$content) {
            throw new \Exception(sprintf('Unable to find fixture file %s', $path));
        }

        return json_decode($content, true);
    }

    /**
     * @return ResolvedConfig
     */
    private static function createEmptyConfig(): array
    {
        return [
            'base' => '/build/',
            'script_attributes' => [],
            'link_attributes' => [],
            'preload_attributes' => [],
        ];
    }

    public function testGetViteDevCompleteConfig(): void
    {
        $mockHttpClient = new MockHttpClient([
            new JsonMockResponse(self::getJsonFixtureContent('vite-infos/basic-response.json')),
            new MockResponse(),
        ]);

        $mockEntrypointsLookupCollection = $this->getEntrypointsLookupCollection('debug-dev');

        $debug = new Debug(
            ['_default' => self::createEmptyConfig()],
            $mockHttpClient,
            $mockEntrypointsLookupCollection
        );

        $configRunning = $debug->getViteCompleteConfigs();

        $this->assertCount(1, $configRunning);
        $this->assertEquals($configRunning[0]['content'], ['principal' => ['foo' => 'bar']]);

        $configStopped = $debug->getViteCompleteConfigs();
        $this->assertCount(1, $configStopped);
        $this->assertSame($configStopped[0]['content'], null);
    }

    public function testGetViteBuildCompleteConfig(): void
    {
        $mockEntrypointsLookupCollection = $this->getEntrypointsLookupCollection('debug-build');

        $debug = new Debug(
            ['_default' => self::createEmptyConfig()],
            new MockHttpClient(),
            $mockEntrypointsLookupCollection
        );

        $config = $debug->getViteCompleteConfigs();
        $this->assertCount(1, $config);
        $this->assertSame($config[0]['content'], null);
    }
}
