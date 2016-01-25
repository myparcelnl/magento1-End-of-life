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

if (!$conn->tableColumnExists($tableName, 'shipment_type')) {
    $conn->addColumn(
        $tableName,
        'shipment_type',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => '16',
            'nullable' => false,
            'default'  => TIG_MyParcel2014_Model_Shipment::TYPE_NORMAL,
            'comment'  => 'Shipment Type',
            'after'    => 'updated_at',
        )
    );
}

$settingsToMove = array(
    'carriers/tig_myparcel/active'              => 'carriers/myparcel/active',
    'carriers/tig_myparcel/title'               => 'carriers/myparcel/title',
    'carriers/tig_myparcel/name'                => 'carriers/myparcel/name',
    'carriers/tig_myparcel/rate_type'           => 'carriers/myparcel/rate_type',
    'carriers/tig_myparcel/type'                => 'carriers/myparcel/type',
    'carriers/tig_myparcel/price'               => 'carriers/myparcel/price',
    'carriers/tig_myparcel/condition_name'      => 'carriers/myparcel/condition_name',
    'carriers/tig_myparcel/handling_type'       => 'carriers/myparcel/handling_type',
    'carriers/tig_myparcel/handling_fee'        => 'carriers/myparcel/handling_fee',
    'carriers/tig_myparcel/pakjegemak_active'   => 'carriers/myparcel/pakjegemak_active',
    'carriers/tig_myparcel/pakjegemak_fee'      => 'carriers/myparcel/pakjegemak_fee',
    'carriers/tig_myparcel/sallowspecific'      => 'carriers/myparcel/sallowspecific',
    'carriers/tig_myparcel/specificcountry'     => 'carriers/myparcel/specificcountry',
    'carriers/tig_myparcel/sort_order'          => 'carriers/myparcel/sort_order',
);

foreach ($settingsToMove as $from => $to) {
    $installer->moveConfigSettingInDb($from, $to);
}

$installer->endSetup();
