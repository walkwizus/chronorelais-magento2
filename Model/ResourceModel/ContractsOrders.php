<?php
namespace Chronopost\Chronorelais\Model\ResourceModel;;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class OrderStatusModel
 * @package Chronopost\Chronorelais\Model\ResourceModel
 */
class ContractsOrders extends AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        // Table Name and Primary Key column
        $this->_init('chronopost_chronorelais_contracts_orders', 'entity_id');
    }

}