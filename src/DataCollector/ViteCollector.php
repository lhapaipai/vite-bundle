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
        private readonly EntrypointRenderer $entrypointRenderer
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = $this->entrypointRenderer->getRenderedFiles();
    }

    /**
     * @return array<string, Tag>
     */
    public function getRenderedStyles(): array
    {
        return $this->data['styles'];
    }

    /**
     * @return array<string, Tag>
     */
    public function getRenderedScripts(): array
    {
        return $this->data['scripts'];
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
