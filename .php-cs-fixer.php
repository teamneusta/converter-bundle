<?php

/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:3.13.1|configurator
 * you can change this configuration by importing this file.
 */
$config = new PhpCsFixer\Config();
$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->exclude([
        'var/',
    ]);

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        'align_multiline_comment' => true,
        'array_indentation' => true,
        'blank_line_after_opening_tag' => true,
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'linebreak_after_opening_tag' => true,
    ])
    ->setFinder($finder);
