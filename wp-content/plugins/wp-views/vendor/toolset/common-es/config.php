<?php


$config = [
	'common' => [
		'blocks' => [
			'tabs' => [
				'presets' => [
					'normal-hover-active' => [
						[
							'name' => 'normal',
							'title' => __( 'Normal', 'wpv-views' ),
							'class' => null, // Extra class. Use &.className when the class is on the root element.
							'pseudoClass' => null, // The "normal" state has no pseudo class.
							'storageKey' => null, // Store the "normal" state on the root.
						],
						[
							'name' => 'hover',
							'title' => __( 'Hover', 'wpv-views' ),
							'pseudoClass' => ':hover',
							'storageKey' => ':hover',
						],
						[
							'name' => 'active',
							'title' => __( 'Active', 'wpv-views' ),
							'pseudoClass' => ':active',
							'storageKey' => ':active',
						],
					],
				],
			],
			'controls' => [
				'order' => [
					'idClasses',
					'equalColumnsCount',
					'columnGap',
					'rowGap',
					'top',
					'bottom',
					'blockAlign',
					'display',
					'font',
					'fontFamily',
					'fontSize',
					'lineHeight',
					'letterSpacing',
					'fontIconToolbar',
					'textDecoration',
					'fontWeight',
					'fontStyle',
					'textTransform',
					'textAlign',
					'textColor',
					'textShadow',
					'background',
					'backgroundColor',
					'margin',
					'border',
					'borderRadius',
					'scale',
					'rotate',
					'opacity',
					'boxShadow',
					'width',
					'aspectRatio',
					'height',
					'applyMaxWidth',
					'minHeight',
					'verticalAlign',
					'zIndex',
				],
			],
		],
	],
];

return apply_filters( 'toolset-blocks-config', $config );

