<?php

/**
 * Class Urbit_InventoryFeed_Model_Config_Tag
 */
class Urbit_InventoryFeed_Model_Config_Tag extends Urbit_InventoryFeed_Model_Config_Abstract
{
    /**
     * Provide available product tags as a value/label array
     * @return array
     */
    public function toOptionArray()
    {
        $storeID = Mage::app()->getStore()->getStoreId();

        /** @var Mage_Tag_Model_Resource_Tag_Collection $collection */
        $collection = Mage::getModel("tag/tag")
            ->getCollection()
            ->addFieldToFilter('status', Mage_Tag_Model_Tag::STATUS_APPROVED)
            ->setOrder('name', Varien_Data_Collection::SORT_ORDER_ASC)
        ;

        if ($storeID) {
            $collection->addStoreFilter($storeID);
        }

        $list = $collection
            ->load()
            ->toArray()
        ;

        $tags = array();

        foreach ($list['items'] as $tag) {
            if (!isset($tag['tag_id']) || !isset($tag['name'])) {
                continue;
            }

            $tags[] = array(
                'value' => $tag['tag_id'],
                'label' => $tag['name'],
            );
        }

        return $tags;
    }
}