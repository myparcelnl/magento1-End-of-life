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
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.tig.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 *
 *
 */

class TIG_MyParcel2014_Model_Observer_Cron
{

    /**
     * Get all NL and EU shipments up to 30 days.
     *
     */
    protected function checkEUShipments()
    {
        $codes = array(
            'NL','BE','BG','DK','DE','EE','FI','FR','HU','IE',
            'IT','LV','LT','LU','MC','AT','PL','PT','RO','SI',
            'SK','ES','CZ','GB','SE'
        );
        $date = date('Y-m-d', strtotime('-14 day'));

        $resource = Mage::getSingleton('core/resource');

        $collection = Mage::getResourceModel('tig_myparcel/shipment_collection');

        $collection->getSelect()->joinLeft(
            array('shipping_address' => $resource->getTableName('sales/order_address')),
            "main_table.entity_id=shipping_address.parent_id AND shipping_address.address_type='shipping'",
            array());

        $collection->addFieldToFilter('shipping_address.country_id', array('in' => array($codes)));
        $collection->addFieldToFilter('main_table.is_final', array('eq' => '0'));
        $collection->addFieldToFilter('main_table.created_at', array('gt' => $date));

        $this->_checkStatus($collection);
    }


    /**
     * Get CD shipments.
     *
     * This will fetch all CD shipments.
     *
     */
    protected function checkCDShipments()
    {

        $codes = array(
            'NL','BE','BG','DK','DE','EE','FI','FR','HU','IE',
            'IT','LV','LT','LU','MC','AT','PL','PT','RO','SI',
            'SK','ES','CZ','GB','SE'
        );

        $resource = Mage::getSingleton('core/resource');

        $collection = Mage::getResourceModel('tig_myparcel/shipment_collection');

        $collection->getSelect()->joinLeft(
            array('shipping_address' => $resource->getTableName('sales/order_address')),
            "main_table.entity_id=shipping_address.parent_id AND shipping_address.address_type='shipping'",
            array());

        $collection->addFieldToFilter('main_table.is_final', array('eq' => '0'));
        $collection->addFieldToFilter('shipping_address.country_id', array('nin' => array($codes)));

        $this->_checkStatus($collection);
    }

    /**
     * Retrieve shipment status from Myparcel
     *
     * @var TIG_MyParcel2014_Model_Shipment $shipment
     * @param $collection
     */
    protected function _checkStatus($collection)
    {
        /**
         * @var Mage_Sales_Model_Order_Shipment $shipment
         * @var TIG_MyParcel2014_Model_Shipment $myParcelShipment
         */
        $consignmentIds = array();
        $myParcelShipments = array();

        foreach ($collection as $myParcelShipment){
            if($myParcelShipment->hasConsignmentId()){
                $consignmentId = $myParcelShipment->getConsignmentId();
                $consignmentIds[] = $consignmentId;
                $myParcelShipments[$consignmentId] = $myParcelShipment;
            }
        }


        $apiInfo    = Mage::getModel('tig_myparcel/api_myParcel');
        $responseShipments = $apiInfo->getConsignmentsInfoData($consignmentIds);

        if($responseShipments){
            foreach($responseShipments as $responseShipment){
                $myParcelShipment = $myParcelShipments[$responseShipment->id];
                $myParcelShipment->updateStatus($responseShipment);
            }
        }
        /*foreach ($collection as $shipment)  {
            $api           = Mage::getModel('tig_myparcel/api_myParcel');
            $consignmentId = $shipment->getConsignmentId();
            $barcode       = $shipment->getBarcode();
            $status        = $shipment->getStatus();

            $response = $api->createRetrieveStatusRequest($consignmentId)
                ->sendRequest()
                ->getRequestResponse();

            if (is_array($response)) {

                if($response['tracktrace'] != $barcode && !empty($response['tracktrace'])){

                    //check if e-mailtemplate isset
                    $cutoff = strtotime("-1 hour");
                    $shipmentTime = strtotime($shipment->getCreatedAt());
                    if ($shipmentTime > $cutoff) {

                        $isSend = $helper->sendBarcodeEmail($response['tracktrace'],$shipment);
                        if($isSend){
                            //add comment to order-comment history
                            $comment = $helper->__('Track&amp;Trace e-mail is send: %s',$barcode);
                            $order = $shipment->getOrder();
                            $order->addStatusHistoryComment($comment);
                            $order->setEmailSent(true);
                            $order->save();
                        }
                    }


                    $shipment->setBarcode($response['tracktrace']);
                    $helper->log('new barcode: '.$response['tracktrace']);
                }

                if($response['status'] != $status){
                    $shipment->setStatus($response['status']);
                }

                if($response['final'] == '1'){
                    $shipment->setIsFinal('1');
                }

                if($shipment->hasDataChanges()){
                    $shipment->save();
                }
            } else {
                $helper->log($api->getRequestErrorDetail(),Zend_Log::ERR);
            }
        }*/

    }


    public function checkStatus()
    {
        $helper = Mage::helper('tig_myparcel');

        if(!$helper->isEnabled()){
            return $this;
        }

        $this->checkEUShipments();
        $this->checkCDShipments();

        return $this;
    }


}
