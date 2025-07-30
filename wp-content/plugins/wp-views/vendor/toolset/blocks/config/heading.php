<?php
/**
 * Config for the Heading block.
 * IMPORTANT: The Heading does only use config for the link style. The block config is partly used to save
 *              migration work later.
 */

return [
	'slug' => 'heading',
	'title' => __( 'Single Field', 'wpv-views' ),
	'description' => __( 'A heading with full styling and typography controls. You can enter the text directly or choose text from a field.', 'wpv-views' ),
	'keywords' => [
		'heading',
		__( 'Heading', 'wpv-views' ),
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
