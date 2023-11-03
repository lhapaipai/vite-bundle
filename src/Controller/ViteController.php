<?php

namespace Pentatrion\ViteBundle\Controller;

use Pentatrion\ViteBundle\Asset\EntrypointsLookupCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ViteController
{
    public $httpClient;
    public string $defaultConfig;
    public array $configs;
    private $entrypointsLookupCollection;
    private $proxyOrigin;

    public function __construct(
        string $defaultConfig,
        array $configs,
        HttpClientInterface $httpClient,
        EntrypointsLookupCollection $entrypointsLookupCollection,
        ?string $proxyOrigin
    ) {
        $this->defaultConfig = $defaultConfig;
        $this->configs = $configs;
        $this->httpClient = $httpClient;

        $this->entrypointsLookupCollection = $entrypointsLookupCollection;
        $this->proxyOrigin = $proxyOrigin;
    }

    public function proxyBuild(string $path, string $configName = null): Response
    {
        if (is_null($configName)) {
            $configName = $this->defaultConfig;
        }

        $entrypointsLookup = $this->entrypointsLookupCollection->getEntrypointsLookup($configName);

        $viteDevServer = $entrypointsLookup->getViteServer();
        $base = $entrypointsLookup->getBase();

        if (is_null($viteDevServer)) {
            return new \Exception('Vite dev server not available');
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
