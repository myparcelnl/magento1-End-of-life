<?php

    /* @var $installer TIG_MyParcel2014_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();
    $tableName = $installer->getTable('tig_myparcel/shipment');
    if (!$conn->tableColumnExists($tableName, 'is_xl')) {
        $conn->addColumn(
            $tableName,
            'is_xl',
            array(
                'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Is xl consignment',
                'after'    => 'is_credit',
            )
        );
    }
    $installer->endSetup();