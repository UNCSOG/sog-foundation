<?php
function register_dd_layout_cell_type($cell_type, $args) {
	$cellsFactory = WPDD_RegisteredCellTypesFactory::build();
	return $cellsFactory->register_dd_layout_cell_type($cell_type, $args);
}
// This is for retro compatibility only with versions < 1.0
function register_dd_layout_theme_section($theme_section, $args) {
	$cellsFactory = WPDD_RegisteredCellTypesFactory::build();
	return $cellsFactory->register_dd_layout_theme_section($theme_section, $args);
}

function register_dd_layout_custom_row($theme_section, $args) {
	$cellsFactory = WPDD_RegisteredCellTypesFactory::build();
	return $cellsFactory->register_dd_layout_theme_section($theme_section, $args);
}