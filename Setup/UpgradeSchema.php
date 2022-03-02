<?php
namespace Chronopost\Chronorelais\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        //handle all possible upgrade versions

        $connection = $setup->getConnection();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            if (!$installer->tableExists('chronopost_order_export_status')) {
                $table = $installer->getConnection()->newTable(
                    $installer->getTable('chronopost_order_export_status')
                )
                    ->addColumn(
                        'entity_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'nullable' => false,
                            'primary'  => true,
                            'unsigned' => true,
                        ],
                        'Entity ID'
                    )
                    ->addColumn(
                        'order_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false,
                            'unsigned' => true,
                        ],
                        'Order ID'
                    )
                    ->addColumn(
                        'livraison_le_samedi',
                        Table::TYPE_TEXT,
                        10,
                        [
                            'nullable' => false,
                            "default"  => 'Yes'
                        ],
                        'Livraison samedi'
                    )
                    ->setComment('Chronopost export status');
                $installer->getConnection()->createTable($table);
            }
        }

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $tableName = $installer->getTable("sales_shipment_track");
            $installer->getConnection()->modifyColumn($tableName,'chrono_reservation_number',array(
                'type' => Table::TYPE_TEXT,
                'length' => 100000,
                'nullable' => true,
                'comment' => 'etiquette content'
            ));
        }

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $installer->getConnection()->addColumn(
                $installer->getTable('quote'),
                'relais_id',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 50,
                    'nullable' => true,
                    'comment' => 'Relais ID',
                ]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order'),
                'relais_id',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 50,
                    'nullable' => true,
                    'comment' => 'Relais ID',
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $installer->getConnection()->addColumn(
                $installer->getTable('quote'),
                'chronopostsrdv_creneaux_info',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 100000,
                    'nullable' => true,
                    'comment' => 'Info RDV',
                ]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order'),
                'chronopostsrdv_creneaux_info',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 100000,
                    'nullable' => true,
                    'comment' => 'Info RDV',
                ]
            );

        }

        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            if (!$installer->tableExists('chronopost_chronorelais_contracts_orders')) {
                $table = $installer->getConnection()->newTable(
                    $installer->getTable('chronopost_chronorelais_contracts_orders')
                )
                    ->addColumn(
                        'entity_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'nullable' => false,
                            'primary'  => true,
                            'unsigned' => true,
                        ],
                        'Entity ID'
                    )
                    ->addColumn(
                        'order_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false,
                            'unsigned' => true,
                        ],
                        'Order ID'
                    )
                    ->addColumn(
                        'contract_name',
                        Table::TYPE_TEXT,
                        255,
                        [
                            'nullable' => false,
                            "default"  => null
                        ],
                        'Contract Name'
                    )
                    ->addColumn(
                        'contract_account_number',
                        Table::TYPE_TEXT,
                        50,
                        [
                            'nullable' => false,
                            "default"  => null
                        ],
                        'Contract account number'
                    )
                    ->addColumn(
                        'contract_sub_account_number',
                        Table::TYPE_TEXT,
                        20,
                        [
                            'nullable' => false,
                            "default"  => null
                        ],
                        'Contract sub account number'
                    )
                    ->addColumn(
                        'contract_account_password',
                        Table::TYPE_TEXT,
                        50,
                        [
                            'nullable' => false,
                            "default"  => null
                        ],
                        'Contract account password'
                    )
                    ->setComment('Chronopost contract order');
                $installer->getConnection()->createTable($table);
            }

        }

        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            if (!$installer->tableExists('chronopost_chronorelais_lt_history')) {
                $table = $installer->getConnection()->newTable(
                    $installer->getTable('chronopost_chronorelais_lt_history')
                )
                    ->addColumn(
                        'entity_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'nullable' => false,
                            'primary'  => true,
                            'unsigned' => true,
                        ],
                        'Entity ID'
                    )
                    ->addColumn(
                        'shipment_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false,
                            'unsigned' => true,
                        ],
                        'Shipment ID'
                    )
                    ->addColumn(
                        'lt_number',
                        Table::TYPE_TEXT,
                        null,
                        [
                            'nullable' => false,
                            'default'  => null
                        ],
                        'LT Number'
                    )
                    ->addColumn(
                        'weight',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false,
                            'default'  => 0
                        ],
                        'Weight of parcel'
                    );
                $installer->getConnection()->createTable($table);
            }

        }

        $installer->endSetup();
    }

}
