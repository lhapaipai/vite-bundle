<?php

namespace Pentatrion\ViteBundle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class Debug
{
    public $httpClient;
    public array $configs;
    private $entrypointsLookupCollection;

    public function __construct(
        array $configs,
        HttpClientInterface $httpClient,
        EntrypointsLookupCollection $entrypointsLookupCollection,
    ) {
        $this->configs = $configs;
        $this->httpClient = $httpClient;
        $this->entrypointsLookupCollection = $entrypointsLookupCollection;
    }

    private function getInfoUrl(string $viteServerHost, string $base): string
    {
        $baseNormalized = '/' !== substr($base, -1) ? $base : substr($base, 0, strlen($base) - 1);

        return sprintf('%s%s%s', $viteServerHost, $baseNormalized, '/@vite/info');
    }

    public function getViteConfigs(): array
    {
        $viteServerRequests = array_map(
            function ($configName) {
                $entrypointsLookup = $this->entrypointsLookupCollection->getEntrypointsLookup($configName);

                return [
                    'configName' => $configName,
                    'response' => $this->httpClient->request('GET', $this->getInfoUrl(
                        $entrypointsLookup->getViteServer(),
                        $entrypointsLookup->getBase()
                    )),
                ];
            },
            array_keys($this->configs)
        );

        $viteConfigs = array_map(
            function ($requests) {
                return [
                    'configName' => $requests['configName'],
                    'content' => $this->prepareViteConfig(json_decode($requests['response']->getContent(), true)),
                ];
            },
            $viteServerRequests
        );

        return $viteConfigs;
    }

    public function prepareViteConfig($config)
    {
        $output = [
            'principal' => [],
        ];
        $groupKeys = ['build', 'define', 'env', 'esbuild', 'experimental', 'inlineConfig', 'logger', 'optimizeDeps', 'resolve', 'server', 'ssr', 'worker'];
        foreach ($config as $key => $value) {
            if (in_array($key, $groupKeys)) {
                ksort($value);
                $output[$key] = $value;
            } else {
                $output['principal'][$key] = $value;
            }
        }

        ksort($output['principal']);

        return $output;
    }
}
