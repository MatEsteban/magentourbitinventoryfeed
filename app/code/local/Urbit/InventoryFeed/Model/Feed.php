<?php

/**
 * Class Urbit_InventoryFeed_Model_Feed
 */
class Urbit_InventoryFeed_Model_Feed
{
    const SCHEDULE_INTERVAL_1MIN   = '1MIN';
    const SCHEDULE_INTERVAL_15MIN  = '15MIN';
    const SCHEDULE_INTERVAL_30MIN  = '30MIN';
    const SCHEDULE_INTERVAL_45MIN  = '45MIN';
    const SCHEDULE_INTERVAL_HOURLY = 'HOURLY';

    const FEED_VERSION = '2017-06-28-1';

    /**
     * Valid products for using in feed
     * @var array
     */
    protected $data = array();

    /**
     * Products collection to process
     * @var Urbit_InventoryFeed_Model_List_Product
     */
    protected $collection = array();

    /**
     * Urbit_InventoryFeed_Model_Feed constructor.
     * @param Urbit_InventoryFeed_Model_List_Product $collection
     */
    public function __construct(Urbit_InventoryFeed_Model_List_Product $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Process products to use in feed
     */
    public function process()
    {
        $inventory = array();

        foreach ($this->collection as $product) {
            $feedInventory = Mage::getModel("inventoryfeed/feed_inventory", $product);

            if ($feedInventory->process()) {
                $inventory[] = $feedInventory->toArray();
            }
        }

        $this->data = $inventory;
    }

    public function toArray()
    {
        if (empty($this->data)) {
            $this->process();
        }

        $lang = Mage::app()->getLocale()->getLocaleCode();

        $version = $this->getFeedVersion();

        return array(
            '$schema' => "https://raw.githubusercontent.com/urbitassociates/urbit-merchant-feeds/master/schemas/inventory/{$version}/inventory.json",
            'content_language' => $lang,
            'attribute_language' => $lang,
            'content_type' => 'inventory',
            'target_country' => array(
                $lang,
            ),
            'version' => $version,
            'feed_format' => array(
                "encoding" => "UTF-8",
            ),
            'schedule' => array(
                'interval' => $this->getIntervalText(),
            ),
            'entities' => $this->data,
        );
    }

    /**
     * Get schedule interval value
     * @return string
     */
    public function getIntervalText()
    {
        /** @var Urbit_InventoryFeed_Model_Config $config */
        $config = Mage::getModel("inventoryfeed/config");

        foreach (array(
            1  => self::SCHEDULE_INTERVAL_1MIN,
            15 => self::SCHEDULE_INTERVAL_15MIN,
            30 => self::SCHEDULE_INTERVAL_30MIN,
            45 => self::SCHEDULE_INTERVAL_45MIN,
            60 => self::SCHEDULE_INTERVAL_HOURLY,
        ) as $time => $val) {
            if ($config->cron['cache_duration'] <= $time) {
                return $val;
            }
        }

        return self::SCHEDULE_INTERVAL_HOURLY;
    }

    public function getFeedVersion()
    {
        return self::FEED_VERSION;
    }
}
