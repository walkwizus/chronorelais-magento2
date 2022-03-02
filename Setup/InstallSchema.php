<?php
namespace Chronopost\Chronorelais\Setup;

use \Magento\Framework\DB\Ddl\Table as Varien_Db_Ddl_Table;
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * install tables
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $tableName = $installer->getTable("sales_shipment_track");
        $installer->getConnection()->addColumn($tableName,'chrono_reservation_number',array(
            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
            'nullable' => true,
            'comment' => 'etiquette content'
        ));

        $installer->endSetup();
    }
}
