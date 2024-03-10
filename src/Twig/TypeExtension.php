<?php

namespace Pentatrion\ViteBundle\Twig;

use Pentatrion\ViteBundle\Service\Debug;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TypeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return ['stringify' => new TwigFilter('stringify', [Debug::class, 'stringify'], ['is_safe' => ['html']])];
    }
}
