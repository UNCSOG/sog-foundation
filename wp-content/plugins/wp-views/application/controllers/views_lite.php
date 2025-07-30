<?php
/**
 * Make adjustments to make sure that Views lite is loaded when necessary
 * @since Views Lite
 */
class WPV_Lite_Handler{

	public function initialize(){

		// make sure that we are working with views lite
		if( true !== wpv_is_views_lite() ){
			return;
		}

		// adjust screen options when sections are removed
		$this->adjust_screen_options();
		$this->remove_views_sections();
	}

	/**
	 * Remove actions and filters that are adding sections to Views edit page
	 */
	private function remove_views_sections() {

		// remove content section
		remove_action( 'wpv_action_view_editor_section_extra', array(
			'WPV_Editor_Content',
			'wpv_editor_section_content'
		), 10, 2 );
		remove_action( 'wpv_action_wpa_editor_section_extra', array(
			'WPV_Editor_Content',
			'wpv_editor_section_content'
		), 10, 2 );

		// remove extra filters section
		remove_action( 'wpv_action_view_editor_section_filter', array(
			'WPV_Editor_Filter_Editor',
			'wpv_editor_section_parametric_search'
		), 30, 2 );
		remove_action( 'wpv_action_wpa_editor_section_filter', array(
			'WPV_Editor_Filter_Editor',
			'wpv_editor_section_parametric_search'
		), 30, 2 );
		remove_action( 'wpv_action_view_editor_section_filter', array(
			'WPV_Editor_Filter_Editor',
			'wpv_editor_section_filter_editor'
		), 35, 2 );
		remove_action( 'wpv_action_wpa_editor_section_filter', array(
			'WPV_Editor_Filter_Editor',
			'wpv_editor_section_filter_editor'
		), 35, 2 );

		// remove pagination section
		remove_action( 'wpv_action_view_editor_section_filter', array(
			'WPV_Editor_Pagination',
			'wpv_add_view_pagination'
		), 10, 2 );
		remove_action( 'wpv_action_wpa_editor_section_filter', array(
			'WPV_Editor_Pagination',
			'wpv_add_archive_pagination'
		), 10, 3 );



	}

	/**
	 * Remove some sections from screen options
	 * Do it very late to make sure that filter section is removed from screen options
	 */
	private function adjust_screen_options(){
		add_filter('wpv_screen_options_editor_section_filter', array( $this, 'remove_filter_screen_options_section' ), 999 );
		add_filter('wpv_screen_options_wpa_editor_section_filter', array( $this, 'remove_filter_screen_options_section' ), 999 );

		// remove output editor section for WPA
		add_filter('wpv_screen_options_wpa_editor_section_layout', array( $this, 'remove_output_editor_from_screen_options' ), 999 );

		// disable items in purpose selector for WPV Archive
		add_filter('wpv_views_archive_screen_options_purpose_selector_disabled_items', array( $this, 'disabled_items_for_wpv_archive_purpose_selector' ) );
		// disable items in purpose selector for WPV
		add_filter('wpv_views_screen_options_purpose_selector_disabled_items', array( $this, 'disabled_items_for_wpv_purpose_selector' ) );

	}

	/**
	 * Unset sections that are not necessary for Views Lite
	 * @param $sections
	 *
	 * @return array
	 */
	public function remove_filter_screen_options_section( $sections ){
		unset( $sections['pagination'] );
		unset( $sections['filter-extra-parametric'] );
		unset( $sections['filter-extra'] );
		return $sections;
	}

	/**
	 * Unset sections that are not necessary for Views Lite in Views Archives editor
	 * @param $sections
	 *
	 * @return mixed
	 */
	public function remove_output_editor_from_screen_options($sections){
		unset( $sections['content'] );
		return $sections;
	}

	/**
	 * Disable parametric in purpose selector for Views Archive
	 * @param $disabled_items
	 *
	 * @return array
	 */
	public function disabled_items_for_wpv_archive_purpose_selector( $disabled_items ){
		return array_merge( $disabled_items, array( 'parametric' ) );
	}

	/**
	 * Disable some options in purpose selector for Views
	 * @param $disabled_items
	 *
	 * @return array
	 */
	public function disabled_items_for_wpv_purpose_selector( $disabled_items ){
		return array_merge( $disabled_items, array( 'pagination','slider','parametric','full' ) );
	}

}