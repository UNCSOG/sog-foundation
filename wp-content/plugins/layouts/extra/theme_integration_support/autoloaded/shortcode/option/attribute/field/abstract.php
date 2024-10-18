<?php

abstract class WPDDL_Shortcode_Option_Attribute_Field_Abstract {
	private $id;
	private $label;
	private $type;
	private $default;
	private $description;

	private $options = array();

	public function setId( $string ) {
		$this->id = $string;
	}

	public function setLabel( $string ) {
		$this->label = $string;
	}

	public function setType( $string ) {
		$this->type = $string;
	}

	public function setDescription( $string ) {
		$this->description = $string;
	}

	public function addOption( $objectOption ) {
		if( is_a( $objectOption, 'WPDDL_Shortcode_Option_Attribute_Field_Option_Abstract' ) ) {
			$this->options[] = $objectOption;
		}
	}

	public function getId() {
		return $this->id;
	}

	public function getField() {
		$options = array();

		foreach( $this->options as $option ) {
			$options[$option->getId()] = $option->getDescription();

			if( $option->isDefault() )
				$this->default = $option->getId();
		}
		return array(
			'label' => $this->label,
			'type' => $this->type,
			'default' => $this->default,
			'description' => $this->description,
			'options' => $options
		);
	}
}