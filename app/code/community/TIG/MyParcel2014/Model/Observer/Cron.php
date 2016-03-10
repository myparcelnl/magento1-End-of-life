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
    public function checkStatus()
    {
        $helper = Mage::helper('tig_myparcel');

        if(!$helper->isEnabled()){
            return $this;
        }

        //$helper->log('MyParcel checkStatus cron is started!');

        //get all MyParcel shipments with no final status
        $myparcelShipmentCollection = Mage::getResourceModel('tig_myparcel/shipment_collection');
        $myparcelShipmentCollection->addFieldToFilter('is_final', array('eq' => '0'));

        foreach ($myparcelShipmentCollection as $shipment)  {
            $api           = Mage::getModel('tig_myparcel/api_myParcel');
            $consignmentId = $shipment->getConsignmentId();
            $barcode       = $shipment->getBarcode();
            $status        = $shipment->getStatus();

            $response = $api->createRetrieveStatusRequest($consignmentId)
                            ->sendRequest()
                            ->getRequestResponse();

            if (is_array($response) && $response['tracktrace'] != false && strlen($response['tracktrace']) > 8) {
                if($response['tracktrace'] != $barcode){
                    $helper->log('new barcode: '.$response['tracktrace']);

                    //check if e-mailtemplate isset
                    $isSend = $helper->sendBarcodeEmail($response['tracktrace'],$shipment);
                    if($isSend){
                        //add comment to order-comment history
                        $comment = $helper->__('Track&amp;Trace e-mail is send: %s',$barcode);
                        $order = $shipment->getOrder();
                        $order->addStatusHistoryComment($comment);
                        $order->setEmailSent(true);
                        $order->save();
                    }
                    $shipment->setBarcode($response['tracktrace']);
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
        }

        return $this;
    }
}
