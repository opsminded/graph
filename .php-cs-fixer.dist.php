<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
;

$config = new Config();
$config->setRules([
    '@PSR12' => true,
    'array_syntax' => ['syntax' => 'short'],
    'binary_operator_spaces' => ['default' => 'align_single_space_minimal'],
    'declare_strict_types' => true,
])
    ->setFinder($finder)
;

return $config;
