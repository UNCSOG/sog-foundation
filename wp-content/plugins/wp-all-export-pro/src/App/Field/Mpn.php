<?php

namespace Wpae\App\Field;


class Mpn extends Field
{
    const SECTION = 'uniqueIdentifiers';

    public function getValue($snippetData)
    {
        $uniqueIdentifiersData = $this->feed->getSectionFeedData(self::SECTION);

        return $uniqueIdentifiersData['mpn'];
    }

    public function getFieldName()
    {
        return 'mpn';
    }
    
}