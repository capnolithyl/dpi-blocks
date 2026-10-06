<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AcfJsonTest extends TestCase {
	private string $root;

	protected function setUp(): void {
		$this->root = dirname( __DIR__, 2 );
	}

	public function test_all_acf_json_is_valid_and_field_keys_are_unique(): void {
		$files = glob( $this->root . '/acf-json/*.json' ) ?: array();
		$this->assertNotEmpty( $files, 'No ACF JSON files were discovered.' );

		$keys = array();

		foreach ( $files as $file ) {
			$group = json_decode( (string) file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
			$this->assertIsArray( $group['fields'] ?? null, basename( $file ) . ' must define fields.' );

			foreach ( $this->flatten_fields( $group['fields'] ) as $field ) {
				$key = $field['key'] ?? '';
				$this->assertIsString( $key, basename( $file ) . ' contains a non-string field key.' );
				$this->assertNotSame( '', $key, basename( $file ) . ' contains an empty field key.' );
				$this->assertArrayNotHasKey( $key, $keys, sprintf( 'Duplicate ACF field key %s in %s and %s.', $key, basename( $file ), $keys[ $key ] ?? '' ) );
				$keys[ $key ] = basename( $file );
			}
		}
	}

	public function test_conditional_logic_references_existing_fields(): void {
		$files      = glob( $this->root . '/acf-json/*.json' ) ?: array();
		$all_fields = array();
		$groups     = array();

		foreach ( $files as $file ) {
			$group  = json_decode( (string) file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
			$fields = $this->flatten_fields( (array) ( $group['fields'] ?? array() ) );
			$groups[ $file ] = $fields;

			foreach ( $fields as $field ) {
				if ( ! empty( $field['key'] ) ) {
					$all_fields[ (string) $field['key'] ] = true;
				}
			}
		}

		foreach ( $groups as $file => $fields ) {
			foreach ( $fields as $field ) {
				foreach ( (array) ( $field['conditional_logic'] ?? array() ) as $and_group ) {
					foreach ( (array) $and_group as $rule ) {
						if ( ! is_array( $rule ) || empty( $rule['field'] ) ) {
							continue;
						}

						$this->assertArrayHasKey(
							(string) $rule['field'],
							$all_fields,
							sprintf( '%s field %s references missing conditional field %s.', basename( $file ), $field['key'] ?? $field['name'] ?? '(unknown)', $rule['field'] )
						);
					}
				}
			}
		}
	}

	public function test_every_bundled_block_has_a_targeting_field_group(): void {
		$blocks = array();

		foreach ( glob( $this->root . '/blocks/*/block.json' ) ?: array() as $file ) {
			$data = json_decode( (string) file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
			$blocks[ (string) $data['name'] ] = false;
		}

		foreach ( glob( $this->root . '/acf-json/*.json' ) ?: array() as $file ) {
			$group = json_decode( (string) file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );

			foreach ( (array) ( $group['location'] ?? array() ) as $rules ) {
				foreach ( (array) $rules as $rule ) {
					if ( ! is_array( $rule ) || 'block' !== ( $rule['param'] ?? '' ) || '==' !== ( $rule['operator'] ?? '' ) ) {
						continue;
					}

					$target = (string) ( $rule['value'] ?? '' );
					if ( str_starts_with( $target, 'dpi/' ) ) {
						$this->assertArrayHasKey( $target, $blocks, basename( $file ) . ' targets unknown block ' . $target );
						$blocks[ $target ] = true;
					}
				}
			}
		}

		foreach ( $blocks as $name => $has_group ) {
			$this->assertTrue( $has_group, $name . ' has no bundled ACF field group.' );
		}
	}

	/**
	 * @param array<int,array<string,mixed>> $fields
	 * @return array<int,array<string,mixed>>
	 */
	private function flatten_fields( array $fields ): array {
		$flat = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$flat[] = $field;
			foreach ( array( 'sub_fields', 'layouts' ) as $children_key ) {
				if ( empty( $field[ $children_key ] ) || ! is_array( $field[ $children_key ] ) ) {
					continue;
				}

				if ( 'layouts' === $children_key ) {
					foreach ( $field[ $children_key ] as $layout ) {
						if ( is_array( $layout ) && ! empty( $layout['sub_fields'] ) && is_array( $layout['sub_fields'] ) ) {
							$flat = array_merge( $flat, $this->flatten_fields( $layout['sub_fields'] ) );
						}
					}
				} else {
					$flat = array_merge( $flat, $this->flatten_fields( $field[ $children_key ] ) );
				}
			}
		}

		return $flat;
	}
}
