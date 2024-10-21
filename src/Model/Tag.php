<?php

namespace Pentatrion\ViteBundle\Model;

class Tag
{
    public const SCRIPT_TAG = 'script';
    public const LINK_TAG = 'link';

    public bool $renderAsTag = true;
    public bool $renderAsLinkHeader = false;

    /**
     * @param array<string, bool|string|null> $attributes
     */
    public function __construct(
        private string $tagName,
        private array $attributes = [],
        private string $content = '',
        private string $origin = '',
        string $preloadOption = 'link-tag',
        private bool $internal = false,
    ) {
        if (self::LINK_TAG === $tagName && isset($attributes['rel'])) {
            if (in_array($attributes['rel'], ['modulepreload', 'preload']) && 'link-tag' !== $preloadOption) {
                $this->renderAsTag = false;
            }

            if ('link-header' === $preloadOption) {
                $this->renderAsLinkHeader = true;
            }
        }
        if (self::SCRIPT_TAG === $tagName) {
            if ('link-header' === $preloadOption && isset($attributes['src'])) {
                $this->renderAsLinkHeader = true;
            }
        }
    }

    public function getFilename(): string
    {
        $src = self::SCRIPT_TAG === $this->tagName ? ($this->attributes['src'] ?? null) : ($this->attributes['href'] ?? null);

        if (is_string($src)) {
            return basename($src);
        }

        return 'unknown';
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function isScriptTag(): bool
    {
        return self::SCRIPT_TAG === $this->tagName;
    }

    public function isLinkTag(): bool
    {
        return self::LINK_TAG === $this->tagName;
    }

    public function isStylesheet(): bool
    {
        return self::LINK_TAG === $this->tagName && 'stylesheet' === $this->getAttribute('rel');
    }

    public function isPreload(): bool
    {
        return self::LINK_TAG === $this->tagName && in_array($this->getAttribute('rel'), ['preload', 'modulepreload']);
    }

    public function isModule(): bool
    {
        return self::SCRIPT_TAG === $this->tagName && 'module' === $this->getAttribute('type');
    }

    /**
     * @return array<string, bool|string|null>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, true|string>
     */
    public function getValidAttributes(): array
    {
        return array_filter(
            $this->attributes,
            function ($value, $key) {
                return null !== $value && false !== $value;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function getAttribute(string $key): string|bool|null
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * @param string      $name  The attribute name
     * @param string|bool $value Value can be "true" to have an attribute without a value (e.g. "defer")
     */
    public function setAttribute(string $name, string|bool $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function removeAttribute(string $name): self
    {
        unset($this->attributes[$name]);

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function removeContent(): self
    {
        $this->content = '';

        return $this;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function isRenderAsTag(): bool
    {
        return $this->renderAsTag;
    }

    public function setRenderAsTag(bool $val): self
    {
        $this->renderAsTag = $val;

        return $this;
    }

    public function isRenderAsLinkHeader(): bool
    {
        return $this->renderAsLinkHeader;
    }

    public function setRenderAsLinkHeader(bool $val): self
    {
        $this->renderAsLinkHeader = $val;

        return $this;
    }
}
