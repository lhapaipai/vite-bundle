<?php

namespace Pentatrion\ViteBundle\Asset;

class TagRenderer
{
    private $defaultScriptAttributes;
    private $defaultLinkAttributes;

    public function __construct(
        $defaultScriptAttributes = [],
        $defaultLinkAttributes = []
    ) {
        $this->defaultScriptAttributes = $defaultScriptAttributes;
        $this->defaultLinkAttributes = $defaultLinkAttributes;
    }

    public function renderScriptFile($fileName, $extraAttributes = [], $withDefaultScriptAttributes = true, $withModule = true)
    {
        if ($withModule) {
            $attributes = [
                'src' => $fileName,
                'type' => 'module',
            ];
        } else {
            $attributes = [
                'src' => $fileName,
                'nomodule' => true,
                'crossorigin' => true,
            ];
        }

        if ($withDefaultScriptAttributes) {
            $attributes = array_merge($attributes, $this->defaultScriptAttributes, $extraAttributes);
        } else {
            $attributes = array_merge($attributes, $extraAttributes);
        }

        return sprintf(
            '<script %s></script>',
            $this->convertArrayToAttributes($attributes)
        );
    }

    public function renderLegacyScriptFile($fileName, $entryName)
    {
        $id = "vite-legacy-entry-$entryName";

        $attributes = [
            'data-src' => $fileName,
            'id' => $id,
            'class' => 'vite-legacy-entry',
        ];

        return sprintf(
            '<script nomodule crossorigin %s>System.import(document.getElementById("'.$id.'").getAttribute("data-src"))</script>',
            $this->convertArrayToAttributes($attributes)
        );
    }

    public function renderReactRefreshInline($devServerUrl)
    {
        return '  <script type="module">
    import RefreshRuntime from "'.$devServerUrl.'@react-refresh"
    RefreshRuntime.injectIntoGlobalHook(window)
    window.$RefreshReg$ = () => {}
    window.$RefreshSig$ = () => (type) => type
    window.__vite_plugin_react_preamble_installed__ = true
    </script>';
    }

    public function renderLegacyCheckInline()
    {
        return '    <script type="module">try{import.meta.url;import("_").catch(()=>1);}catch(e){}window.__vite_is_modern_browser=true;</script>
        <script type="module">!function(){if(window.__vite_is_modern_browser)return;console.warn("vite: loading legacy build because dynamic import or import.meta.url is unsupported, syntax error above should be ignored");var e=document.getElementById("vite-legacy-polyfill"),n=document.createElement("script");n.src=e.src,n.onload=function(){document.querySelectorAll("script.vite-legacy-entry").forEach(function (elt) { System.import(elt.getAttribute("data-src")); }) },document.body.appendChild(n)}();</script>
        <script nomodule>!function(){var e=document,t=e.createElement("script");if(!("noModule"in t)&&"onbeforeload"in t){var n=!1;e.addEventListener("beforeload",(function(e){if(e.target===t)n=!0;else if(!e.target.hasAttribute("nomodule")||!n)return;e.preventDefault()}),!0),t.type="module",t.src=".",e.head.appendChild(t),t.remove()}}();</script>
        ';
    }

    public function renderLinkStylesheet($fileName, $extraAttributes = [])
    {
        $attributes = [
            'rel' => 'stylesheet',
            'href' => $fileName,
        ];

        $attributes = array_merge($attributes, $this->defaultLinkAttributes, $extraAttributes);

        return sprintf(
            '<link %s>',
            $this->convertArrayToAttributes($attributes)
        );
    }

    public function renderLinkPreload($fileName)
    {
        $attributes = [
            'rel' => 'modulepreload',
            'href' => $fileName,
        ];

        return sprintf(
            '<link %s>',
            $this->convertArrayToAttributes($attributes)
        );
    }

    private function convertArrayToAttributes(array $attributes): string
    {
        return implode(' ', array_map(
            function ($key, $value) {
                if (true === $value) {
                    return sprintf('%s', $key);
                } else {
                    return sprintf('%s="%s"', $key, htmlentities($value));
                }
            },
            array_keys($attributes),
            $attributes
        ));
    }
}
