<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Chronopost\Chronorelais\Model\ResourceModel\Order\Grid\Bordereau;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\RequestInterface;
/**
 * Order grid collection
 */
class Collection extends \Chronopost\Chronorelais\Model\ResourceModel\Order\Grid\Collection
{
    protected $request;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        RequestInterface $request,
        $mainTable = 'sales_order_grid',
        $resourceModel = \Magento\Sales\Model\ResourceModel\Order::class
    )
    {
        $this->request = $request;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager,$request,  $mainTable, $resourceModel);
    }
    /**
     * {@inheritdoc}
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $action = $this->request->getActionName();
        if(!preg_match('/impression_[a-zA-Z]*Mass|bordereau_printBordereau/', $action)) {

            $this->addFilterToMap('entity_id','main_table.entity_id');
        $this->getSelect()
            ->where($this->getTable('sales_order') . ".status = 'processing'");
        }
        return $this;
    }
}
