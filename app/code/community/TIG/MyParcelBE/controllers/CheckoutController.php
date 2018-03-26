<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to info@sendmyparcel.be so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact info@sendmyparcel.be for more information.
 *
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_MyParcelBE_CheckoutController extends Mage_Core_Controller_Front_Action
{
    /**
     * Generate data in json format for checkout
     */
    public function infoAction()
    {
        /** @var TIG_MyParcelBE_Helper_AddressValidation $helper */
        $helper = Mage::helper('tig_myparcel/addressValidation');
        /**
         * @var Mage_Sales_Model_Quote $quote
         * @var Mage_Sales_Model_Quote_Item $item
         * @var Mage_Sales_Model_Quote_Address $address
         * @var Mage_Sales_Model_Quote_Address_Rate $rate
         */
        $quote = Mage::getModel('checkout/cart')->getQuote();

        $free = false;
        foreach ($quote->getItemsCollection() as $item) {
            $free = $item->getData('free_shipping') == '1' ? true : false;
            break;
        }

        $basePrice = 0;
        $_incl = 0;
        if(!$free) {
            $address = $quote->getShippingAddress();
            $address->requestShippingRates();

            foreach ($address->getShippingRatesCollection() as $rate) {
                if ($rate->getCarrier() == 'myparcel' &&
                    ($rate->getMethod() == 'flatrate' || $rate->getMethod() == 'tablerate') &&
                    key_exists('rate_id', $rate->getData()) && $rate->getData('rate_id') !== null
                ) {

                    $_excl = $this->getShippingPrice($rate->getPrice(), $quote);
                    $_incl = $this->getShippingPrice($rate->getPrice(), $quote, true);
                    if (Mage::helper('tax')->displayShippingBothPrices() && $_incl != $_excl) {
                        $basePrice = $_incl;
                    } else {
                        $basePrice = $_excl;
                    }
                }
            }
        }
        Mage::getSingleton('core/session')->setMyParcelBasePrice($_incl);

        $data = array();

        $data['address'] = $helper->getQuoteAddress($quote);

        $general['base_price'] =                    $basePrice;
        $general['cutoff_time'] =                   str_replace(',', ':', $helper->getConfig('cutoff_time', 'checkout'));
        if ($data['address']['country'] == TIG_MyParcelBE_Model_Carrier_MyParcel::LOCAL_CC) {
            $general['deliverydays_window'] = $helper->getConfig('deliverydays_window', 'checkout') == 'hide' ? 1 : $helper->getConfig('deliverydays_window', 'checkout');
        } else {
            $general['deliverydays_window'] = 1;
        }
        $general['dropoff_days'] =                  str_replace(',', ';', $helper->getConfig('dropoff_days', 'checkout'));
        $general['saturday_delivery_active'] =      $helper->getConfig('saturday_delivery_active', 'checkout') == "1" ? true : false;
        $general['saturday_delivery_fee'] =         $this->getExtraPrice($basePrice, $this->getShippingPrice($helper->getConfig('saturday_delivery_fee', 'checkout'), $quote));
        $general['dropoff_delay'] =                 $helper->getConfig('dropoff_delay', 'checkout');
        $general['base_color'] =                    $helper->getConfig('base_color', 'checkout');
        $general['select_color'] =                  $helper->getConfig('select_color', 'checkout');
        $data['general'] = (object)$general;

        $delivery['delivery_title'] =               $helper->getConfig('delivery_title', 'delivery');
        $delivery['signature_active'] =             $helper->getConfig('signature_active', 'delivery') == "1" && $data['address']['country'] == TIG_MyParcelBE_Model_Carrier_MyParcel::LOCAL_CC ? true : false;
        $delivery['signature_title'] =              $helper->getConfig('signature_title', 'delivery');
        $delivery['signature_fee'] =                $this->getShippingPrice($helper->getConfig('signature_fee', 'delivery'), $quote);
        $delivery['saturday_delivery_active'] =             $helper->getConfig('saturday_delivery_active', 'delivery') == "1" && $data['address']['country'] == TIG_MyParcelBE_Model_Carrier_MyParcel::LOCAL_CC ? true : false;
        $delivery['saturday_delivery_title'] =      $helper->getConfig('saturday_delivery_title', 'delivery');
        $delivery['saturday_delivery_fee'] =        $this->getShippingPrice($helper->getConfig('saturday_delivery_fee', 'delivery'), $quote);
        $data['delivery'] = (object)$delivery;

        if ($data['address']['country'] == TIG_MyParcelBE_Model_Carrier_MyParcel::LOCAL_CC) {
            $pickup['active'] = $helper->getConfig('pickup_active', 'pickup') == "1" ? true : false;
            $pickup['title'] = $helper->getConfig('pickup_title', 'pickup');
            $pickup['fee'] = $this->getExtraPrice($basePrice, $this->getShippingPrice($helper->getConfig('pickup_fee', 'pickup'), $quote));
            $data['pickup'] = (object)$pickup;
        }

        $info = array(
            'version' => (string) Mage::getConfig()->getModuleConfig("TIG_MyParcelBE")->version,
            'data' => (object)$data
        );

        header('Content-Type: application/json');
        echo(json_encode($info));
        exit;
    }

    public function checkout_optionsAction()
    {
        /**
         * @var Mage_Sales_Model_Quote $quote
         * @var TIG_MyParcelBE_Helper_Data $helper
         */
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $helper = Mage::helper('tig_myparcel');

        $packageType = $helper->getPackageType(false);

        require(Mage::getBaseDir('app') . DS . 'design/frontend/base/default/template/TIG/MyParcelBE/checkout/mypa_checkout_options.phtml');
        exit;
    }

    /**
     * Save the MyParcel data in quote
     */
    public function save_shipping_methodAction()
    {
        Mage::getModel('tig_myparcel/checkout_service')->saveMyParcelShippingMethod();
    }

    /**
     * For testing the cron
     */
    public function cronAction()
    {
        $cronController = new TIG_MyParcelBE_Model_Observer_Cron;
        $cronController->checkStatus();
    }

    /**
     * Get extra price. Check if total shipping price is not below 0 euro
     *
     * @param $basePrice
     * @param $extraPrice
     *
     * @return float
     */
    private function getExtraPrice($basePrice, $extraPrice)
    {
        if ($basePrice + $extraPrice < 0) {
            return 0;
        }
        return (float)$basePrice + $extraPrice;
    }

    /**
     * Get shipping price
     *
     * @param $price
     * @param $quote
     * @param $flag
     *
     * @return mixed
     */
    private function getShippingPrice($price, $quote, $flag = false)
    {
        $flag = $flag ? true : Mage::helper('tax')->displayShippingPriceIncludingTax();
        return (float)Mage::helper('tax')->getShippingPrice($price, $flag, $quote->getShippingAddress());
    }

}
