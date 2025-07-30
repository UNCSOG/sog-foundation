<?php

namespace Wpae\App\Field;


class AvailabilityDate extends Field
{
    const SECTION = 'availabilityPrice';

    const IN_STOCK = 'in_stock';

    const OUT_OF_STOCK = 'out_of_stock';

    public function getValue($snippetData)
    {
        $availabilityPrice = $this->feed->getSectionFeedData(self::SECTION);

        if(isset($availabilityPrice['availabilityDate'])) {
            return $availabilityPrice['availabilityDate'];
        } else {
            return '';
        }
    }

    public function getFieldName()
    {
        return 'availability_date';
    }

}