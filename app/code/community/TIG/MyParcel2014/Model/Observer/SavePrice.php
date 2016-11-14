<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 *
 * DISCLAIMER
 *
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_MyParcel2014_Model_Observer_SavePrice
{

    /**
     * Update rate price in the checkout
     *
     * TIG_MyParcel2014_Helper_Data::updateRatePrice() also ensures that the price will be adjusted at checkout
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function salesQuoteCollectTotalsBefore(Varien_Event_Observer $observer)
    {
        /** @var TIG_MyParcel2014_Helper_Data $helper */
        $helper = Mage::helper('tig_myparcel');

        /**
         * @var Mage_Sales_Model_Quote $quote
         */
        $quote = $observer->getQuote();

        $price = Mage::getSingleton('core/session')->getMyParcelBasePrice();

        $shipAddress = $quote->getShippingAddress();
        if ($price === null) {
            foreach ($shipAddress->getShippingRatesCollection() as $rate) {
                if ($rate->getCarrier() == 'myparcel') {
                    $price = $rate->getPrice();
                    Mage::getSingleton('core/session')->setMyParcelBasePrice($price);
                }
            }
        }

        if(strpos($shipAddress->getShippingMethod(), 'myparcel') !== false) {
            $helper->calculatePrice($price);
        }
    }
}
