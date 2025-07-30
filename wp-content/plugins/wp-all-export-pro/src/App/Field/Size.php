<?php

namespace Wpae\App\Field;


class Size extends Field
{
    const SECTION = 'detailedInformation';

    public function getValue($snippetData)
    {
        $detailedInformationData = $this->feed->getSectionFeedData(self::SECTION);

        if($detailedInformationData['size'] == self::SELECT_FROM_WOOCOMMERCE_PRODUCT_ATTRIBUTES) {

            if(isset($detailedInformationData['sizeAttribute'])) {
                $sizeAttribute = $detailedInformationData['sizeAttribute'];
            } else {
                $sizeAttribute = '';
            }
            return $sizeAttribute;

        } else if($detailedInformationData['size'] == self::CUSTOM_VALUE_TEXT) {
            return $detailedInformationData['sizeCV'];
        } else {
            throw new \Exception('Unknown value '.$detailedInformationData['size'].' for field size');
        }
    }

    public function getFieldName()
    {
        return 'size';
    }


}