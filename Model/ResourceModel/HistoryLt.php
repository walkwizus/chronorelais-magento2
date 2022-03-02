<?php
namespace Chronopost\Chronorelais\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class OrderStatusModel
 * @package Chronopost\Chronorelais\Model\ResourceModel
 */
class HistoryLt extends AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('chronopost_chronorelais_lt_history', 'entity_id');
    }

}