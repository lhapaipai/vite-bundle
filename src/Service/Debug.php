<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\DependencyInjection\PentatrionViteExtension;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-import-type ViteConfigs from PentatrionViteExtension
 */
class Debug
{
    /**
     * @param ViteConfigs $configs
     */
    public function __construct(
        private array $configs,
        private HttpClientInterface $httpClient,
        private EntrypointsLookupCollection $entrypointsLookupCollection,
    ) {
    }

    private function getInfoUrl(string $viteServerHost, string $base): string
    {
        $baseNormalized = '/' !== substr($base, -1) ? $base : substr($base, 0, strlen($base) - 1);

        return sprintf('%s%s%s', $viteServerHost, $baseNormalized, '/@vite/info');
    }

    /**
     * @return array<array{
     *  configName: string,
     *  content: array<mixed>|null
     * }>
     */
    public function getViteCompleteConfigs(): array
    {
        $viteServerRequests = array_map(
            function ($configName) {
                $entrypointsLookup = $this->entrypointsLookupCollection->getEntrypointsLookup($configName);
                $viteServer = $entrypointsLookup->getViteServer();

                return [
                    'configName' => $configName,
                    'response' => is_null($viteServer)
                        ? null
                        : $this->httpClient->request('GET', $this->getInfoUrl($viteServer, $entrypointsLookup->getBase())),
                ];
            },
            array_keys($this->configs)
        );

        $viteConfigs = array_map(
            function ($request) {
                $content = null;
                try {
                    if (!is_null($request['response'])) {
                        /** @var array<mixed> $data */
                        $data = json_decode($request['response']->getContent(), true);
                        $content = $this->prepareViteConfig($data);
                    }
                } catch (\Exception) {
                    // dev server is not running
                }

                return [
                    'configName' => $request['configName'],
                    'content' => $content,
                ];
            },
            $viteServerRequests
        );

        return $viteConfigs;
    }

    /**
     * @param array<mixed> $config
     *
     * @return array<mixed>
     */
    public function prepareViteConfig($config)
    {
        $output = [
            'principal' => [],
        ];
        $groupKeys = ['build', 'define', 'env', 'esbuild', 'experimental', 'inlineConfig', 'logger', 'optimizeDeps', 'resolve', 'server', 'ssr', 'worker'];
        /** @var array<string, mixed> $value */
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

    public static function stringify(mixed $value): string
    {
        if (is_null($value)) {
            return '<i>null</i>';
        }

        if (is_array($value) && 0 === count($value)) {
            return '[]';
        }

        if (is_scalar($value)) {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            if ('' === $value) {
                return '<i>Empty string</i>';
            }

            return (string) $value;
        }

        if (is_array($value)) {
            $content = '<ul>';
            foreach ($value as $k => $v) {
                $content .= '<li>';

                if (is_string($k)) {
                    $content .= $k.': ';
                } elseif (is_scalar($v)) {
                    $content .= $v.'<br>';
                } else {
                    $content .= self::stringify($v).'<br>';
                }

                $content .= '</li>';
            }
            $content .= '</ul>';

            return $content;
        }

        return '<pre>'.print_r($value, true).'</pre>';
    }
}
