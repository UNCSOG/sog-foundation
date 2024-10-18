<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain;


/**
 * Interface IBlockContent
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain
 *
 * @since TB 1.3
 */
interface IBlockContent {
	/**
	 * @return string
	 */
	public function get();

	/**
	 * @return bool
	 */
	public function has_search();

	/**
	 * @return string
	 */
	public function get_content_search_container();

	/**
	 * @return string
	 */
	public function get_content_between_start_and_search();

	/**
	 * @return string
	 */
	public function get_content_between_start_and_output();

	/**
	 * @return string
	 */
	public function get_content_between_search_and_output();

	/**
	 * @return string
	 */
	public function get_content_between_output_and_search();

	/**
	 * @return string
	 */
	public function get_content_between_search_and_end();

	/**
	 * @return string
	 */
	public function get_content_between_output_and_end();
}
