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

    public function renderScriptFile($fileName, $extraAttributes = [], $withDefaultScriptAttributes = true)
    {
        $attributes = [
            'src' => $fileName,
            'type' => 'module',
        ];

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
