<?php

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests']);

return (new PhpCsFixer\Config())
    ->setRules(
        [
            '@PSR2' => true,
            'strict_param' => true,
            'array_syntax' => ['syntax' => 'short'],
        ]
    )
    ->setCacheFile(__DIR__ . '/.php_cs.cache')
    ->setFinder($finder);
