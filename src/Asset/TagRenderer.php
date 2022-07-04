<?php

namespace Pentatrion\ViteBundle\Asset;

class TagRenderer
{
    public function renderScriptFile($fileName)
    {
        $attributes = [
            'src' => $fileName,
            'type' => 'module'
        ];
        return sprintf(
            '<script %s></script>',
            $this->convertArrayToAttributes($attributes)
        );
    }

    public function renderReactRefreshInline($devServerUrl)
    {
        return '  <script type="module">
    import RefreshRuntime from "' . $devServerUrl . '@react-refresh"
    RefreshRuntime.injectIntoGlobalHook(window)
    window.$RefreshReg$ = () => {}
    window.$RefreshSig$ = () => (type) => type
    window.__vite_plugin_react_preamble_installed__ = true
    </script>';
    }

    public function renderLinkStylesheet($fileName)
    {
        $attributes = [
            'rel' => 'stylesheet',
            'href' => $fileName
        ];
        return sprintf(
            '<link %s>',
            $this->convertArrayToAttributes($attributes)
        );
    }

    public function renderLinkPreload($fileName)
    {
        $attributes = [
            'rel' => 'modulepreload',
            'href' => $fileName
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
                if ($value === true) {
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
