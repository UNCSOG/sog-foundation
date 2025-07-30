<?php

namespace OTGS\Toolset\Views\Model\Wordpress;

/**
 * Wrapper for the WordPress wp_die function.
 *
 * @since 3.2
 */
class WpDie {

	/**
	 * Message content.
	 *
	 * @var string
	 */
	private $message = '';

	/**
	 * Message type.
	 *
	 * @var string
	 */
	private $message_type = 'error';

	/**
	 * Set the message.
	 *
	 * @param string $message
	 */
	public function set_message( $message ) {
		$this->message = $message;
	}

	/**
	 * Set the message type.
	 *
	 * @param string $message
	 */
	public function set_message_type( $message_type ) {
		$this->message_type = $message_type;
	}

	/**
	 * Do wp_die and eventually show the message.
	 */
	public function wp_die() {
		if ( empty( $this->message ) ) {
			wp_die();
		}

		wp_die(
			sprintf(
				'<div class="toolset-alert toolset-alert-%s">%s</div>',
				esc_attr( $this->message_type ),
				$this->message
			)
		);

	}
}
