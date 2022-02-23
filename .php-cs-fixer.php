<?php

declare(strict_types=1);

$config = new Prooph\CS\Config\Prooph();
$finder = $config->getFinder();

$finder->exclude('vendor');
$finder->in(__DIR__);
$finder->append(['.php_cs']);

$cacheDir = \getenv('TRAVIS') ? \getenv('HOME') . '/.php-cs-fixer' : __DIR__;

$config->setCacheFile($cacheDir . '/.php_cs.cache');

return $config;
