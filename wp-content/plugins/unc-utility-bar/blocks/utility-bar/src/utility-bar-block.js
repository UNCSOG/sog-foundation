/**
 * The Utility bar block
 * 
 */

//libraries
import { registerBlockType } from '@wordpress/blocks';
//import './utility-bar-block-editor-style.scss'
import './utility-bar-block.scss'

//local imports
import json from '../block.json';
import { Edit } from './edit';

registerBlockType(json, {
    edit: Edit,
});