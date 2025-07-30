<?php

namespace Wpae\App\Field;


class PromotionId extends Field
{
    const SECTION = 'advancedAttributes';

    public function getValue($snippetData)
    {
        $advancedAttributes = $this->feed->getSectionFeedData(self::SECTION);

        if(!isset($advancedAttributes['promotionId'])) {
            return '';
        }
        
        $promotionId = $advancedAttributes['promotionId'];
		$this->mappings = $advancedAttributes['promotionIdMappings'];
		return $promotionId;
    }

    public function getFieldName()
    {
        return 'promotion_id';
    }
}