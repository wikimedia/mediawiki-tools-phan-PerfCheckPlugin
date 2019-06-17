<?php

// Config for MediaWiki core

$base = require __DIR__ . '/base-config.php';

$coreCfg = [
	'directory_list' => [
		'includes/',
		'languages/',
		'maintenance/',
		'mw-config/',
		'resources/',
		'skins/',
		'vendor/',
	],

	'exclude_analysis_directory_list' => [
		'vendor/',
		'tests/phan/stubs/',
		'maintenance/language/',
		'skins/',
	],
];

return $coreCfg + $base;
