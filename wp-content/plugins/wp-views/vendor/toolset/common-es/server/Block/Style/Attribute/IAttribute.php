<?php

namespace ToolsetCommonEs\Block\Style\Attribute;


interface IAttribute {
	public function get_name();
	public function get_css();
	public function is_transform();
}
