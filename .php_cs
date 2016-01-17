<?php

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers([
        'psr0',

        // psr-1
        'encoding',
        'short_tag',

        // subset of symphony level
        'extra_empty_lines',
        'no_empty_lines_after_phpdocs',
        'no_blank_lines_after_class_opening',
        'operators_spaces',
        'phpdoc_indent',
        'phpdoc_no_package',
        'phpdoc_params',
        'phpdoc_trim',
        'phpdoc_scalar',
        'remove_leading_slash_use',
        'return',
        'self_accessor',
        'single_array_no_trailing_comma',
        'single_quote',
        'spaces_after_semicolon',
        'spaces_before_semicolon',
        'unused_use',
        'whitespacy_lines',

        // contrib checks
        'concat_with_spaces',
        'newline_after_open_tag',
        'ordered_use',
        'single_quote',
    ])
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->in(__DIR__)
    );
