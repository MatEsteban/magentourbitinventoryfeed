<?php

/**
 * Class Urbit_InventoryFeed_Model_Config
 *
 * @property array $cron
 * @property array $filter
 * @property array $fields
 */
class Urbit_InventoryFeed_Model_Config
{
    /**
     * Current configuration
     * @var array
     */
    protected static $_config = array();

    /**
     * Urbit_InventoryFeed_Model_Config constructor.
     */
    public function __construct()
    {
        $this->load();
    }

    /**
     * Load configuration from magento scope
     */
    public function load()
    {
        if (empty(self::$_config)) {
            self::$_config = Mage::getStoreConfig('inventoryfeed_config');
        }
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function get($name)
    {
        $param = false;

        if (stripos($name, '/')) {
            list($name, $param) = explode('/', $name);
        }

        if (!isset(self::$_config[$name])) {
            throw new Exception("Try to get unknown configuration field - `{$name}`");
        }

        if ($param !== false) {
            if (!isset(self::$_config[$name][$param])) {
                throw new Exception("Try to get unknown parameter of configuration field - `{$name}/{$param}`");
            }

            return self::$_config[$name][$param];
        }

        return self::$_config[$name];
    }

    /**
     * Get parameter of multiselect config field
     * @param string $name
     * @return array
     * @throws Exception
     */
    public function getSelect($name)
    {
        $val = $this->get($name);

        if (is_array($val)) {
            return $val;
        }

        return explode(",", $val);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed
     * @throws Exception
     */
    public function set($name, $value)
    {
        $param = false;

        if (stripos($name, '/')) {
            list($name, $param) = explode('/', $name);
        }

        if (!isset(self::$_config[$name])) {
            throw new Exception("Try to set unknown configuration field - `{$name}`");
        }

        if ($param !== false) {
            if (!isset(self::$_config[$name][$param])) {
                throw new Exception("Try to set unknown parameter of configuration field - `{$name}/{$param}`");
            }

            self::$_config[$name][$param] = $value;

        } else {
            self::$_config[$name] = $value;
        }

        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed
     * @throws Exception
     */
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }
}