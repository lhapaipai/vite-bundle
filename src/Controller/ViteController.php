<?php

namespace Pentatrion\ViteBundle\Controller;

use Pentatrion\ViteBundle\Service\EntrypointsLookup;
use Pentatrion\ViteBundle\Service\EntrypointsLookupCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ViteController
{
    public function __construct(
        private string $defaultConfig,
        private HttpClientInterface $httpClient,
        private EntrypointsLookupCollection $entrypointsLookupCollection,
        private ?string $proxyOrigin,
    ) {
    }

    public function proxyBuild(string $path, ?string $configName = null): Response
    {
        if (is_null($configName)) {
            $configName = $this->defaultConfig;
        }

        $entrypointsLookup = $this->entrypointsLookupCollection->getEntrypointsLookup($configName);
        $origin = $this->proxyOrigin ?? $this->resolveDevServer($entrypointsLookup);
        $base = $entrypointsLookup->getBase();

        $response = $this->httpClient->request(
            'GET',
            $origin.$base.$path,
            ['headers' => ['Accept-Encoding' => '']],
        );

        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();

        return new Response($content, $statusCode, $headers);
    }

    private function resolveDevServer(EntrypointsLookup $entrypointsLookup): string
    {
        $viteDevServer = $entrypointsLookup->getViteServer();

        if (is_null($viteDevServer)) {
            throw new \Exception('Vite dev server not available');
        }

        return $viteDevServer;
    }
}
