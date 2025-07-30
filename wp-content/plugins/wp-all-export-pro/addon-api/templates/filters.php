<?php
/**
 * @var $addon \Wpae\AddonAPI\PMXE_Addon_Base
 * @var $groups array
 */
?>
<optgroup label="<?php _e( $addon->name(), "wp_all_export_plugin" ); ?>">
    <?php
    foreach ( $groups as $key => $group ) {
        foreach ( $group['fields'] as $field ) {
            ?>
            <option value="<?php echo 'cf_' . esc_attr( $field['key'] ); ?>"
                    data-type="<?php echo $field['type']; ?>"><?php echo esc_html( $field['label'] ); ?></option>
            <?php
        }
    }
    ?>
</optgroup>
