<?php

namespace OTGS\Toolset\CRED\Model\Cache\Forms;

use OTGS\Toolset\CRED\Controller\Forms\Post\Main as PostForms;
use OTGS\Toolset\CRED\Controller\Forms\User\Main as UserForms;
use \CRED_Association_Form_Main as AssociationForms;
use OTGS\Toolset\CRED\Model\Wordpress\Transient;

/**
 * Generate caching objects for forms based on their domain.
 * 
 * @since 2.1.2
 */
class Factory {

	/** @var \wpdb  */
	private $wpdb;

	/** @var Transient  */
	private $wp_transient;


	/**
	 * Factory constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param Transient $wp_transient
	 */
	public function __construct( \wpdb $wpdb, Transient $wp_transient ) {
		$this->wpdb = $wpdb;
		$this->wp_transient = $wp_transient;
	}

	/**
	 * Get caching object by transient key.
	 *
	 * @param $transient_key
	 *
	 * @return \OTGS\Toolset\CRED\Cache\ITransient
	 * @since 2.1.2
	 */
	public function create_by_transient_key( $transient_key ) {
		switch( $transient_key ) {
			case PostForms::TRANSIENT_KEY:
				return new Post( $this->wpdb, $this->wp_transient );
			case UserForms::TRANSIENT_KEY:
				return new User( $this->wpdb, $this->wp_transient );
			case AssociationForms::TRANSIENT_KEY:
				return new Association( $this->wpdb, $this->wp_transient );
			default:
				throw new \Exception( 'Unknown transient key' );
		}
	}

	/**
	 * Get caching object by post type.
	 * @param $post_type
	 *
	 * @return \OTGS\Toolset\CRED\Cache\ITransient
	 * @since 2.1.2
	 */
	public function create_by_post_type( $post_type ) {
		global $wpdb;

		switch( $post_type ) {
			case PostForms::POST_TYPE:
				return new Post( $this->wpdb, $this->wp_transient );
			case UserForms::POST_TYPE:
				return new User( $this->wpdb, $this->wp_transient );
			case AssociationForms::ASSOCIATION_FORMS_POST_TYPE:
				return new Association( $this->wpdb, $this->wp_transient );
			default:
				throw new \Exception( 'Unknown post type' );
		}
	}

	/**
	 * Get caching object by domain.
	 * @param $domain
	 *
	 * @return \OTGS\Toolset\CRED\Cache\ITransient
	 * @since 2.1.2
	 */
	public function create_by_domain( $domain ) {
		global $wpdb;

		switch( $domain ) {
			case \CRED_Form_Domain::POSTS:
				return new Post( $this->wpdb, $this->wp_transient );
			case \CRED_Form_Domain::USERS:
				return new User( $this->wpdb, $this->wp_transient );
			case \CRED_Form_Domain::ASSOCIATIONS:
				return new Association( $this->wpdb, $this->wp_transient );
			default:
				throw new \Exception( 'Unknown domain' );
		}
	}
}