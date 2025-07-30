<?php

namespace Wpae\App\Field;


class SizeType extends Field
{
    const SECTION = 'detailedInformation';

    public function getValue($snippetData)
    {
        $detailedInformationData = $this->feed->getSectionFeedData(self::SECTION);

        if(isset($detailedInformationData['sizeType'])) {
            
            $sizeType = $detailedInformationData['sizeType'];
            $this->mappings = $detailedInformationData['sizeTypeMappings'];
            return $sizeType;
        } else {
            return '';
        }

    }

    public function getFieldName()
    {
        return 'size_type';
    }
}