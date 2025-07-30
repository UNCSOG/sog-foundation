<?php

namespace Wpae\App\Field;


class Multipack extends Field
{
    const SECTION = 'advancedAttributes';

    public function getValue($snippetData)
    {
        $advancedAttributes = $this->feed->getSectionFeedData(self::SECTION);

        if(!isset($advancedAttributes['multipack'])) {
            return '';
        }

        return $advancedAttributes['multipack'];
    }

    public function getFieldName()
    {
        return 'multipack';
    }
}