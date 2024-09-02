<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\Exception\UndefinedConfigNameException;
use Symfony\Component\DependencyInjection\ServiceLocator;

class EntrypointsLookupCollection
{
    /** @param ServiceLocator<EntrypointsLookup> $entrypointsLookupLocator */
    public function __construct(
        private ServiceLocator $entrypointsLookupLocator,
        private string $defaultConfigName,
    ) {
    }

    public function getEntrypointsLookup(?string $configName = null): EntrypointsLookup
    {
        if (is_null($configName)) {
            $configName = $this->defaultConfigName;
        }

        if (!$this->entrypointsLookupLocator->has($configName)) {
            throw new UndefinedConfigNameException(sprintf('The config "%s" is not set.', $configName));
        }

        return $this->entrypointsLookupLocator->get($configName);
    }
}
