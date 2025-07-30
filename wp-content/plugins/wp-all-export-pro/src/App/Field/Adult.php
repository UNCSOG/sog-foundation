<?php

namespace Wpae\App\Field;


class Adult extends Field
{
    const SECTION = 'advancedAttributes';

    public function getValue($snippetData)
    {
        $advancedAttributes = $this->feed->getSectionFeedData(self::SECTION);

        if($advancedAttributes['adult'] === self::CUSTOM_VALUE_TEXT) {
            return $advancedAttributes['adultCV'];
        } else {
            return $advancedAttributes['adult'];
        }
    }

    public function getFieldName()
    {
        return 'adult';
    }
}