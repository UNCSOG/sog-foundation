<?php

/**
 * Menu Items Manager
 *
 * Manages the utility bar menu items, including default items,
 * enabling/disabling them, and adding custom items.
 *
 * @package Utility_Bar
 */

namespace Utility_Bar;

if (!defined('ABSPATH')) {
    exit;
}

class Menu_Items {

    /**
     * Option key for storing menu items configuration
     */
    const OPTION_KEY = '_unc_utility_bar_menu_items';

    /**
     * Get the default menu items that ship with the plugin.
     *
     * @return array
     */
    public static function get_defaults() {
        return [
            [
                'id'          => 'accessibility',
                'label'       => 'Accessibility',
                'url'         => 'https://www.unc.edu/about/accessibility/',
                'enabled'     => true,
                'hide_mobile' => false,
                'is_default'  => true,
            ],
            [
                'id'          => 'events',
                'label'       => 'Events',
                'url'         => 'https://www.unc.edu/events/',
                'enabled'     => true,
                'hide_mobile' => true,
                'is_default'  => true,
            ],
            [
                'id'          => 'libraries',
                'label'       => 'Libraries',
                'url'         => 'http://library.unc.edu/',
                'enabled'     => true,
                'hide_mobile' => true,
                'is_default'  => true,
            ],
            [
                'id'          => 'maps',
                'label'       => 'Maps',
                'url'         => 'https://maps.unc.edu/',
                'enabled'     => true,
                'hide_mobile' => false,
                'is_default'  => true,
            ],
            [
                'id'          => 'departments',
                'label'       => 'Departments',
                'url'         => 'https://www.unc.edu/a-z/',
                'enabled'     => true,
                'hide_mobile' => false,
                'is_default'  => true,
            ],
            [
                'id'          => 'connectcarolina',
                'label'       => 'ConnectCarolina',
                'url'         => 'https://connectcarolina.unc.edu/',
                'enabled'     => true,
                'hide_mobile' => true,
                'is_default'  => true,
            ],
            [
                'id'          => 'unc-search',
                'label'       => 'UNC Search',
                'url'         => 'https://www.unc.edu/search',
                'enabled'     => true,
                'hide_mobile' => false,
                'is_default'  => true,
            ],
        ];
    }

    /**
     * Get the saved menu items configuration.
     * Falls back to defaults if nothing is saved.
     *
     * @return array
     */
    public static function get_items() {
        $saved = get_option(self::OPTION_KEY, false);

        if (false === $saved || !is_array($saved)) {
            return self::get_defaults();
        }

        return $saved;
    }

    /**
     * Get only the enabled menu items, ready for rendering.
     *
     * @return array
     */
    public static function get_enabled_items() {
        $items = self::get_items();
        return array_filter($items, function ($item) {
            return !empty($item['enabled']);
        });
    }

    /**
     * Sanitize the menu items array before saving.
     *
     * @param mixed $input The raw input from the settings form.
     * @return array Sanitized menu items.
     */
    public static function sanitize($input) {
        if (!is_array($input)) {
            return self::get_defaults();
        }

        $sanitized = [];

        foreach ($input as $item) {
            if (empty($item['label']) || empty($item['url'])) {
                continue;
            }

            $sanitized[] = [
                'id'          => !empty($item['id']) ? sanitize_key($item['id']) : sanitize_key($item['label']),
                'label'       => sanitize_text_field($item['label']),
                'url'         => esc_url_raw($item['url']),
                'enabled'     => !empty($item['enabled']),
                'hide_mobile' => !empty($item['hide_mobile']),
                'is_default'  => !empty($item['is_default']),
            ];
        }

        // Ensure all defaults are present (they can be disabled but not removed)
        $default_ids = array_column(self::get_defaults(), 'id');
        $saved_ids = array_column($sanitized, 'id');

        foreach (self::get_defaults() as $default) {
            if (!in_array($default['id'], $saved_ids)) {
                $default['enabled'] = false;
                $sanitized[] = $default;
            }
        }

        return $sanitized;
    }
}
