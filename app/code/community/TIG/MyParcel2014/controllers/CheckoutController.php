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
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_MyParcel2014_CheckoutController extends Mage_Core_Controller_Front_Action
{
    /**
     * Generate data in json format for checkout
     */
    public function infoAction()
    {
        /** @var TIG_MyParcel2014_Helper_AddressValidation $helper */
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
        $general['deliverydays_window'] =           $helper->getConfig('deliverydays_window', 'checkout') == 'hide' ? 1 : $helper->getConfig('deliverydays_window', 'checkout');
        $general['dropoff_days'] =                  str_replace(',', ';', $helper->getConfig('dropoff_days', 'checkout'));
        $general['dropoff_delay'] =                 $helper->getConfig('dropoff_delay', 'checkout');
        $general['base_color'] =                    $helper->getConfig('base_color', 'checkout');
        $general['select_color'] =                  $helper->getConfig('select_color', 'checkout');
        $data['general'] = (object)$general;

        $delivery['delivery_title'] =               $helper->getConfig('delivery_title', 'delivery');
        $delivery['only_recipient_active'] =        $helper->getConfig('only_recipient_active', 'delivery') == "1" ? true : false;
        $delivery['only_recipient_title'] =         $helper->getConfig('only_recipient_title', 'delivery');
        $delivery['only_recipient_fee'] =           $this->getShippingPrice($helper->getConfig('only_recipient_fee', 'delivery'), $quote);
        $delivery['signature_active'] =             $helper->getConfig('signature_active', 'delivery') == "1" ? true : false;
        $delivery['signature_title'] =              $helper->getConfig('signature_title', 'delivery');
        $delivery['signature_fee'] =                $this->getShippingPrice($helper->getConfig('signature_fee', 'delivery'), $quote);
        $delivery['signature_and_only_recipient_fee'] =                $this->getShippingPrice($helper->getConfig('signature_and_only_recipient_fee', 'delivery'), $quote);
        $data['delivery'] = (object)$delivery;

        $morningDelivery['active'] =                $helper->getConfig('morningdelivery_active', 'morningdelivery') == "1" ? true : false;
        $morningDelivery['fee'] =                   $basePrice + $this->getShippingPrice($helper->getConfig('morningdelivery_fee', 'morningdelivery'), $quote);
        $data['morningDelivery'] = (object)$morningDelivery;

        $eveningDelivery['active'] =                $helper->getConfig('eveningdelivery_active', 'eveningdelivery') == "1" ? true : false;
        $eveningDelivery['fee'] =                   $basePrice + $this->getShippingPrice($helper->getConfig('eveningdelivery_fee', 'eveningdelivery'), $quote);
        $data['eveningDelivery'] = (object)$eveningDelivery;

        $pickup['active'] =                         $helper->getConfig('pickup_active', 'pickup') == "1" ? true : false;
        $pickup['title'] =                          $helper->getConfig('pickup_title', 'pickup');
        $pickup['fee'] =                            $basePrice + $this->getShippingPrice($helper->getConfig('pickup_fee', 'pickup'), $quote);
        $data['pickup'] = (object)$pickup;

        $pickupExpress['active'] =                  $helper->getConfig('pickup_express_active', 'pickup_express') == "1" ? true : false;
        $pickupExpress['fee'] =                     $basePrice + $this->getShippingPrice($helper->getConfig('pickup_express_fee', 'pickup_express'), $quote);
        $data['pickupExpress'] = (object)$pickupExpress;


        $info = array(
            'version' => (string) Mage::getConfig()->getModuleConfig("TIG_MyParcel2014")->version,
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
         * @var TIG_MyParcel2014_Helper_Data $helper
         */
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $helper = Mage::helper('tig_myparcel');

        $packageType = $helper->getPackageType($quote->getItemsCollection(), 'NL', false, false, true);

        /** Get mailbox Price */
        $_excl = $this->getShippingPrice($helper->getConfig('mailbox_fee', 'mailbox'), $quote);
        $_incl = $this->getShippingPrice($helper->getConfig('mailbox_fee', 'mailbox'), $quote, true);
        if (Mage::helper('tax')->displayShippingBothPrices() && $_incl != $_excl) {
            $mailBoxPrice = $_incl;
        } else {
            $mailBoxPrice = $_excl;
        }
        $mailBoxPrice = '&#8364; ' . str_replace('.', ',', $mailBoxPrice);

        require(Mage::getBaseDir('app') . DS . 'design/frontend/base/default/template/TIG/MyParcel2014/checkout/mypa_checkout_options.phtml');
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
        $cronController = new TIG_MyParcel2014_Model_Observer_Cron;
        $cronController->checkStatus();
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
