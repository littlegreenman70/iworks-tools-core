<?php
/**
 * Summary score ring colour bands.
 *
 * @package IWorksIE\Core
 */

namespace IWorksIE\Core\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use IWorksIE\Core\UI;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IWorksIE\Core\UI::summary_bar
 */
final class SummaryScoreTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'translate' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_score_90_plus_is_good() {
		// 9 good, 1 recommended => 90.
		$html = UI::summary_bar( 9, 1, 0 );
		$this->assertStringContainsString( 'iwt-summary__score--good', $html );
		$this->assertStringContainsString( '>90</span>', $html );
	}

	public function test_score_mid_band_is_recommended_even_with_critical() {
		// 7 good, 0 recommended, 3 critical => 70. Colour follows score, not status mix.
		$html = UI::summary_bar( 7, 0, 3 );
		$this->assertStringContainsString( 'iwt-summary__score--recommended', $html );
		$this->assertStringContainsString( '>70</span>', $html );
	}

	public function test_score_under_60_is_critical() {
		$html = UI::summary_bar( 5, 0, 5 );
		$this->assertStringContainsString( 'iwt-summary__score--critical', $html );
		$this->assertStringContainsString( '>50</span>', $html );
	}
}
