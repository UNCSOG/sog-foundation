<?php

namespace Wpae\App\Field;


class Availability extends Field
{
    const SECTION = 'availabilityPrice';

    const IN_STOCK = 'in_stock';

    const OUT_OF_STOCK = 'out_of_stock';

    public function getValue($snippetData)
    {
        $availabilityPrice = $this->feed->getSectionFeedData(self::SECTION);

        if($availabilityPrice['availability'] == 'useWooCommerceStockValues') {
            $product = wc_get_product( $this->entry->ID );
            if($product->is_in_stock()) {
                return self::IN_STOCK;
            } else {
                return self::OUT_OF_STOCK;
            }
        } else if($availabilityPrice['availability'] == self::CUSTOM_VALUE_TEXT) {
            return $availabilityPrice['availabilityCV'];
        } else {
            throw new \Exception('Unknown value for availability: '. $availabilityPrice['availability']);
        }
    }

    public function getFieldName()
    {
        return 'availability';
    }

}