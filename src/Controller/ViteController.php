<?php

namespace Pentatrion\ViteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ViteController
{
    public $viteDevServer;
    public $httpClient;

    public function __construct($viteDevServer, $viteBase, HttpClientInterface $httpClient)
    {
      $this->viteDevServer = $viteDevServer;
      $this->viteBase = $viteBase;
      $this->httpClient = $httpClient;
    }

    public function proxyBuild($path): Response
    {
        $response = $this->httpClient->request(
            'GET',
            $this->viteDevServer.$this->viteBase.$path
        );

        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();

        return new Response($content, $statusCode, $headers);
    }
}