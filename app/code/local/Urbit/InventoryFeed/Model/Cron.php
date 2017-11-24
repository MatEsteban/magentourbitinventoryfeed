<?php

/**
 * Class Urbit_InventoryFeed_Model_Cron
 */
class Urbit_InventoryFeed_Model_Cron extends Mage_Core_Helper_Abstract
{
	/**
	 * Generate feed file to cache
	 */
	public function generateFeed()
    {
        /** @var Urbit_InventoryFeed_Model_Config $config */
        $config = Mage::getModel("inventoryfeed/config");

        $products = Mage::getModel(
            "inventoryfeed/list_product",
            $config->filter
        );

        Mage::helper("inventoryfeed/feed")->generateFeed($products);
	}
}