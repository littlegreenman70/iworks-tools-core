<?php
/**
 * Report builder tests.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use IWorksIE\Core\Checks\Result;
use IWorksIE\Core\Report;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IWorksIE\Core\Report
 */
final class ReportTest extends TestCase {

	public function test_from_results_groups_by_status() {
		$md = Report::from_results(
			'Demo Report',
			array(
				Result::critical(
					'Bad',
					'Broken',
					array(
						'why' => 'Y',
						'fix' => 'F',
					)
				),
				Result::good( 'Ok', 'Fine' ),
			),
			array(
				'site' => 'https://example.test',
				'wp'   => '6.7',
				'php'  => '8.3',
				'date' => '2026-11-26T12:00:00Z',
			)
		);

		$this->assertStringContainsString( '# Demo Report', $md );
		$this->assertStringContainsString( '## Critical', $md );
		$this->assertStringContainsString( '## Good', $md );
		$this->assertStringContainsString( '**Why it matters:** Y', $md );
		$this->assertStringContainsString( 'https://example.test', $md );
	}
}
