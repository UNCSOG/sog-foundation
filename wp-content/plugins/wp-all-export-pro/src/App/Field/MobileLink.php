<?php

namespace Wpae\App\Field;


class MobileLink extends Field
{
    const SECTION = 'basicInformation';

    public function getValue($snippetData)
    {
        $basicInformationData = $this->feed->getSectionFeedData(self::SECTION);

        if(!isset($basicInformationData['mobileLink'])) {
            return '';
        }

        return $basicInformationData['mobileLink'];

    }

    public function getFieldName()
    {
        return 'mobile_link';
    }
}