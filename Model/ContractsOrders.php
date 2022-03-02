<?php
namespace Chronopost\Chronorelais\Model;

use \Magento\Framework\Model\AbstractModel;

class ContractsOrders extends AbstractModel
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'chronopost';

    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'contracts_orders';

    /**
     * Name of object id field
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Chronopost\Chronorelais\Model\ResourceModel\ContractsOrders');
    }

}