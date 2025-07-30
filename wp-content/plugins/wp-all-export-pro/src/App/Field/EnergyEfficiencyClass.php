<?php

namespace Wpae\App\Field;


class EnergyEfficiencyClass extends Field
{
    const SECTION = 'advancedAttributes';

    public function getValue($snippetData)
    {
        $advancedAttributes = $this->feed->getSectionFeedData(self::SECTION);

        if(!isset($advancedAttributes['energyEfficiencyClass'])) {
            return '';
        }

		$this->mappings = $advancedAttributes['energyEfficiencyClassMappings'];
		return $advancedAttributes['energyEfficiencyClass'];
    }

    public function getFieldName()
    {
        return 'energy_efficiency_class';
    }
}