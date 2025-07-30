<?php
/**
 * @var $addon \Wpae\AddonAPI\PMXE_Addon_Base
 * @var $groups array
 */

use function Wpae\AddonAPI\isGroupVisible;

?>

<p class="wpae-available-fields-group">
    <?php _e( $addon->name(), "wp_all_export_plugin" ); ?><span class="wpae-expander">+</span>
</p>

<div class="wp-all-export-jetengine-wrapper wpae-custom-field">
    <?php
    foreach ( $groups as $key => $group ) {
        $is_group_visible = isGroupVisible( $group );

        if ( ! $is_group_visible ) {
            continue;
        }

        ?>
        <div class="wpae-addon-field">
            <ul>
                <li>
                    <div class="default_column" rel="">
                        <label class="wpallexport-element-label"><?php echo esc_html( $group['label'] ); ?></label>
                        <input type="hidden" name="rules[]"
                               value="pmxe_<?php echo $addon->slug; ?>_<?php echo esc_attr( ( ! empty( $group['ID'] ) ) ? $group['ID'] : $group['id'] ); ?>"/>
                    </div>
                </li>
                <?php foreach ( $group['fields'] as $field ) { ?>
                    <li class="pmxe_<?php echo $addon->slug; ?>_<?php echo esc_attr( ( ! empty( $group['ID'] ) ) ? $group['ID'] : $group['id'] ); ?> wp_all_export_auto_generate">
                        <div class="custom_column" rel="<?php echo esc_attr( ( $i + 1 ) ); ?>">
                            <label class="wpallexport-xml-element"><?php echo $field['label']; ?></label>
                            <input type="hidden" name="ids[]" value="1"/>
                            <input type="hidden" name="cc_label[]" value="<?php echo esc_attr( $field['key'] ); ?>"/>
                            <input type="hidden" name="cc_php[]" value=""/>
                            <input type="hidden" name="cc_code[]" value=""/>
                            <input type="hidden" name="cc_sql[]" value=""/>
                            <input type="hidden" name="cc_options[]"
                                   value="<?php echo esc_attr( serialize( array_merge( $field, array( 'group_id' => ( ( ! empty( $group['ID'] ) ) ? $group['ID'] : $group['id'] ) ) ) ) ); ?>"/>
                            <input type="hidden" name="cc_type[]" value="<?php echo $addon->slug; ?>"/>
                            <input type="hidden" name="cc_value[]" value="<?php echo esc_attr( $field['key'] ); ?>"/>
                            <input type="hidden" name="cc_name[]" value="<?php echo esc_attr( $field['label'] ); ?>"/>
                            <input type="hidden" name="cc_settings[]" value=""/>
                            <input type="hidden" name="cc_combine_multiple_fields[]" value="">
                            <input type="hidden" name="cc_combine_multiple_fields_value[]" value="">
                        </div>
                    </li>
                    <?php
                    $i ++;
                }
                ?>
            </ul>
        </div>
    <?php } ?>
</div>
