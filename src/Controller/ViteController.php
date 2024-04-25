<?php

namespace Pentatrion\ViteBundle\Controller;

use Pentatrion\ViteBundle\Service\EntrypointsLookupCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ViteController
{
    public function __construct(
        private string $defaultConfig,
        private HttpClientInterface $httpClient,
        private EntrypointsLookupCollection $entrypointsLookupCollection,
        private ?string $proxyOrigin
    ) {
    }

    public function proxyBuild(string $path, ?string $configName = null): Response
    {
        $origin = $this->proxyOrigin ?? resolveDevServer($configName);

        $response = $this->httpClient->request(
            'GET',
            $origin.$base.$path
        );

        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();

        return new Response($content, $statusCode, $headers);
    }

    private function resolveDevServer(?string $configName = null): string
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

        return $viteDevServer;
    }
}
