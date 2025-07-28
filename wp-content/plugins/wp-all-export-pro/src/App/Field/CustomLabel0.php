<?php

namespace Wpae\App\Field;


class CustomLabel0 extends Field
{
    const SECTION = 'advancedAttributes';

    public function getValue($snippetData)
    {
        $advancedAttributes = $this->feed->getSectionFeedData(self::SECTION);
        
        if(!isset($advancedAttributes['customLabel0'])) {
            return '';
        }

		$this->mappings = $advancedAttributes['customLabel0Mappings'];
        return $advancedAttributes['customLabel0'];
    }

    public function getFieldName()
    {
        return 'custom_label_0';
    }
}