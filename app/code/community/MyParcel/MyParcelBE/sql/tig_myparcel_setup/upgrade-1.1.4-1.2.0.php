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

/* @var $installer MyParcel_MyParcelBE_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$tableName = $installer->getTable('myparcel_be/shipment');

if (!$conn->tableColumnExists($tableName, 'shipment_type')) {
    $conn->addColumn(
        $tableName,
        'shipment_type',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => '16',
            'nullable' => false,
            'default'  => MyParcel_MyParcelBE_Model_Shipment::TYPE_NORMAL,
            'comment'  => 'Shipment Type',
            'after'    => 'updated_at',
        )
    );
}

$settingsToMove = array(
    'carriers/myparcel_be/active'              => 'carriers/myparcel/active',
    'carriers/myparcel_be/title'               => 'carriers/myparcel/title',
    'carriers/myparcel_be/name'                => 'carriers/myparcel/name',
    'carriers/myparcel_be/rate_type'           => 'carriers/myparcel/rate_type',
    'carriers/myparcel_be/type'                => 'carriers/myparcel/type',
    'carriers/myparcel_be/price'               => 'carriers/myparcel/price',
    'carriers/myparcel_be/condition_name'      => 'carriers/myparcel/condition_name',
    'carriers/myparcel_be/handling_type'       => 'carriers/myparcel/handling_type',
    'carriers/myparcel_be/handling_fee'        => 'carriers/myparcel/handling_fee',
    'carriers/myparcel_be/pakjegemak_active'   => 'carriers/myparcel/pakjegemak_active',
    'carriers/myparcel_be/pakjegemak_fee'      => 'carriers/myparcel/pakjegemak_fee',
    'carriers/myparcel_be/sallowspecific'      => 'carriers/myparcel/sallowspecific',
    'carriers/myparcel_be/specificcountry'     => 'carriers/myparcel/specificcountry',
    'carriers/myparcel_be/sort_order'          => 'carriers/myparcel/sort_order',
);

foreach ($settingsToMove as $from => $to) {
    $installer->moveConfigSettingInDb($from, $to);
}

$installer->endSetup();
