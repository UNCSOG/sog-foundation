<?php

abstract class WPDDL_Shortcode_Option_Abstract {
	private $shortcode_id;

	private $name;
	private $label;
	private $post_selection = false;

	private $attributes = array();


	public function setName( $string ) {
		$this->name = $string;
	}

	public function setLabel( $string ) {
		$this->label = $string;
	}

	public function setShortcodeId( $string ) {
		$this->shortcode_id = $string;
	}

	public function activatePostSelection() {
		$this->post_selection = true;
	}

	public function deactivatePostSelection() {
		$this->post_selection = false;
	}

	public function addAttribute( $objectAttribute ) {
		if( is_a( $objectAttribute, 'WPDDL_Shortcode_Option_Attribute_Abstract' ) ) {
			$this->attributes[] = $objectAttribute;
		}
	}

	private function getOptions() {
		$attributes = array();

		foreach( $this->attributes as $attribute ) {
			$attributes[$attribute->getId()] = $attribute->getAttribute();
		}

		$options = array(
			'name' => $this->name,
			'label' => $this->label,
			'post-selection' => $this->post_selection,
			'attributes' => $attributes
		);

		return $options;
	}

	public function apply() {
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data', array( $this, 'Gui' ) );
	}

	public function Gui( $views_shortcodes ){
		$views_shortcodes[$this->shortcode_id] = array(
			'callback' => array( $this, 'GuiOptions' )
		);
		return $views_shortcodes;
	}

	public function GuiOptions() {
		return $this->getOptions();
	}
}