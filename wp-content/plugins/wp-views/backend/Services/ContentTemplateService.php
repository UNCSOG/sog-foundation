<?php

namespace OTGS\Toolset\Views\Services;

use OTGS\Toolset\Views\Model\Wordpress\Wpdb;
use OTGS\Toolset\Views\Models\ContentTemplate\UsagePostType;
use OTGS\Toolset\Views\Models\ContentTemplate\UsageSettings;
use Toolset\DynamicSources\ToolsetSources\CustomFieldService;

/**
 * Handle the Content Template object manipulation
 */
class ContentTemplateService {

	const POST_TYPE = 'view-template';

	const META_KEY_CUSTOM_TEMPLATE = '_views_template';

	/**
	 * @var \WPV_WordPress_Archive_Frontend
	 */
	private $archive_frontend;

	/**
	 * @var \Toolset_Field_Definition_Factory_Post
	 */
	private $post_field_definition_factory;

	/**
	 * @var \WPV_Settings
	 */
	private $wpv_settings;

	/**
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * @var int[]
	 */
	private $assigned_post_templates = [];

	/**
	 * @param \WPV_WordPress_Archive_Frontend $archive_frontend
	 * @param \Toolset_Field_Definition_Factory_Post $post_field_definition_factory
	 * @param \WPV_Settings $wpv_settings
	 * @param Wpdb $wpdb
	 */
	public function __construct(
		\WPV_WordPress_Archive_Frontend $archive_frontend,
		\Toolset_Field_Definition_Factory_Post $post_field_definition_factory,
		\WPV_Settings $wpv_settings,
		Wpdb $wpdb
	) {
		$this->archive_frontend = $archive_frontend;
		$this->post_field_definition_factory = $post_field_definition_factory;
		$this->wpv_settings = $wpv_settings;
		$this->wpdb = $wpdb->get_wpdb();
	}

	/**
	 * Check if ContentTemplate PostMeta is null and if any post_type is assigned to it.
	 * If both true, creates the new post meta for the post_type that is assigned
	 *
	 * @param int $content_template_id
	 */
	public function migrate_content_template( $content_template_id ) {
		$usage = get_post_meta( $content_template_id, 'usage', true );
		if ( $usage ) {
			return;
		}

		$assignments = $this->wpv_settings->get_view_template_settings();
		if ( ! in_array( $content_template_id, $assignments, true ) ) {
			return;
		}

		$usages = [];
		foreach ( $assignments as $post_type => $assigned_content_template_id ) {
			if ( $content_template_id === $assigned_content_template_id ) {
				$usages[] = array(
					'post_type' => $post_type,
					'enabled' => true,
					'conditions' => null,
				);
			}
		}
		update_post_meta( $content_template_id, 'usage', $usages );
	}


	/**
	 * Deletes all the post meta referencing custom template on posts, for a given post type.
	 * Optionally, it can take a content_template_id, and will only delete posts with that CT assigned.
	 *
	 * @param string $post_type_slug
	 * @param int|null $only_content_template_id
	 *
	 * @return int|bool Number of rows deleted. Boolean false on error.
	 */
	public function clean_post_type_assigned_posts( $post_type_slug, $only_content_template_id = null ) {

		// as this post meta is only for Toolset internal usage, we can skip WP Hooks
		// and run this change in one single query.
		$table_postmeta = $this->wpdb->postmeta;
		$table_posts = $this->wpdb->posts;

		$query = "
DELETE `$table_postmeta` FROM `$table_postmeta`
LEFT JOIN `$table_posts` ON `$table_posts`.id = `$table_postmeta`.post_id
WHERE `$table_posts`.post_type = %s
AND `$table_postmeta`.meta_key = %s
";

		if ( null !== $only_content_template_id ) {
			$query .= " AND `$table_postmeta`.meta_value = %s";
			$prepared_query = $this->wpdb->prepare( $query, $post_type_slug, self::META_KEY_CUSTOM_TEMPLATE, $only_content_template_id );
		} else {
			$prepared_query = $this->wpdb->prepare( $query, $post_type_slug, self::META_KEY_CUSTOM_TEMPLATE );
		}

		$query_result = $this->wpdb->query( $prepared_query );

		$this->wpdb->flush();
		return $query_result;
	}

	/**
	 * Get the template assigned to a given Post.
	 * Calculates the value if it's the first time in memory.
	 *
	 * @param \WP_Post $post
	 * @param int $default_content_template_id
	 *
	 * @return int
	 */
	public function get_template_for( \WP_Post $post, $default_content_template_id = 0 ) {
		if ( isset( $this->assigned_post_templates[ $post->ID ] ) ) {
			return $this->assigned_post_templates[ $post->ID ];
		}

		$assigned_content_template = $this->calculate_template_for( $post, $default_content_template_id );
		$this->assigned_post_templates[ $post->ID ] = $assigned_content_template;

		return $assigned_content_template;
	}

	/**
	 * Calculates the template assigned to a given Post according Toolset priorities.
	 * Zero value means no template.
	 *
	 * @param \WP_Post $post
	 * @param int $default_content_template_id
	 *
	 * @return int
	 */
	public function calculate_template_for( \WP_Post $post, $default_content_template_id = 0 ) {
		$template_selected = get_post_meta( $post->ID, '_views_template', true );

		if ( '0' !== $template_selected && empty( $template_selected ) ) {
			$key_settings = sprintf( '%s%s', \WPV_SETTINGS::SINGLE_POST_TYPES_CT_ASSIGNMENT_PREFIX, $post->post_type );
			$key_settings_conditions = sprintf( '%s%s', \WPV_SETTINGS::SINGLE_POST_TYPES_CT_CONDITIONS_ASSIGNMENT_PREFIX, $post->post_type );

			$settings_post_type_ct = $this->wpv_settings[ $key_settings ] ? $this->wpv_settings[ $key_settings ] : $default_content_template_id;
			$conditions_settings = $this->wpv_settings[ $key_settings_conditions ] ?: [];
			$usage = UsagePostType::createFromDatabaseArray( $settings_post_type_ct, $conditions_settings );
			$template_selected = $usage->getContentTemplateForPost( $post );
		}
		return (int) $template_selected;
	}

	/**
	 * Parses existing conditions usage for this content template and assigns the correct priority.
	 *
	 * @param int $content_template_id
	 * @param int $priority
	 */
	public function set_priority_settings( $content_template_id, $priority ) {
		$content_template_usages = get_post_meta( $content_template_id, 'usage', true );
		foreach ( $content_template_usages as $content_template_usage ) {
			if ( null !== $content_template_usage[ UsageSettings::KEY_CONDITIONS_GROUP ] ) {
				$this->set_priority_post_type_setting( $content_template_id, $priority, $content_template_usage['post_type'] );
			}
		}
		$this->wpv_settings->save();
	}


	/**
	 * Removes the settings form a post that is being transitioned from publish state.
	 *
	 * @param int $content_template_id
	 */
	public function remove_content_template_settings( $content_template_id ) {
		$content_template_usages = get_post_meta( $content_template_id, 'usage', true );

		if ( '' === $content_template_usages ) {
			// CT has no assigned usages.
			$content_template_usages = array();
		}

		foreach ( $content_template_usages as $content_template_usage ) {
			$this->set_usage_settings( $content_template_usage[ UsageSettings::KEY_POST_TYPE ], $content_template_id, false, null, '', 0, 0, false );
		}
		$this->wpv_settings->save();
	}


	/**
	 * Applies the Conditions to the CT settings mechanism for a CT that is being published.
	 *
	 * @param int $content_template_id
	 */
	public function publish_content_template_settings( $content_template_id ) {
		$content_template_usages = get_post_meta( $content_template_id, 'usage', true );

		if ( '' === $content_template_usages ) {
			// CT has no assigned usages.
			$content_template_usages = array();
		}

		foreach ( $content_template_usages as $usage ) {
			if (
				! isset( $usage[ UsageSettings::KEY_POST_TYPE ] ) ||
				! isset( $usage[ UsageSettings::KEY_ENABLED ] )
			) {
				continue;
			}
			$this->set_usage_settings(
				$usage[ UsageSettings::KEY_POST_TYPE ],
				$content_template_id,
				$usage[ UsageSettings::KEY_ENABLED ],
				isset( $usage[ UsageSettings::KEY_CONDITIONS_GROUP ] ) ? $usage[ UsageSettings::KEY_CONDITIONS_GROUP ] : null,
				isset( $usage[ UsageSettings::KEY_PARSED_CONDITIONS_GROUP ] ) ? $usage[ UsageSettings::KEY_PARSED_CONDITIONS_GROUP ] : '',
				isset( $usage[ UsageSettings::KEY_PRIORITY ] ) ? $usage[ UsageSettings::KEY_PRIORITY ] : 0,
				time(),
				false
			);
		}
		$this->wpv_settings->save();
	}

	/**
	 * Apply the Content Template usage to Settings.
	 *
	 * @param string $post_type_slug
	 * @param integer $content_template_id
	 * @param boolean $enabled
	 * @param array $conditions
	 * @param string $parsed_conditions
	 * @param integer $priority
	 * @param integer $updated_at
	 * @param boolean $persist
	 */
	public function set_usage_settings( $post_type_slug, $content_template_id, $enabled, $conditions, $parsed_conditions, $priority, $updated_at, $persist = true ) {
		$usage_conditions = $this->get_post_type_usage_settings_excluding_content_template( $post_type_slug, $content_template_id );

		if ( $enabled ) {
			if ( null === $conditions || empty( $conditions ) ) {
				$this->set_default_usage_for_post_type( $post_type_slug, $content_template_id );
			} else {
				// Add the new conditions to the settings array.
				$usage_conditions[] = array(
					UsageSettings::KEY_CONTENT_TEMPLATE_ID => $content_template_id,
					UsageSettings::KEY_CONDITIONS_GROUP => $conditions,
					UsageSettings::KEY_PARSED_CONDITIONS_GROUP => $parsed_conditions,
					UsageSettings::KEY_UPDATED_AT => $updated_at,
					UsageSettings::KEY_PRIORITY => $priority,
				);

				$this->remove_default_usage_for_post_type( $post_type_slug, $content_template_id );
			}
		}

		if ( ! $enabled ) {
			$this->remove_default_usage_for_post_type( $post_type_slug, $content_template_id );
		}

		$this->set_usage_conditions_for_post_type( $post_type_slug, $usage_conditions );

		if ( $persist ) {
			$this->wpv_settings->save();
		}
	}


	/**
	 * Sets the default post meta to the Content Template, in order to load the proper editor.
	 *
	 * @param \WP_Post $post
	 *
	 * @return mixed
	 */
	public function set_default_meta( \WP_Post $post ) {
		if ( self::POST_TYPE !== $post->post_type ) {
			return;
		}
		$toolset_user_editor_value = get_post_meta(
			$post->ID,
			\WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE,
			true
		);

		if ( '' === $toolset_user_editor_value ) {
			add_post_meta(
				$post->ID,
				\WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE,
				$this->wpv_settings->default_user_editor,
				true
			);
		}
	}

	/**
	 * Sets priority setting for a post type and content template.
	 *
	 * @param int $content_template_id
	 * @param int $priority
	 * @param string $post_type_slug
	 */
	private function set_priority_post_type_setting( $content_template_id, $priority, $post_type_slug ) {
		$post_type_conditions_settings_key = sprintf( '%s%s', \WPV_Settings::SINGLE_POST_TYPES_CT_CONDITIONS_ASSIGNMENT_PREFIX, $post_type_slug );

		if ( isset( $this->wpv_settings[ $post_type_conditions_settings_key ] ) ) {
			$settings = $this->wpv_settings[ $post_type_conditions_settings_key ];

			if ( is_array( $settings ) ) {
				foreach ( $settings as $key => $setting ) {
					if ( intval( $content_template_id ) === intval( $setting[ UsageSettings::KEY_CONTENT_TEMPLATE_ID ] ) ) {
						$setting[ UsageSettings::KEY_PRIORITY ] = $priority;
					}
					$settings[ $key ] = $setting;
				}
			}
			$this->wpv_settings[ $post_type_conditions_settings_key ] = $settings;
		}
	}

	/**
	 * Returns the usage settings for a given post type, excluding the given content template.
	 *
	 * @param string $post_type_slug
	 * @param int $content_template_id
	 *
	 * @return array|mixed|null
	 */
	private function get_post_type_usage_settings_excluding_content_template( $post_type_slug, $content_template_id ) {
		$post_type_conditions_settings_key = sprintf( '%s%s', \WPV_Settings::SINGLE_POST_TYPES_CT_CONDITIONS_ASSIGNMENT_PREFIX, $post_type_slug );

		$current_conditions = [];
		if ( isset( $this->wpv_settings[ $post_type_conditions_settings_key ] ) ) {
			$current_conditions = $this->wpv_settings[ $post_type_conditions_settings_key ];

			if ( is_array( $current_conditions ) ) {
				foreach ( $current_conditions as $key => $current_condition ) {
					// Remove the condition if belongs to the current content template.
					if (
						isset( $current_condition['content_template_id'] ) &&
						$current_condition['content_template_id'] === $content_template_id
					) {
						unset( $current_conditions[ $key ] );
					}
				}
			}
		}

		return $current_conditions;
	}


	/**
	 * Sets a Content Template as the default for a given post type.
	 *
	 * @param string $post_type_slug
	 * @param int $content_template_id
	 */
	private function set_default_usage_for_post_type( $post_type_slug, $content_template_id ) {
		$post_type_settings_key = sprintf( '%s%s', \WPV_Settings::SINGLE_POST_TYPES_CT_ASSIGNMENT_PREFIX, $post_type_slug );
		$previous_content_template_id = $this->wpv_settings[ $post_type_settings_key ];

		// We clear the "manual" assignment on post meta level for the previous CT.
		if ( intval( $previous_content_template_id ) > 0 ) {
			$this->clean_post_type_assigned_posts( $post_type_slug, $previous_content_template_id );
		}

		if ( $previous_content_template_id !== $content_template_id ) {
			$this->wpv_settings[ $post_type_settings_key ] = $content_template_id;

			if ( $previous_content_template_id > 0 ) {

				// Update post meta for the affected content template.
				$previous_content_template_usages = get_post_meta( $previous_content_template_id, 'usage', true );
				foreach ( $previous_content_template_usages as $usage_key => $previous_content_template_usage ) {
					if ( $previous_content_template_usage['post_type'] === $post_type_slug ) {
						unset( $previous_content_template_usages[ $usage_key ] );
					}
				}
				update_post_meta( $previous_content_template_id, 'usage', $previous_content_template_usages );
			}
		}
	}

	/**
	 * Removes a Content Template as the default for a given post type.
	 *
	 * @param string $post_type_slug
	 * @param int $content_template_id
	 */
	private function remove_default_usage_for_post_type( $post_type_slug, $content_template_id ) {
		$post_type_settings_key = sprintf( '%s%s', \WPV_Settings::SINGLE_POST_TYPES_CT_ASSIGNMENT_PREFIX, $post_type_slug );

		if ( (string) $this->wpv_settings[ $post_type_settings_key ] === (string) $content_template_id ) {
			unset( $this->wpv_settings[ $post_type_settings_key ] );
		}

		$this->clean_post_type_assigned_posts( $post_type_slug, $content_template_id );
	}

	/**
	 * Sets array of usage conditions for a given post type.
	 *
	 * @param string $post_type_slug
	 * @param array $usage_conditions
	 */
	private function set_usage_conditions_for_post_type( $post_type_slug, $usage_conditions ) {
		$post_type_conditions_settings_key = sprintf( '%s%s', \WPV_Settings::SINGLE_POST_TYPES_CT_CONDITIONS_ASSIGNMENT_PREFIX, $post_type_slug );
		$this->wpv_settings[ $post_type_conditions_settings_key ] = $usage_conditions;
	}
}
