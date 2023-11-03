<?php

namespace Pentatrion\ViteBundle\Asset;

class TagRenderer
{
    private array $globalScriptAttributes;
    private array $globalLinkAttributes;

    public function __construct(
        array $scriptAttributes = [],
        array $linkAttributes = []
    ) {
        $this->globalScriptAttributes = $scriptAttributes;
        $this->globalLinkAttributes = $linkAttributes;
    }

    public function createViteClientScript(string $src): Tag
    {
        return $this->createInternalScriptTag(
            [
                'type' => 'module',
                'src' => $src,
            ]
        );
    }

    public function createReactRefreshScript(string $devServerUrl): Tag
    {
        return $this->createInternalScriptTag(
            ['type' => 'module'],
            InlineContent::getReactRefreshInlineCode($devServerUrl)
        );
    }

    public function createSafariNoModuleScript(): Tag
    {
        return $this->createInternalScriptTag(
            ['nomodule' => true],
            InlineContent::SAFARI10_NO_MODULE_FIX_INLINE_CODE
        );
    }

    public function createDynamicFallbackScript(): Tag
    {
        return $this->createInternalScriptTag(
            ['type' => 'module'],
            InlineContent::DYNAMIC_FALLBACK_INLINE_CODE
        );
    }

    public function createDetectModernBrowserScript(): Tag
    {
        return $this->createInternalScriptTag(
            ['type' => 'module'],
            InlineContent::DETECT_MODERN_BROWSER_INLINE_CODE
        );
    }

    public function createInternalScriptTag(array $attributes = [], string $content = ''): Tag
    {
        $tag = new Tag(
            Tag::SCRIPT_TAG,
            $attributes,
            $content,
            true
        );

        return $tag;
    }

    public function createScriptTag(array $attributes = [], string $content = ''): Tag
    {
        $tag = new Tag(
            Tag::SCRIPT_TAG,
            array_merge($this->globalScriptAttributes, $attributes),
            $content
        );

        return $tag;
    }

    public function createLinkStylesheetTag(string $fileName, array $extraAttributes = []): Tag
    {
        $attributes = [
            'rel' => 'stylesheet',
            'href' => $fileName,
        ];

        $tag = new Tag(
            Tag::LINK_TAG,
            array_merge($this->globalLinkAttributes, $attributes, $extraAttributes)
        );

        return $tag;
    }

    public function createModulePreloadLinkTag(string $fileName, array $extraAttributes = []): Tag
    {
        $attributes = [
            'rel' => 'modulepreload',
            'href' => $fileName,
        ];

        $tag = new Tag(
            Tag::LINK_TAG,
            array_merge($attributes, $extraAttributes)
        );

        return $tag;
    }

    public static function generateTag(Tag $tag): string
    {
        return $tag->isLinkTag() ? sprintf(
            '<%s %s>',
            $tag->getTagName(),
            self::convertArrayToAttributes($tag->getAttributes())
        ) : sprintf(
            '<%s %s>%s</%s>',
            $tag->getTagName(),
            self::convertArrayToAttributes($tag->getAttributes()),
            $tag->getContent(),
            $tag->getTagName()
        );
    }

    private static function convertArrayToAttributes(array $attributes): string
    {
        $nonNullAttributes = array_filter(
            $attributes,
            function ($value, $key) {
                return null !== $value && false !== $value;
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
