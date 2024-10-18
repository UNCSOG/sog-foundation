<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Views conditional output shortcode manager.
 * Uses WPToolset_Types and WPV_Handle_Users_Functions,
 * we should remove those dependencis so we do not need to load TC->toolset_forms.
 *
 * @since unknown
 */
class WPV_Views_Conditional {

	const SHORTCODE_NAME = 'wpv-conditional';

	const SHORTCODE_START_TAG = '[wpv-conditional';

	const SHORTCODE_END_TAG = '[/wpv-conditional]';

	/**
	 * Helper to resolve attributes to get data for related posts.
	 *
	 * @var Toolset_Shortcode_Attr_Item_M2M
	 */
	private $attr_item_chain = null;

	/**
	 * Helper to resolve attributes to get data for related posts.
	 *
	 * @var Toolset_Common_Bootstrap
	 */
	private $toolset_common_bootstrap = null;

	/**
	 * Instantiate the class.
	 *
	 * @param \Toolset_Shortcode_Attr_Item_M2M $attr_item_chain
	 * @param \Toolset_Common_Bootstrap $toolset_common_bootstrap
	 * @since 2.7.3
	 */
	public function __construct(
		\Toolset_Shortcode_Attr_Item_M2M $attr_item_chain,
		\Toolset_Common_Bootstrap $toolset_common_bootstrap
	) {
		$this->attr_item_chain = $attr_item_chain;
		$this->toolset_common_bootstrap = $toolset_common_bootstrap;
	}

	/**
	 * Initialize the conditionals:
	 * - get dependencies loaded.
	 * - register shortcodes.
	 * - add an API filter to resolve shortcodes early.
	 * - register the editor buttons.
	 *
	 * @return void
	 */
	public function initialize() {
		$toolset_common_sections = array(
			'toolset_parser',
			'toolset_forms'// Load WPToolset_Types and WPV_Handle_Users_Functions
		);
		$this->toolset_common_bootstrap->load_sections( $toolset_common_sections );

		add_shortcode( self::SHORTCODE_NAME, array( $this, 'resolve_shortcode' ) );
		add_filter( 'wpv_process_conditional_shortcodes', array( $this, 'process_conditional_shortcodes' ) );

		add_action( 'wp_loaded', array( $this, 'register_editor_buttons' ) );
		add_action( 'current_screen', array( $this, 'disable_editor_buttons_on_blocks_editor' ) );

		add_filter( 'wpv_filter_match_innermost_conditional_shortcode', array( $this, 'match_innermost_conditional_shortcode' ) );
	}

	/**
	 * Resolve the shortcode.
	 *
	 * @param array $attr
	 * @param string $content
	 * @return string
	 */
	public function resolve_shortcode( $attr, $content = '' ) {
		global $post;
		$has_post = true;
		$id = '';
		if ( empty( $post->ID ) ) {
			// Will not execute any condition that involves custom fields
			$has_post = false;
		} else {
			$id = $post->ID;
		}

		if (
			empty( $attr['if'] )
			|| (
				empty( $content )
				&& $content !== '0'
			)
		) {
			return ''; // ignore
		}

		extract(
			shortcode_atts(
				array(
					'evaluate' => 'true',
					'debug' => false,
					'if' => true
				),
				$attr
			)
		);


		$out = '';
		$evaluate = ( $evaluate == 'true' || $evaluate === true ) ? true : false;
		$debug = ( $debug == 'true' || $debug === true ) ? true : false;

		$attr['if'] = str_replace( " NEQ ", " ne ", $attr['if'] );
		$attr['if'] = str_replace( " neq ", " ne ", $attr['if'] );
		$attr['if'] = str_replace( " EQ ", " = ", $attr['if'] );
		$attr['if'] = str_replace( " eq ", " = ", $attr['if'] );
		$attr['if'] = str_replace( " NE ", " ne ", $attr['if'] );
		$attr['if'] = str_replace( " != ", " ne ", $attr['if'] );

		$attr['if'] = str_replace( " LT ", " < ", $attr['if'] );
		$attr['if'] = str_replace( " lt ", " < ", $attr['if'] );
		$attr['if'] = str_replace( " LTE ", " <= ", $attr['if'] );
		$attr['if'] = str_replace( " lte ", " <= ", $attr['if'] );
		$attr['if'] = str_replace( " GT ", " > ", $attr['if'] );
		$attr['if'] = str_replace( " gt ", " > ", $attr['if'] );
		$attr['if'] = str_replace( " GTE ", " >= ", $attr['if'] );
		$attr['if'] = str_replace( " gte ", " >= ", $attr['if'] );

		// Sometimes (e.g. inside a View block), > sign can be mangled to &gt;. So, just turn it back to what it should
		// be before processing.
		$attr['if'] = str_replace( '&gt;', '>', $attr['if'] );

		if ( strpos( $content, 'wpv-b64-' ) === 0 ) {
			$content = substr( $content, 7 );
			$content = base64_decode( $content );
		}

		$evaluation_result = $this->parse_conditional( $post, $attr['if'], $debug, $attr, $id, $has_post );

		if (
			(
				$evaluate
				&& $evaluation_result['passed']
			) || (
				!$evaluate
				&& !$evaluation_result['passed']
			)
		) {
			$out = $content;
		}

		if (
			$debug
			&& current_user_can( EDIT_VIEWS )
		) {
			$class_name = defined( 'REST_REQUEST' ) ? ' class="wpv-conditional-debug-wrapper"' : '';
			$out .= '<pre ' . $class_name . '>' . $evaluation_result['debug'] . '</pre>';
		}

		apply_filters( 'wpv_shortcode_debug', self::SHORTCODE_NAME, wp_json_encode( $attr ), '', 'Data received from cache', $out );

		return $out;
	}

	/**
	 * Register the conditional output editor buttons both on MCE and quicktags.
	 * Note that this needs to run after init so third parties have initialized their own pages.
	 *
	 * @since 2.6.1
	 */
	public function register_editor_buttons() {
		/**
		 * Filter to disable the conditional output quicktag on demand on all editors of the current page.
		 *
		 * @param bool
		 * @return bool
		 * @since 2.6.1
		 */
		if ( apply_filters( 'wpv_filter_wpv_disable_conditional_output_quicktag', false ) ) {
			return;
		}

		add_filter( 'mce_external_plugins', array( $this, 'wpv_add_views_conditional_button_scripts' ), 9 );
		add_filter( 'mce_buttons', array( $this, 'register_buttons_editor' ), 9 );
		add_action( 'admin_print_footer_scripts', array( $this, 'add_quicktags' ), 99 );
		add_action( 'wp_print_footer_scripts', array( $this, 'add_quicktags' ), 99 );
	}

	/**
	 * Maybe disable the editor buttons on block editor pages.
	 *
	 * @param \WP_Screen $current_screen
	 */
	public function disable_editor_buttons_on_blocks_editor( $current_screen  ) {
		if (
			method_exists($current_screen, 'is_block_editor')
			&& $current_screen->is_block_editor()
		) {
			remove_filter( 'mce_external_plugins', array( $this, 'wpv_add_views_conditional_button_scripts' ), 9 );
			remove_filter( 'mce_buttons', array( $this, 'register_buttons_editor' ), 9 );
			remove_action( 'admin_print_footer_scripts', array( $this, 'add_quicktags' ), 99 );
		}
	}

	/**
	 * Register the MCE button scripts.
	 *
	 * @param array $plugin_array
	 * @return array
	 */
	public function wpv_add_views_conditional_button_scripts( $plugin_array ) {
		if ( wp_script_is( 'views-shortcodes-gui-script' ) ) {
			// Enqueue TinyMCE plugin script with its ID.
			$plugin_array['wpv_add_views_conditional_button'] = WPV_URL_EMBEDDED . '/res/js/views_conditional_button_plugin.js?ver=' . WPV_VERSION;
		}

		return $plugin_array;
	}

	/**
	 * Register the MCE button.
	 *
	 * @param array $buttons
	 * @return array
	 */
	public function register_buttons_editor( $buttons ) {
		if ( wp_script_is( 'views-shortcodes-gui-script' ) ) {
			// Register buttons with their id.
			array_push( $buttons, 'wpv_conditional_output' );
		}

		return $buttons;
	}

	/**
	 * Register the quicktag button.
	 */
	public function add_quicktags() {
		if ( wp_script_is( 'views-shortcodes-gui-script' ) ) {
			?>
			<script type="text/javascript">
				QTags.addButton('wpv_conditional', '<?php echo esc_js( __( 'conditional output', 'wpv-views' ) ); ?>', wpv_add_conditional_quicktag_function, '', 'c', '<?php echo esc_js( __( 'Views conditional output', 'wpv-views' ) ); ?>', 121, '', {
					ariaLabel: '<?php echo esc_js( __( 'Views conditional output', 'wpv-views' ) ); ?>',
					ariaLabelClose: '<?php echo esc_js( __( 'Close Views conditional output', 'wpv-views' ) ); ?>'
				});
			</script>
			<?php
		}
	}

	/**
	 * Parse the shortcode condition.
	 *
	 * @param WP_Post|null $post
	 * @param string $condition
	 * @param bool $debug
	 * @param array $attr
	 * @param int|string $id
	 * @param bool $has_post
	 * @return array (
	 *     @type string debug The debug log string if crafted
	 *     @type bool passed Whether the condition evaluates to TRUE or FALSE
	 * )
	 */
	private function parse_conditional( $post, $condition, $debug, $attr, $id, $has_post ) {

		$logging_string = "####################\nwpv-conditional attributes\n####################\n"
			. htmlentities( print_r( $attr, true ) )
			. "\n####################\nDebug information\n####################"
			. "\n--------------------\nOriginal expression: "
			. htmlentities( $condition )
			. "\n--------------------";

		// Resolve getting custom fields values from legacy relationships parents,
		// using the following syntax (double quotes in place of single also apply):
		//
		// $(field-slug).id(parent-slug), $('field-slug').id(parent-slug)
		// $(field-slug).id($parent-slug), $('field-slug').id($parent-slug)
		// $(field-slug).id('parent-slug'), $('field-slug').id('parent-slug')
		// $(field-slug).id('$parent-slug'), $('field-slug').id('$parent-slug')
		if ( strpos( $condition, '.id(' ) !== false ) {
			preg_match_all( '/[$]\(([^\)]+)\)\.id\(([^\)]+)\)/Uim', $condition, $matches );
			$matches_count = count( $matches[0] );
			if ( $matches_count > 0 ) {
				for ( $i = 0; $i < $matches_count; $i++ ) {
					$parent_name = str_replace( array( '"', "'" ), '', $matches[2][ $i ] );
					if ( strpos( $parent_name, '$' ) !== 0 ) {
						$parent_name = '$' . $parent_name;
					}
					if ( ! $post_id = $this->attr_item_chain->get( array( 'id' => $parent_name ) ) ) {
						// no valid item
						continue;
					}

					$field_name = str_replace( array( '"', "'" ), '', $matches[1][ $i ] );
					$temp_condition = $matches[0][ $i ];

					$condition = $this->extract_fields_in_condition( $condition, $temp_condition, $post_id, $field_name );
				}
			}
		}

		// Resolve getting values from m2m relationships related posts,
		// using the following syntax (double quotes in place of single also apply):
		//
		// $(field-slug).item(parent-slug), $('field-slug').item(parent-slug)
		// $(field-slug).item($parent-slug), $('field-slug').item($parent-slug)
		// $(field-slug).item('parent-slug'), $('field-slug').item('parent-slug')
		// $(field-slug).item('$parent-slug'), $('field-slug').item('$parent-slug')
		//
		// #(taxonomy-slug).item(parent-slug)
		// #(taxonomy-slug).item($parent-slug)
		// #(taxonomy-slug).item('parent-slug')
		// #(taxonomy-slug).item('$parent-slug')
		if ( strpos( $condition, '.item(' ) !== false ) {
			preg_match_all( '/[$]\(([^\)]+)\)\.item\(([^\)]+)\)/Uim', $condition, $matches );
			$matches_count = count( $matches[0] );
			if ( $matches_count > 0 ) {
				for ( $i = 0; $i < $matches_count; $i++ ) {
					$related_reference = str_replace( array( '"', "'" ), '', $matches[2][ $i ] );
					if ( ! $post_id = $this->attr_item_chain->get( array( 'item' => $related_reference ) ) ) {
						// no valid item
						continue;
					}

					$field_name = str_replace( array( '"', "'" ), '', $matches[1][ $i ] );
					$temp_condition = $matches[0][ $i ];

					$condition = $this->extract_fields_in_condition( $condition, $temp_condition, $post_id, $field_name );
				}
			}

			preg_match_all( '/[#]\(([^\)]+)\)\.item\(([^\)]+)\)/Uim', $condition, $matches );
			$matches_count = count( $matches[0] );
			if ( $matches_count > 0 ) {
				for ( $i = 0; $i < $matches_count; $i++ ) {
					$related_reference = str_replace( array( '"', "'" ), '', $matches[2][ $i ] );
					if ( ! $post_id = $this->attr_item_chain->get( array( 'item' => $related_reference ) ) ) {
						// no valid item
						continue;
					}

					$temp_condition = $matches[0][ $i ];
					$taxonomy_slug = $matches[1][ $i ];

					$condition = $this->extract_taxonomies_in_condition( $condition, $temp_condition, $post_id, $taxonomy_slug );
				}
			}
		}

		// Resolve getting term values,
		// using the following syntax (double quotes in place of single also apply):
		//
		// #(taxonomy-slug)
		preg_match_all( '/\#\(([^()]+)\)/', $condition, $matches );
		$matches_count = count( $matches[0] );
		if ( $matches_count > 0 ) {
			for ( $i = 0; $i < $matches_count; $i++ ) {
				$temp_condition = $matches[0][ $i ];
				$taxonomy_slug = $matches[1][ $i ];

				$condition = $this->extract_taxonomies_in_condition( $condition, $temp_condition, $id, $taxonomy_slug );
			}
		}

		/* Resolve parent*/

		$data = WPToolset_Types::getCustomConditional( $condition, '', WPToolset_Types::getConditionalValues( $id ) );

		$evaluate = $data['custom'];
		$values = $data['values'];

		if ( strpos( $evaluate, "REGEX" ) === false ) {
			$evaluate = trim( stripslashes( $evaluate ) );
			// Check dates
			$evaluate = wpv_filter_parse_date( $evaluate );
			$evaluate = $this->handle_user_function( $evaluate );
		}

		$fields = $this->extract_fields( $evaluate );

		$evaluate = apply_filters( 'wpv-extra-condition-filters', $evaluate );
		$temp = $this->extract_variables( $evaluate, $attr, $has_post, $id );
		$evaluate = $temp['evaluate'];
		$logging_string .= $temp['log'];
		if (
			empty( $fields )
			&& empty( $values )
		) {
			$passed = $this->evaluate_custom( $evaluate );
		} else {
			$evaluate = $this->update_values_in_expression( $evaluate, $fields, $values, $id );
			$logging_string .= "\n--------------------\nConverted expression: "
				. htmlentities( $evaluate )
				. "\n--------------------";
			$passed = $this->evaluate_custom( $evaluate );
		}

		if ( defined( 'REST_REQUEST' ) ) {
			$logging_string = htmlentities( $logging_string );
		}
		return array( 'debug' => $logging_string, 'passed' => $passed );

	}

	/**
	 * Adjust the syntax of a condition based on a field of a given post ID,
	 *
	 * @param string $condition_string
	 * @param string $condition_match
	 * @param int $post_id
	 * @param string $field_slug
	 * @return string
	 */
	private function extract_fields_in_condition( $condition_string, $condition_match, $post_id, $field_slug ) {
		$data = WPToolset_Types::getCustomConditional( $condition_match, '', WPToolset_Types::getConditionalValues( $post_id ) );
		if ( isset( $data['values'][ $field_slug ] ) ) {
			if ( is_string( $data['values'][ $field_slug ] ) ) {
				$value = "'" . str_replace( "'", "", $data['values'][ $field_slug ] ) . "'";
				$condition_string = str_replace( $condition_match, $value, $condition_string );
			}
		} else {
			$condition_string = str_replace( $condition_match, "''", $condition_string );
		}

		return $condition_string;
	}

	/**
	 * Adjust the syntax of a condition based on a taxonomy of a given post ID,
	 *
	 * @param string $condition_string
	 * @param string $condition_match
	 * @param int $post_id
	 * @param string $taxonomy_slug
	 * @return string
	 */
	private function extract_taxonomies_in_condition( $condition_string, $condition_match, $post_id, $taxonomy_slug ) {
		$post_terms = wp_get_post_terms( $post_id, $taxonomy_slug );

		if (
			is_wp_error( $post_terms )
			|| empty( $post_terms )
		) {
			$condition_string = str_replace( $condition_match, 'ARRAY()', $condition_string );
		} else {
			$replace_condition = 'ARRAY(';
			$replace_condition_list = array();
			foreach ( $post_terms as $term ) {
				$replace_condition_list[] = '\'' . $term->slug . '\'';
				$replace_condition_list[] = '\'' . $term->term_id . '\'';
				$replace_condition_list[] = '\'' . str_replace( "'", "", $term->name ) . '\'';
			}
			$replace_condition .= implode( ',', $replace_condition_list ) . ')';
			$condition_string = str_replace( $condition_match, $replace_condition, $condition_string );
		}

		return $condition_string;
	}

	/**
	 * Extract the named variables from a condition if they match any given attribute.
	 *
	 * @param string $evaluate
	 * @param array $atts
	 * @param bool $has_post
	 * @param int $id
	 * @return array (
	 *     @type string evaluate String to evaluate
	 *     @type string log Logging string, if generated
	 * )
	 */
	private function extract_variables( $evaluate, $atts, $has_post, $id ) {
		$logging_string = '';
		// Evaluate quoted variables that are to be used as strings
		// '$f1' will replace $f1 with the custom field value

		$strings_count = preg_match_all( '/(\'[\$\w^\']*\')/', $evaluate, $matches );
		if (
			$strings_count
			&& $strings_count > 0
		) {
			for( $i = 0; $i < $strings_count; $i++ ) {
				$string = $matches[1][ $i ];
				// remove single quotes from string literals to get value only
				$string = ( strpos( $string, '\'' ) === 0 ) ? substr( $string, 1, strlen( $string ) - 2 ) : $string;
				if ( strpos( $string, '$' ) === 0 ) {
					$quoted_variables_logging_extra = '';
					$variable_name = substr( $string, 1 ); // omit dollar sign
					if ( isset( $atts[ $variable_name ] ) ) {
						$string = get_post_meta( $id, $atts[ $variable_name ], true );
						$string = str_replace( "'", "", $string );
						$evaluate = str_replace( $matches[1][ $i ], "'" . $string . "'", $evaluate );
					} else {
						$evaluate = str_replace( $matches[1][ $i ], "", $evaluate );
						$quoted_variables_logging_extra = "\n\tERROR: Key " . $matches[1][ $i ] . " does not point to a valid attribute in the wpv-if shortcode: expect parsing errors";
					}
					$logging_string .= "\nAfter replacing " . ( $i + 1 ) . " quoted variables: " . $evaluate . $quoted_variables_logging_extra;
				}
			}
		}

		// Evaluate non-quoted variables, by de-quoting the quoted ones if needed


		$strings_count = preg_match_all(
			'/((\$\w+)|(\'[^\']*\'))\s*([\!<>\=|lt|lte|eq|ne|gt|gte]+)\s*((\$\w+)|(\'[^\']*\'))/',
			$evaluate, $matches
		);

		// get all string comparisons - with variables and/or literals
		if (
			$strings_count
			&& $strings_count > 0
		) {
			for( $i = 0; $i < $strings_count; $i++ ) {

				// get both sides and sign
				$first_string = $matches[1][ $i ];
				$second_string = $matches[5][ $i ];
				$math_sign = $matches[4][ $i ];

				$general_variables_logging_extra = '';

				// remove single quotes from string literals to get value only
				$first_string = ( strpos( $first_string, '\'' ) === 0 ) ? substr( $first_string, 1, strlen( $first_string ) - 2 ) : $first_string;
				$second_string = ( strpos( $second_string, '\'' ) === 0 ) ? substr( $second_string, 1, strlen( $second_string ) - 2 ) : $second_string;
				$general_variables_logging_extra .= "\n\tComparing " . $first_string . " to " . $second_string;

				// replace variables with text representation
				if (
					strpos( $first_string, '$' ) === 0
					&& $has_post
				) {
					$variable_name = substr( $first_string, 1 ); // omit dollar sign
					if ( isset( $atts[ $variable_name ] ) ) {
						$first_string = get_post_meta( $id, $atts[ $variable_name ], true );
						$first_string = str_replace( "'", "", $first_string );
					} else {
						$first_string = '';
						$general_variables_logging_extra .= "\n\tERROR: Key " . $variable_name . " does not point to a valid attribute in the wpv-if shortcode";
					}
				}
				if ( strpos( $second_string, '$' ) === 0 && $has_post ) {
					$variable_name = substr( $second_string, 1 );
					if ( isset( $atts[ $variable_name ] ) ) {
						$second_string = get_post_meta( $id, $atts[ $variable_name ], true );
						$second_string = str_replace( "'", "", $second_string );
					} else {
						$second_string = '';
						$general_variables_logging_extra .= "\n\tERROR: Key " . $variable_name . " does not point to a valid attribute in the wpv-if shortcode";
					}
				}


				$evaluate = ( is_numeric( $first_string ) ? str_replace( $matches[1][ $i ], $first_string, $evaluate ) : str_replace( $matches[1][ $i ], "'$first_string'", $evaluate ) );
				$evaluate = ( is_numeric( $second_string ) ? str_replace( $matches[5][ $i ], $second_string, $evaluate ) : str_replace( $matches[5][ $i ], "'$second_string'", $evaluate ) );
				$logging_string .= "\nAfter replacing " . ( $i + 1 ) . " general variables and comparing strings: " . $evaluate . $general_variables_logging_extra;
			}
		}
		// Evaluate comparisons when at least one of them is numeric
		$strings_count = preg_match_all( '/(\'[^\']*\')/', $evaluate, $matches );
		if (
			$strings_count
			&& $strings_count > 0
		) {
			for( $i = 0; $i < $strings_count; $i++ ) {
				$string = $matches[1][ $i ];
				// remove single quotes from string literals to get value only
				$string = ( strpos( $string, '\'' ) === 0 ) ? substr( $string, 1, strlen( $string ) - 2 ) : $string;
				if ( is_numeric( $string ) ) {
					$evaluate = str_replace( $matches[1][ $i ], $string, $evaluate );
					$logging_string .= "\nAfter matching " . ( $i + 1 ) . " numeric strings into real numbers: " . $evaluate;
					$logging_string .= "\n\tMatched " . $matches[1][ $i ] . " to " . $string;
				}
			}
		}

		// Evaluate all remaining variables
		if ( $has_post ) {
			$count = preg_match_all( '/\$(\w+)/', $evaluate, $matches );

			// replace all variables with their values listed as shortcode parameters
			if (
				$count
				&& $count > 0
			) {
				$logging_string .= "\nRemaining variables: " . var_export( $matches[1], true );
				// sort array by length desc, fix str_replace incorrect replacement
				// wpv_sort_matches_by_length belongs to common/functions.php
				$matches[1] = wpv_sort_matches_by_length( $matches[1] );

				foreach( $matches[1] as $match ) {
					if ( isset( $atts[ $match ] ) ) {
						$meta = get_post_meta( $id, $atts[ $match ], true );
						$meta = str_replace( "'", "", $meta );
						if (
							empty( $meta )
							&& ! is_numeric( $meta )
						) {
							$meta = "''";
						}
					} else {
						$meta = "0";
					}
					$evaluate = str_replace( '$' . $match, $meta, $evaluate );
					$logging_string .= "\nAfter replacing remaining variables: " . $evaluate;
				}
			}
		}

		return array(
			'evaluate' => $evaluate,
			'log' => $logging_string,
		);
	}

	/**
	 * Evaluates conditions using custom conditional statement.
	 *
	 * @uses wpv_condition()
	 *
	 * @param type $post
	 * @param type $evaluate
	 * @return boolean
	 */
	private function evaluate_custom( $evaluate ) {
		$check = false;
		try {
			$parser = new Toolset_Parser( $evaluate );
			$parser->parse();
			$check = $parser->evaluate();
		} catch( Exception $e ) {
			$check = false;
		}

		return $check;
	}

	/**
	 * Get the remaining custom fields used in the condition.
	 *
	 * @param string $evaluate
	 * @return array
	 */
	private function extract_fields( $evaluate ) {
		//###############################################################################################
		//https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193583580/comments
		//Fix REGEX conditions that contains \ that is stripped out
		if ( strpos( $evaluate, 'REGEX' ) === false ) {
			$evaluate = trim( stripslashes( $evaluate ) );
			// Check dates
			$evaluate = wpv_filter_parse_date( $evaluate );
			$evaluate = $this->handle_user_function( $evaluate );
		}

		// Add quotes = > < >= <= === <> !==
		$strings_count = preg_match_all( '/[=|==|===|<=|<==|<===|>=|>==|>===|\!===|\!==|\!=|<>]\s(?!\$)(\w*)[\)|\$|\W]/', $evaluate, $matches );

		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $temp_match ) {
				$temp_replace = is_numeric( $temp_match ) ? $temp_match : '\'' . $temp_match . '\'';
				$evaluate = str_replace( ' ' . $temp_match . ')', ' ' . $temp_replace . ')', $evaluate );
			}
		}
		// if new version $(field-value) use this regex
		if ( preg_match( '/\$\(([^()]+)\)/', $evaluate ) ) {
			preg_match_all( '/\$\(([^()]+)\)/', $evaluate, $matches );
		} // if old version $field-value use this other
		else {
			preg_match_all( '/\$([^\s]*)/', $evaluate, $matches );
		}


		$fields = array();
		if ( ! empty( $matches ) ) {
			foreach ( $matches[1] as $field_name ) {
				$fields[ trim( $field_name, '()' ) ] = trim( $field_name, '()' );
			}
		}

		return $fields;
	}

	/**
	 * Callback to sort items by length.
	 *
	 * @param string $a
	 * @param string $b
	 * @return int
	 */
	public function sort_by_length( $a, $b ) {
		return strlen( $b ) - strlen( $a );
	}

	/**
	 * Replace field references with their values in the condition.
	 *
	 * @param string $evaluate
	 * @param array $fields
	 * @param array $values
	 * @param int $id
	 * @return string
	 */
	private function update_values_in_expression( $evaluate, $fields, $values, $id ) {

		// use string replace to replace any fields with their values.
		// Sort by length just in case a field name contians a shorter version of another field name.
		// eg.  $my-field and $my-field-2

		$keys = array_keys( $fields );
		usort( $keys, array( $this, 'sort_by_length' ) );

		foreach ( $keys as $key ) {
			$is_numeric = false;
			$is_array = false;
			$value = isset( $values[ $fields[ $key ] ] ) ? $values[ $fields[ $key ] ] : '';

			if ( ! empty( $id ) ) {
				/**
				 * Maybe filter the postmeta value based on its meta key.
				 *
				 * This filter mimics the native get_post_metadata filter documented here:
				 * https://developer.wordpress.org/reference/functions/get_metadata/
				 * If it returns a not null value, then use the returned value instead of the original.
				 * It will hijack legacy '_wpcf_belongs_{slug}_id' meta keys and return the current m2m-compatible
				 * parent value, if any.
				 *
				 * @param null
				 * @param $id int|string The ID of the post that the postmeta belongs to
				 * @patam $fields[ $key ] string The key of the postmeta
				 * @param true Return always a single value, not an array
				 *
				 * @since m2m
				 */
				$postmeta_access_m2m_value = apply_filters( 'toolset_postmeta_access_m2m_get_post_metadata', null, $id, $fields[ $key ], true );
				$value = ( null === $postmeta_access_m2m_value )
					? $value
					: $postmeta_access_m2m_value;
			}

			if ( '' === $value ) {
				$value = "''";
			}

			if ( is_numeric( $value ) ) {
				$is_numeric = true;
			}

			if ( 'array' === gettype( $value ) ) {
				$is_array = true;
				// workaround for datepicker data to cover all cases
				if ( array_key_exists( 'timestamp', $value ) ) {
					if ( is_numeric( $value['timestamp'] ) ) {
						$value = $value['timestamp'];
					} elseif ( is_array( $value['timestamp'] ) ) {
						$value = implode( ',', array_values( $value['timestamp'] ) );
					}
				} elseif ( array_key_exists( 'datepicker', $value ) ) {
					if ( is_numeric( $value['datepicker'] ) ) {
						$value = $value['datepicker'];
					} elseif ( is_array( $value['datepicker'] ) ) {
						$value = implode( ',', array_values( $value['datepicker'] ) );
					}
				} else {
					$value = implode( ',', array_values( $value ) );
				}
			}

			if (
				! empty( $value )
				&& "''" !== $value
				&& ! $is_numeric
				&& ! $is_array
			) {
				$value = str_replace( "'", "", $value );
				$value = '\'' . $value . '\'';
			}

			// First replace the $(field_name) format
			$evaluate = str_replace( '$(' . $fields[ $key ] . ')', $value, $evaluate );
			// next replace the $field_name format
			$evaluate = str_replace( '$' . $fields[ $key ], $value, $evaluate );
		}

		return $evaluate;
	}

	/**
	 * Undocumented function. This shoudl be reviewed.
	 *
	 * @param string $evaluate
	 * @return string
	 */
	private function handle_user_function( $evaluate ) {
		$evaluate = stripcslashes( $evaluate );
		$occurrences = preg_match_all( '/(\\w+)\(([^\)]*)\)/', $evaluate, $matches );

		if ( $occurrences > 0 ) {
			for( $i = 0; $i < $occurrences; $i++ ) {
				$result = false;
				$function = $matches[1][ $i ];
				$field = isset( $matches[2] ) ? rtrim( $matches[2][ $i ], ',' ) : '';

				if ( 'USER' === $function ) {
					$result = WPV_Handle_Users_Functions::get_user_field( $field );
				}

				if ( $result ) {
					$evaluate = str_replace( $matches[0][ $i ], $result, $evaluate );
				}
			}
		}

		return $evaluate;
	}

	/**
	 * Process the conditional shortcode on its own, if included on a given content.
	 *
	 * @param string $content
	 * @return string
	 * @since 2.7.3
	 */
	public function process_conditional_shortcodes( $content ) {
		if ( false === strpos( $content, '[' . self::SHORTCODE_NAME ) ) {
			return $content;
		}

		global $shortcode_tags;

		// Back up current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		add_shortcode( self::SHORTCODE_NAME, array( $this, 'resolve_shortcode' ) );

		while (
			false !== strpos( $content, self::SHORTCODE_START_TAG )
			&& false !== strrpos( $content, self::SHORTCODE_END_TAG )
		) {
			$match = $this->match_innermost_conditional_shortcode( $content );

			// This will only processes the [wpv-conditional] shortcode
			$pattern = get_shortcode_regex( array( self::SHORTCODE_NAME ) );
			$match_corrected = $match;
			if ( 0 !== preg_match( "/$pattern/s", $match, $match_data ) ) {
				// Base64 Encode the shortcode content so the WP can't strip out any data it doesn't like.
				// Be sure to prevent base64_encoding more than just the needed: only do it if there are inner shortcodes
				if ( strpos( $match_data[5], '[' ) !== false ) {
					// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					$match_corrected = str_replace( $match_data[5], 'wpv-b64-' . base64_encode( $match_data[5] ), $match_corrected );
				}

				$match_attributes = wpv_shortcode_parse_condition_atts( $match_data[3] );
				if ( isset( $match_attributes['if'] ) ) {
					// Make sure to correct conditionals comparisons to remove unwanted characters.
					$match_evaluate_corrected = str_replace( '<=', 'lte', $match_attributes['if'] );
					$match_evaluate_corrected = str_replace( '<>', 'ne', $match_evaluate_corrected );
					$match_evaluate_corrected = str_replace( '<', 'lt', $match_evaluate_corrected );
					$match_corrected = str_replace( $match_attributes['if'], $match_evaluate_corrected, $match_corrected );
				}
			} else {
				// We only want to process shortcodes when there are actually shortcodes to process!
				// Otherwise, we are just burning resources, because in this case...
				// $match === $content, which means processing what we wanted to avoid processing.
				// How cna this happen? Well, there are ghosts! For example, excertp shortcodes can print half a conditional shortcode.
				break;
			}

			$shortcode = do_shortcode( $match_corrected );
			$content = str_replace( $match, $shortcode, $content );
		}

		// Put the original shortcodes back
		$shortcode_tags = $orig_shortcode_tags;

		return $content;
	}

	/**
	 * Matches the innermost conditional shortcode and returns it along with its content.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function match_innermost_conditional_shortcode( $content ) {
		$start_tag_position = strpos( $content, self::SHORTCODE_START_TAG );
		$end_tag_position = strrpos( $content, self::SHORTCODE_END_TAG );

		if (
			false === $start_tag_position ||
			false === $end_tag_position
		) {
			return $content;
		}

		// Extract the content between the first conditional shortcode start tag and the last shortcode end tag.
		$conditional = substr( $content, $start_tag_position, $end_tag_position - $start_tag_position + strlen( self::SHORTCODE_END_TAG ) );

		// Removes the opening tag of the found conditional shortcode in order to search for nested ones.
		$maybe_nested_conditional_content = substr( $conditional, strlen( self::SHORTCODE_START_TAG ) );

		// Repeat while there are nested shortcodes.
		while ( (int) strpos( $maybe_nested_conditional_content, self::SHORTCODE_START_TAG ) > 0 ) {
			// Find the position of a nested conditional shortcode...
			$start_tag_position = strpos( $maybe_nested_conditional_content, self::SHORTCODE_START_TAG );

			// and the last instance of a conditional shortcode end tag. We are going to search for more nested conditional shortcodes there...
			$end_tag_position = strrpos( $maybe_nested_conditional_content, self::SHORTCODE_END_TAG );

			// extract the content found...
			$conditional = substr( $maybe_nested_conditional_content, $start_tag_position, $end_tag_position - $start_tag_position + strlen( self::SHORTCODE_END_TAG ) );

			// and removes the opening tag of the found conditional shortcode in order to search for nested ones all over again.
			$maybe_nested_conditional_content = substr( $conditional, strlen( self::SHORTCODE_START_TAG ) );
		}

		// ...return the innermost nested conditional shortcode.
		return substr( self::SHORTCODE_START_TAG . $maybe_nested_conditional_content, 0, strpos( $conditional, self::SHORTCODE_END_TAG ) + strlen( self::SHORTCODE_END_TAG ) );
	}
}
