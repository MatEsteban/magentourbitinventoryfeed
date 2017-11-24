<?php

/**
 * Class Urbit_InventoryFeed_IndexController
 */
class Urbit_InventoryFeed_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Urbit_InventoryFeed_Model_List_Product
     */
    protected $_products;

    /**
     * Index action for plugin frontend
     * Show product feed in json format
     */
    public function IndexAction()
    {
	    /** @var Urbit_InventoryFeed_Model_Config $config */
        $config = Mage::getModel("inventoryfeed/config");

        /**
         * @var UrbitInventoryFeed_Model_List_Product
         */
        $this->_products = Mage::getModel("inventoryfeed/list_product", $config->filter);

        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody($this->getProductsJson());
        ;
    }

    /**
     * Return json feed data
     * @return string
     */
    public function getProductsJson()
    {
        /** @var Urbit_InventoryFeed_Helper_Feed $feedHelper */
        $feedHelper = Mage::helper("inventoryfeed/feed");

        if (!$feedHelper->checkCache()) {
            $feedHelper->generateFeed($this->_products);
        }

        return $feedHelper->getDataJson();
    }
}
