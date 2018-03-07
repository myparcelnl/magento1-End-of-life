<?php

    /* @var $installer TIG_MyParcelBE_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();
    $tableName = $installer->getTable('sales/quote');
    if (!$conn->tableColumnExists($tableName, 'myparcel_data')) {
        $conn->addColumn(
            $tableName,
            'myparcel_data',
            array(
                'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Checkout MyParcel data',
            )
        );
    }
    $tableName = $installer->getTable('sales/order');
    if (!$conn->tableColumnExists($tableName, 'myparcel_data')) {
        $conn->addColumn(
            $tableName,
            'myparcel_data',
            array(
                'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Checkout MyParcel data',
            )
        );
    }
    $installer->endSetup();