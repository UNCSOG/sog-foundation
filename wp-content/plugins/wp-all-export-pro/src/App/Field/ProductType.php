<?php

namespace Wpae\App\Field;


class ProductType extends Field
{
    const SECTION = 'productCategories';

    public function getValue($snippetData)
    {
        $productCategoriesData = $this->feed->getSectionFeedData(self::SECTION);

        if($productCategoriesData['productType'] == 'useWooCommerceProductCategories') {

            if($this->entry->post_type == 'product') {
                $productId = $this->entry->ID;
            } else if($this->entry->post_type == 'product_variation') {
                $productId = $this->entry->post_parent;
            }
            else {
                return '';
            }

            $categories = $this->getProductCategories($productId);

            return $categories;

        } else if($productCategoriesData['productType'] == self::CUSTOM_VALUE_TEXT) {
            return $productCategoriesData['productTypeCV'];
        } else {
            throw new \Exception('Unknown product type value '.$productCategoriesData['productType']);
        }

    }

    function getProductCategories( $productId ){

        $maxLength = 0;

        $output    = '';
        $taxonomy  = 'product_cat';

        $termIds = wp_get_post_terms( $productId, $taxonomy, array('fields' => 'ids') );

        foreach( $termIds as $termId ) {
            $termNames = [];

            $ancestors = get_ancestors( $termId, $taxonomy );

            foreach($ancestors  as $ancestorId ){
                $termNames[] = get_term( $ancestorId, $taxonomy )->name;
            }

            $termNames = array_reverse($termNames);

            $termNames[] = get_term( $termId, $taxonomy )->name;

            if(count($termNames) > $maxLength) {
                $maxLength = count($termNames);
                $output = implode(' > ', $termNames);
            }
        }

        return $output;
    }

    public function getFieldName()
    {
        return 'product_type';
    }


}