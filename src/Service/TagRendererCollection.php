<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\Exception\UndefinedConfigNameException;
use Symfony\Component\DependencyInjection\ServiceLocator;

class TagRendererCollection
{
    private ServiceLocator $tagRendererLocator;
    private string $defaultConfigName;

    public function __construct(
        ServiceLocator $tagRendererLocator,
        string $defaultConfigName
    ) {
        $this->tagRendererLocator = $tagRendererLocator;
        $this->defaultConfigName = $defaultConfigName;
    }

    public function getTagRenderer(string $configName = null): TagRenderer
    {
        if (is_null($configName)) {
            $configName = $this->defaultConfigName;
        }

        if (!$this->tagRendererLocator->has($configName)) {
            throw new UndefinedConfigNameException(sprintf('The config "%s" is not set.', $configName));
        }

        return $this->tagRendererLocator->get($configName);
    }
}
