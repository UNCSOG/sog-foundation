<?php

/**
 * Interface for handlers of hook API calls.
 */
interface CRED_Api_Handler_Interface {

	/**
	 * @param array $arguments Original action/filter arguments.
	 * @return mixed
	 */
	function process_call( $arguments );

}