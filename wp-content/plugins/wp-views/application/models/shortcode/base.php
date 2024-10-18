<?php
/**
 * Class WPV_Shortcode_Base
 *
 * @since 2.6.4
 */
abstract class WPV_Shortcode_Base implements WPV_Shortcode_Interface {
	abstract function get_value( $atts, $content );

	/**
	 * Gets the post, either the one with ID equal to the ID given as a parameter or its translation.
	 *
	 * @param $item_id
	 *
	 * @return null|WP_Post
	 */
	protected function get_post( $item_id ) {
		$item = get_post( $item_id );

		if ( null === $item ) {
			return null;
		}

		// Adjust for WPML support
		// If WPML is enabled, $item_id should contain the right ID for the current post in the current language
		// However, if using the id attribute, we might need to adjust it to the translated post for the given ID
		$item_id = apply_filters( 'translate_object_id', $item_id, $item->post_type, true, null );

		if ( $item_id !== $item->ID ) {
			$item = get_post( $item_id );
		}

		return $item;
	}

	/**
	 * Gets the term, either the one with term_id equal to the ID given as a parameter or its translation.
	 *
	 * @param $term_id
	 * @return null|WP_Term
	 * @since 3.0.1
	 */
	protected function get_term( $term_id ) {
		if ( null === $term_id ) {
			global $WP_Views;
			$term = $WP_Views->get_current_taxonomy_term();

			if (
				null === $term
				&& (
					is_tax()
					|| is_category()
					|| is_tag()
				)
			) {
				$term = get_queried_object();
				if ( ! $term instanceof WP_Term ) {
					return null;
				}
			}

			return $term;
		}

		$term = get_term( $term_id );

		if ( is_wp_error( $term ) ) {
			return null;
		}

		$term_id = apply_filters( 'translate_object_id', $term_id, $term->taxonomy, true, null );

		if ( $term_id !== $term->term_id ) {
			$term = get_term( $term_id );
		}

		return $term;
	}

	/**
	 * Get the translation, either if it follows the new or the legacy context.
	 *
	 * @param string      $name
	 * @param string      $value
	 * @param null|string $context
	 * @param bool        $is_legacy
	 *
	 * @return string
	 */
	protected function get_translation( $name, $value, $context = null, $is_legacy = false ) {
		if ( $is_legacy ) {
			$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array() );
			$context = 'View ' . $view_settings['view_slug'];
		}

		return wpv_translate(
			$name,
			$value,
			false,
			$context
		);
	}
}
