<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * @since m2m
 */
class WPV_Ajax_Handler_Get_Relationships_Data extends Toolset_Ajax_Handler_Abstract {

	/**
	 * WP Nonce.
	 *
	 * @var string
	 * @since m2m
	 */
	const NONCE = 'wpv_get_relationships_data';

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {

		$this->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_GET_RELATIONSHIPS_DATA,
			'parameter_source' => 'get',
			'capability_needed' => EDIT_VIEWS,
		) );

		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			$this->process_call_m2m();
		} else {
			$this->process_call_legacy();
		}
	}

	/**
	 * Process the AJAX call when m2m has not been activated.
	 *
	 * @since m2m
	 */
	private function process_call_legacy() {
		$data = array(
			'relationships' => array()
		);

		$legacy_relationships = get_option( 'wpcf_post_relationship', array() );

		if ( is_array( $legacy_relationships ) ) {
			foreach ( $legacy_relationships as $has => $belongs ) {
				$belongs_keys = array_keys( $belongs );
				if ( count( $belongs_keys ) < 1 ) {
					continue;
				}
				$data['relationships'][] = array(
					'label' => '',
					'labelSingular' => '',
					'type' => '',
					'origin' => 'wizard',
					'isLegacy' => true,
					'roles' => array(
						'parent' => array( $has ),
						'child' => array( $belongs_keys[0] ),
						'intermediary' => array()
					)
				);
			}
		}

		$this->ajax_finish(
			$data,
			true
		);
	}

	/**
	 * Process the AJAX call when m2m has been activated.
	 *
	 * @since m2m
	 */
	private function process_call_m2m() {
		$data = array(
			'relationships' => array()
		);

		$definitions_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$definitions = $definitions_repository->get_definitions();
		foreach ( $definitions as $definition_slug => $definition_data ) {
			$def_intermediary_post_type = $definition_data->get_intermediary_post_type();
			if ( null === $def_intermediary_post_type ) {
				$def_intermediary = array();
			} else {
				$def_intermediary = array( $def_intermediary_post_type );
			}

			$def_origin = $definition_data->get_origin()->get_origin_keyword();

			$data['relationships'][ $definition_slug ] = array(
				'label' => $definition_data->get_display_name_plural(),
				'labelSingular' => $definition_data->get_display_name_singular(),
				'type' => $definition_slug,
				'origin' => $def_origin,
				'isLegacy' => $definition_data->needs_legacy_support(),
				'roles' => array(
					'parent' => $definition_data->get_parent_type()->get_types(),
					'child' => $definition_data->get_child_type()->get_types(),
					'intermediary' => $def_intermediary
				)
			);

			if ( Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD === $def_origin ) {
				$child_post_types = $definition_data->get_child_type()->get_types();
				$child_post_type_object = get_post_type_object( $child_post_types[0] );
				if ( null != $child_post_type_object ) {
					$data['relationships'][ $definition_slug ]['label'] = $child_post_type_object->labels->name;
					$data['relationships'][ $definition_slug ]['labelSingular'] = $child_post_type_object->labels->singular_name;
				}
			}
		}

		$this->ajax_finish(
			$data,
			true
		);
	}

}
