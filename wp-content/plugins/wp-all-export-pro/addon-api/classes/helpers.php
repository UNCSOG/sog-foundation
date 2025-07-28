<?php

namespace Wpae\AddonAPI;

/**
 * Renders a view.
 *
 * @param string $viewPath
 * @param array $data
 * @param string|null $defaultView
 * @param bool $echo
 *
 * @return string|null
 * @throws \Exception
 */
function view(
    string $viewPath,
    array $data,
    $defaultView = null,
    $echo = true
) {
    $path            = PMXE_ROOT_DIR . '/addon-api' . '/templates/';
    $filePath        = $path . $viewPath . '.php';
    $defaultFilePath = $path . $defaultView . '.php';

    extract( $data );

    $view = $filePath;

    if ( ! is_file( $view ) ) {
        if ( $defaultView ) {
            $view = $defaultFilePath;
        } else {
            throw new \Exception( "The requested template file $filePath was not found." );
        }
    }

    if ( $echo ) {
        include $view;
    } else {
        ob_start();
        include $view;

        return ob_get_clean();
    }
}

function isGroupVisible( $group ) {
    $rules_array      = array();
    $is_group_visible = false;
    $rules            = false;

    if ( ! empty( $group['location'] ) && is_array( $group['location'] ) && ! isset( $group['location']['rules'] ) ) {
        $rules_array = $group['location'];
    } elseif ( ! empty( $group['location'] ) && is_array( $group['location'] ) && isset( $group['location']['rules'] ) && is_array( $group['location']['rules'] ) ) {
        $rules_array = $group['location']['rules'];
        $rules       = true;
    }

    if ( ! empty( $rules_array ) ) {

        foreach ( $rules_array as $locationRule ) {
            if ( $rules === false ) {
                $rule = array_shift( $locationRule );
            } else {
                $rule = $locationRule;
            }

            if ( \XmlExportEngine::$is_user_export && $rule['param'] == 'user_form' ) {
                $is_group_visible = true;
                break;
            } elseif ( \XmlExportEngine::$is_taxonomy_export && $rule['param'] == 'taxonomy' ) {
                $is_group_visible = true;
                break;
            } elseif ( 'specific' == \XmlExportEngine::$exportOptions['export_type'] && $rule['param'] == 'post_type' ) {
                if ( $rule['operator'] == '==' && in_array( $rule['value'], \XmlExportEngine::$post_types ) ) {
                    $is_group_visible = true;
                    break;
                } elseif ( $rule['operator'] != '==' && ! in_array( $rule['value'], \XmlExportEngine::$post_types ) ) {
                    $is_group_visible = true;
                    break;
                }
            } elseif ( 'advanced' == \XmlExportEngine::$exportOptions['export_type'] ) {
                $is_group_visible = true;
                break;
            } // Include local blocks field groups except when exporting Users.
            elseif ( ! \XmlExportEngine::$is_user_export && $rule['param'] == 'block' ) {
                $is_group_visible = true;
                break;
            }
        }
    } else {
        $is_group_visible = true;
    }

    return $is_group_visible;
}

/**
 * @param array $field
 *
 * @return string
 */
function getFieldClass( $field, $extras = [] ) {
    $class = 'Wpae\AddonAPI\PMXE_Addon_' . ucfirst( $field['type'] ) . '_Field';

	if (isset($extras[$field['type']])) {
		$class = $extras[$field['type']];
	}

    return class_exists( $class ) ? $class : PMXE_Addon_Field::class;
}

/**
 * Make a string safe for use as an XML element name.
 *
 * @param string $elName
 *
 * @return string
 */
function normalizeElementName( $elName ) {
    $elName = str_replace( ' ', '_', $elName );

    // TODO: Add more replacements here.
    return $elName;
}

// Class Helpers

trait Singleton {
    /** @var self|null */
    private static $instance = null;

    /**
     * @return self
     */
    final public static function getInstance(): self {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // Prevent cloning of the instance
    public function __clone() {
    }

    // Prevent deserialization of the instance
    public function __wakeup() {
    }
}


trait Updatable {
    public ?PMXE_Addon_Updater $updater;

    public function initEed() {
        $api_url     = $this->getApiUrl();
        $plugin_file = $this->getPluginFile();

        if ( empty( $api_url ) ) {
            return;
        }

        $this->updater = new PMXE_Addon_Updater(
            $api_url,
            $plugin_file,
            [
                'version'   => $this->version,       // current version number
                'license'   => false,                // license key (used get_option above to retrieve from DB)
                'item_name' => $this->getEddName(),  // name of this plugin
                'author'    => $this->getEddAuthor() // author of this plugin
            ]
        );
    }

    public function getEddName() {
        return $this->name();
    }

    public function getEddAuthor() {
        return 'Soflyy';
    }

    public function getPluginFile() {
        return $this->rootDir . '/plugin.php';
    }

    public function getApiUrl() {
        $options = get_option( 'PMXE_Plugin_Options' );
        $api_url = null;

        if ( ! empty( $options['info_api_url_new'] ) ) {
            $api_url = $options['info_api_url_new'];
        } elseif ( ! empty( $options['info_api_url'] ) ) {
            $api_url = $options['info_api_url'];
        }

        return $api_url;
    }
}

trait NotAString {
    public function toString() {
        return '';
    }
}
