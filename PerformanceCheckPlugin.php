<?php declare( strict_types=1 );

require_once __DIR__ . '/src/PerformanceVisitor.php';

use Phan\PluginV3;
use Phan\PluginV3\PostAnalyzeNodeCapability;

class PerformanceCheckPlugin extends PluginV3 implements PostAnalyzeNodeCapability {
	/**
	 * @inheritDoc
	 */
	public static function getPostAnalyzeNodeVisitorClassName(): string {
		return PerformanceVisitor::class;
	}
}

return new PerformanceCheckPlugin;
