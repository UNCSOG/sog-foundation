<?php

namespace OTGS\Toolset\CRED\Model\Forms\Post\Expiration;

/**
 * Post expiration form settings model.
 *
 * @since 2.3
 */
class Settings {

	const FORM_META_SETTING_NAME = '_cred_post_expiration';

	/**
	 * Get the default form settings for the post expiration feature.
	 *
	 * @return array
	 * @since 2.3
	 */
	private function get_default_settings() {
		return array(
			'enable'          => 0,
			'action'          => array(
				'post_status'    => '',
				'custom_actions' => array()
			),
			'expiration_time' => array(
				'expiration_date'   => 0,
				'expiration_period' => 'hours'
			)
		);
	}

	/**
	 * Get the form expiration settings.
	 *
	 * @param int $form_id
	 * @return array
	 * @since 2.3
	 */
	public function load( $form_id ) {
		$form_settings = get_post_meta( $form_id, self::FORM_META_SETTING_NAME, true );
		$form_settings = is_array( $form_settings ) ? $form_settings : array();

		$form_settings = $this->normalize_expiration_settings( $form_settings );
		$form_settings = $this->apply_defaults( $this->get_default_settings(), $form_settings );

		return $form_settings;
	}

	/**
	 * Save the form expiration settings.
	 *
	 * @param int $form_id
	 * @param array $settings
	 * @return array
	 * @since 2.3
	 */
	public function save_posted_settings( $form_id ) {
		$posted_settings = toolset_getpost( self::FORM_META_SETTING_NAME, array() );
		$form_settings = $this->apply_defaults( $this->get_default_settings(), $posted_settings );
		$form_settings = cred_sanitize_array( $form_settings );

		update_post_meta( $form_id, self::FORM_META_SETTING_NAME, $form_settings );

		return $form_settings;
	}

	/**
	 * Normalize the settings on expiration periods so they refer to hours, always.
	 *
	 * @param array $settings
	 * @return array
	 * @since 2.3
	 */
	private function normalize_expiration_settings( $settings ) {
		if (
			isset( $settings['expiration_time']['weeks'] )
			&& $settings['expiration_time']['days']
		) {
			$hours_in_weeks = ( $settings['expiration_time']['weeks'] * 7 ) * 24;
			$hours_in_days = $settings['expiration_time']['days'] * 24;
			$settings['expiration_time']['expiration_date'] = $hours_in_days + $hours_in_weeks;
			$settings["expiration_time"]['expiration_period'] = 'hours';
		}

		return $settings;
	}

	/**
	 * Apply defaults to form expiration settings.
	 *
	 * @param array $defaults
	 * @param array $settings
	 * @return array
	 * @since 2.3
	 */
	public function apply_defaults( $defaults, $settings ) {
		$merged = $defaults;
		foreach ( $settings as $key => &$value ) {
			if ( is_array( $value ) && isset( $merged [ $key ] ) && is_array( $merged [ $key ] ) ) {
				$merged[ $key ] = $this->apply_defaults( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

}
