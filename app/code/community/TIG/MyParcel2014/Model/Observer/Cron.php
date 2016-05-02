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
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright   Copyright (c) 2016 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

class TIG_MyParcel2014_Model_Observer_Cron
{

    /** @var TIG_MyParcel2014_Helper_Data $helper */
    public $helper;

    /**
     * Init
     */
    public function _construct()
    {
        $this->helper = Mage::helper('tig_myparcel');
    }

    /**
     * @return $this
     */
    public function checkStatus()
    {
        if (!$this->helper->isEnabled()) {
            return $this;
        }

        $this->_checkEUShipments();
        $this->_checkCDShipments();

        return $this;
    }

    protected function _checkEUShipments()
    {
        $resource   = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel('tig_myparcel/shipment_collection');

        $collection->getSelect()->joinLeft(
            array('shipping_address' => $resource->getTableName('sales/order_address')),
            "main_table.entity_id=shipping_address.parent_id AND shipping_address.address_type='shipping'",
            array());

        $collection->addFieldToFilter('shipping_address.country_id', array(
                'in' => array($this->helper->whiteListCodes()))
        );
        $collection->addFieldToFilter('main_table.is_final', array('eq' => '0'));
        $collection->addFieldToFilter('main_table.created_at', array(
                'gt' => date('Y-m-d', strtotime('-14 day')))
        );

        $this->_checkCollectionStatus($collection);
    }

    protected function _checkCDShipments()
    {
        $resource   = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel('tig_myparcel/shipment_collection');

        $collection->getSelect()->joinLeft(
            array('shipping_address' => $resource->getTableName('sales/order_address')),
            "main_table.entity_id=shipping_address.parent_id AND shipping_address.address_type='shipping'",
            array());

        $collection->addFieldToFilter('main_table.is_final', array('eq' => '0'));
        $collection->addFieldToFilter('shipping_address.country_id', array(
                'nin' => array($this->helper->whiteListCodes()))
        );

        $this->_checkCollectionStatus($collection);
    }

    /**
     * Retrieve shipment status from Myparcel
     *
     * @param $collection
     *
     * @throws Exception
     */
    protected function _checkCollectionStatus($collection)
    {
        /** @var TIG_MyParcel2014_Model_Shipment $shipment */
        foreach ($collection as $shipment) {
            $api           = Mage::getModel('tig_myparcel/api_myParcel');
            $consignmentId = $shipment->getConsignmentId();
            $barcode       = $shipment->getBarcode();

            $response = $api->createRetrieveStatusRequest($consignmentId)
                ->sendRequest()
                ->getRequestResponse();

            if (is_array($response)) {
                // Check if there is an new barcode
                if (!empty($response['tracktrace']) && $response['tracktrace'] != $barcode) {
                    // Send the barcode email, but first check if the e-mail tempalte is set.
                    $shipmentTime = strtotime($shipment->getCreatedAt());
                    if ($shipmentTime > strtotime('-1 hour')
                        && $this->helper->sendBarcodeEmail($response['tracktrace'],$shipment)) {
                        //add comment to order-comment history
                        $comment = $this->helper->__('Track&amp;Trace e-mail is send: %s',$barcode);
                        /** @var Mage_Sales_Model_Order $order */
                        $order = $shipment->getOrder();
                        $order->addStatusHistoryComment($comment);
                        $order->setEmailSent(true);
                        $order->save();
                    }

                    $shipment->setBarcode($response['tracktrace']);
                    $this->helper->log('new barcode: '.$response['tracktrace']);
                }

                if($response['status'] != $shipment->getStatus()){
                    $shipment->setStatus($response['status']);
                }

                if($response['final'] == '1'){
                    $shipment->setIsFinal('1');
                }

                if($shipment->hasDataChanges()){
                    $shipment->save();
                }

            } else {
                $this->helper->log($api->getRequestErrorDetail(), Zend_Log::ERR);
            }
        }
    }
}
