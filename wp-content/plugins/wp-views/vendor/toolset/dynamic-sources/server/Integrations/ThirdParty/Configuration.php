<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty;

use Toolset\DynamicSources\Integrations\ThirdParty\XML\XML2Array;
use Toolset\DynamicSources\Integrations\ThirdParty\XML\XMLConfigReadFileFactory;

/**
 * Handles the automatic Dynamic Sources integration mechanism that fetches, parses, processes and saves the configuration
 * for each plugin.
 */
class Configuration {
	const TOOLSET_CONFIG_FILE_NAME = 'toolset-config.xml';

	const TOOLSET_CONFIG_VALIDATION_FILE_PATH = __DIR__ . '/XML/toolset-config.xsd';

	const TOOLSET_CONFIG_OPTION_NAME = 'toolset_dynamic_sources_config';

	/** @var array */
	private $active_plugins = [];

	/** @var array */
	private $ds_config_files = [];

	/** @var array */
	private $config_all = [];

	/** @var XMLConfigReadFileFactory */
	private $xml_config_read_file_factory;

	/** @var XML2Array */
	private $xml_2_array;

	/**
	 * Configuration constructor.
	 *
	 * @param XMLConfigReadFileFactory $xml_config_read_file_factory
	 * @param XML2Array                $xml_2_array
	 */
	public function __construct( XMLConfigReadFileFactory $xml_config_read_file_factory, XML2Array $xml_2_array ) {
		$this->xml_config_read_file_factory = $xml_config_read_file_factory;
		$this->xml_2_array = $xml_2_array;
	}

	/**
	 * Handles the configuration mechanism loading.
	 */
	public function load() {
		$this->load_plugins_ds_config();
		$this->parse_ds_config_files();
		$this->save_config_settings();
	}

	/**
	 * Loads the Dynamic Sources configuration from the configuration files in the root of each of the integrated third-party plugins.
	 */
	private function load_plugins_ds_config() {
		if ( is_multisite() ) {
			// Get multi site plugins
			$plugins = get_site_option( 'active_sitewide_plugins' );
			if ( ! empty( $plugins ) ) {
				foreach ( $plugins as $p => $dummy ) {
					if ( ! $this->check_on_config_file( $p ) ) {
						continue;
					}
					$plugin_slug = dirname( $p );
					$config_file = WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . self::TOOLSET_CONFIG_FILE_NAME;
					if ( trim( $plugin_slug, '\/.' ) && file_exists( $config_file ) ) {
						$this->ds_config_files[] = $config_file;
					}
				}
			}
		}

		// Get single site or current blog active plugins
		$plugins = get_option( 'active_plugins' );
		if ( ! empty( $plugins ) ) {
			foreach ( $plugins as $p ) {
				if ( ! $this->check_on_config_file( $p ) ) {
					continue;
				}

				$plugin_slug = dirname( $p );
				$config_file = WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . self::TOOLSET_CONFIG_FILE_NAME;
				if ( trim( $plugin_slug, '\/.' ) && file_exists( $config_file ) ) {
					$this->ds_config_files[] = $config_file;
				}
			}
		}

		// Get the must-use plugins
		$mu_plugins = wp_get_mu_plugins();

		if ( ! empty( $mu_plugins ) ) {
			foreach ( $mu_plugins as $mup ) {
				if ( ! $this->check_on_config_file( $mup ) ) {
					continue;
				}

				$plugin_dir_name = dirname( $mup );
				$plugin_base_name = basename( $mup, '.php' );
				$plugin_sub_dir = $plugin_dir_name . '/' . $plugin_base_name;
				if ( file_exists( $plugin_sub_dir . '/' . self::TOOLSET_CONFIG_FILE_NAME ) ) {
					$config_file = $plugin_sub_dir . '/' . self::TOOLSET_CONFIG_FILE_NAME;
					$this->ds_config_files[] = $config_file;
				}
			}
		}
	}

	/**
	 * Forms the array of configuration files for third-party plugins containing configuration data for the automatic Dynamic
	 * Sources integration.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	private function check_on_config_file( $name ) {
		if ( empty( $this->active_plugins ) ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$this->active_plugins = get_plugins();
		}

		$config_index_file_data = maybe_unserialize( get_option( 'toolset_dynamic_sources_config_index' ) );
		$config_files_arr = maybe_unserialize( get_option( 'toolset_dynamic_sources_config_files_arr' ) );

		if ( ! $config_index_file_data || ! $config_files_arr ) {
			return true;
		}

		if ( isset( $this->active_plugins[ $name ] ) ) {
			$plugin_info = $this->active_plugins[ $name ];
			$plugin_slug = dirname( $name );
			$name = $plugin_info['Name'];
			$config_data = $config_index_file_data->plugins;
			$config_files_arr = $config_files_arr->plugins;
			$config_file = WP_PLUGIN_DIR . '/' . $plugin_slug . '/toolset-config.xml';
			$type = 'plugin';
		} else {
			$config_data = $config_index_file_data->themes;
			$config_files_arr = $config_files_arr->themes;
			$config_file = get_template_directory() . '/toolset-config.xml';
			$type = 'theme';
		}

		foreach ( $config_data as $item ) {
			if ( $name === $item->name && isset( $config_files_arr[ $item->name ] ) ) {
				if ( $item->override_local || ! file_exists( $config_file ) ) {
					$ds_config = new \stdClass();
					$ds_config->config = $this->xml_2_array->get( $config_files_arr[ $item->name ] );
					$ds_config->type = $type;
					$ds_config->admin_text_context = basename( dirname( $config_file ) );
					$this->ds_config_files[] = $ds_config;
					return false;
				} else {
					return true;
				}
			}
		}

		return true;
	}

	/**
	 * Parses the array of configuration files for third-party plugins containing configuration data for the automatic Dynamic
	 * Sources integration and builds the final array with the final configuration data to be saved.
	 */
	private function parse_ds_config_files() {
		$config_all = [
			'toolset-config' => [
				'blocks' => [],
			],
		];

		$config_all_updated = false;

		if ( ! empty( $this->ds_config_files ) ) {
			foreach ( $this->ds_config_files as $file ) {
				if ( is_object( $file ) ) {
					$config = $file->config;
				} else {
					$xml_config_file = $this->xml_config_read_file_factory->create_xml_config_read_file( $file );
					$config = $xml_config_file->get();
				}

				do_action( 'toolset/dynamic_sources/actions/parse_config_file', $file );

				$config_all = $this->merge_with( $config_all, $config );
				$config_all_updated = true;
			}
		}

		if ( $config_all_updated ) {
			$this->config_all = apply_filters( 'toolset/dynamic_sources/filters/config_array', $config_all );
		}
	}

	/**
	 * Merges the single config array (second argument) into the "all configs" array (first argument).
	 *
	 * @param array $all_configs
	 * @param array $config
	 *
	 * @return array
	 */
	private function merge_with( $all_configs, $config ) {
		if ( isset( $config['toolset-config'] ) ) {
			$toolset_config = $config['toolset-config'];
			$toolset_config_all = $all_configs['toolset-config'];
			$toolset_config_all = $this->parse_config_index( $toolset_config_all, $toolset_config, 'block', 'blocks' );
			$all_configs['toolset-config'] = $toolset_config_all;
		}

		return $all_configs;
	}

	/**
	 * Handles the merging of the configuration data for automatic Dynamic Sources integration, read for a single third-party plugin,
	 * into the array holding the data for all the third-party plugins.
	 *
	 * @param array  $config_all
	 * @param array  $toolset_config
	 * @param string $index_sing
	 * @param string $index_plur
	 *
	 * @return array
	 */
	private function parse_config_index( $config_all, $toolset_config, $index_sing, $index_plur ) {
		if ( isset( $toolset_config[ $index_plur ][ $index_sing ] ) ) {
			if ( isset( $toolset_config[ $index_plur ][ $index_sing ]['value'] ) ) { // single
				$config_all[ $index_plur ][ $index_sing ][] = $toolset_config[ $index_plur ][ $index_sing ];
			} else {
				foreach ( (array) $toolset_config[ $index_plur ][ $index_sing ] as $cf ) {
					$config_all[ $index_plur ][ $index_sing ][] = $cf;
				}
			}
		}

		return $config_all;
	}

	/**
	 * Saves the configuration into the WP options table.
	 */
	private function save_config_settings() {
		$config = $this->parse_config_for_option_save( $this->config_all );

		update_option( self::TOOLSET_CONFIG_OPTION_NAME, $config );
	}

	/**
	 * Retrieves the "blocks" element from he config array.
	 *
	 * @param array $config
	 *
	 * @return array
	 */
	private function get_blocks_from_config( $config ) {
		if (
			! isset( $config['toolset-config'] ) ||
			! is_array( $config['toolset-config'] ) ||
			! isset( $config['toolset-config']['blocks'] ) ||
			! is_array( $config['toolset-config']['blocks'] ) ||
			! isset( $config['toolset-config']['blocks']['block'] ) ||
			! is_array( $config['toolset-config']['blocks']['block'] )
		) {
			return array();
		}

		return $config['toolset-config']['blocks']['block'];
	}

	/**
	 * Gets the dynamic attributes array with all the relevant dynamic attributes information for the specified block.
	 *
	 * @param array       $dynamic_block_attributes
	 * @param string|null $attribute_group_name
	 *
	 * @return array
	 */
	private function get_dynamic_block_attributes( $dynamic_block_attributes, $attribute_group_name = null ) {
		$dynamic_block_attributes_array = array();

		foreach ( $dynamic_block_attributes as $dynamic_block_attribute ) {
			if (
				! isset( $dynamic_block_attribute['value'] ) ||
				! isset( $dynamic_block_attribute['attr'] ) ||
				! is_array( $dynamic_block_attribute['attr'] ) ||
				! isset( $dynamic_block_attribute['attr']['category'] ) ||
				! isset( $dynamic_block_attribute['attr']['label'] )
			) {
				continue;
			}

			$attr_name = $dynamic_block_attribute['value'];

			$dynamic_block_attributes_array[ $attr_name ] = array();

			// For the case of a text attribute, the conversion of the value of it to string is forced.
			// Makes it possible to use for example number fields (Post ID) to text attributes.
			if ( 'text' === $dynamic_block_attribute['attr']['category'] ) {
				$dynamic_block_attributes_array[ $attr_name ]['forceType'] = 'lodash.toString';
			}

			$dynamic_block_attributes_array[ $attr_name ]['outputFormat'] = 'normal';
			if ( isset( $dynamic_block_attribute['outputFormat'] ) ) {
				$dynamic_block_attributes_array[ $attr_name ]['outputFormat'] = $dynamic_block_attribute['outputFormat'];
			}

			foreach ( $dynamic_block_attribute['attr'] as $attr_key => $attr_value ) {
				if ( 'wrapper' === $attr_key ) {
					switch ( $attr_value ) {
						case 'paragraph':
							$attr_value = '<p>%s</p>';
							break;
						case 'div':
							$attr_value = '<div>%s</div>';
							break;
					}
				}

				if ( 'forceType' === $attr_key ) {
					switch ( $attr_value ) {
						case 'int':
							$attr_value = 'parseInt';
							break;
						case 'string':
							$attr_value = 'lodash.toString';
							break;
					}
				}

				$dynamic_block_attributes_array[ $attr_name ][ $attr_key ] = $attr_value;
			}

			if ( $attribute_group_name ) {
				$dynamic_block_attributes_array[ $attr_name ]['group'] = $attribute_group_name;
			}
		}

		return $dynamic_block_attributes_array;
	}

	/**
	 * Parses the configuration array holding the data for all the for third-party plugins that have automatic Dynamic
	 * Sources integration and produces an array of data in a proper form to be saved in the WP options table.
	 *
	 * @param array $config
	 *
	 * @return array
	 */
	private function parse_config_for_option_save( array $config ) {
		$parsed_configuration = array();
		$blocks = $this->get_blocks_from_config( $config );

		foreach ( $blocks as $block ) {
			$dynamic_block_attribute_groups_array = array();
			$grouped_dynamic_block_attributes = array();
			$ungrouped_dynamic_block_attributes = array();

			if ( isset( $block['dynamic-attribute-group'] ) && is_array( $block['dynamic-attribute-group'] ) ) {
				foreach ( $block['dynamic-attribute-group'] as $attribute_group ) {
					if ( ! isset( $attribute_group['dynamic-attribute'] ) || ! is_array( $attribute_group['dynamic-attribute'] ) ) {
						continue;
					}

					$dynamic_block_attributes = $attribute_group['dynamic-attribute'];

					// If the current block has a single dynamic attribute...
					if ( isset( $dynamic_block_attributes['value'] ) ) {
						$dynamic_block_attributes = array( $dynamic_block_attributes );
					}

					$grouped_dynamic_block_attributes = array_merge( $grouped_dynamic_block_attributes, $this->get_dynamic_block_attributes( $dynamic_block_attributes, $attribute_group['attr']['name'] ) );

					$dynamic_block_attribute_groups_array[ $attribute_group['attr']['name'] ] = array(
						'label' => $attribute_group['attr']['label'],
					);

					if ( isset( $attribute_group['attr']['condition'] ) ) {
						$dynamic_block_attribute_groups_array[ $attribute_group['attr']['name'] ]['condition'] = $attribute_group['attr']['condition'];
					}
					if ( isset( $attribute_group['attr']['parentCondition'] ) ) {
						$dynamic_block_attribute_groups_array[ $attribute_group['attr']['name'] ]['parentCondition'] = $attribute_group['attr']['parentCondition'];
					}
				}
			}

			// If there are ungrouped dynamic attributes.
			if ( isset( $block['dynamic-attribute'] ) ) {
				// If the current block has a single dynamic attribute...
				if ( isset( $block['dynamic-attribute']['value'] ) ) {
					$block['dynamic-attribute'] = array( $block['dynamic-attribute'] );
				}

				$ungrouped_dynamic_block_attributes = $this->get_dynamic_block_attributes( $block['dynamic-attribute'] );
			}

			$merged_dynamic_attributes = array_merge( $grouped_dynamic_block_attributes, $ungrouped_dynamic_block_attributes );
			if ( 0 < count( $merged_dynamic_attributes ) ) {
				$parsed_configuration[ $block['attr']['name'] ] = array(
					'dynamicAttributes' => $merged_dynamic_attributes,
					'removeSaveWrapper' => isset( $block['attr']['removeSaveWrapper'] ) ? $block['attr']['removeSaveWrapper'] : false,
				);

				if ( 0 < count( $dynamic_block_attribute_groups_array ) ) {
					$parsed_configuration[ $block['attr']['name'] ]['groups'] = $dynamic_block_attribute_groups_array;
				}
			}
			if ( isset( $block['attr']['condition'] ) ) {
				$parsed_configuration[ $block['attr']['name'] ]['condition'] = $block['attr']['condition'];
			}
		}

		return $parsed_configuration;
	}
}
