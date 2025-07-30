<?php

namespace OTGS\Toolset\Views\Controller\Cache\Meta;

/**
 * GUI for the settings about the meta fields cache.
 *
 * @since 2.8.1
 */
class Gui {

	const CRON = 'cron';
	const MANUAL = 'manual';

	/**
	 * Initialize this GUI by adding a section to the right tab of the Toolset Settings.
	 *
	 * @since 2.8.1
	 */
	public function initialize() {
		add_filter( 'toolset_filter_toolset_register_settings_front-end-content_section', array( $this, 'render_options' ), 75 );
	}

	/**
	 * Render the options for this set of settings.
	 *
	 * @param array $sections
	 * @return array
	 * @since 2.8.1
	 * @since 2.8.2 Move the output to a proper template.
	 */
	public function render_options( $sections ) {
		$settings = \WPV_Settings::get_instance();

		$context = array(
			'manage_meta_transient_method' => $settings->manage_meta_transient_method,
		);

		$template_repository = \WPV_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();
		$section_content = $renderer->render(
			$template_repository->get( \WPV_Output_Template_Repository::VIEWS_SETTINGS_CACHE_OPTIONS ),
			$context,
			false
		);

		$sections['wpv-manage-meta-transient-method'] = array(
			'slug' => 'wpv-manage-meta-transient-method',
			/* translators: Title of the setting section about the plugin generated cache */
			'title' => __( 'Cache', 'wpv-views' ),
			'content' => $section_content,
		);
		return $sections;
	}

}
