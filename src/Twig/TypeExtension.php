<?php

namespace Pentatrion\ViteBundle\Twig;

use Pentatrion\ViteBundle\Service\Debug;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TypeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('symfonyvite_stringify', [Debug::class, 'stringify'], ['is_safe' => ['html']])];
    }
}
