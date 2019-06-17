<?php

// This is the base config, shared with all the others and based on MW's one.

// If xdebug is enabled, we need to increase the nesting level for phan
ini_set( 'xdebug.max_nesting_level', 1000 );

return [
	 // Needed in order to easily read if/elseifs
	'simplify_ast' => false,

	'backward_compatibility_checks' => false,

	'quick_mode' => false,

	// Ignore LOW
	'minimum_severity' => 5,

	// Only show our errors
	'whitelist_issue_types' => [
		'PerformanceCheckArrayMap',
		'PerformanceCheckQueryLoop',
		'PerformancheCheckLoopFunction',
		'PerformanceCheckLiteralRegex',
		'PerformanceCheckSwitchableElseif',
		'PerformanceCheckStrtr',
		// This is included to make it clear that a given file was not correctly analyzed
		'PhanSyntaxError'
	],

	'plugins' => [
		__DIR__ . '/../PerformanceCheckPlugin.php',
	],
];
