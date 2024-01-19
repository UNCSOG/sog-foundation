<?php

namespace ToolsetCommonEs\Utils\Config;


use ToolsetCommonEs\Utils\Data\IData;

class Block {

	private $config;

	public function __construct( IData $block_config ) {
		$this->config = $block_config;
	}

	public function get_slug() {
		return $this->config->find(
			['slug'],
			''
		);
	}

	public function get_root_css_class( $template_source = null ) {
		$root_class = null;

		if ( $template_source ) {
			$root_class = $this->config->find( [ 'template', 'source', $template_source, 'css', 'rootClass' ], null );
		}

		if ( ! $root_class ) {
			$root_class = $this->config->find( [ 'css', 'rootClass' ], null );
		}

		return $root_class ?: '';
	}

	/**
	 * @param string $template_source_slug
	 *
	 * @return array
	 */
	public function get_template_source_default_attributes( $template_source_slug ) {
		return $this->config->find(
			['template', 'source', $template_source_slug, 'attributes', 'defaults' ],
			[]
		);
	}

	/**
	 * @param mixed $attributes
	 *
	 * @return string|null
	 */
	public function get_active_template_source( $attributes ) {
		if( $source_in_attributes = $this->config->find_in( ['template', 'source'], $attributes ) ) {
			return $source_in_attributes;
		}

		// No source stored. Check for default.
		if( $default_template_source = $this->config->find( ['template', 'default'] ) ) {
			return $default_template_source;
		}

		return null;
	}

	/**
	 * @param array $attributes
	 * @return array
	 */
	public function merge_template_source_defaults_to_attributes( $attributes ) {
		if( ! $active_template_source = $this->get_active_template_source( $attributes ) ) {
			// No active template source.
			return $attributes;
		}

		$defaults_template_source = $this->get_template_source_default_attributes( $active_template_source );

		return array_merge( $defaults_template_source, $attributes );
	}

	public function get_style_map( $template_source = null ) {
		$style_map = $this->config->find( [ 'css', 'styleMap' ] );
		$normalized_style_map = [];

		foreach( $style_map as $selector => $storages ) {
			if(
				is_array( $storages ) &&
				array_key_exists( 'selectors', $storages ) &&
				array_key_exists( 'attributes', $storages )
			) {
				$final_selectors = [];


				foreach( $storages['selectors'] as $s ) {
					$final_selectors[] = $this->apply_root_class_to_selector( $s, $template_source );
				}

				$final_selector = implode( '!', $final_selectors );
				$storages = $storages['attributes'];
			} else {
				$final_selector = $this->apply_root_class_to_selector( $selector, $template_source );
			}

			$normalized_style_map[ $final_selector ] = [];

			foreach( $storages as $storage_key => $fields ) {
				$normalized_style_map[ $final_selector ][ $storage_key ] = [];

				$storage_key_attributes = $this->get_fields( $storage_key );

				if( $fields === 'all' ) {
					$normalized_style_map[ $final_selector ][ $storage_key ] = $storage_key_attributes;
				} elseif ( is_array( $fields ) ) {
					foreach( $fields as $field ) {
						if( is_string( $field ) && array_key_exists( $field, $storage_key_attributes ) ) {
							$normalized_style_map[ $final_selector ][ $storage_key ][ $field ] = $storage_key_attributes[ $field ];
						}
					}
				}
			}
		}

		return $normalized_style_map;
	}

	private function get_attributes() {
		return $this->config->find( [ 'attributes' ], [] );
	}

	public function get_tabs_of_storage_key( $storage_key ) {
		return $this->config->find( ['attributes', $storage_key, 'tabs' ], [] );
	}

	public function get_fields( $of_storage_key = null ) {
		$attributes = $this->get_attributes();
		$normalized_fields = [];

		foreach( $attributes as $storage_key => $storages ) {
			if( ! array_key_exists( 'fields', $storages ) ) {
				continue;
			}

			$normalized_fields[ $storage_key ] = [];

			foreach( $storages['fields'] as $field_key => $field ) {
				if( is_string( $field ) ) {
					$normalized_fields[ $storage_key ][ $field ] = [
						'type' => $field
					];
				} elseif( is_array( $field ) ) {
					if( ! array_key_exists( 'type', $field ) ) {
						$field['type'] = $field_key;
					}

					$normalized_fields[ $storage_key ][ $field_key ] = $field;
				}
			}
		}

		if( $of_storage_key ) {
			return array_key_exists( $of_storage_key, $normalized_fields ) ?
				$normalized_fields[ $of_storage_key ] :
				[];
		}

		return $normalized_fields;
	}

	public function find_in( $needle, $values, $return_on_false = false ) {
		return $this->config->find_in( $needle, $values, $return_on_false );
	}

	private function apply_root_class_to_selector( $selector, $template_source = null ) {
		$root_css_class = $this->get_root_css_class( $template_source );
		$root_css_class = ! empty( $root_css_class ) ?
			'.' . $root_css_class :
			'';

		if( strpos( $selector, '%rootClass%' ) === false ) {
			$selector = '%rootClass% ' . $selector;
		}

		$selector = str_replace( '%rootClass%', $root_css_class, $selector );

		return trim( $selector );
	}
}
