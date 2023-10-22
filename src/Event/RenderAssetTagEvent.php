<?php

/*
 * This file is inspired from the Symfony WebpackEncoreBundle package.
 */

namespace Pentatrion\ViteBundle\Event;

/**
 * Dispatched each time a script or link tag is rendered.
 */
final class RenderAssetTagEvent
{
    public const TYPE_SCRIPT = 'script';
    public const TYPE_LINK = 'link';
    public const TYPE_PRELOAD = 'preload';

    private string $type;
    private array $attributes;
    private bool $isBuild;

    public function __construct(string $type, array $attributes, bool $isBuild)
    {
        $this->type = $type;
        $this->attributes = $attributes;
        $this->isBuild = $isBuild;
    }

    public function isScriptTag(): bool
    {
        return self::TYPE_SCRIPT === $this->type;
    }

    public function isLinkTag(): bool
    {
        return self::TYPE_LINK === $this->type;
    }

    public function isPreloadTag(): bool
    {
        return self::TYPE_PRELOAD === $this->type;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isBuild(): bool
    {
        return $this->isBuild;
    }

    /**
     * @param string      $name  The attribute name
     * @param string|bool $value Value can be "true" to have an attribute without a value (e.g. "defer")
     */
    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function removeAttribute(string $name): void
    {
        unset($this->attributes[$name]);
    }
}
