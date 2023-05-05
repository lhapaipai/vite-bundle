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
    private $proxyOrigin;

    public function __construct(
        string $defaultBuild,
        array $builds,
        HttpClientInterface $httpClient,
        EntrypointsLookup $entrypointsLookup,
        ?string $proxyOrigin
    ) {
        $this->defaultBuild = $defaultBuild;
        $this->builds = $builds;
        $this->httpClient = $httpClient;

        $this->entrypointsLookup = $entrypointsLookup;
        $this->proxyOrigin = $proxyOrigin;
    }

    public function proxyBuild($path, $buildName = null): Response
    {
        if (is_null($buildName)) {
            $buildName = $this->defaultBuild;
        }

        $viteDevServer = $this->entrypointsLookup->getViteServer($buildName);

        if (is_null($viteDevServer) || false === $viteDevServer) {
            return new \Exception('Vite dev server not available');
        }

        $origin = $this->proxyOrigin ?? $viteDevServer['origin'];

        $response = $this->httpClient->request(
            'GET',
            $origin.$this->builds[$buildName]['base'].$path
        );

        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();

        return new Response($content, $statusCode, $headers);
    }
}
