<?php
/**
 * Config for the Single Field block.
 * IMPORTANT: The Single Field does only use config for the link style. The block config is partly used to save
 *              migration work later.
 */

return [
	'slug' => 'field',
	'title' => __( 'Single Field', 'wpv-views' ),
	'description' => __( 'Displays a single field within your content.', 'wpv-views' ),
	'keywords' => [
		'single field',
		__( 'single field', 'wpv-views' ),
		'toolset',
	],
	'supports' => [
		'customClassName' => false,
	],
	'css' => [
		'rootClass' => '',
		'styleMap' => [
			'a' => [
				'linkStyle' => 'all',
			],
		],
	],
	'panels' => [
		'link-settings' => [
			'title' => __( 'Link Typography', 'woocommerce-views' ),
			'tabs' => 'normal-hover-active',
			'fields' => [
				'linkStyle' => 'all',
			],
		],
	],
	'attributes' => [
		'style' => [
			'type' => 'object',
			'fields' => [
				'font',
				'textColor',
			],
		],
		'linkStyle' => [
			'type' => 'object',
			'fields' => [
				'textColor',
				'font',
				'fontSize',
				'fontStyle',
				'fontWeight',
				'textDecoration',
				'lineHeight',
				'letterSpacing',
				'textTransform',
				'textShadow',
			],
		],
	],
];
