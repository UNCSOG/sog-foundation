<?php

namespace OTGS\Toolset\Views\Models\ContentTemplate;

/**
 * A class representing the set of usage settings for a given post type.
 */
class UsagePostType {

	/** @var int */
	private $default_content_template_id;

	/** @var UsageSettings[] */
	private $settings;

	/**
	 * @param $default_content_template_id
	 * @param UsageSettings[] $settings
	 */
	public function __construct( $default_content_template_id, $settings = [] ) {
		$this->default_content_template_id = $default_content_template_id;
		$this->settings = $settings;
	}

	/**
	 * Returns the post_type usage settings applied to a specific content template id.
	 *
	 * @param $content_template_id
	 *
	 * @return UsageSettings|null
	 */
	public function getContentTemplateSettings( $content_template_id ) {
		foreach ( $this->settings as $usage_setting ) {
			if ( $content_template_id === $usage_setting->getContentTemplateId() ) {
				return $usage_setting;
			}
		}
		return null;
	}

	/**
	 * Returns the selected content template for the current post.
	 *
	 * @param \WP_Post $post
	 * @return int
	 */
	public function getContentTemplateForPost( \WP_Post $post ) {
		$prior_usage_setting = null;
		$prior_usage_matches_count = 0;
		foreach( $this->settings as $usage_setting ) {
			$usage_matches_count = $usage_setting->countConditionMatches( $post );

			$prior_usage_setting = $this->getPriorUsageSetting(
				$prior_usage_setting,
				$prior_usage_matches_count,
				$usage_setting,
				$usage_matches_count,
				$post
			);

			if ( $prior_usage_setting === $usage_setting ) {
				$prior_usage_matches_count = $usage_matches_count;
			}
		}

		if ( $prior_usage_setting ) {
			return $prior_usage_setting->getContentTemplateId();
		}
		return $this->default_content_template_id;
	}

	/**
	 * @return int default content template ID for this post type
	 */
	public function getDefaultContentTemplateId() {
		return $this->default_content_template_id;
	}

	/**
	 * Returns the available settings for this CT.
	 *
	 * @return array|UsageSettings[]
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * Returns the usage settings which applies with more priority to the post.
	 *
	 * @param UsageSettings|null $current_usage_setting
	 * @param int $current_usage_matches_count
	 * @param UsageSettings $new_usage_setting
	 * @param int $new_usage_matches_count
	 * @param \WP_Post $post
	 */
	private function getPriorUsageSetting(
		$current_usage_setting,
		$current_usage_matches_count,
		$new_usage_setting,
		$new_usage_matches_count,
		$post
	) {
		if ( 0 === $new_usage_matches_count ) {
			return $current_usage_setting;
		} else if ( null === $current_usage_setting ) {
			return $new_usage_setting;
		}

		if ( $new_usage_setting->getPriority() > $current_usage_setting->getPriority() ) {
			return $new_usage_setting;
		} else if ( $new_usage_setting->getPriority() < $current_usage_setting->getPriority() ) {
			return $current_usage_setting;
		}

		if ( $current_usage_matches_count > $new_usage_matches_count ) {
			return $current_usage_setting;
		} else if ( $current_usage_matches_count < $new_usage_matches_count ) {
			return $new_usage_setting;
		}

		if ( $current_usage_setting->getUpdatedAt() > $new_usage_setting->getUpdatedAt() ) {
			return $current_usage_setting;
		} else if ( $current_usage_setting->getUpdatedAt() < $new_usage_setting->getUpdatedAt() ) {
			return $new_usage_setting;
		}

		return $current_usage_setting;
	}

	/**
	 * Creates a instance given an database value formatted as array.
	 *
	 * @param int $default_content_template_id
	 * @param array $usage_settings_array
	 *
	 * @return UsagePostType
	 */
	public static function createFromDatabaseArray( $default_content_template_id = 0, $usage_settings_array = [] ) {
		$usage_settings = [];
		foreach( $usage_settings_array as $usage_setting_array ) {
			$usage_settings_instance = UsageSettings::createFromDatabaseArray( $usage_setting_array );
			if ( null !== $usage_settings_instance ) {
				$usage_settings[] = $usage_settings_instance;
			}
		}
		return new self(
			$default_content_template_id,
			$usage_settings
		);
	}

}
