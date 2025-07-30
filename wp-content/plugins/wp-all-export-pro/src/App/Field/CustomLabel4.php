<?php

namespace Wpae\App\Field;


class CustomLabel4 extends Field
{
    const SECTION = 'advancedAttributes';

    public function getValue($snippetData)
    {
        $advancedAttributes = $this->feed->getSectionFeedData(self::SECTION);

        if(!isset($advancedAttributes['customLabel4'])) {
            return '';
        }

		$this->mappings = $advancedAttributes['customLabel4Mappings'];
		return $advancedAttributes['customLabel4'];
    }

    public function getFieldName()
    {
        return 'custom_label_4';
    }
}