<?php

namespace Wpae\App\Field;


class ExpirationDate extends Field
{
    const SECTION = 'advancedAttributes';

    public function getValue($snippetData)
    {
        $advancedAttributes = $this->feed->getSectionFeedData(self::SECTION);

        if(!isset($advancedAttributes['expirationDate'])) {
            return '';
        }
        
        return $advancedAttributes['expirationDate'];
    }

    public function getFieldName()
    {
        return 'expiration_date';
    }
}