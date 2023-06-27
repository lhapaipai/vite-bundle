<?php

namespace Pentatrion\ViteBundle\Asset;

class TagRenderer
{
    private $defaultBuild;
    private $builds;

    // https://gist.github.com/samthor/64b114e4a4f539915a95b91ffd340acc
    public const SAFARI10_NO_MODULE_FIX = '<!-- SAFARI10_NO_MODULE_FIX --><script nomodule>!function(){var e=document,t=e.createElement("script");if(!("noModule"in t)&&"onbeforeload"in t){var n=!1;e.addEventListener("beforeload",(function(e){if(e.target===t)n=!0;else if(!e.target.hasAttribute("nomodule")||!n)return;e.preventDefault()}),!0),t.type="module",t.src=".",e.head.appendChild(t),t.remove()}}();</script>';

    public const DETECT_MODERN_BROWSER_CODE = '<!-- DETECT_MODERN_BROWSER_CODE --><script type="module">try{import.meta.url;import("_").catch(()=>1);}catch(e){}window.__vite_is_modern_browser=true;</script>';

    // load the <script nomodule crossorigin id="vite-legacy-polyfill" src="..."></script>
    // and the <script nomodule crossorigin id="vite-legacy-entry" data-src="..."></script>
    // if browser accept modules but don't dynamic import or import.meta
    public const DYNAMIC_FALLBACK_INLINE_CODE = '
    <!-- DYNAMIC_FALLBACK_INLINE_CODE --><script type="module">
        (function() {
            if (window.__vite_is_modern_browser) return;
            console.warn("vite: loading legacy build because dynamic import or import.meta.url is unsupported, syntax error above should be ignored");
            var legacyPolyfill = document.getElementById("vite-legacy-polyfill")
            var script = document.createElement("script");
            script.src = legacyPolyfill.src;
            script.onload = function() {
                document.querySelectorAll("script.vite-legacy-entry").forEach(function(elt) {
                    System.import(elt.getAttribute("data-src"));
                });
            };
            document.body.appendChild(script);
        })();
    </script>';

    public const SYSTEM_JS_INLINE_CODE = 'System.import(document.getElementById("__ID__").getAttribute("data-src"))';

    public function __construct(
        $defaultBuild = 'default',
        $builds = []
    ) {
        $this->defaultBuild = $defaultBuild;
        $this->builds = $builds;
    }

    public function getSystemJSInlineCode($id): string
    {
        return str_replace('__ID__', $id, self::SYSTEM_JS_INLINE_CODE);
    }

    public function renderReactRefreshInline($devServerUrl): string
    {
        return '  <script type="module">
    import RefreshRuntime from "'.$devServerUrl.'@react-refresh"
    RefreshRuntime.injectIntoGlobalHook(window)
    window.$RefreshReg$ = () => {}
    window.$RefreshSig$ = () => (type) => type
    window.__vite_plugin_react_preamble_installed__ = true
    </script>'.PHP_EOL;
    }

    public function renderScriptFile($extraAttributes = [], $content = '', $buildName = null): string
    {
        if (is_null($buildName)) {
            $buildName = $this->defaultBuild;
        }

        $attributes = array_merge($this->builds[$buildName]['script_attributes'], $extraAttributes);

        return $this->renderTag('script', $attributes, $content);
    }

    public function renderLinkStylesheet($fileName, $extraAttributes = [], $buildName = null): string
    {
        if (is_null($buildName)) {
            $buildName = $this->defaultBuild;
        }

        $attributes = [
            'rel' => 'stylesheet',
            'href' => $fileName,
        ];

        $attributes = array_merge($attributes, $this->builds[$buildName]['link_attributes'], $extraAttributes);

        return $this->renderTag('link', $attributes);
    }

    public function renderLinkPreload($fileName, $extraAttributes = [], $buildName = null): string
    {
        if (is_null($buildName)) {
            $buildName = $this->defaultBuild;
        }

        $attributes = [
            'rel' => 'modulepreload',
            'href' => $fileName,
        ];

        $attributes = array_merge($attributes, $this->builds[$buildName]['link_attributes'], $extraAttributes);

        return $this->renderTag('link', $attributes);
    }

    public function renderTag($tagName, $attributes, $content = ''): string
    {
        return sprintf(
            '<%s %s>%s</%s>',
            $tagName,
            self::convertArrayToAttributes($attributes),
            $content,
            $tagName
        ).PHP_EOL;
    }

    private static function convertArrayToAttributes(array $attributes): string
    {
        $nonNullAttributes = array_filter(
            $attributes,
            function ($value, $key) {
                return null !== $value;
            },
            ARRAY_FILTER_USE_BOTH
        );

        return implode(' ', array_map(
            function ($key, $value) {
                if (true === $value) {
                    return sprintf('%s', $key);
                } else {
                    return sprintf('%s="%s"', $key, htmlentities($value));
                }
            },
            array_keys($nonNullAttributes),
            $nonNullAttributes
        ));
    }
}
