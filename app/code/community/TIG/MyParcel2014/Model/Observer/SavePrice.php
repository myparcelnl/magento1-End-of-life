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
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function salesQuoteCollectTotalsBefore(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getQuote();




        $newHandlingFee = 15;
        $store    = Mage::app()->getStore($quote->getStoreId());
        $carriers = Mage::getStoreConfig('carriers', $store);
        foreach ($carriers as $carrierCode => $carrierConfig) {
            if($carrierCode == 'myparcel'){
                $store->setConfig("carriers/{$carrierCode}/handling_type", 'F'); #F - Fixed, P - Percentage
                $store->setConfig("carriers/{$carrierCode}/handling_fee", $newHandlingFee);

                ###If you want to set the price instead of handling fee you can simply use as:
                #$store->setConfig("carriers/{$carrierCode}/price", $newPrice);
            }
        }
    }

    private function calculatePrice($quote)
    {
        $rates = Mage::getModel('tig_myparcel/carrier_myParcel')->collectRates($quote);
        $rates = $rates->getAllRates();
        $rate = $rates[0];
        $price = (float)$rate->getData('price');


    }
}
