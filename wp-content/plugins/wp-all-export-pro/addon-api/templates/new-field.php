<?php
/**
 * @var $addon \Wpae\AddonAPI\PMXE_Addon_Base
 * @var $groups array
 */
?>
<?php foreach ( $groups as $key => $group ) { ?>
    <optgroup
            label="<?php esc_html_e( $addon->name(), "wp_all_export_plugin" ); ?> - <?php echo esc_attr( $group['label'] ); ?>">
        <?php
        foreach ( $group['fields'] as $field ) {
            $field_options = esc_html( serialize( array_merge( $field, [ 'group_id' => $group['id'] ] ) ) );
            ?>
            <option value="<?php echo $addon->slug; ?>" label="<?php echo esc_attr( $field['key'] ); ?>"
                    options="<?php echo esc_attr( $field_options ); ?>"><?php echo esc_html( $field['label'] ); ?></option>
            <?php
        }
        ?>
    </optgroup>
<?php } ?>
