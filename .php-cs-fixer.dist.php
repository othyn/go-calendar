<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/bin')
    ->in(__DIR__ . '/src');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'no_superfluous_phpdoc_tags' => true,
        'php_unit_method_casing' => [
            'case' => 'snake_case',
        ],
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'not_operator_with_successor_space' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
    ])
    ->setLineEnding("\n")
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->setFinder($finder);
