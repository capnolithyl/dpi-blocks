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

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = '' ): string {
		return $text;
	}
}

final class BlockLabTest extends TestCase {
	public function test_scenarios_cover_every_choice_including_nested_choices_and_missing_media(): void {
		$lab = new BlockLab();
		$method = new ReflectionMethod( BlockLab::class, 'build_scenarios' );
		$method->setAccessible( true );
		$group = array( 'fields' => array(
			array( 'name' => 'layout', 'type' => 'button_group', 'choices' => array( 'left' => 'Left', 'right' => 'Right', 'stacked' => 'Stacked' ), 'default_value' => 'left' ),
			array( 'name' => 'slides', 'type' => 'repeater', 'sub_fields' => array(
				array( 'key' => 'field_placement', 'type' => 'select', 'choices' => array( 'background' => 'Background', 'left' => 'Left', 'right' => 'Right' ) ),
				array( 'key' => 'field_image', 'type' => 'image' ),
			) ),
		) );
		// Use a deterministic image ID without a WordPress database.
		$group['fields'][1]['sub_fields'][1]['type'] = 'number';
		$group['fields'][1]['sub_fields'][1]['default_value'] = 99;
		$scenarios = $method->invoke( $lab, $group );
		$this->assertContains( 'stacked', array_column( array_column( $scenarios, 'fields' ), 'layout' ) );
		$this->assertContains( 'right', array_map( static fn( $scenario ) => $scenario['fields']['slides'][0]['field_placement'], $scenarios ) );
		$values = array_map( static fn( $scenario ) => serialize( $scenario['fields'] ), $scenarios );
		$this->assertSame( count( $values ), count( array_unique( $values ) ) );

		$method = new ReflectionMethod( BlockLab::class, 'without_media' );
		$method->setAccessible( true );
		$group['fields'][1]['sub_fields'][1]['type'] = 'image';
		$fields = $method->invoke( $lab, $group['fields'], $scenarios[0]['fields'] );
		$this->assertSame( 0, $fields['slides'][0]['field_image'] );
		$this->assertSame( 'left', $fields['layout'] );
	}
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
