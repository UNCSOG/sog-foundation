<?php if (!defined('ABSPATH'))  die('Security check'); ?>
<p class='cred-explain-text'>
	<?php _e('These are the messages displayed for different form actions. They are used only for validation of generic fields and some basic fields, for example, the post title and username. If you are using WPML, you can translate these messages on the WPML -> String Translation page.', 'wp-cred'); ?>
</p>
<table class="cred-form-texts">
<tbody>
<?php
foreach ($messages as $msgid=>$msg)
{
    if (isset($descriptions[$msgid])) {
    ?><tr>
        <td class="cred-form-texts-desc"><?php echo $descriptions[$msgid]; ?></td>
        <td class="cred-form-texts-msg"><input name='_cred[extra][messages][<?php echo $msgid; ?>]' type='text' value='<?php echo esc_attr($msg); ?>' /></td>
    </tr><?php }
}
?>
</tbody>
</table>