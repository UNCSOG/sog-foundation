<?php

namespace Wpae\App\Field;


class Pattern extends Field
{
    const SECTION = 'detailedInformation';

    public function getValue($snippetData)
    {
        $detailedInformationData = $this->feed->getSectionFeedData(self::SECTION);

        if($detailedInformationData['pattern'] == self::SELECT_FROM_WOOCOMMERCE_PRODUCT_ATTRIBUTES) {

            if(isset($detailedInformationData['patternAttribute'])){
                $patternAttribute = $detailedInformationData['patternAttribute'];
                return $patternAttribute;
            } else {
                return '';
            }

            
        } else if($detailedInformationData['pattern'] == self::CUSTOM_VALUE_TEXT) {
            return $detailedInformationData['patternCV'];
        } else {
            throw new \Exception('Unknown vale '.$detailedInformationData['pattern'].' for field pattern');
        }
    }

    public function getFieldName()
    {
        return 'pattern';
    }
}