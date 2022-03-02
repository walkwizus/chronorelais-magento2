<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Chronopost\Chronorelais\Model\ResourceModel\Order\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\RequestInterface;

/**
 * Order grid collection
 */
class Collection extends \Magento\Sales\Model\ResourceModel\Order\Grid\Collection
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
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * {@inheritdoc}
     */
    protected function _initSelect()
    {

        parent::_initSelect();

        $action = $this->request->getActionName();
        //if(!preg_match('/impression_[a-zA-Z]*Mass|bordereau_printBordereau/', $action)){
            $this->addFilterToMap('entity_id','main_table.entity_id');
            $this->getSelect()
                ->join($this->getTable('sales_order'),'main_table.entity_id = '.$this->getTable('sales_order').'.entity_id ',array($this->getTable('sales_order').'.shipping_method',$this->getTable('sales_order').'.total_qty_ordered'))
                ->joinLeft($this->getTable('sales_shipment'),'main_table.entity_id = '.$this->getTable('sales_shipment').'.order_id',array(new \Zend_Db_Expr('if(isNull('.$this->getTable('sales_shipment').'.increment_id) , "--" , GROUP_CONCAT(DISTINCT '.$this->getTable('sales_shipment').'.increment_id SEPARATOR ", ")) as shipment_id')))
                ->joinLeft($this->getTable('sales_shipment_track'),'main_table.entity_id = '.$this->getTable('sales_shipment_track').'.order_id',array(new \Zend_Db_Expr('if(isNull('.$this->getTable('sales_shipment_track').'.track_number) , "--" , GROUP_CONCAT(DISTINCT '.$this->getTable('sales_shipment_track').'.track_number SEPARATOR ", ")) as track_number')))
                ->joinLeft($this->getTable('chronopost_order_export_status'),'main_table.entity_id = '.$this->getTable('chronopost_order_export_status').'.order_id',array(new \Zend_Db_Expr("if(isNull(".$this->getTable('chronopost_order_export_status').".livraison_le_samedi) , ".$this->getTable('sales_order').".shipping_method , ".$this->getTable('chronopost_order_export_status').".livraison_le_samedi) as livraison_le_samedi")))
                ->where($this->getTable('sales_order').'.shipping_method LIKE "chrono%"')
                ->group("main_table.entity_id");

            $this->addFilterToMap("status","main_table.status");
            $this->addFilterToMap("shipment_id","sales_shipment.increment_id");
            $this->addFilterToMap("increment_id","main_table.increment_id");
            $this->addFilterToMap("created_at","main_table.created_at");
        //}

        return $this;
    }
}
