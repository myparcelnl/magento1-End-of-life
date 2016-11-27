<?php
/* @var $installer TIG_MyParcel2014_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('sales/order');
if (!$conn->tableColumnExists($tableName, 'myparcel_send_date')) {
    $conn->addColumn(
        $tableName,
        'myparcel_send_date',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DATE,
            'nullable' => true,
            'default'  => date('Y-m-d'),
            'comment'  => 'The day to send the parcel',
        )
    );
}
$installer->endSetup();