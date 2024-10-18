<?php

abstract class WPDDL_Shortcode_Option_Attribute_Abstract {
	private $id;
	private $label;
	private $header;

	private $fields = array();

	public function setId( $string ) {
		$this->id = $string;
	}

	public function setLabel( $string ) {
		$this->label = $string;
	}

	public function setHeader( $string ) {
		$this->header = $string;
	}

	public function addField( $objectField ) {
		if( is_a( $objectField, 'WPDDL_Shortcode_Option_Attribute_Field_Abstract' ) ) {
			$this->fields[] = $objectField;
		}
	}

	public function getId(){
		return $this->id;
	}

	public function getAttribute() {
		$fields = array();

		foreach( $this->fields as $field ) {
			$fields[$field->getId()] = $field->getField();
		}

		return array(
			'label' => $this->label,
			'header' => $this->header,
			'fields' => $fields,
		);
	}
}