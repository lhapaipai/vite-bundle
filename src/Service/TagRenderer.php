<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\Model\Tag;
use Pentatrion\ViteBundle\Util\InlineContent;

class TagRenderer
{
    /**
     * @param array<string, bool|string|null> $globalDefaultAttributes
     * @param array<string, bool|string|null> $globalScriptAttributes
     * @param array<string, bool|string|null> $globalLinkAttributes
     * @param array<string, bool|string|null> $globalPreloadAttributes
     * @param 'none'|'link-tag'|'link-header' $preload
     */
    public function __construct(
        private array $globalDefaultAttributes = [],
        private array $globalScriptAttributes = [],
        private array $globalLinkAttributes = [],
        private array $globalPreloadAttributes = [],
        private string $preload = 'link-tag',
    ) {
    }

    public function createViteClientScript(string $src, string $entryName): Tag
    {
        return $this->createInternalScriptTag(
            [
                'type' => 'module',
                'src' => $src,
                'crossorigin' => true,
            ],
            '',
            $entryName
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

    /** @param array<string, bool|string|null> $attributes */
    public function createInternalScriptTag(array $attributes = [], string $content = '', string $origin = ''): Tag
    {
        $tag = new Tag(
            Tag::SCRIPT_TAG,
            $attributes,
            $content,
            $origin,
            $this->preload,
            true,
        );

        return $tag;
    }

    /** @param array<string, bool|string|null> $attributes */
    public function createScriptTag(array $attributes = [], string $content = '', string $origin = '', bool $internal = false): Tag
    {
        $tag = new Tag(
            Tag::SCRIPT_TAG,
            array_merge(
                $this->globalDefaultAttributes,
                $this->globalScriptAttributes,
                $attributes
            ),
            $content,
            $origin,
            $this->preload,
            $internal
        );

        return $tag;
    }

    /** @param array<string, bool|string|null> $extraAttributes */
    public function createLinkStylesheetTag(string $fileName, array $extraAttributes = [], string $origin = ''): Tag
    {
        $attributes = [
            'rel' => 'stylesheet',
            'href' => $fileName,
        ];

        $tag = new Tag(
            Tag::LINK_TAG,
            array_merge(
                $this->globalDefaultAttributes,
                $this->globalLinkAttributes,
                $attributes,
                $extraAttributes
            ),
            '',
            $origin,
            $this->preload
        );

        return $tag;
    }

    /** @param array<string, bool|string|null> $extraAttributes */
    public function createModulePreloadLinkTag(string $fileName, array $extraAttributes = [], string $origin = ''): Tag
    {
        $attributes = [
            'rel' => 'modulepreload',
            'href' => $fileName,
        ];

        $tag = new Tag(
            Tag::LINK_TAG,
            array_merge(
                $this->globalDefaultAttributes,
                $this->globalPreloadAttributes,
                $attributes,
                $extraAttributes
            ),
            '',
            $origin,
            $this->preload
        );

        return $tag;
    }

    public static function generateTag(Tag $tag): string
    {
        return $tag->isLinkTag() ? sprintf(
            '<%s %s>',
            $tag->getTagName(),
            self::convertArrayToAttributes($tag)
        ) : sprintf(
            '<%s %s>%s</%s>',
            $tag->getTagName(),
            self::convertArrayToAttributes($tag),
            $tag->getContent(),
            $tag->getTagName()
        );
    }

    private static function convertArrayToAttributes(Tag $tag): string
    {
        $validAttributes = $tag->getValidAttributes();

        return implode(' ', array_map(
            function ($key, $value) {
                if (true === $value) {
                    return sprintf('%s', $key);
                } else {
                    return sprintf('%s="%s"', $key, htmlentities($value));
                }
            },
            array_keys($validAttributes),
            $validAttributes
        ));
    }
}
