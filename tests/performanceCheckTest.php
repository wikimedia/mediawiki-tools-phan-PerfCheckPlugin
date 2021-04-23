<?php

/**
 * phpcs:disable MediaWiki.Commenting.MissingCovers.MissingCovers
 * phpcs:disable MediaWiki.Usage.ForbiddenFunctions.shell_exec
 */
class PerformanceCheckTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected output for the directory
	 * @dataProvider provideScenarios
	 */
	public function testScenarios( $name, $expected ) {
		// Ensure that we're in the main project folder
		chdir( __DIR__ . '/../' );
		$cmd = "php vendor/phan/phan/phan" .
			" --project-root-directory \"tests/\"" .
			" --allow-polyfill-parser" .
			" --config-file \"integration-test-config.php\"" .
			" -l \"integration/$name\"";

		$res = shell_exec( $cmd );
		$this->assertEquals( $expected, $res );
	}

	/**
	 * Data provider for testScenarios
	 *
	 * @return Generator
	 */
	public function provideScenarios() {
		$iterator = new DirectoryIterator( __DIR__ . '/integration' );

		foreach ( $iterator as $dir ) {
			if ( $dir->isDot() ) {
				continue;
			}
			$folder = $dir->getPathname();
			$testName = basename( $folder );
			$expected = file_get_contents( $folder . '/expectedResults.txt' );

			yield $testName => [ $testName, $expected ];
		}
	}
}
