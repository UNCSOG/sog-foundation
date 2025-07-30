<?php

namespace Wpae\App\Field;


class Condition extends Field
{
    const SECTION = 'basicInformation';
    
    public function getValue($snippetData)
    {
        $basicInformationData = $this->feed->getSectionFeedData(self::SECTION);

        $condition = $basicInformationData['condition'];
        $this->mappings = $basicInformationData['conditionMappings'];

        return $condition;
    }

    public function getFieldName()
    {
        return 'condition';
    }
}