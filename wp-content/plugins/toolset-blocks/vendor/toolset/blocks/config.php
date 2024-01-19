<?php

! defined( 'TB_BLOCKS_NAMESPACE' ) && define( 'TB_BLOCKS_NAMESPACE', 'toolset-blocks' );
! defined( 'TB_CONFIG_PATH' ) && define( 'TB_CONFIG_PATH', TB_PATH . '/config' );

// Basic config.
$config = [
	'blocks' => [
		'namespace' => TB_BLOCKS_NAMESPACE,
	],
];

// Extend this array for any new block config file.
$block_config_files = [
	TB_CONFIG_PATH . '/heading.php',
	TB_CONFIG_PATH . '/single-field.php',
];

// Load all block config files of the above array.
foreach ( $block_config_files as $block_config_file ) {
	if ( file_exists( $block_config_file ) ) {
		$block_config = include $block_config_file;
		if ( is_array( $block_config ) && array_key_exists( 'slug', $block_config ) ) {
			$config['blocks'][ $block_config['slug'] ] = $block_config;
		}
	}
}

// Return complete config.
return $config;
