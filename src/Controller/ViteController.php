<?php

namespace Pentatrion\ViteBundle\Controller;

use Pentatrion\ViteBundle\Asset\EntrypointsLookup;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ViteController
{
    public $httpClient;
    public string $defaultBuild;
    public array $builds;
    private $entrypointsLookup;
    private $viteDevServer;

    public function __construct(
        string $defaultBuild,
        array $builds,
        HttpClientInterface $httpClient,
        EntrypointsLookup $entrypointsLookup
    ) {
        $this->defaultBuild = $defaultBuild;
        $this->builds = $builds;
        $this->httpClient = $httpClient;
        $this->entrypointsLookup = $entrypointsLookup;

        $this->viteDevServer = $this->entrypointsLookup->getViteServer();
    }

    public function proxyBuild($path): Response
    {
        $viteDevServer = $this->entrypointsLookup->getViteServer();
        if (is_null($viteDevServer) || false === $viteDevServer) {
            return new \Exception('Vite dev server not available');
        }

        $response = $this->httpClient->request(
            'GET',
            $this->viteDevServer['origin'].$this->viteBase.$path
        );

        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();

        return new Response($content, $statusCode, $headers);
    }
}
