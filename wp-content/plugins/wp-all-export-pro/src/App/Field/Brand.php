<?php

namespace Wpae\App\Field;


class Brand extends Field
{
    const SECTION = 'uniqueIdentifiers';

    public function getValue($snippetData)
    {
        $uniqueIdentifiersData = $this->feed->getSectionFeedData(self::SECTION);

        $value = $uniqueIdentifiersData['brand'];

        return $value;
    }

    public function getFieldName()
    {
        return 'brand';
    }
}