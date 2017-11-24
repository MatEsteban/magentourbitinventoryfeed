<?php

/**
 * Class Urbit_InventoryFeed_Model_Config_Category
 */
class Urbit_InventoryFeed_Model_Config_Category extends Urbit_InventoryFeed_Model_Config_Abstract
{
    /**
     * Provide available categories as a value/label array
     * @return array
     */
    public function toOptionArray()
    {
        $categories = array();
        $storeID = Mage::app()->getStore()->getStoreId();

        $allCategoriesCollection = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('name')
            ->addFieldToFilter('level', array('gt'=>'0'))
        ;

        if ($storeID) {
            $allCategoriesCollection->setStore($storeID);
        }

        $allCategoriesArray = $allCategoriesCollection->load()->toArray();

        $categoriesArray = $allCategoriesCollection
            ->addAttributeToSelect('level')
            ->addAttributeToSort('path', 'asc')
            ->addFieldToFilter('is_active', array('eq'=>'1'))
            ->addFieldToFilter('level', array('gt'=>'1'))
            ->load()
            ->toArray()
        ;

        foreach ($categoriesArray as $categoryId => $category) {

            if (!isset($category['name'])) {
                continue;
            }

            $categoryIds = explode('/', $category['path']);
            $nameParts = array();

            foreach ($categoryIds as $catId) {
                if ($catId == 1 || !isset($allCategoriesArray[$catId]) || !isset($allCategoriesArray[$catId]['name'])) {
                    continue;
                }

                $nameParts[] = $allCategoriesArray[$catId]['name'];
            }

            $categories[$categoryId] = array(
                'value' => $categoryId,
                'label' => implode(' / ', $nameParts)
            );
        }

        return $categories;
    }
}
