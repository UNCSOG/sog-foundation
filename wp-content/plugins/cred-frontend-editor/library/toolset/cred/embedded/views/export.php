<?php

if (!defined('ABSPATH'))
    die('Security check');
if (!current_user_can(CRED_CAPABILITY)) {
    die('Access Denied');
}
$export_nonce = wp_create_nonce('cred-export-all');
?>
<p>
	<a style='margin-right:15px' class='button button-large cred-export-all' href='<?php echo CRED_CRED::route( '/Forms/exportAll?all&_wpnonce=' . $export_nonce ); ?>' target='_blank' title='<?php echo esc_attr( __('Export All Post Forms', 'wp-cred') ); ?>'><?php echo __('Export All Post Forms', 'wp-cred'); ?></a>
	<a style='margin-right:15px' class='button button-large cred-export-all' href='<?php echo CRED_CRED::route( '/Forms/exportAll?all&type=user&_wpnonce=' . $export_nonce ); ?>' target='_blank' title='<?php echo esc_attr( __('Export All User Forms', 'wp-cred') ); ?>'><?php echo __('Export All User Forms', 'wp-cred'); ?></a>
</p>