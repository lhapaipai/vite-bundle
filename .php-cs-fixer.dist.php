<?php

$config = new \PhpCsFixer\Config();

$config
    ->setRules([
        '@Symfony' => true,
    ])
    ->getFinder()
    ->in(__DIR__.'/src')
    ->append([__FILE__])
;

return $config;
