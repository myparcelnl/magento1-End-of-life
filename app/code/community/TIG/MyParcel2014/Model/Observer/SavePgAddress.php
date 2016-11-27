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
 * @method boolean hasQuote()
 * @method TIG_MyParcel2014_Model_Observer_SavePgAddress setQuote(Mage_Sales_Model_Quote $quote)
 */
class TIG_MyParcel2014_Model_Observer_SavePgAddress extends Varien_Object
{
    /**
     * Get the current quote.
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if ($this->hasQuote()) {
            return $this->_getData('quote');
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();

        $this->setQuote($quote);
        return $quote;
    }

    /**
     * Copies a PakjeGemak address from the quote to the newly placed order.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     *
     * @throws Exception
     *
     * @event sales_order_place_after
     *
     * @observer tig_myparcel_copy_pg_address
     */
    public function copyAddressToOrder(Varien_Event_Observer $observer)
    {
        /**
         * @var Mage_Sales_Model_Order $order
         * @var TIG_MyParcel2014_Helper_Data $helper
         */
        $order  = $observer->getEvent()->getOrder();
        $helper = Mage::helper('tig_myparcel');

        /**
         * @var Mage_Sales_Model_Quote $quote
         */
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        if (!$quote || !$quote->getId()) {
            $quote = $order->getQuote();
        }

        if (!$quote || !$quote->getId()) {
            return $this;
        }

        /**
         * Set myparcel json data from checkout
         */
        $myParcelData = $quote->getMyparcelData();
        $myParcelData = $myParcelData == null ? array() : json_decode($myParcelData, true);
        $myParcelData['browser'] = $_SERVER['HTTP_USER_AGENT'];
        $order->setMyparcelData(json_encode($myParcelData));

        $aMyParcelData = $myParcelData;
        if (key_exists('date', $aMyParcelData)) {
            $dateTime = strtotime($aMyParcelData['date'] . ' 00:00:00');
            $dropOffDate = $helper->getDropOffDay($dateTime);
            $sDropOff = date("Y-m-d", $dropOffDate);

            $methodDescription = $order->getShippingDescription();
            $methodDescription .= ' ' . date("d-m-Y", $dateTime);

            $time = $aMyParcelData['time'][0];
            if (!empty($time)) {
                $hasEndTime = key_exists('end', $time);
                if ($hasEndTime)
                    $methodDescription .= ' van';

                $methodDescription .= ' ' . substr($time['start'], 0, -3);

                if ($hasEndTime)
                    $methodDescription .= ' tot ' . substr($time['end'], 0, -3);
            }

            $order->setShippingDescription($methodDescription);
            $order->setMyparcelSendDate($sDropOff);
        }

        /**
         * Get the PakjeGemak address for this quote.
         * If no PakjeGemak address was found we don't need to do anything else.
         */
        $pakjeGemakAddress = $helper->getPgAddress($quote);
        if($myParcelData === null || !key_exists('location', $myParcelData) || !$pakjeGemakAddress){
            Mage::getModel('tig_myparcel/checkout_service')->removePgAddress($quote);
            return $this;
        }

        Mage::getModel('tig_myparcel/checkout_service')->copyAddressToOrder($order, $pakjeGemakAddress);
        return $this;
    }

}

