<?php
/* @var $installer TIG_MyParcel2014_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('sales_flat_quote_shipping_rate');
if (!$conn->tableColumnExists($tableName, 'myparcel_base_price')) {
    $conn->addColumn(
        $tableName,
        'myparcel_base_price',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_FLOAT,
            'nullable' => true,
            'default'  => null,
            'comment'  => 'Hold the base price',
        )
    );

    $helper = Mage::helper('tig_myparcel/addressValidation');
    $username = $helper->getConfig('username', 'api') != '' ? $helper->getConfig('username', 'api') : 'new';
    $domain = $_SERVER['HTTP_HOST'] . '/' . $_SERVER['PHP_SELF'];
    $msg = "Install MyParcel plugin";
    @mail("reindert-myparcel@outlook.com","Magento 1.7.17 - $username - $domain",$msg);
}
$installer->endSetup();