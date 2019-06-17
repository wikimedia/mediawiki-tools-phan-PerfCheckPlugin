<?php

// Config for self-analysis.

use \Phan\Config;

// If xdebug is enabled, we need to increase the nesting level for phan
ini_set( 'xdebug.max_nesting_level', 1000 );

return [
	'file_list' => [
		Config::projectPath( './PerformanceCheckPlugin.php' ),
	],

	'directory_list' => [
		Config::projectPath( 'src' ),
		Config::projectPath( 'vendor' )
	],

	'exclude_analysis_directory_list' => [
		Config::projectPath( 'vendor' )
	],

	'backward_compatibility_checks' => false,

	'quick_mode' => false,

	'analyze_signature_compatibility' => true,

	'minimum_severity' => 0,

	'allow_missing_properties' => false,

	'null_casts_as_any_type' => false,

	'scalar_implicit_cast' => false,

	'ignore_undeclared_variables_in_global_scope' => false,

	'dead_code_detection' => true,

	'dead_code_detection_prefer_false_negative' => true,

	'read_type_annotations' => true,

	'processes' => 1,

	'generic_types_enabled' => true,

	'warn_about_undocumented_throw_statements' => true,

	'plugins' => [
		'UnusedSuppressionPlugin',
		'DuplicateExpressionPlugin',
		Config::projectPath( './PerformanceCheckPlugin.php' ),
	],
];
