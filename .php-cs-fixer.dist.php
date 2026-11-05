<?php
/**
 * PHP CS Fixer config: WordPress Coding Standards (roughly), as RosarioSIS applies them.
 * Counterpart of phpcs.xml (WordPress-Core with exclusions).
 *
 * Run from the project root (PHP >= 7.4 required by PHP CS Fixer):
 *   php-cs-fixer fix --dry-run --diff
 *   php-cs-fixer fix
 *
 * @link https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/
 */

$root = __DIR__;

$finder = PhpCsFixer\Finder::create()
	->in( [
		$root . '/classes',
		$root . '/functions',
		$root . '/ProgramFunctions',
		$root . '/modules',
		$root . '/plugins',
	] )
	->name( '*.php' )
	->notPath( '#(^|/)vendor/#' )
	->notPath( '#^functions/PHPCompatibility/#' )
	->notPath( '#(^|/)node_modules/#' )
	->notPath( '#^modules/[^/]+/classes/#' )
	->notPath( '#^modules/[^/]+/includes/gateways/#' )
	->notPath( '#^plugins/[^/]+/classes/#' )
	->notPath( '#^plugins/REST_API/api\.php$#' );

$config = new PhpCsFixer\Config();

return $config
	->setRiskyAllowed( false )
	// WordPress: tabs for indentation, LF line endings.
	->setIndent( "\t" )
	->setLineEnding( "\n" )
	->setRules( [
		// Indentation & whitespace.
		'indentation_type' => true,
		'line_ending' => true,
		'no_trailing_whitespace' => true,
		'no_trailing_whitespace_in_comment' => true,
		'no_whitespace_in_blank_line' => true,
		'single_blank_line_at_eof' => true,
		'no_extra_blank_lines' => [
			'tokens' => [ 'extra', 'curly_brace_block', 'parenthesis_brace_block', 'square_brace_block', 'throw', 'use' ],
		],

		// Allman style (RosarioSIS): brace on its own line for classes, functions and control structures.
		'braces_position' => [
			'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
			'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
			'anonymous_functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
			'anonymous_classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
			'control_structures_opening_brace' => 'next_line_unless_newline_at_signature_end',
		],
		'control_structure_braces' => true,
		'control_structure_continuation_position' => [ 'position' => 'next_line' ], // else / elseif / catch on a new line.
		'no_multiple_statements_per_line' => true,
		'single_space_around_construct' => true,
		'elseif' => true,
		'no_alternative_syntax' => false, // WP allows if: endif; in templates.
		'switch_case_semicolon_to_colon' => true,
		'switch_case_space' => true,
		'full_opening_tag' => true,
		'no_closing_tag' => true,

		// WP spaces inside parentheses & brackets: foo( $a ), array( $a ), $a[ $key ].
		'spaces_inside_parentheses' => [ 'space' => 'single' ],
		'array_indentation' => true,
		'whitespace_after_comma_in_array' => [ 'ensure_single_space' => true ],
		'no_trailing_comma_in_singleline' => true,
		'trailing_comma_in_multiline' => [ 'elements' => [ 'arrays' ] ],
		'array_syntax' => [ 'syntax' => 'short' ], // RosarioSIS uses [].
		'normalize_index_brace' => true,

		// Spacing around operators & keywords.
		'binary_operator_spaces' => [ 'default' => 'single_space' ],
		'concat_space' => [ 'spacing' => 'one' ],
		'unary_operator_spaces' => true,
		'not_operator_with_successor_space' => true, // WP: if ( ! $a ).
		'ternary_operator_spaces' => true,
		'standardize_not_equals' => true,
		'object_operator_without_whitespace' => true,
		'cast_spaces' => [ 'space' => 'single' ],
		'type_declaration_spaces' => true,
		'function_declaration' => [ 'closure_function_spacing' => 'one', 'closure_fn_spacing' => 'one' ],
		'method_argument_space' => [
			'on_multiline' => 'ensure_fully_multiline',
			'keep_multiple_spaces_after_comma' => false,
		],
		'no_spaces_after_function_name' => true,
		'no_spaces_around_offset' => [ 'positions' => [ 'inside' ] ],
		'single_line_after_imports' => true,
		'semicolon_after_instruction' => true,
		'no_singleline_whitespace_before_semicolons' => true,
		'space_after_semicolon' => [ 'remove_in_empty_for_expressions' => true ],
		'class_attributes_separation' => [ 'elements' => [ 'method' => 'one', 'property' => 'one' ] ],
		'visibility_required' => [ 'elements' => [ 'property', 'method' ] ],
		'no_blank_lines_after_class_opening' => true,
		'lowercase_keywords' => true,
		'constant_case' => [ 'case' => 'lower' ],
		'lowercase_static_reference' => true,
		'magic_constant_casing' => true,
		'native_function_casing' => true,
		'integer_literal_case' => true,

		// Strings & comments: WP prefers single quotes, // comments.
		'single_quote' => true,
		'single_line_comment_style' => [ 'comment_types' => [ 'hash' ] ],
		'no_empty_comment' => true,
		'no_empty_statement' => true,

				// Imports.
		'no_unused_imports' => true,
	] )
	->setFinder( $finder );
