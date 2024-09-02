<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\Exception\UndefinedConfigNameException;
use Symfony\Component\DependencyInjection\ServiceLocator;

class TagRendererCollection
{
    /** @param ServiceLocator<TagRenderer> $tagRendererLocator */
    public function __construct(
        private ServiceLocator $tagRendererLocator,
        private string $defaultConfigName,
    ) {
    }

    public function getTagRenderer(?string $configName = null): TagRenderer
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
