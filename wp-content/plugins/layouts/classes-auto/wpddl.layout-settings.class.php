<?php

/**
 * Class WPDDL_Layout_Settings
 * @since 1.7
 * caches Layouts settings from the DB in associatiave array( $post_id => $settings ) both in JSON string format and php array format
 * ensures data is properly written and fetched from DB when needed
 */
class WPDDL_Layout_Settings {

	private $post_id;
	/** @var  WPDDL_Cache */
	private $raw_cache;
	/** @var  WPDDL_Cache */
	private $decoded_cache;

	/**
	 * WPDDL_Layout_Settings constructor.
	 *
	 * @param $post_id
	 * @param WPDDL_Cache|null $raw_cache caches the layouts settings object in JSON string format
	 * @param WPDDL_Cache|null $decoded_cache caches the layouts settings object in php associative array format
	 */
	public function __construct( $post_id, WPDDL_Cache $raw_cache = null, WPDDL_Cache $decoded_cache = null ) {
		$this->post_id       = $post_id;
		if ( $raw_cache ) {
			$this->raw_cache = $raw_cache;
		} else {
			$this->raw_cache = new WPDDL_Cache( 'temp_raw');
		}
		if ( $decoded_cache ) {
			$this->decoded_cache = $decoded_cache;
		} else {
			$this->decoded_cache = new WPDDL_Cache( 'temp_decoded' );
		}
	}

	/**
	 * @param bool $as_array tells the method to output layout settings in JSON string format (default) or as an associative array
	 * @param bool $clear_cache when set to true it clears the cache before fetching the settings object, otherwise it gets the cached version of it: default to false
	 *
	 * @return mixed|null
	 */
	public function get( $as_array = false, $clear_cache = false ) {

		if ( ! $this->raw_cache->has( $this->post_id ) || $clear_cache ) {

			$this->raw_cache->set( $this->post_id, get_post_meta( $this->post_id, WPDDL_LAYOUTS_SETTINGS, true ) );

		}

		if ( $as_array && ( ! $this->decoded_cache->has( $this->post_id ) || $clear_cache ) ) {

			if ( $this->raw_cache->has( $this->post_id ) ) {
				$this->decoded_cache->set( $this->post_id, json_decode( $this->raw_cache->get( $this->post_id ) ) );
			} else {
				$this->decoded_cache->set( $this->post_id, null );
			}
		}

		return $as_array ? $this->decoded_cache->get( $this->post_id ) : $this->raw_cache->get( $this->post_id );
	}

	/**
	 * @param $settings the layout JSON object as stored in `wp_postmeta` table with '_dd_layouts_settings' `meta_key` in JSON string or associative array format
	 *
	 * @return bool|int
	 * 09/10/2018 Removed second argument $original erroneously never used and added a systematic call to $this->get() to always confront with old data during update_post_meta call
	 */
	public function update( $settings ) {
		$meta_key = WPDDL_LAYOUTS_SETTINGS;

		if ( is_string( $settings ) ) {
			$settings = wp_json_encode( json_decode( $settings, true ) );
		} else if ( is_array( $settings ) || is_object( $settings ) ) {
			$settings = wp_json_encode( (array) $settings );
		}

		$original = $this->get();

		$result = update_post_meta( $this->post_id, $meta_key, addslashes( $settings ), $original );

		if( $result ){
			$this->update_caches( $settings );
		}

		return $result;
	}

	public function clear_cache() {
		$this->raw_cache->clear( $this->post_id );
		$this->decoded_cache->clear( $this->post_id );
	}

	/**
	 * @param $settings the layout JSON object as stored in `wp_postmeta` table with '_dd_layouts_settings' `meta_key` in mandatory JSON string format
	 * @since 2.5
	 */
	private function update_caches( $settings ){
		$this->update_raw_cache( $settings );
		$this->update_decoded_cache( json_decode( $settings ) );
	}

	/**
	 * @param $settings the layout JSON object as stored in `wp_postmeta` table with '_dd_layouts_settings' `meta_key` in mandatory JSON string format
	 * @since 2.5
	 */
	private function update_raw_cache( $settings ){
		$this->raw_cache->set( $this->post_id, $settings );
	}

	/**
	 * @param $settings the layout JSON object as stored in `wp_postmeta` table with '_dd_layouts_settings' `meta_key` in mandatory JSON string format
	 * @since 2.5
	 */
	private function update_decoded_cache( $settings ){
		$this->decoded_cache->set( $this->post_id, $settings );
	}
}
