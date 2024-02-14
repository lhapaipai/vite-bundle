<?php

namespace Pentatrion\ViteBundle\DataCollector;

use Pentatrion\ViteBundle\Service\EntrypointRenderer;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ViteCollector extends AbstractDataCollector
{
    private EntrypointRenderer $entrypointRenderer;

    public function __construct(
        EntrypointRenderer $entrypointRenderer
    ) {
        $this->entrypointRenderer = $entrypointRenderer;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = [
            'foo' => 'bar',
        ];
    }

    public function getFoo(): string
    {
        return $this->data['foo'];
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
