<?php

namespace Wpae\App\Field;


class ShippingLength extends Field
{
    const SECTION = 'shipping';

    public function getValue($snippetData)
    {
        $shippingData = $this->feed->getSectionFeedData(self::SECTION);

        if ($shippingData['dimensions'] == 'useWooCommerceProductValues') {

            $currentUnit = get_option('woocommerce_dimension_unit');
            $toUnit = $shippingData['convertTo'];

            $product = $_product = wc_get_product($this->entry->ID);

            if ($currentUnit !== $toUnit) {

                $shippingLength = $product->get_length();

                if (is_numeric($shippingLength)) {
                    $length = wc_get_dimension($shippingLength, $toUnit, $currentUnit);
                } else {
                    $length = $shippingLength;
                }
            } else {
                $length = $product->get_length();
            }

            if ($length) {
                return $length . ' ' . $toUnit;
            } else {
                return '';
            }
        } else {
            if (isset($shippingData['dimensionsCV'])) {
                return $shippingData['dimensionsCV'];
            } else {
                return '';
            }

        }
    }

    public function getFieldName()
    {
        return 'shipping_length';
    }
}