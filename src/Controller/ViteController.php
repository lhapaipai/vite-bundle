<?php

namespace Pentatrion\ViteBundle\Controller;

use Pentatrion\ViteBundle\Service\EntrypointsLookupCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ViteController
{
    private HttpClientInterface $httpClient;
    private string $defaultConfig;
    private EntrypointsLookupCollection $entrypointsLookupCollection;
    private ?string $proxyOrigin;

    public function __construct(
        string $defaultConfig,
        HttpClientInterface $httpClient,
        EntrypointsLookupCollection $entrypointsLookupCollection,
        ?string $proxyOrigin
    ) {
        $this->defaultConfig = $defaultConfig;
        $this->httpClient = $httpClient;

        $this->entrypointsLookupCollection = $entrypointsLookupCollection;
        $this->proxyOrigin = $proxyOrigin;
    }

    public function proxyBuild(string $path, ?string $configName = null): Response
    {
        if (is_null($configName)) {
            $configName = $this->defaultConfig;
        }

        $entrypointsLookup = $this->entrypointsLookupCollection->getEntrypointsLookup($configName);

        $viteDevServer = $entrypointsLookup->getViteServer();
        $base = $entrypointsLookup->getBase();

        if (is_null($viteDevServer)) {
            throw new \Exception('Vite dev server not available');
        }

        $origin = $this->proxyOrigin ?? $viteDevServer;

        $response = $this->httpClient->request(
            'GET',
            $origin.$base.$path
        );

        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();

        return new Response($content, $statusCode, $headers);
    }
}
