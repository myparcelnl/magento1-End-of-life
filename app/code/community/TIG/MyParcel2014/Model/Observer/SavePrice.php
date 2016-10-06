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

        $helper = Mage::helper('tig_myparcel');
        /**
         * @var Mage_Sales_Model_Quote $quote
         */
        $quote = $observer->getQuote();
        if ($quote->getMyparcelData() !== null) {
            $store = Mage::app()->getStore($quote->getStoreId());
                $carriers = Mage::getStoreConfig('carriers', $store);

                foreach ($carriers as $carrierCode => $carrierConfig) {
                    if ($carrierCode == 'myparcel') {
                        $fee = $this->_isFree() ? 0 : $helper->calculatePrice($quote);
                        $store->setConfig("carriers/{$carrierCode}/handling_type", 'F'); #F - Fixed, P - Percentage
                        $store->setConfig("carriers/{$carrierCode}/price", $fee);

                        ###If you want to set the price instead of handling fee you can simply use as:
                        #$store->setConfig("carriers/{$carrierCode}/price", $newPrice);
                    }
                }
        }
    }

    private function _isFree()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        foreach ($quote->getItemsCollection() as $item) {
            if ($item->getData('free_shipping') == '1') {
                return true;
            }
        }
        return false;
    }
}
