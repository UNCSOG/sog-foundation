<?php

namespace Wpae\App\Field;


class CustomLabel3 extends Field
{
    const SECTION = 'advancedAttributes';

    public function getValue($snippetData)
    {
        $advancedAttributes = $this->feed->getSectionFeedData(self::SECTION);

        if(!isset($advancedAttributes['customLabel3'])) {
            return '';
        }

        $this->mappings = $advancedAttributes['customLabel3Mappings'];
		return $advancedAttributes['customLabel3'];
    }

    public function getFieldName()
    {
        return 'custom_label_3';
    }
}