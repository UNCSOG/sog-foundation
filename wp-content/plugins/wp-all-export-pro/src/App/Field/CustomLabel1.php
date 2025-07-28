<?php

namespace Wpae\App\Field;


class CustomLabel1 extends Field
{
    const SECTION = 'advancedAttributes';

    public function getValue($snippetData) {
	    $advancedAttributes = $this->feed->getSectionFeedData( self::SECTION );

	    if ( ! isset( $advancedAttributes['customLabel1'] ) ) {
		    return '';
	    }

	    $this->mappings = $advancedAttributes['customLabel1Mappings'];
	    return $advancedAttributes['customLabel1'];
    }

    public function getFieldName()
    {
        return 'custom_label_1';
    }
}