<?php

/**
 * Class Urbit_InventoryFeed_Model_Config_Attribute
 */
class Urbit_InventoryFeed_Model_Config_Attribute extends Urbit_InventoryFeed_Model_Config_Abstract
{
    /**
     * Provide available product tags as a value/label array
     * @return array
     */
    public function toOptionArray()
    {
        $storeID = Mage::app()->getStore()->getStoreId();

        /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $collection */
        $collection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addFieldToSelect("*")
            ->setOrder('frontend_label', Varien_Data_Collection::SORT_ORDER_ASC)
        ;


        $list = $collection
            ->load()
            ->toArray()
        ;

        $attributes = array(
            array(
                'value' => '',
                'label' => 'Not setted',
            ),
        );

        foreach ($list['items'] as $attr) {
            if (!isset($attr['is_user_defined']) || !isset($attr['attribute_code']) || !isset($attr['frontend_label'])) {
                continue;
            }

            $hasLabel = strlen(trim($attr['frontend_label']));

            if ($hasLabel) {
                $attributes[] = array(
                    'value' => $attr['attribute_code'],
                    'label' => $attr['frontend_label'],
                );
            }
        }

        return $attributes;
    }
}
