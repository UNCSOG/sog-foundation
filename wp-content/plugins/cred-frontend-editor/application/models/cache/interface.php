<?php

namespace OTGS\Toolset\CRED\Model\Cache;

/**
 * Interface for generating transients.
 * 
 * @since 2.1.2
 */
interface ITransient {

	/**
	 * @return mixed
	 */
	public function generate_transient();

	/**
	 * @return mixed
	 */
	public function delete_transient();

	/**
	 * @return mixed
	 */
	public function get_transient();
}