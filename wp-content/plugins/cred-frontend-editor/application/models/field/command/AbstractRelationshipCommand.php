<?php

namespace OTGS\Toolset\CRED\Model\Field\Command;

abstract class AbstractRelationshipCommand extends \CRED_Field_Command_Base {

	const PARENT_URL_FORMAT = 'parent_%s_id';

	const STATIC_FIELDS_INDEX = '';

	/** @var string|null */
	protected $expected_parent_post_type = null;

	/**
	 * Get the field to operate with, from the \CRED_StaticClass::$out schema.
	 *
	 * @return mixed[]
	 */
	protected function get_field() {
		return \CRED_StaticClass::$out[ 'fields' ][ static::STATIC_FIELDS_INDEX ][ $this->field_name ];
	}

	/**
	 * Setup field data from its own attributes plus some shortcode attribute values.
	 *
	 * @param mixed[] $field
	 * @return mixed[]
	 */
	protected function setup_field() {
		$field = $this->get_field();

		$field[ 'form_html_id' ] = $this->translate_field_factory->get_html_form_field_id( $field );

		$field[ 'order_by' ] = toolset_getarr( $this->filtered_attributes, 'order', 'title' );
		$field[ 'order' ] = toolset_getarr( $this->filtered_attributes, 'ordering', 'ASC' );

		$field[ 'placeholder' ] = toolset_getarr( $this->filtered_attributes, 'select_text', toolset_getarr( $field, 'description', '' ) );

		$form_post_object = $this->form->getForm();
		$field[ 'wpml_context' ] = $form_post_object->post_type
			. '-' . $form_post_object->post_title
			. '-' . $form_post_object->ID;

		$field[ 'data' ][ 'validate' ] = array();
		if ( $this->required ) {
			$field[ 'data' ][ 'validate' ] = array(
				'required' => array(
					'message' => toolset_getarr( $this->filtered_attributes, 'validate_text' ),
					'active' => 1,
				),
			);
		}

		return $field;
	}

	/**
	 * Print a specific alert message when parent post_id has wrong post_type
	 *
	 * @since 1.9.4
	 * @deprecated To be removed: when trying to force a default which post type does not match the expected one,
	 *     we should just ignore it, but not provide any unspecific, erratic and isolated error message.
	 */
	public function add_default_parent_post_type_top_error_message() {
		echo '<div class="alert alert-danger">'
			. (
				isset( $this->expected_parent_post_type ) && ! empty( $this->expected_parent_post_type )
				? sprintf(
					__( 'Could not set the parent post because it has the wrong type. The parent for this post should be of type %s.', 'wp-cred' ),
					esc_html( $this->expected_parent_post_type )
				)
				: __( 'Could not set the parent post because it has the wrong type.', 'wp-cred' )
			)
		. '</div>';
	}

	/**
	 * Decide whether the parent selector will be limited to items by a given author.
	 *
	 * @param string|int $force_author
	 * @param string $field_name
	 * @return string|int
	 */
	protected function maybe_set_ancestor_filter_by_author( $force_author, $field_name ) {
		if ( '$current' === $force_author ) {
			$force_author = (int) get_current_user_id();

			return $force_author;
		}

		if ( ! empty( $force_author ) ) {
			return $force_author;
		}

		$form_id = $this->form_rendering->form_id;

		/**
		 * Force a post author on a specific post form parent selectors.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_parent_in_post_form_' . $form_id,
			$force_author,
			$field_name
		);
		/**
		 * Force a post author on a specific post form and a specific parent selector.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_' . $field_name . '_parent_in_post_form_' . $form_id,
			$force_author
		);
		/**
		 * Force a post author on all post forms parent selectors.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_parent_in_post_form',
			$force_author,
			$form_id,
			$field_name
		);
		/**
		 * Force a post author on all CRED interfaces to set a related post.
		 *
		 * This is also used in the frontend post forms when setting a related post.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_related_post',
			$force_author
		);

		/**
		 * Force a post author on all Toolset interfaces to set a related post.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'toolset_force_author_in_related_post',
			$force_author
		);

		if ( '$current' === $force_author ) {
			$force_author = get_current_user_id();
			$force_author = (int) $force_author;
		}

		return $force_author;
	}

	/**
	 * Craft the URL parameter to set the parent.
	 *
	 * @param mixed[] $placeholders
	 * @return string
	 */
	protected function get_parent_url_parameter( $placeholders ) {
		return vsprintf( static::PARENT_URL_FORMAT, $placeholders );
	}

	/**
	 * Maybe fill the default value from an URL parameter, counting on author restrictions by attribute.
	 *
	 * Note that when set to use a given post, we make the field readonly,
	 * even if the type and author restrictions apply and turn it empty.
	 *
	 * @param string|int $force_author
	 * @param string $post_type Post type of the expected ancestor in this field.
	 */
	protected function maybe_set_default_from_url( $force_author, $post_type ) {
		if ( null === $post_type ) {
			return null;
		}

		$parent_url_parameter = $this->get_parent_url_parameter( [ $post_type ] );
		$default_candidate = toolset_getget( $parent_url_parameter, false );

		if ( false === $default_candidate ) {
			return null;
		}

		$this->readonly = true;

		$default_candidate = (int) $default_candidate;

		$default_candidate_type = get_post_field( 'post_type', $default_candidate );

		if ( $default_candidate_type !== $post_type ) {
			return null;
		}

		if ( empty( $force_author ) ) {
			return $default_candidate;
		}

		$default_candidate_author = get_post_field( 'post_author', $default_candidate );

		return ( (int) $default_candidate_author === (int) $force_author )
			? $default_candidate
			: null;
	}

	/**
	 * Maybe fill the default value from the current post, counting on type author restrictions by attribute.
	 *
	 * Note that when set to force the current post, we make the field readonly,
	 * even if the type and author restrictions apply and turn it empty.
	 *
	 * @param string|int $force_author
	 * @param string $post_type Post type of the expected ancestor in this field.
	 */
	protected function maybe_set_default_from_current( $force_author, $post_type ) {
		if ( '$current' !== $this->filtered_attributes['value'] ) {
			return null;
		}

		$this->readonly = true;

		global $post;

		if ( $post->post_type !== $post_type ) {
			return null;
		}

		if ( empty( $force_author ) ) {
			return $post->ID;
		}

		return ( (int) $post->post_author === (int) $force_author )
			? $post->ID
			: null;
	}

	/**
	 * Calculate the forced arguments to pass to classic potential parent queries.
	 *
	 * @param mixed[] $field
	 * @param string|int $force_author
	 * @return mixed[]
	 */
	protected function calculate_forced_args( $field, $force_author ) {
		$forced_args = array(
			'orderby' => toolset_getarr( $field, 'order_by' ),
			'order' => toolset_getarr( $field, 'order' ),
		);

		if ( '' !== $force_author ) {
			$force_author = (int) $force_author;
			if ( $force_author > 0 ) {
				$forced_args['author'] = $force_author;
			} else {
				$forced_args['post__in'] = array( '0' );
			}
		}

		return $forced_args;
	}

	/**
	 * Populate the selector with the potential parents, if any.
	 *
	 * Note that when select2 is to be used, there are no potential parents to loop over.
	 *
	 * @param mixed[] $field
	 * @param \WP_Post[] $potential_parents
	 * @param int|null $default_option
	 * @return mixed[]
	 */
	protected function populate_options( $field, $potential_parents, $default_option ) {
		$field[ 'data' ][ 'options' ] = array();

		foreach ( $potential_parents as $option ) {
			$option_id = (string) ( $option->ID );
			$field[ 'data' ][ 'options' ][ $option_id ] = array(
				'title' => $option->post_title,
				'value' => $option_id,
				'display_value' => $option_id,
			);
		}

		$field[ 'data' ][ 'options' ][ 'default' ] = $default_option;

		return $field;
	}

	/**
	 * Make sure we cover all normalized attributes.
	 *
	 * @param mixed[] $field
	 * @param mixed[] $force_author
	 * @return mixed[]
	 */
	protected function populate_additional_attributes( $field, $force_author ) {
		return array(
			'preset_value' => $this->value,
			'urlparam' => toolset_getarr( $this->filtered_attributes, 'urlparam' ),
			'make_readonly' => $this->readonly,
			'max_width' => toolset_getarr( $this->filtered_attributes, 'max_width' ),
			'max_height' => toolset_getarr( $this->filtered_attributes, 'max_height' ),
			'class' => toolset_getarr( $this->filtered_attributes, 'class' ),
			'output' => toolset_getarr( $this->filtered_attributes, 'output' ),
			'select_text' => toolset_getarr( $this->filtered_attributes, 'select_text' ),
			'data-orderby' => toolset_getarr( $field, 'order_by' ),
			'data-order' => toolset_getarr( $field, 'order' ),
			'data-author' => $force_author,
		);
	}

	/**
	 * Normalize stored field data for future usage.
	 *
	 * @todo Evaluate whether the key form_fields is actually right for all usages!?
	 *
	 * @param mixed[] $field
	 * @param mixed[] $field_object
	 */
	protected function normalize_stored_field_data( $field, $field_object ) {
		\CRED_StaticClass::$out['form_fields'][ $this->field_name ] = $this->get_uniformed_field( $field, $field_object );
		\CRED_StaticClass::$out['current_form_fields'][ $this->field_name ] = $this->get_uniformed_field( $field, $field_object );
		\CRED_StaticClass::$out['form_fields_info'][ $this->field_name ] = array(
			'type' => toolset_getarr( $field, 'type' ),
			'repetitive' => ( isset( $field['data']['repetitive'] ) && $field['data']['repetitive'] ),
			'plugin_type' => toolset_getarr( $field, 'plugin_type', '' ),
			'name' => $this->field_name,
		);
	}

}
