<?php
namespace Chronopost\Chronorelais\Model\ResourceModel;;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class OrderStatusModel
 * @package Chronopost\Chronorelais\Model\ResourceModel
 */
class OrderExportStatus extends AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        // Table Name and Primary Key column
        $this->_init('chronopost_order_export_status', 'entity_id');
    }

}