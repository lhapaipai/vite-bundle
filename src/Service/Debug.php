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
        private ?string $proxyOrigin,
    ) {
    }

    public static function getInfoUrl(string $viteServerHost, string $base): string
    {
        $baseNormalized = rtrim($base, '/');

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
                $viteServer = $this->proxyOrigin ?? $entrypointsLookup->getViteServer();

                return [
                    'configName' => $configName,
                    'response' => is_null($viteServer)
                        ? null
                        : $this->httpClient->request('GET', self::getInfoUrl($viteServer, $entrypointsLookup->getBase())),
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
                        $content = self::prepareViteConfig($data);
                    }
                } catch (\Exception $e) {
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
    public static function prepareViteConfig($config)
    {
        $output = [
            'principal' => [],
        ];
        $groupKeys = ['build', 'define', 'env', 'esbuild', 'experimental', 'inlineConfig', 'logger', 'optimizeDeps', 'resolve', 'server', 'ssr', 'worker'];
        /** @var array<string, mixed> $value */
        foreach ($config as $key => $value) {
            if (in_array($key, $groupKeys)) {
                if (\is_array($value)) {
                    ksort($value);
                }
                $output[$key] = $value;
            } else {
                $output['principal'][$key] = $value;
            }
        }

        ksort($output['principal']);

        return $output;
    }

    public static function stringifyScalar(mixed $value): string
    {
        if (!is_scalar($value)) {
            throw new \Exception('Unable to stringify no scalar value');
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ('' === $value) {
            return '<i>Empty string</i>';
        }

        return (string) $value;
    }

    public static function stringify(mixed $value): string
    {
        if (is_null($value)) {
            return '<i>null</i>';
        }

        if (is_scalar($value)) {
            return self::stringifyScalar($value);
        }

        if (is_array($value)) {
            if (0 === count($value)) {
                return '[]';
            }
            $content = '<ul>';
            foreach ($value as $k => $v) {
                $content .= '<li>';

                if (is_string($k)) {
                    $content .= $k.': ';
                }

                if (is_scalar($v)) {
                    $content .= self::stringifyScalar($v);
                } else {
                    $content .= self::stringify($v);
                }

                $content .= '</li>';
            }
            $content .= '</ul>';

            return $content;
        }

        return '<pre>'.print_r($value, true).'</pre>';
    }
}
