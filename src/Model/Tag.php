<?php

namespace Pentatrion\ViteBundle\Model;

class Tag
{
    public const SCRIPT_TAG = 'script';
    public const LINK_TAG = 'link';

    private string $tagName;
    private array $attributes;
    private string $content;
    private bool $internal;

    public function __construct(string $tagName, array $attributes, string $content = '', bool $internal = false)
    {
        $this->tagName = $tagName;
        $this->attributes = $attributes;
        $this->content = $content;
        $this->internal = $internal;
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
        return self::LINK_TAG === $this->tagName
            && isset($this->attributes['rel'])
            && 'stylesheet' === $this->attributes['rel'];
    }

    public function isModulePreload(): bool
    {
        return self::LINK_TAG === $this->tagName
            && isset($this->attributes['rel'])
            && 'modulepreload' === $this->attributes['rel'];
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $key)
    {
        return key_exists($key, $this->attributes) ? $this->attributes[$key] : null;
    }

    /**
     * @param string      $name  The attribute name
     * @param string|bool $value Value can be "true" to have an attribute without a value (e.g. "defer")
     */
    public function setAttribute(string $name, $value): self
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

    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }

    public function removeContent(): self
    {
        $this->content = '';

        return $this;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }
}
