<?php

$config = new \PhpCsFixer\Config();

$config
    ->setRules([
        '@Symfony' => true,
    ])
    ->getFinder()
    ->in(__DIR__.'/src')
;

return $config;
