<?php

namespace Wpae\App\Field;


class SalePriceEffectiveDate extends Field
{
    const SECTION = 'availabilityPrice';

    const IN_STOCK = 'in_stock';

    const OUT_OF_STOCK = 'out_of_stock';

    public function getValue($snippetData)
    {
        $availabilityPrice = $this->feed->getSectionFeedData(self::SECTION);

        if(isset($availabilityPrice['salePriceEffectiveDate'])) {
            return $availabilityPrice['salePriceEffectiveDate'];
        } else {
            return '';
        }

    }

    public function getFieldName()
    {
        return 'sale_price_effective_date';
    }

}