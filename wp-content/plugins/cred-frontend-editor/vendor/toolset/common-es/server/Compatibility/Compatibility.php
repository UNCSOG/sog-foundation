<?php

namespace ToolsetCommonEs\Compatibility;

class Compatibility {
	/** @var string */
	private $id;

	/** @var ILocation */
	private $location;

	/** @var ISettings */
	private $third_party_component;

	/** @var IRule[] */
	private $rules = [];

	/** @var ISelector[] */
	private $additional_selectors = [];

	/**
	 * Compatibility constructor.
	 *
	 * @param string $id
	 * @param ILocation $location
	 * @param ISettings $settings
	 */
	public function __construct( $id, ILocation $location, ISettings $settings ) {
		$this->id = $id;
		$this->location = $location;
		$this->third_party_component = $settings;
	}

	/**
	 * @param IRule $rule
	 */
	public function add_rule( IRule $rule ) {
		$this->rules[] = $rule;
	}

	/**
	 * @param ISelector $selector
	 */
	public function add_selector( ISelector $selector ) {
		$this->additional_selectors[] = $selector;
	}

	/**
	 * Prints all compatibility css to admin head on post edit pages.
	 */
	public function apply_css_rules() {
		if( ! $this->location->is_open() ) {
			return;
		}

		$css_rules = '';

		// Collect all rules.
		foreach( $this->rules as $rule ) {
			$css_rules .= $rule->get_as_string(
				$this->third_party_component,
				$this->build_additionial_selector_as_string()
			);
		}

		// Print all rules on the post edit page.
		$this->location->apply_css_rules( $css_rules, $this->id );
	}

	public function apply_custom_fonts() {
		if( ! $this->location->is_open() ) {
			return;
		}

		$this->third_party_component->apply_custom_fonts();
	}

	private function build_additionial_selector_as_string() {
		$css_selectors_strings = [ $this->location->get_css_selector() ];

		foreach( $this->additional_selectors as $selector ) {
			$css_selectors_strings[] = $selector->get_css_selector();
		}

		return implode( ' ', $css_selectors_strings ) . ' ';
	}
}
