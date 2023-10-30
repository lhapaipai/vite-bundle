<?php

/*
 * This file is inspired from the Symfony WebpackEncoreBundle package.
 */

namespace Pentatrion\ViteBundle\Event;

use Pentatrion\ViteBundle\Asset\Tag;

/**
 * Dispatched each time a script or link tag is rendered.
 */
final class RenderAssetTagEvent
{
    private bool $build;
    private Tag $tag;

    public function __construct(bool $build, Tag $tag)
    {
        $this->build = $build;
        $this->tag = $tag;
    }

    public function isBuild(): bool
    {
        return $this->build;
    }

    public function getTag(): Tag
    {
        return $this->tag;
    }
}
