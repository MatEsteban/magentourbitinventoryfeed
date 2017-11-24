<?php

/**
 * Class Urbit_InventoryFeed_Helper_Feed
 */
class Urbit_InventoryFeed_Helper_Feed extends Mage_Core_Helper_Abstract
{
    /**
     * feed file generation
     * @param Urbit_InventoryFeed_Model_List_Product $collection product collection
     * @return string
     */
    public function generateFeed(Urbit_InventoryFeed_Model_List_Product $collection)
    {
        $inventory = Mage::getModel("inventoryfeed/feed", $collection)->toArray();

        $json = json_encode($inventory, JSON_PRETTY_PRINT);

        $this->setDataJson($json);

        return $json;
    }

    /**
     * Check cache on expire. True if it still valid
     * @return bool
     */
    public function checkCache()
    {
        $cacheDuration = Mage::getModel("inventoryfeed/config")->get("cron/cache_duration");

        $filePath = $this->getCacheFilePath();

        return file_exists($filePath) && (time() - filemtime($filePath)) < ($cacheDuration * 60);
    }

    /**
     * Get feed cache file path
     * @return string
     */
    public function getCacheFilePath()
    {
        $storeID = Mage::app()->getStore()->getId();

        return Mage::getBaseDir('cache') . DS . "inventoryfeed_{$storeID}.json";
    }

    /**
     * Get data from cache file
     * @return mixed
     */
    public function getData()
    {
        return json_decode($this->getDataJson(), true);
    }

    /**
     * Get plain json from cache file
     * @return string
     */
    public function getDataJson()
    {
        return file_get_contents($this->getCacheFilePath());
    }

    /**
     * Set data to cache file
     * @param mixed $data
     * @return bool|int
     */
    public function setData($data)
    {
        return $this->setDataJson(json_encode($data));
    }

    /**
     * Set json data to cache file
     * @param string $json
     * @return bool|string
     */
    public function setDataJson($json)
    {
        return file_put_contents($this->getCacheFilePath(), $json);
    }
}