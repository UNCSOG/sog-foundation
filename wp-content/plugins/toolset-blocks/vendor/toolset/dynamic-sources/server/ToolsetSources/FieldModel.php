<?php

namespace Toolset\DynamicSources\ToolsetSources;

/**
 * Simple model representing a custom field definition.
 */
class FieldModel {

	/** @var string */
	private $slug;

	/** @var string */
	private $name;

	/** @var string */
	private $type;

	/** @var array */
	private $categories;

	/** @var array|null */
	private $options;

	/** @var boolean */
	private $is_repeatable;

	/** @var boolean */
	private $is_created_by_types;


	/**
	 * FieldModel constructor.
	 *
	 * @param string $slug
	 * @param string $name
	 * @param string $type
	 * @param mixed[] $categories
	 * @param mixed[]|null $options
	 * @param bool $is_repeatable
	 * @param bool $is_created_by_types
	 */
	public function __construct( $slug, $name, $type, $categories, $options, $is_repeatable, $is_created_by_types ) {
		$this->slug = $slug;
		$this->name = $name;
		$this->type = $type;
		$this->categories = $categories;
		$this->options = $options;
		$this->is_repeatable = $is_repeatable;
		$this->is_created_by_types = $is_created_by_types;
	}


	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}


	/**
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}


	/**
	 * @return string
	 */
	public function get_type_slug() {
		return $this->type;
	}

	public function get_categories() {
		return $this->categories;
	}

	/**
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * @return boolean
	 */
	public function is_repeatable() {
		return $this->is_repeatable;
	}

	/**
	 * @return boolean
	 */
	public function is_created_by_types() {
		return $this->is_created_by_types;
	}
}
