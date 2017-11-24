<?php

/**
 * Class Urbit_InventoryFeed_Model_Feed_Inventory
 *
 * Special properties:
 * @property $isSimple
 * @property $currencyCode
 *
 * Field properties (for feed $data property):
 * @property string $id
 * @property array $prices
 * @property array $inventory
 */
class Urbit_InventoryFeed_Model_Feed_Inventory
{
    /**
     * Array with product fields
     * @var array
     */
    protected $data = array();

    /**
     * Magento product object
     * @var Mage_Catalog_Model_Product
     */
    protected $product;

    /**
     * Magento product resource object
     * @var Mage_Catalog_Model_Resource_Product
     */
    protected $resource;

    /**
     * @var bool
     */
    protected $isTableOption = false;

    /**
     * @var array
     */
    protected $tableOptions;

    /**
     * Urbit_InventoryFeed_Model_Feed_Product constructor.
     * @param Mage_Catalog_Model_Product $product
     */
    public function __construct(Mage_Catalog_Model_Product $product)
    {
        $this->product = $product->load($product->getId());
        $this->resource = $product->getResource();

        //get is table option
        $config = Mage::getStoreConfig('inventoryfeed_config/fields/inventory', Mage::app()->getStore()->getStoreId());

        if (strlen($config) > 2) {
            $config = explode(',', $config);

            if ($config[0] == 'table' && count($config) >= 5) {
                $this->isTableOption = true;
                $this->tableOptions = array(
                    'table'      => $config[1],
                    'product_id' => $config[2],
                    'location'   => $config[3],
                    'qty'        => $config[4],
                );
            }
        }
    }

    /**
     * Get feed product data
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Get feed product data fields
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        if (stripos($name, 'is') === 0 && method_exists($this, $name)) {
            return $this->{$name}();
        }

        $getMethod = "get{$name}";

        if (method_exists($this, $getMethod)) {
            return $this->{$getMethod}();
        }

        return null;
    }

    /**
     * Set feed product data fields
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $setMethod = "set{$name}";

        if (method_exists($this, $setMethod)) {
            $this->{$setMethod}($value);

            return;
        }

        $this->data[$name] = $value;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return $this|mixed|null
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        $property = strtolower(preg_replace("/^unset/", "", $name));
        $propertyExist = isset($this->data[$property]);

        if ($propertyExist) {
            if (stripos($name, 'unset') === 0) {
                unset($this->data[$property]);

                return $this;
            }

            if (stripos($name, 'get') === 0) {
                return $this->{$property};
            }

            if (stripos($name, 'set') === 0 && isset($arguments[0])) {
                $this->{$property} = $arguments[0];

                return $this;
            }
        }

        throw new Exception("Unknown method {$name}");
    }

    /**
     * Process Magento product and get data for feed
     * @return bool
     */
    public function process()
    {
        if (!$this->isSimple) {
            return false;
        }

        $this->processId();

        $positive_quantity = ($this->isTableOption) ? $this->processTableOptions() : $this->processInventory();

        if (!$positive_quantity) {
            return false;
        }

        $this->processPrices();

        return true;
    }

    /**
     * Process table options
     */
    protected function processTableOptions()
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $productId = $this->product->getId();

        try {
            $data = $connection->query("
                SELECT {$this->tableOptions['product_id']}, {$this->tableOptions['location']}, {$this->tableOptions['qty']}
                FROM {$this->tableOptions['table']}
                WHERE {$this->tableOptions['product_id']} = '{$productId}'
            ")->fetchAll(PDO::FETCH_ASSOC)
            ;

            if (!empty($data[0])) {
                foreach ($data as $inventoryElem) {
                    if ((int)$inventoryElem[$this->tableOptions['qty']] > 0) {
                        $inventory[] = array(
                            'location' => $inventoryElem[$this->tableOptions['location']],
                            'quantity' => $inventoryElem[$this->tableOptions['qty']],
                        );
                    }
                }

                if (!$inventory) {
                    return false;
                }

                $this->inventory = $inventory;
            } else {
                throw new \Exception('');
            }
        } catch (\Exception $e) {
            Mage::log("Urber inventory feed fail with message: {$e->getMessage()}! (table) Product Id: {$productId}");

            return false;
        }

        return true;
    }

    /**
     * Process product id
     */
    protected function processId()
    {
        $product = $this->product;
        $sku = $product->getSku();

        $this->id = ($sku) ? $sku : (string)$product->getId();
    }

    /**
     * Process product prices
     */
    protected function processPrices()
    {
        $product = $this->product;

        // get tax country from module config
        $configTaxCountry = $this->model("inventoryfeed/config", 'get', 'tax');
        $configTaxCountry = $configTaxCountry['tax_country'];

        //get default shop's country
        $countryCode = Mage::getStoreConfig('general/country/default');

        //get taxes filtered by product's tax class
        $taxCol = Mage::getModel('tax/calculation')->getResourceCollection();
        $productTaxClassId = $product->getTaxClassId();
        $taxCol->addFieldtoFilter('product_tax_class_id', $productTaxClassId);
        $rates = $taxCol->load()->getData();

        $productTax = null;
        $defaultTax = null;

        if ($rates) {

            foreach ($rates as $rate) {
                $rateInfo = Mage::getSingleton('tax/calculation_rate')->load($rate['tax_calculation_rate_id']);

                $countryId = $rateInfo->getTaxCountryId();

                if ($countryId == $configTaxCountry) {
                    $productTax = $rateInfo->getRate();
                } elseif ($countryId == $countryCode) {
                    $defaultTax = $rateInfo->getRate();
                }
            }
        }

        // Regular price
        $prices = array(
            array(
                "currency" => $this->currencyCode,
                "value" => $this->getPriceWithTax($this->product->getPrice(), $productTax, $defaultTax) * 100,
                "type"     => "regular",
                "vat"      => $this->getFormattedVat($productTax, $defaultTax),
            ),
        );

        // Special price with date range

        if ($product->getSpecialPrice()) {
            $from = new DateTime($product->getSpecialFromDate());
            $from = $from->format('c');

            $to = new DateTime($product->getSpecialToDate());
            $to = $to->format('c');

            $prices[] = array(
                "currency"             => $this->currencyCode,
                "value"                => $this->getPriceWithTax($product->getSpecialPrice(), $productTax, $defaultTax) * 100,
                "type"                 => "sale",
                'price_effective_date' => "{$from}/{$to}",
                "vat"                  => $this->getFormattedVat($productTax, $defaultTax),
            );
        }

        // current special price

        $rule = Mage::getModel('catalogrule/rule')->calcProductPriceRule($product, $product->getPrice());

        if ($rule) {
            $prices[] = array(
                "currency" => $this->currencyCode,
                "value" => $this->getPriceWithTax($rule, $productTax, $defaultTax) * 100,
                "type"     => "sale",
                "vat"      => $this->getFormattedVat($productTax, $defaultTax),
            );
        }

        $this->prices = $prices;
    }

    protected function getPriceWithTax($priceValue, $tax, $defaultTax)
    {
        return $priceValue + ($tax ? ($priceValue * (float)$tax / 100) : ($defaultTax ? $priceValue * (float)$defaultTax / 100 : 0));
    }

    protected function getFormattedVat($tax, $defaultTax)
    {
        return $tax ? ((float)$tax * 100) : ($defaultTax ? (float)$defaultTax * 100 : null);
    }

    /**
     * Process product store inventory
     */
    protected function processInventory()
    {
        if ($this->isTableOption && $this->processTableOptions()) {
            foreach ($this->inventory as $item) {
                if ($item['quantity'] <= 0) {
                    return false;
                }
            }

            return true;
        }

        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($this->product);

        $quantity = (int)$stockItem->getQty();

        if ($quantity <= 0) {
            return false;
        }

        $inventory = array();

        $inventory[] = array(
            'location' => "{$stockItem->getStockId()}", // Location of current stock
            'quantity' => $quantity,   // Currently stocked items for location
        );

        $this->inventory = $inventory;

        return true;
    }

    /**
     * Check if product have simple type
     * @return bool
     */
    public function isSimple()
    {
        return $this->product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
    }

    /**
     * Helper function
     * Get currency code for current store
     * @return string
     */
    protected function getCurrencyCode()
    {
        return Mage::app()
            ->getStore()
            ->getCurrentCurrencyCode()
            ;
    }

    /**
     * Helper function
     * Call function of other models
     * @param string $name
     * @param string $func
     * @param mixed $param
     * @return mixed
     */
    protected function model($name, $func, $param)
    {
        return Mage::getModel($name)
            ->{$func}(
                $param
            )
            ;
    }

    /**
     * Helper function
     * Get product attribute value
     * @param string $name
     * @return mixed
     */
    protected function attr($name)
    {
        $attr = $this->resource->getAttribute($name);

        if (!$attr) {
            return null;
        }

        return $attr->getFrontend()
            ->getValue($this->product)
            ;
    }
}
