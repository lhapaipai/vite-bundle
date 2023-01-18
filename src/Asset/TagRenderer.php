<?php

namespace Pentatrion\ViteBundle\Asset;

class TagRenderer
{
    /** @deprecated */
    private $defaultScriptAttributes = [];
    /** @deprecated */
    private $defaultLinkAttributes = [];

    private $defaultBuild;
    private $builds;

    // https://gist.github.com/samthor/64b114e4a4f539915a95b91ffd340acc
    protected const SAFARI10_NO_MODULE_FIX = '<script nomodule>!function(){var e=document,t=e.createElement("script");if(!("noModule"in t)&&"onbeforeload"in t){var n=!1;e.addEventListener("beforeload",(function(e){if(e.target===t)n=!0;else if(!e.target.hasAttribute("nomodule")||!n)return;e.preventDefault()}),!0),t.type="module",t.src=".",e.head.appendChild(t),t.remove()}}();</script>';

    protected const DETECT_MODERN_BROWSER_CODE = '<script type="module">try{import.meta.url;import("_").catch(()=>1);}catch(e){}window.__vite_is_modern_browser=true;</script>';
    protected const DYNAMIC_FALLBACK_INLINE_CODE = '
    <script type="module">
        ! function() {
            if (window.__vite_is_modern_browser) return;
            console.warn("vite: loading legacy build because dynamic import or import.meta.url is unsupported, syntax error above should be ignored");
            var e = document.getElementById("vite-legacy-polyfill"),
                n = document.createElement("script");
                n.src = e.src,
                n.onload = function() {
                    document.querySelectorAll("script.vite-legacy-entry").forEach(function(elt) {
                        System.import(elt.getAttribute("data-src"));
                    })
                },
                document.body.appendChild(n)
        }();
    </script>';

    protected const SYSTEM_JS_INLINE_CODE = 'System.import(document.getElementById("__ID__").getAttribute("data-src"))';

    public function renderLegacyCheckInline()
    {
        return self::DETECT_MODERN_BROWSER_CODE
            .self::DYNAMIC_FALLBACK_INLINE_CODE
            .self::SAFARI10_NO_MODULE_FIX;
    }

    public function getSystemJSInlineCode($id)
    {
        return str_replace('__ID__', $id, self::SYSTEM_JS_INLINE_CODE);
    }

    public function __construct(
        $defaultBuild = 'default',
        $builds = []
    ) {
        $this->defaultBuild = $defaultBuild;
        $this->builds = $builds;
    }

    public function renderScriptFile($attributes = [], $content = '', $buildName = null, $withDefaultScriptAttributes = true)
    {
        if ($withDefaultScriptAttributes) {
            if (is_null($buildName)) {
                $buildName = $this->defaultBuild;
            }

            $attributes = array_merge($this->builds[$buildName]['script_attributes'], $attributes);
        }

        return sprintf(
            '<script %s>%s</script>',
            $this->convertArrayToAttributes($attributes),
            $content
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

    public function renderLinkStylesheet($fileName, $extraAttributes = [], $buildName = null)
    {
        if (is_null($buildName)) {
            $buildName = $this->defaultBuild;
        }

        $attributes = [
            'rel' => 'stylesheet',
            'href' => $fileName,
        ];

        $attributes = array_merge($attributes, $this->builds[$buildName]['link_attributes'], $extraAttributes);

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
