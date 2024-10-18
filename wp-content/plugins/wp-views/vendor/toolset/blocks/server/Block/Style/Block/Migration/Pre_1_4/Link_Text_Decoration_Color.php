<?php

namespace ToolsetBlocks\Block\Style\Block\Migration\Pre_1_4;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Block\Style\Block\Migration\ITask;
use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;

/**
 * Class Link_Text_Decoration_Color
 *
 * Headline and Single Field forced text-decoration to be none for links via scss.
 * This has changed with 1.4. It introduced a panel "Link Styles" with font-style controls, which allow to disable
 * bold / italic / underline / striketrhough. So when the user wants to overwrite underline from the theme he can
 * now do it and we no longer need to force the reset. Anyway, for blocks created pre 1.4 we need to keep the behaviour
 * and therefor we need to add text-decoration none, for the case the user has nothing selected.
 *
 * In addition also the style "color" needs to be applied to links as before.
 *
 * @package ToolsetBlocks\Block\Style\Block
 */
class Link_Text_Decoration_Color implements ITask {

	/**
	 * @param ABlock $block
	 * @param FactoryStyleAttribute $factory_style_attribute
	 */
	public function migrate( ABlock $block, FactoryStyleAttribute $factory_style_attribute ) {
		$class_name = get_class( $block );
		if ( ! defined( "$class_name::KEY_MIGRATE_PRE_1_4_LINK_TEXT_DECORATION_COLOR" ) ) {
			// The block is not for this migration.
			return;
		}

		if ( strpos( $block->get_content(), 'data-last-update' ) !== false ) {
			// No pre 1.4 block.
			return;
		}

		$style_group_key = $class_name::KEY_MIGRATE_PRE_1_4_LINK_TEXT_DECORATION_COLOR;

		foreach ( [ Devices::DEVICE_DESKTOP, Devices::DEVICE_TABLET, Devices::DEVICE_PHONE ] as $device ) {
			// Pre 1.4. block. Get config['style'].
			$attributes = $block->get_style_attributes();
			$style_attributes = array_key_exists( 'style', $attributes ) ? $attributes['style'] : [];
			$device_storage_key = null; // Desktop use root.

			// Select device data. Not needed for Desktop as Destkop uses root.
			if ( in_array( $device, [ Devices::DEVICE_TABLET, Devices::DEVICE_PHONE ], true ) ) {
				$device_storage_key = $device;
				$style_attributes = array_key_exists( $device, $style_attributes ) ?
					$style_attributes[ $device ] :
					[];
			}

			// Text Decoration.
			// If defined, simply use the definition.
			// If not defined, we need to apply 'none' as 'none' was pre 1.4 applied through scss.
			$link_text_decoration = array_key_exists( 'textDecoration', $style_attributes ) ?
				$style_attributes['textDecoration'] :
				$factory_style_attribute->get_attribute( 'textDecoration', 'none' );

			$block->add_style_attribute(
				$link_text_decoration,
				$style_group_key,
				$device_storage_key
			);

			// Color.
			// Also add color as explicit rule for the a (as it was before). Probably not needed in most cases
			// but better take care of all cases, otherwise some clients will have some surprises after update.
			if ( array_key_exists( 'textColor', $style_attributes ) ) {
				$block->add_style_attribute(
					$style_attributes['textColor'],
					$style_group_key,
					$device_storage_key
				);
			}
		}
	}
}
