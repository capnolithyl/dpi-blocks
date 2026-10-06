<?php

declare(strict_types=1);

use DPI\Blocks\BlockLab;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

if ( ! function_exists( 'absint' ) ) {
	function absint( mixed $value ): int {
		return abs( (int) $value );
	}
}

require_once dirname( __DIR__, 2 ) . '/includes/BlockLab.php';

final class BlockLabTest extends TestCase {
	public function test_generated_repeater_and_group_rows_use_acf_field_keys_recursively(): void {
		$lab    = new BlockLab();
		$method = new ReflectionMethod( BlockLab::class, 'sample_value' );
		$method->setAccessible( true );

		$field = array(
			'key'        => 'field_parent_repeater',
			'name'       => 'items',
			'type'       => 'repeater',
			'min'        => 1,
			'max'        => 0,
			'sub_fields' => array(
				array(
					'key'        => 'field_nested_group',
					'name'       => 'settings',
					'type'       => 'group',
					'sub_fields' => array(
						array(
							'key'           => 'field_nested_toggle',
							'name'          => 'enabled',
							'type'          => 'true_false',
							'default_value' => 1,
						),
					),
				),
			),
		);

		$this->assertSame(
			array(
				array(
					'field_nested_group' => array(
						'field_nested_toggle' => true,
					),
				),
			),
			$method->invoke( $lab, $field, false )
		);
	}

	public function test_each_scenario_gets_a_distinct_stable_acf_block_id(): void {
		$lab    = new BlockLab();
		$method = new ReflectionMethod( BlockLab::class, 'scenario_block_id' );
		$method->setAccessible( true );

		$baseline  = $method->invoke( $lab, 'dpi/stats', 'Baseline' );
		$alternate = $method->invoke( $lab, 'dpi/stats', 'Alternate settings' );

		$this->assertSame( $baseline, $method->invoke( $lab, 'dpi/stats', 'Baseline' ) );
		$this->assertNotSame( $baseline, $alternate );
		$this->assertStringStartsWith( 'block_dpi_lab_', $baseline );
		$this->assertStringStartsWith( 'block_dpi_lab_', $alternate );
	}
}
