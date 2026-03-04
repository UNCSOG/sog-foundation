/**
 * Alert Service Banner
 * and my first block from scratch in a long while :)
 * 
 * from
 * https://wp-gb.com/contrastchecker/
 * https://github.com/WordPress/gutenberg/tree/trunk/packages/block-editor/src/components/contrast-checker
 * https://awhitepixel.com/blog/how-to-add-color-settings-to-your-custom-gutenberg-block/
 * https://github.com/WordPress/gutenberg/issues/31223
 * 
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { EditBlock } from './editBlock';
import './alert-banner-admin.scss';

/**
 * Internal dependencies
 * so we dont have to write this twice
 */
import json from '../block.json';
const { name } = json;

registerBlockType(json, {
    edit: EditBlock,
});

// window.addEventListener("load", (event) => {
//     console.log('back');
// });