<?php

namespace OTGS\Toolset\Views\Models\ContentTemplate;

/**
 * A class represeting the usage setings for a given content template and a given post type.
 */
class UsageSettings {

	const KEY_POST_TYPE = 'post_type';
	const KEY_ENABLED = 'enabled';
	const KEY_CONTENT_TEMPLATE_ID = 'content_template_id';
	const KEY_CONDITIONS_GROUP = 'conditions';
	const KEY_PARSED_CONDITIONS_GROUP = 'parsed_conditions';
	const KEY_PRIORITY = 'priority';
	const KEY_UPDATED_AT = 'updated_at';

	/**
	 * @var int
	 */
	private $content_template_id;

	/**
	 * @var UsageConditionGroup|null
	 */
	private $usage_condition_group;

	/**
	 * @var int
	 */
	private $updated_at;

	/**
	 * @var int
	 */
	private $priority;

	/**
	 * UsageSettings constructor.
	 *
	 * @param int $content_template_id
	 * @param UsageConditionGroup|null $usage_condition_group
	 * @param int $priority
	 * @param int $updated_at
	 */
	public function __construct( $content_template_id, $usage_condition_group, $priority = 0, $updated_at = 0 ) {
		$this->content_template_id = $content_template_id;
		$this->usage_condition_group = $usage_condition_group;
		$this->priority = $priority;
		$this->updated_at = $updated_at;
	}

	/**
	 * Count the amount of conditions matching for a post.
	 *
	 * @param \WP_Post $post Post to check it's CT
	 *
	 * @return int
	 */
	public function countConditionMatches( \WP_Post $post ) {
		return $this->usage_condition_group->countConditionMatches( $post );
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * @return int
	 */
	public function getUpdatedAt() {
		return $this->updated_at;
	}

	/**
	 * @return int
	 */
	public function getContentTemplateId() {
		return $this->content_template_id;
	}

	/**
	 * @return string
	 */
	public function toString() {
		return $this->usage_condition_group->toString();
	}

	/**
	 * @param array|null $usage_settings_array
	 *
	 * @return UsageSettings|null
	 */
	public static function createFromDatabaseArray( $usage_settings_array ) {

		$condition_group = $usage_settings_array[ self::KEY_CONDITIONS_GROUP ];
		$usage_group = UsageConditionGroup::createFromDatabaseArray( $condition_group );
		if ( null === $usage_group ) {
			return null;
		}

		return new self(
			toolset_getarr( $usage_settings_array, self::KEY_CONTENT_TEMPLATE_ID, '' ),
			$usage_group,
			toolset_getarr( $usage_settings_array, self::KEY_PRIORITY, '' ),
			toolset_getarr( $usage_settings_array, self::KEY_UPDATED_AT, '' )
		);
	}

}
