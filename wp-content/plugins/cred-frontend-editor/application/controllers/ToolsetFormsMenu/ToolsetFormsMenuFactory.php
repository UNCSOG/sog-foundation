<?php

namespace OTGS\Toolset\CRED\Controller\ToolsetFormsMenu;

use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\PostFieldsList;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\PostFormEdit;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\PostFormList;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\PostFormNew;

use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\RelationshipFormList;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\RelationshipFormEdit;

use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\UserFieldsList;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\UserFormEdit;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\UserFormList;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\UserFormNew;

class ToolsetFormsMenuFactory {

	/** @var \Toolset_Constants */
	private $toolset_constants;

	/** @var string */
	private $mode;

	/**
	 * Constructor.
	 *
	 * @param \Toolset_Constants $toolset_constants
	 */
	public function __construct(
		\Toolset_Constants $toolset_constants
	) {
		$this->toolset_constants = $toolset_constants;
	}

	/**
	 * Set the mode for the menu management.
	 *
	 * Can be full, embedded or empty.
	 *
	 * @param string $mode
	 */
	public function set_mode( $mode ) {
		$this->mode = $mode;
	}

	/**
	 * Get the page object, given its ID.
	 *
	 * @param string $page_id
	 * @return \OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\BasePage|null
	 */
	public function get_page( $page_id ) {
		switch ( $page_id ) {
			case PostFormList::ID:
				return new PostFormList( $this->mode, $this->toolset_constants );
			case PostFormNew::ID:
				return new PostFormNew( $this->mode, $this->toolset_constants );
			case PostFormEdit::ID:
				return new PostFormEdit( $this->mode, $this->toolset_constants );
			case PostFieldsList::ID:
				return new PostFieldsList( $this->mode, $this->toolset_constants );

			case UserFormList::ID:
				return new UserFormList( $this->mode, $this->toolset_constants );
			case UserFormNew::ID:
				return new UserFormNew( $this->mode, $this->toolset_constants );
			case UserFormEdit::ID:
				return new UserFormEdit( $this->mode, $this->toolset_constants );
			case UserFieldsList::ID:
				return new UserFieldsList( $this->mode, $this->toolset_constants );

			case RelationshipFormList::ID:
				return new RelationshipFormList( $this->mode, $this->toolset_constants );
			case RelationshipFormEdit::ID:
				return new RelationshipFormEdit( $this->mode, $this->toolset_constants );
		}

		return null;
	}

}
