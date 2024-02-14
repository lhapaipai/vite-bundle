<?php

namespace Pentatrion\ViteBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TypeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return ['stringify' => new TwigFilter('stringify', [$this, 'stringify'], ['is_safe' => ['html']])];
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

            return $value;
        }
        if (is_array($value)) {
            $content = '<ul>';
            foreach ($value as $k => $v) {
                $content .= '<li>';
                if (is_string($k)) {
                    $content .= $k.': ';
                }
                if (is_scalar($value)) {
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
