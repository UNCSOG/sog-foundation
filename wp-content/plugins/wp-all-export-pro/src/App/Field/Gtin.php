<?php

namespace Wpae\App\Field;


class Gtin extends Field
{
    const SECTION = 'uniqueIdentifiers';
    
    public function getValue($snippetData)
    {
        $uniqueIdentifiersData = $this->feed->getSectionFeedData(self::SECTION);

        return $uniqueIdentifiersData['gtin'];
    }

    public function getFieldName()
    {
        return 'gtin';
    }


}