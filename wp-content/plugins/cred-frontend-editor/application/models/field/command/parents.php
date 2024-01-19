<?php

/**
 * Transform shortcode attributes to field object for rendering legacy Types parent selector fields.
 *
 * @note Those relationships were based on the _wpcf_belongs_{parent_post_type_slug}_id custom field,
 * since a post could only have one parent on another post type.
 *
 * @note Legacy parents and hierarchical parents share the same execute logic.
 *
 * @since 2.0
 */
class CRED_Field_Command_Parents extends CRED_Field_Command_Hierarchical_Parents {

	const STATIC_FIELDS_INDEX = 'parents';

}
