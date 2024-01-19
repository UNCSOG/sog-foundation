<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<?php echo $js; ?>
<div style='display:none'>
<?php $iframehandle=$link_id.'_iframe'; ?>
<iframe name='<?php echo $iframehandle; ?>' id='<?php echo $iframehandle; ?>' src=''></iframe>
</div>
<a href='<?php echo $link; ?>' <?php if ($link_atts!==false) echo $link_atts; ?> id='<?php echo $link_id; ?>' target='<?php echo $iframehandle; ?>' onclick='return _cred_cred_delete_post_handler(true, this, false, false, "<?php echo esc_js($message); ?>", <?php echo intval($message_show); ?>);'><?php echo $text; ?></a>
