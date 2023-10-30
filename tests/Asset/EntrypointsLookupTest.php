<?php

namespace Pentatrion\ViteBundle\Tests\Asset;

use Pentatrion\ViteBundle\Asset\EntrypointsLookup;
use Pentatrion\ViteBundle\Exception\EntrypointNotFoundException;
use PHPUnit\Framework\TestCase;

class EntrypointsLookupTest extends TestCase
{
    private function getEntrypointsLookup($prefix)
    {
        return new EntrypointsLookup(
            __DIR__.'/../fixtures/entrypoints',
            ['base' => '/'.$prefix.'/'],
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
            ['origin' => 'http://127.0.0.1:5173', 'base' => '/build/'],
            $entrypointsLookupBasicDev->getViteServer()
        );

        $this->assertEquals(
            false,
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
    public function testGetDevFiles($entryName, $files)
    {
        $entrypointsLookupBasicDev = $this->getEntrypointsLookup('basic-dev');

        $this->assertEquals($files['css'], $entrypointsLookupBasicDev->getCSSFiles($entryName));
        $this->assertEquals($files['dynamic'], $entrypointsLookupBasicDev->getJavascriptDynamicDependencies($entryName));
        $this->assertEquals($files['js'], $entrypointsLookupBasicDev->getJSFiles($entryName));
        $this->assertEquals($files['preload'], $entrypointsLookupBasicDev->getJavascriptDependencies($entryName));
    }

    public function buildfilesProvider()
    {
        return [
            ['app', [
                'assets' => [],
                'css' => [],
                'dynamic' => [],
                'js' => ['/build/assets/pageImports-53eb9fd1.js'],
                'preload' => [],
            ]],
            ['theme', [
                'assets' => [],
                'css' => ['/build/assets/theme-62617963.css'],
                'dynamic' => [],
                'js' => [],
                'preload' => [],
            ]],
            ['with-dep', [
                'assets' => [],
                'css' => ['/build/assets/main-76fa9059.css'],
                'dynamic' => [],
                'js' => ['/build/assets/main-e664f4b5.js'],
                'preload' => ['/build/assets/vue-2d05229a.js', '/build/assets/react-2d05228c.js'],
            ]],
            ['with-async', [
                'assets' => [],
                'css' => ['/build/assets/main-76fa9059.css'],
                'dynamic' => ['/build/assets/async-script-12324565.js'],
                'js' => ['/build/assets/main-e664f4b5.js'],
                'preload' => ['/build/assets/vue-2d05229a.js', '/build/assets/react-2d05228c.js'],
            ]],
        ];
    }

    /**
     * @dataProvider buildfilesProvider
     */
    public function testGetBuildFiles($entryName, $files)
    {
        $entrypointsLookupBasicBuild = $this->getEntrypointsLookup('basic-build');

        $this->assertEquals($files['css'], $entrypointsLookupBasicBuild->getCSSFiles($entryName));
        $this->assertEquals($files['dynamic'], $entrypointsLookupBasicBuild->getJavascriptDynamicDependencies($entryName));
        $this->assertEquals($files['js'], $entrypointsLookupBasicBuild->getJSFiles($entryName));
        $this->assertEquals($files['preload'], $entrypointsLookupBasicBuild->getJavascriptDependencies($entryName));
    }

    public function buildLegacyProvider()
    {
        return [
            ['app', [
                'assets' => [],
                'css' => [],
                'dynamic' => [],
                'js' => ['/build/assets/app-23802617.js'],
                'preload' => [],
                'legacy_js' => '/build/assets/app-legacy-59951366.js',
            ]],
            ['theme', [
                'assets' => [],
                'css' => ['/build/assets/theme-5cd46aed.css'],
                'dynamic' => [],
                'js' => [],
                'preload' => [],
                'legacy_js' => '/build/assets/theme-legacy-de9eb869.js',
            ]],
        ];
    }

    /**
     * @dataProvider buildLegacyProvider
     */
    public function testGetBuildLegacyFiles($entryName, $files)
    {
        $entrypointsLookupLegacyBuild = $this->getEntrypointsLookup('legacy-build');

        $this->assertEquals($files['css'], $entrypointsLookupLegacyBuild->getCSSFiles($entryName));
        $this->assertEquals($files['dynamic'], $entrypointsLookupLegacyBuild->getJavascriptDynamicDependencies($entryName));
        $this->assertEquals($files['js'], $entrypointsLookupLegacyBuild->getJSFiles($entryName));
        $this->assertEquals($files['preload'], $entrypointsLookupLegacyBuild->getJavascriptDependencies($entryName));
        $this->assertEquals($files['legacy_js'], $entrypointsLookupLegacyBuild->getLegacyJSFile($entryName));
    }

    public function testHashOfFiles()
    {
        $entrypointsLookupBasicBuild = $this->getEntrypointsLookup('basic-build');
        $this->assertEquals(
            null,
            $entrypointsLookupBasicBuild->getFileHash('/build/assets/pageImports-53eb9fd1.js')
        );

        $entrypointsLookupMetadataBuild = $this->getEntrypointsLookup('metadata-build');
        $this->assertEquals(
            'sha256-qABtt8+MbhDq8dts7DSJOnBqCO1QbV2S6zg24ylLkKY=',
            $entrypointsLookupMetadataBuild->getFileHash('http://cdn.with-cdn.symfony-vite-dev.localhost/assets/pageVue-bda8ac3b.js')
        );

        $entrypointsLookupMetadataBuild = $this->getEntrypointsLookup('metadata-build');
        $this->assertEquals(
            null,
            $entrypointsLookupMetadataBuild->getFileHash('/build-file-without-metadata.js')
        );
    }
}
