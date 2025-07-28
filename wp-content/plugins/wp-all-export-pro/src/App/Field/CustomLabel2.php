<?php

namespace Wpae\App\Field;


class CustomLabel2 extends Field
{
    const SECTION = 'advancedAttributes';

    public function getValue($snippetData)
    {
        $advancedAttributes = $this->feed->getSectionFeedData(self::SECTION);

        if(!isset($advancedAttributes['customLabel2'])) {
            return '';
        }

		$this->mappings = $advancedAttributes['customLabel2Mappings'];
		return $advancedAttributes['customLabel2'];
    }

    public function getFieldName()
    {
        return 'custom_label_2';
    }
}