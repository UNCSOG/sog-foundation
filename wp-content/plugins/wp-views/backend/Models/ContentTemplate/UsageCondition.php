<?php

namespace OTGS\Toolset\Views\Models\ContentTemplate;

/**
 * Representation of a usage condition belonging to a specific group.
 */
class UsageCondition {

	const KEY_FIRST_ARGUMENT = 'firstArgument';
	const KEY_SECOND_ARGUMENT = 'secondArgument';
	const KEY_OPERATOR = 'operator';
	const KEY_VALUE = 'value';
	const KEY_LABEL = 'label';
	const KEY_TYPE = 'type';

	const SOURCE_TAXONOMY = 'taxonomy';
	const SOURCE_CUSTOM_FIELD = 'custom-field';
	const SOURCE_NATIVE_FIELD = 'native-field';

	/** @var array */
	private $source;

	/** @var string */
	private $source_value;

	/** @var string */
	private $source_label;

	/** @var string */
	private $operator;

	/** @var string */
	private $operator_label;

	/** @var string */
	private $value;

	/**
	 * UsageCondition constructor.
	 *
	 * @param array $source
	 * @param string $source_value
	 * @param string $source_label
	 * @param string $operator
	 * @param string $operator_label
	 * @param string $value
	 */
	public function __construct( $source, $source_value, $source_label, $operator, $operator_label, $value ) {
		$this->source = $source;
		$this->source_value = $source_value;
		$this->source_label = $source_label;
		$this->operator = $operator;
		$this->operator_label = $operator_label;
		$this->value = $value;
	}

	/**
	 * Whether if this condition applies to a post.
	 *
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function appliesTo( \WP_Post $post ) {
		if ( self::SOURCE_TAXONOMY === $this->source ) {
			return $this->has_term( $this->value, $this->source_value, $post );
		}
		if ( self::SOURCE_NATIVE_FIELD === $this->source  ) {
			return $post->{$this->source_value} === $this->value;
		}
		if ( self::SOURCE_CUSTOM_FIELD === $this->source ) {
			$field_value = get_post_meta( $post->ID, $this->source_value, true );
			if (
				'0' !== $this->value
				&& '0' !== $field_value
				&& empty( $this->value )
			) {
				return empty( $field_value );
			}
			return $field_value === $this->value;
		}
		return false;
	}

	/**
	 * Check whether a given post has a given term set on a condition.
	 * Note that on WPML-powered sites term slugs need to be transformed into translated term IDs.
	 *
	 * @param string $term_slug
	 * @param string $taxonomy_name
	 * @param \WP_Post $post
	 * @return bool
	 */
	private function has_term( $term_slug, $taxonomy_name, $post ) {
		$current_language = apply_filters( 'wpml_current_language', '' );
		$default_language = apply_filters( 'wpml_default_language', '' );

		if ( $current_language === $default_language ) {
			return has_term( $term_slug, $taxonomy_name, $post );
		}

		$term = get_term_by( 'slug', $term_slug, $taxonomy_name );
		if ( false === $term ) {
			return false;
		}

		$translated_term_id = apply_filters( 'wpml_object_id', $term->term_id, $taxonomy_name );

		return has_term( $translated_term_id, $taxonomy_name, $post );
	}

	/**
	 * @return string
	 */
	public function toString() {
		return sprintf( '<b>%s</b>: %s', $this->source_label, $this->value );
	}

	/**
	 * @param array $usage_condition_array - An array coming from the database setting storage
	 *
	 * @return UsageCondition
	 */
	public static function createFromDatabaseArray( $usage_condition_array ) {
		$value = isset( $usage_condition_array[ self::KEY_SECOND_ARGUMENT ][ self::KEY_VALUE ][ self::KEY_VALUE ] ) ?
			$usage_condition_array[ self::KEY_SECOND_ARGUMENT ][ self::KEY_VALUE ][ self::KEY_VALUE ] : null;
		return new UsageCondition(
			$usage_condition_array[ self::KEY_FIRST_ARGUMENT ][ self::KEY_VALUE ][ self::KEY_TYPE ],
			$usage_condition_array[ self::KEY_FIRST_ARGUMENT ][ self::KEY_VALUE ][ self::KEY_VALUE ],
			$usage_condition_array[ self::KEY_FIRST_ARGUMENT ][ self::KEY_VALUE ][ self::KEY_LABEL ],
			$usage_condition_array[ self::KEY_OPERATOR ][ self::KEY_VALUE ],
			$usage_condition_array[ self::KEY_OPERATOR ][ self::KEY_LABEL ],
			$value
		);
	}

}
