<?php

/**
 * Menu Items management UI
 * Allows enabling/disabling default items, adding custom items, reordering, and removing custom items.
 */

if (!defined('ABSPATH')) exit;

$menu_items = \Utility_Bar\Menu_Items::get_items();
$option_key = \Utility_Bar\Menu_Items::OPTION_KEY;
?>

<div id="ub-menu-items-manager">

    <table class="widefat ub-menu-items-table" id="ub-menu-items-table">
        <thead>
            <tr>
                <th class="ub-col-sort"></th>
                <th class="ub-col-enabled">Enabled</th>
                <th class="ub-col-label">Label</th>
                <th class="ub-col-url">URL</th>
                <th class="ub-col-mobile">Hide on Mobile</th>
                <th class="ub-col-actions"></th>
            </tr>
        </thead>
        <tbody id="ub-menu-items-body">
            <?php foreach ($menu_items as $index => $item) :
                $is_default = !empty($item['is_default']);
                $enabled    = !empty($item['enabled']);
                $hide_mob   = !empty($item['hide_mobile']);
            ?>
                <tr class="ub-menu-item-row <?php echo $is_default ? 'ub-default-item' : 'ub-custom-item'; ?>" data-index="<?php echo $index; ?>">
                    <td class="ub-col-sort">
                        <span class="dashicons dashicons-menu ub-drag-handle" title="Drag to reorder"></span>
                    </td>
                    <td class="ub-col-enabled">
                        <input type="hidden" name="<?php echo esc_attr($option_key); ?>[<?php echo $index; ?>][enabled]" value="0">
                        <input type="checkbox"
                            name="<?php echo esc_attr($option_key); ?>[<?php echo $index; ?>][enabled]"
                            value="1"
                            <?php checked($enabled); ?>>
                    </td>
                    <td class="ub-col-label">
                        <input type="text"
                            name="<?php echo esc_attr($option_key); ?>[<?php echo $index; ?>][label]"
                            value="<?php echo esc_attr($item['label']); ?>"
                            class="regular-text"
                            <?php echo $is_default ? 'readonly' : ''; ?>>
                    </td>
                    <td class="ub-col-url">
                        <input type="url"
                            name="<?php echo esc_attr($option_key); ?>[<?php echo $index; ?>][url]"
                            value="<?php echo esc_attr($item['url']); ?>"
                            class="regular-text"
                            <?php echo $is_default ? 'readonly' : ''; ?>>
                    </td>
                    <td class="ub-col-mobile">
                        <input type="hidden" name="<?php echo esc_attr($option_key); ?>[<?php echo $index; ?>][hide_mobile]" value="0">
                        <input type="checkbox"
                            name="<?php echo esc_attr($option_key); ?>[<?php echo $index; ?>][hide_mobile]"
                            value="1"
                            <?php checked($hide_mob); ?>>
                    </td>
                    <td class="ub-col-actions">
                        <!-- Hidden fields to preserve id and is_default -->
                        <input type="hidden"
                            name="<?php echo esc_attr($option_key); ?>[<?php echo $index; ?>][id]"
                            value="<?php echo esc_attr($item['id']); ?>">
                        <input type="hidden"
                            name="<?php echo esc_attr($option_key); ?>[<?php echo $index; ?>][is_default]"
                            value="<?php echo $is_default ? '1' : '0'; ?>">
                        <?php if (!$is_default) : ?>
                            <button type="button" class="button ub-remove-item" title="Remove this item">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        <?php else : ?>
                            <span class="description">default</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top: 12px;">
        <button type="button" class="button button-secondary" id="ub-add-menu-item">
            <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
            Add Custom Menu Item
        </button>
    </p>

</div>

<script type="text/html" id="tmpl-ub-menu-item-row">
    <tr class="ub-menu-item-row ub-custom-item" data-index="{{data.index}}">
        <td class="ub-col-sort">
            <span class="dashicons dashicons-menu ub-drag-handle" title="Drag to reorder"></span>
        </td>
        <td class="ub-col-enabled">
            <input type="hidden" name="<?php echo esc_attr($option_key); ?>[{{data.index}}][enabled]" value="0">
            <input type="checkbox"
                name="<?php echo esc_attr($option_key); ?>[{{data.index}}][enabled]"
                value="1" checked>
        </td>
        <td class="ub-col-label">
            <input type="text"
                name="<?php echo esc_attr($option_key); ?>[{{data.index}}][label]"
                value=""
                class="regular-text"
                placeholder="Link text">
        </td>
        <td class="ub-col-url">
            <input type="url"
                name="<?php echo esc_attr($option_key); ?>[{{data.index}}][url]"
                value=""
                class="regular-text"
                placeholder="https://example.com">
        </td>
        <td class="ub-col-mobile">
            <input type="hidden" name="<?php echo esc_attr($option_key); ?>[{{data.index}}][hide_mobile]" value="0">
            <input type="checkbox"
                name="<?php echo esc_attr($option_key); ?>[{{data.index}}][hide_mobile]"
                value="1">
        </td>
        <td class="ub-col-actions">
            <input type="hidden"
                name="<?php echo esc_attr($option_key); ?>[{{data.index}}][id]"
                value="">
            <input type="hidden"
                name="<?php echo esc_attr($option_key); ?>[{{data.index}}][is_default]"
                value="0">
            <button type="button" class="button ub-remove-item" title="Remove this item">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </td>
    </tr>
</script>
