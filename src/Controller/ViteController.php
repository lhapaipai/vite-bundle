<?php

namespace Pentatrion\ViteBundle\Controller;

use Exception;
use Pentatrion\ViteBundle\Asset\EntrypointsLookup;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ViteController
{
    public $httpClient;
    public string $viteBase;
    private $entrypointsLookup;
    private $viteDevServer;

    public function __construct(
        string $viteBase,
        HttpClientInterface $httpClient,
        EntrypointsLookup $entrypointsLookup
    ) {
        $this->viteBase = $viteBase;
        $this->httpClient = $httpClient;
        $this->entrypointsLookup = $entrypointsLookup;

        $this->viteDevServer = $this->entrypointsLookup->getViteServer();
    }

    public function proxyBuild($path): Response
    {
        if (is_null($this->viteDevServer) || false === $this->viteDevServer) {
            return new Exception('Vite dev server not available');
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
