<?php

namespace Pentatrion\ViteBundle\DataCollector;

use Pentatrion\ViteBundle\Model\Tag;
use Pentatrion\ViteBundle\Service\EntrypointRenderer;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViteCollector extends AbstractDataCollector
{
    public function __construct(
        private EntrypointRenderer $entrypointRenderer,
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = $this->entrypointRenderer->getRenderedTags();
    }

    /**
     * @return array<Tag>
     */
    public function getRenderedTags(): array
    {
        /* @phpstan-ignore-next-line data is array<Tag> */
        return $this->data;
    }

    /**
     * @return array<Tag>
     */
    public function getRenderedScripts(): array
    {
        /* @phpstan-ignore-next-line data is array<Tag> */
        return array_filter($this->data, fn (Tag $tag) => $tag->isScriptTag());
    }

    /**
     * @return array<Tag>
     */
    public function getRenderedStylesheets(): array
    {
        /* @phpstan-ignore-next-line data is array<Tag> */
        return array_filter($this->data, fn (Tag $tag) => $tag->isStylesheet());
    }

    public function getName(): string
    {
        return 'pentatrion_vite.vite_collector';
    }

    public static function getTemplate(): ?string
    {
        return '@PentatrionVite/Collector/vite_collector.html.twig';
    }
}
