<?php

$rules = [
    '@PSR2' => true,
    '@DoctrineAnnotation' => true,
    'align_multiline_comment' => true,
    'array_syntax' => ['syntax' => 'short'],
    'cast_spaces' => ['space' => 'none'],
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'compact_nullable_typehint' => true,
    'heredoc_to_nowdoc' => true,
    'function_typehint_space' => true,
    'list_syntax' => ['syntax' => 'short'],
    'lowercase_cast' => true,
    'method_argument_space' => ['ensure_fully_multiline' => true],
    'new_with_braces' => true,
    'no_blank_lines_after_class_opening' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_empty_comment' => true,
    'no_empty_phpdoc' => true,
    'no_empty_statement' => true,
    'no_extra_consecutive_blank_lines' => [
        'tokens' => ['break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block']
    ],
    'no_leading_import_slash' => true,
    'no_leading_namespace_whitespace' => true,
    'no_mixed_echo_print' => true,
    'no_multiline_whitespace_around_double_arrow' => true,
    'no_multiline_whitespace_before_semicolons' => true,
    'no_null_property_initialization' => true,
    'no_short_echo_tag' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'no_superfluous_elseif' => true,
    'no_unneeded_control_parentheses' => true,
    'no_trailing_comma_in_singleline_array' => true,
    'no_unneeded_curly_braces' => true,
    'no_unneeded_final_method' => true,
    'no_unused_imports' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'no_whitespace_before_comma_in_array' => true,
    'no_whitespace_in_blank_line' => true,
    'normalize_index_brace' => true,
    'ordered_imports' => true,
    'phpdoc_add_missing_param_annotation' => true,
    'phpdoc_no_alias_tag' => true,
    'phpdoc_order' => true,
    'phpdoc_single_line_var_spacing' => true,
    'phpdoc_types' => true,
    'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
    'semicolon_after_instruction' => true,
    'self_accessor' => true,
    'short_scalar_cast' => true,
    'single_line_comment_style' => true,
    'single_blank_line_before_namespace' => true,
    'single_quote' => true,
    'space_after_semicolon' => true,
    'standardize_not_equals' => true,
    'ternary_operator_spaces' => true,
    'trailing_comma_in_multiline_array' => true,
    'trim_array_spaces' => true,
    'unary_operator_spaces' => true,
    'whitespace_after_comma_in_array' => true,
    'dir_constant' => true,
    'function_to_constant' => true,
    'is_null' => ['use_yoda_style' => false],
    'modernize_types_casting' => true,
    'no_php4_constructor' => true,
    'no_unreachable_default_argument_value' => true,
    'psr4' => true,
    '@PHP71Migration' => true,
    'return_type_declaration' => true,
    '@PHP71Migration:risky' => true,
];


$config = PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in('src')
            ->in('tests')
    );

return $config;
