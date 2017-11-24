<?php

/**
 * Class Urbit_InventoryFeed_Adminhtml_InventoryfeedbackendController
 */
class Urbit_InventoryFeed_Adminhtml_InventoryfeedbackendController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/system/config/inventoryfeed_config');
    }

    /**
     * Redirect to system configuration page of plugin
     */
	public function indexAction()
    {
        Mage::app()->getResponse()->setRedirect(
            Mage::helper("adminhtml")->getUrl(
                "adminhtml/system_config/edit/section/inventoryfeed_config"
            )
        );
    }

    /**
     * Get columns names in table (ajax)
     */
    public function getColumnsAction()
    {
        $result = array();
        $table = $this->getRequest()->getParam('table');

        /** @var Magento_Db_Adapter_Pdo_Mysql $connection */
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $columns = $connection->query("DESCRIBE {$table};")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($columns as $column) {
            $result[] = array(
                'id' => $column,
                'name' => uc_words($column, ' ', '_')
            );
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
    }
}
