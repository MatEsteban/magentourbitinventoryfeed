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
        $config = $this->getConfig();

        //get product_id filter's value from config 
       $configProductFilterValue = isset($config->filter['product_id']) ? $config->filter['product_id'] : null;

        //ajax response for Left Multiselect
        if ($this->getRequest()->getPost('ajax_left')) {

            $tags = $this->getRequest()->getPost('tags');
            $categories = $this->getRequest()->getPost('collects');
            $stock = $this->getRequest()->getPost('stock');

            $filterArray = array(
                'category' => $categories ? implode(',', $categories) : '',
                'tag' => $tags ? implode(',', $tags) : '',
                'stock' => $stock ? : '',
                'product_id' => '',
            );

            $productCollection = $this->getFilteredProductCollection($filterArray);
            $optionsArray = $this->getOptionArray($productCollection);

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($optionsArray));
            
            return;
        }

        //ajax response for Right Multiselect
        if ($this->getRequest()->getPost('ajax_right')) { 

            if ($configProductFilterValue) {
                $filterArray = $this->getFilterArrayForProductId($config);

                $productCollection = $this->getFilteredProductCollection($filterArray);
                $optionsArray = $this->getOptionArray($productCollection);

                $this->getResponse()->setHeader('Content-type', 'application/json');
                $this->getResponse()->setBody(json_encode($optionsArray));
            }

            return;
        }

        $filterArray = $configProductFilterValue ? 
            $this->getFilterArrayForProductId($config) : $config->filter;

        $this->_products = Mage::getModel("inventoryfeed/list_product", $filterArray);

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

    //return array of products filtered by $filterArray
    public function getFilteredProductCollection($filterArray) {
        $products = Mage::getModel("inventoryfeed/list_product", $filterArray);

        return $products->getCollection()->getItems();
    }

    //return options for multiselect
    public function getOptionArray($collection) 
    {
        $optionsArray = array();

        foreach ($collection as $product) {
            $optionsArray[] = array(
                'id'    => $product->getId(),
                'name'  => $product->load($product->getId())->getName(),
            );
        }

        return $optionsArray;
    }

    public function getFilterArrayForProductId($config)
    {
        $config = $this->getConfig();

        return array(
            'category' => '',
            'tag' => '',
            'stock' => '',
            'product_id' => $config->filter['product_id'] ? : '',
        );
    }

    public function getConfig()
    {
        return Mage::getModel("inventoryfeed/config");
    }
}
