<?php

/*
 * This file is inspired from the Symfony WebpackEncoreBundle package.
 */

namespace Pentatrion\ViteBundle\Event;

use Pentatrion\ViteBundle\Model\Tag;

/**
 * Dispatched each time a script or link tag is rendered.
 */
final class RenderAssetTagEvent
{
    public function __construct(
        private bool $build,
        private Tag $tag,
    ) {
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
