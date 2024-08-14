<?php

namespace Pentatrion\ViteBundle\Util;

class InlineContent
{
    /* Safari 10.1 supports modules, but does not support the `nomodule` attribute
     *  it will load <script nomodule> anyway
     * https://gist.github.com/samthor/64b114e4a4f539915a95b91ffd340acc
     */
    public const SAFARI10_NO_MODULE_FIX_INLINE_CODE = '!function(){var e=document,t=e.createElement("script");if(!("noModule"in t)&&"onbeforeload"in t){var n=!1;e.addEventListener("beforeload",(function(e){if(e.target===t)n=!0;else if(!e.target.hasAttribute("nomodule")||!n)return;e.preventDefault()}),!0),t.type="module",t.src=".",e.head.appendChild(t),t.remove()}}();';

    /**
     * set or not the __vite_is_modern_browser variable
     * https://github.com/vitejs/vite/pull/15021.
     */
    public const DETECT_MODERN_BROWSER_INLINE_CODE = 'import.meta.url;import("_").catch(()=>1);(async function*(){})().next();if(location.protocol!="file:"){window.__vite_is_modern_browser=true}';

    /* if your browser understands the modules but not dynamic import,
     * load the legacy entrypoints
     *
     * load the <script nomodule crossorigin id="vite-legacy-polyfill" src="..."></script>
     * and the <script nomodule crossorigin id="vite-legacy-entry" data-src="..."></script>
     * if browser accept modules but don't dynamic import or import.meta
     */
    public const DYNAMIC_FALLBACK_INLINE_CODE = <<<INLINE
        \n    (function() {
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
            })();\n
        INLINE;

    public static function getSystemJSInlineCode(string $id): string
    {
        $content = 'System.import(document.getElementById("__ID__").getAttribute("data-src"))';

        return str_replace('__ID__', $id, $content);
    }

    public static function getReactRefreshInlineCode(string $devServerUrl): string
    {
        return <<<INLINE
            \n    import RefreshRuntime from "$devServerUrl@react-refresh"
                RefreshRuntime.injectIntoGlobalHook(window)
                window.\$RefreshReg$ = () => {}
                window.\$RefreshSig$ = () => (type) => type
                window.__vite_plugin_react_preamble_installed__ = true\n
            INLINE;
    }
}
