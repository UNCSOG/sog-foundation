<?php

namespace Wpae\App\Field;


class ShippingDimensions extends Field
{
    const SECTION = 'shipping';

    public function getValue($snippetData)
    {
        $shippingData = $this->feed->getSectionFeedData(self::SECTION);

        if($shippingData['dimensions'] == 'useWooCommerceProductValues') {

            $currentUnit = get_option('woocommerce_dimension_unit');
            $toUnit = $shippingData['convertTo'];

            $product = wc_get_product($this->entry->ID);

            $product_width = $product->get_width();

            if(is_numeric($product_width)) {
                $width = wc_get_dimension($product->get_width(), $toUnit, $currentUnit);
            } else {
                $width = '';
            }

            if($width) {
                return $width . ' ' . $toUnit;
            } else {
                return '';
            }
        } else {
            if(isset($shippingData['dimensionsCV'])) {
                return $shippingData['dimensionsCV'];
            } else {
                return '';
            }
        }
    }

    public function getFieldName()
    {
        return 'shipping_dimensions';
    }
}