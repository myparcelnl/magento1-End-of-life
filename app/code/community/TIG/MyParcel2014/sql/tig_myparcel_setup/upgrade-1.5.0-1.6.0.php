<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) 2013 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

/* @var $installer TIG_MyParcel2014_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$tableName = $installer->getTable('tig_myparcel/shipment');

if (!$conn->tableColumnExists($tableName, 'track_id')) {
    $conn->addColumn(
        $tableName,
        'track_id',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'   => '10',
            'nullable' => true,
            'default'  => null,
            'comment'  => 'Track id',
            'after'    => 'order_id',
        )
    )
    ->addIndex($installer->getIdxName(
        'tig_myparcel/shipment',
        array('track_id'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('track_id'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX))
    ->addForeignKey($installer->getFkName('tig_myparcel/shipment', 'track_id', 'sales/order_shipment_track', 'entity_id'),
        'track_id', $installer->getTable('sales/order_shipment_track'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE);


}


$installer->endSetup();
