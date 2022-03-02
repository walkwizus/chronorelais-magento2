<?php
namespace Chronopost\Chronorelais\Model\ResourceModel\OrderExportStatus;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Chronopost\Chronorelais\Model\OrderExportStatus', 'Chronopost\Chronorelais\Model\ResourceModel\OrderExportStatus');
    }

}