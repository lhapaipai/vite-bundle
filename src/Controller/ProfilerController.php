<?php

namespace Pentatrion\ViteBundle\Controller;

use Pentatrion\ViteBundle\Service\Debug;
use Pentatrion\ViteBundle\Twig\TypeExtension;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ProfilerController
{
    public function __construct(
        private Debug $debug,
        private Environment $twig
    ) {
    }

    public function info(): Response
    {
        $viteConfigs = $this->debug->getViteConfigs();

        $this->twig->addExtension(new TypeExtension());

        $response = new Response(
            $this->twig->render('@PentatrionVite/Profiler/info.html.twig', [
                'viteConfigs' => $viteConfigs,
            ])
        );

        return $response;
    }
}
