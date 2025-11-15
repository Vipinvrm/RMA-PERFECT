<?php

namespace Krishaweb\Rma\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->createRmaStatusHistoryTable($setup);
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->addPickupFieldsToRmaOrder($setup);
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->addRmaTypeFields($setup);
        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $this->addCustomerPickupFields($setup);
        }

        // NEW: Add fields for "Other" option and image uploads
        if (version_compare($context->getVersion(), '1.0.5', '<')) {
            $this->addOtherFieldsAndImageUpload($setup);
        }

        $setup->endSetup();
    }

    private function createRmaStatusHistoryTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('krishaweb_rma_status_history');

        if (!$setup->getConnection()->isTableExists($tableName)) {
            $table = $setup->getConnection()->newTable($tableName)
                ->addColumn(
                    'history_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary' => true,
                        'unsigned' => true,
                    ],
                    'History ID'
                )
                ->addColumn(
                    'rma_order_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'unsigned' => true],
                    'RMA Order ID'
                )
                ->addColumn(
                    'status',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Status'
                )
                ->addColumn(
                    'comment',
                    Table::TYPE_TEXT,
                    '2M',
                    ['nullable' => true],
                    'Comment'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addIndex(
                    $setup->getIdxName('krishaweb_rma_status_history', ['rma_order_id']),
                    ['rma_order_id']
                )
                ->addForeignKey(
                    $setup->getFkName(
                        'krishaweb_rma_status_history',
                        'rma_order_id',
                        'krishaweb_rma_order',
                        'rma_order_id'
                    ),
                    'rma_order_id',
                    $setup->getTable('krishaweb_rma_order'),
                    'rma_order_id',
                    Table::ACTION_CASCADE
                )
                ->setComment('RMA Status History Table');

            $setup->getConnection()->createTable($table);
        }
    }

    private function addPickupFieldsToRmaOrder(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('krishaweb_rma_order');

        $fieldsToAdd = [
            'pickup_scheduled' => [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Pickup Scheduled (0=No, 1=Yes)'
            ],
            'pickup_date' => [
                'type' => Table::TYPE_DATE,
                'nullable' => true,
                'comment' => 'Pickup Date'
            ],
            'pickup_time_slot' => [
                'type' => Table::TYPE_TEXT,
                'length' => 50,
                'nullable' => true,
                'comment' => 'Pickup Time Slot'
            ],
            'pickup_address' => [
                'type' => Table::TYPE_TEXT,
                'length' => '2M',
                'nullable' => true,
                'comment' => 'Pickup Address'
            ],
            'pickup_contact_name' => [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Pickup Contact Name'
            ],
            'pickup_contact_phone' => [
                'type' => Table::TYPE_TEXT,
                'length' => 20,
                'nullable' => true,
                'comment' => 'Pickup Contact Phone'
            ],
            'pickup_instructions' => [
                'type' => Table::TYPE_TEXT,
                'length' => '2M',
                'nullable' => true,
                'comment' => 'Pickup Instructions'
            ],
            'courier_name' => [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Courier Name'
            ],
            'tracking_number' => [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Tracking Number'
            ]
        ];

        foreach ($fieldsToAdd as $fieldName => $fieldConfig) {
            if (!$connection->tableColumnExists($tableName, $fieldName)) {
                $connection->addColumn($tableName, $fieldName, $fieldConfig);
            }
        }
    }

    private function addRmaTypeFields(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        // Add rma_type to reason table
        $reasonTable = $setup->getTable('krishaweb_rma_reason');
        if (!$connection->tableColumnExists($reasonTable, 'rma_type')) {
            $connection->addColumn(
                $reasonTable,
                'rma_type',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 50,
                    'nullable' => false,
                    'default' => 'exchange',
                    'comment' => 'RMA Type (exchange/return)',
                    'after' => 'title'
                ]
            );
        }

        // Add rma_type to condition table
        $conditionTable = $setup->getTable('krishaweb_rma_condition');
        if (!$connection->tableColumnExists($conditionTable, 'rma_type')) {
            $connection->addColumn(
                $conditionTable,
                'rma_type',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 50,
                    'nullable' => false,
                    'default' => 'exchange',
                    'comment' => 'RMA Type (exchange/return)',
                    'after' => 'title'
                ]
            );
        }

        // Add rma_type to rma table
        $rmaTable = $setup->getTable('krishaweb_rma_rma');
        if (!$connection->tableColumnExists($rmaTable, 'rma_type')) {
            $connection->addColumn(
                $rmaTable,
                'rma_type',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 50,
                    'nullable' => false,
                    'default' => 'exchange',
                    'comment' => 'RMA Type (exchange/return)',
                    'after' => 'order_id'
                ]
            );
        }

        // Add rma_type to rma_order table
        $rmaOrderTable = $setup->getTable('krishaweb_rma_order');
        if (!$connection->tableColumnExists($rmaOrderTable, 'rma_type')) {
            $connection->addColumn(
                $rmaOrderTable,
                'rma_type',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 50,
                    'nullable' => false,
                    'default' => 'exchange',
                    'comment' => 'RMA Type (exchange/return)',
                    'after' => 'order_id'
                ]
            );
        }
    }

    private function addCustomerPickupFields(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('krishaweb_rma_order');

        $fieldsToAdd = [
            'customer_pickup_submitted' => [
                'type' => Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Customer Pickup Details Submitted (0=No, 1=Yes)'
            ],
            'customer_pickup_date' => [
                'type' => Table::TYPE_DATE,
                'nullable' => true,
                'comment' => 'Customer Preferred Pickup Date'
            ],
            'customer_pickup_time_slot' => [
                'type' => Table::TYPE_TEXT,
                'length' => 50,
                'nullable' => true,
                'comment' => 'Customer Preferred Time Slot'
            ],
            'customer_pickup_address' => [
                'type' => Table::TYPE_TEXT,
                'length' => '2M',
                'nullable' => true,
                'comment' => 'Customer Pickup Address'
            ],
            'customer_pickup_contact_name' => [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Customer Contact Name'
            ],
            'customer_pickup_contact_phone' => [
                'type' => Table::TYPE_TEXT,
                'length' => 20,
                'nullable' => true,
                'comment' => 'Customer Contact Phone'
            ],
            'customer_pickup_instructions' => [
                'type' => Table::TYPE_TEXT,
                'length' => '2M',
                'nullable' => true,
                'comment' => 'Customer Pickup Instructions'
            ],
            'pickup_request_submitted_at' => [
                'type' => Table::TYPE_TIMESTAMP,
                'nullable' => true,
                'comment' => 'Pickup Request Submitted At'
            ]
        ];

        foreach ($fieldsToAdd as $fieldName => $fieldConfig) {
            if (!$connection->tableColumnExists($tableName, $fieldName)) {
                $connection->addColumn($tableName, $fieldName, $fieldConfig);
            }
        }
    }

    /**
     * Add fields for "Other" reason/condition text and image uploads
     */
    private function addOtherFieldsAndImageUpload(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('krishaweb_rma_rma');

        $fieldsToAdd = [
            'reason_other_text' => [
                'type' => Table::TYPE_TEXT,
                'length' => '2M',
                'nullable' => true,
                'comment' => 'Other Reason Text (when Other is selected)',
                'after' => 'reason'
            ],
            'condition_other_text' => [
                'type' => Table::TYPE_TEXT,
                'length' => '2M',
                'nullable' => true,
                'comment' => 'Other Condition Text (when Other is selected)',
                'after' => 'condition'
            ],
            'images' => [
                'type' => Table::TYPE_TEXT,
                'length' => '2M',
                'nullable' => true,
                'comment' => 'Product Images (JSON array of image paths)',
                'after' => 'condition_other_text'
            ]
        ];

        foreach ($fieldsToAdd as $fieldName => $fieldConfig) {
            if (!$connection->tableColumnExists($tableName, $fieldName)) {
                $connection->addColumn($tableName, $fieldName, $fieldConfig);
            }
        }
    }
}