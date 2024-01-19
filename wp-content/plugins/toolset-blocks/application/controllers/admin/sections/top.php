<?php

namespace OTGS\Toolset\Views\Controller\Admin\Section;

/**
 * Top section for Views and WPAs editor, below the top bar and above the sections.
 */
class Top {

	/**
	 * Initialize the top section.
	 *
	 * @since 2.7
	 */
	public function initialize() {
		add_action( 'wpv_action_view_editor_section_top', array( $this, 'wpv_editor_section_title' ), 1 );
		add_action( 'wpv_action_wpa_editor_section_top', array( $this, 'wpv_editor_section_title' ), 1 );
	}

	/**
	 * Render the container for promotional videos on top of Views and WPAs editors.
	 *
	 * @param array $view_settings
	 * @since 2.7
	 */
	public function wpv_editor_section_title( $view_settings ) {
		if ( ! toolset_getget( 'toolset_help_video', false ) ) {
			return;
		}
		?>
		<div class="toolset-video-box-wrap"></div>
		<?php
	}

}
