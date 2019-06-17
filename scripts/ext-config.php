<?php

// Config for MediaWiki extensions

$base = require __DIR__ . '/base-config.php';
$IP = getenv( 'MW_INSTALL_PATH' ) !== false
	// Replace \\ by / for windows users to let exclude work correctly
	? str_replace( '\\', '/', getenv( 'MW_INSTALL_PATH' ) )
	: '../..';

$extCfg = [
	'directory_list' => [
		$IP . 'includes/',
		$IP . 'languages/',
		$IP . 'maintenance/',
		$IP . 'mw-config/',
		$IP . 'resources/',
		$IP . 'skins/',
		$IP . 'vendor/',
		'.'
	],
	'exclude_analysis_directory_list' => [
		$IP . 'vendor/',
		$IP . 'tests/phan/stubs/',
		$IP . 'maintenance/language/',
		'vendor'
	],
];

unset( $IP );

return $extCfg + $base;
