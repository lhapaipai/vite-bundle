<?php

namespace Pentatrion\ViteBundle\Tests\Asset;

use Pentatrion\ViteBundle\Asset\EntrypointsLookup;
use Pentatrion\ViteBundle\Asset\FileAccessor;
use Pentatrion\ViteBundle\Exception\EntrypointNotFoundException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class EntrypointsLookupTest extends TestCase
{
    private function getEntrypointsLookup($prefix)
    {
        /**
         * @var FileAccessor|Stub $fileAccessor
         */
        $fileAccessor = $this->createStub(FileAccessor::class);

        $fileAccessor
            ->method('getData')
            ->willReturnCallback(function ($prefix, $fileType) {
                $path = __DIR__.'/../fixtures/entrypoints/'.$prefix.'/'.$fileType.'.json';

                return json_decode(file_get_contents($path), true);
            });

        $fileAccessor
            ->method('hasFile')
            ->willReturnCallback(function ($prefix, $fileType) {
                $path = __DIR__.'/../fixtures/entrypoints/'.$prefix.'/'.$fileType.'.json';

                return file_exists($path);
            });

        return new EntrypointsLookup(
            $fileAccessor,
            $prefix,
            true
        );
    }

    public function testExtra()
    {
        $entrypointsLookupFileNotExists = $this->getEntrypointsLookup('not-found');
        $entrypointsLookupBasicBuild = $this->getEntrypointsLookup('basic-build');

        $this->assertEquals(
            false,
            $entrypointsLookupFileNotExists->hasFile()
        );

        $this->assertEquals(
            true,
            $entrypointsLookupBasicBuild->hasFile()
        );
    }

    public function testExceptionOnMissingEntry()
    {
        $entrypointsLookupBasicBuild = $this->getEntrypointsLookup('basic-build');
        $this->expectException(EntrypointNotFoundException::class);

        $entrypointsLookupBasicBuild->getJSFiles('unknown-entrypoint');
    }

    public function testViteServer()
    {
        $entrypointsLookupBasicDev = $this->getEntrypointsLookup('basic-dev');
        $entrypointsLookupBasicBuild = $this->getEntrypointsLookup('basic-build');

        $this->assertEquals(
            'http://127.0.0.1:5173',
            $entrypointsLookupBasicDev->getViteServer()
        );

        $this->assertEquals(
            null,
            $entrypointsLookupBasicBuild->getViteServer()
        );

        $this->assertEquals(
            false,
            $entrypointsLookupBasicDev->isBuild()
        );

        $this->assertEquals(
            true,
            $entrypointsLookupBasicBuild->isBuild()
        );
    }

    public function devfilesProvider()
    {
        return [
            ['app', [
                'assets' => [],
                'css' => [],
                'dynamic' => [],
                'js' => ['http://127.0.0.1:5173/build/assets/app.js'],
                'preload' => [],
            ]],
            ['theme', [
                'assets' => [],
                'css' => ['http://127.0.0.1:5173/build/assets/theme.scss'],
                'dynamic' => [],
                'js' => [],
                'preload' => [],
            ]],
        ];
    }

    /**
     * @dataProvider devfilesProvider
     */
    public function testGetDevFiles($entryName, $expectedFiles)
    {
        $entrypointsLookupBasicDev = $this->getEntrypointsLookup('basic-dev');

        $this->assertEquals($expectedFiles['css'], $entrypointsLookupBasicDev->getCSSFiles($entryName));
        $this->assertEquals($expectedFiles['dynamic'], $entrypointsLookupBasicDev->getJavascriptDynamicDependencies($entryName));
        $this->assertEquals($expectedFiles['js'], $entrypointsLookupBasicDev->getJSFiles($entryName));
        $this->assertEquals($expectedFiles['preload'], $entrypointsLookupBasicDev->getJavascriptDependencies($entryName));
    }

    public function buildfilesProvider()
    {
        return [
            ['app', [
                'assets' => [],
                'css' => [],
                'dynamic' => [],
                'js' => ['/build/assets/app.js'],
                'preload' => [],
            ]],
            ['theme', [
                'assets' => [],
                'css' => ['/build/assets/theme.css'],
                'dynamic' => [],
                'js' => [],
                'preload' => [],
            ]],
            ['with-dep', [
                'assets' => [],
                'css' => ['/build/assets/main.css'],
                'dynamic' => [],
                'js' => ['/build/assets/main.js'],
                'preload' => ['/build/assets/vue.js', '/build/assets/react.js'],
            ]],
            ['with-async', [
                'assets' => [],
                'css' => ['/build/assets/main.css'],
                'dynamic' => ['/build/assets/async-script.js'],
                'js' => ['/build/assets/main.js'],
                'preload' => ['/build/assets/vue.js', '/build/assets/react.js'],
            ]],
        ];
    }

    /**
     * @dataProvider buildfilesProvider
     */
    public function testGetBuildFiles($entryName, $expectedFiles)
    {
        $entrypointsLookupBasicBuild = $this->getEntrypointsLookup('basic-build');

        $this->assertEquals($expectedFiles['css'], $entrypointsLookupBasicBuild->getCSSFiles($entryName));
        $this->assertEquals($expectedFiles['dynamic'], $entrypointsLookupBasicBuild->getJavascriptDynamicDependencies($entryName));
        $this->assertEquals($expectedFiles['js'], $entrypointsLookupBasicBuild->getJSFiles($entryName));
        $this->assertEquals($expectedFiles['preload'], $entrypointsLookupBasicBuild->getJavascriptDependencies($entryName));
    }

    public function buildLegacyProvider()
    {
        return [
            ['app', [
                'assets' => [],
                'css' => [],
                'dynamic' => [],
                'js' => ['/build/assets/app.js'],
                'preload' => [],
                'legacy_js' => '/build/assets/app-legacy.js',
            ]],
            ['theme', [
                'assets' => [],
                'css' => ['/build/assets/theme.css'],
                'dynamic' => [],
                'js' => [],
                'preload' => [],
                'legacy_js' => '/build/assets/theme-legacy.js',
            ]],
        ];
    }

    /**
     * @dataProvider buildLegacyProvider
     */
    public function testGetBuildLegacyFiles($entryName, $expectedFiles)
    {
        $entrypointsLookupLegacyBuild = $this->getEntrypointsLookup('legacy-build');

        $this->assertEquals($expectedFiles['css'], $entrypointsLookupLegacyBuild->getCSSFiles($entryName));
        $this->assertEquals($expectedFiles['dynamic'], $entrypointsLookupLegacyBuild->getJavascriptDynamicDependencies($entryName));
        $this->assertEquals($expectedFiles['js'], $entrypointsLookupLegacyBuild->getJSFiles($entryName));
        $this->assertEquals($expectedFiles['preload'], $entrypointsLookupLegacyBuild->getJavascriptDependencies($entryName));
        $this->assertEquals($expectedFiles['legacy_js'], $entrypointsLookupLegacyBuild->getLegacyJSFile($entryName));
    }

    public function testHashOfFiles()
    {
        $entrypointsLookupBasicBuild = $this->getEntrypointsLookup('basic-build');
        $this->assertEquals(
            null,
            $entrypointsLookupBasicBuild->getFileHash('/build/assets/app.js')
        );

        $entrypointsLookupMetadataBuild = $this->getEntrypointsLookup('metadata-build');
        $this->assertEquals(
            'sha256-XYZ',
            $entrypointsLookupMetadataBuild->getFileHash('/build/assets/app.js')
        );

        $entrypointsLookupMetadataBuild = $this->getEntrypointsLookup('metadata-build');
        $this->assertEquals(
            null,
            $entrypointsLookupMetadataBuild->getFileHash('/build-file-without-metadata.js')
        );
    }
}
