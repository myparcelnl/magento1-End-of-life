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
 * @copyright   Copyright (c) 2015 Total Internet Group B.V. (http://www.tig.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_MyParcel2014_Model_Checkout_Service
{
    /**
     * Save in checkout MyParcel shipping method
     */
    public function saveMyParcelShippingMethod(){

        $helper = Mage::helper('tig_myparcel');
        $request = Mage::app()->getRequest();
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        /**
         * If shipping method is myparcel
         */
        if ($request->isPost() && strpos($request->getPost('shipping_method', ''), 'myparcel') !== false) {

            $delivery = json_decode($request->getPost('mypa-delivery-time', ''), true);

            $quote = Mage::getModel('checkout/cart')->getQuote();
            $rates = Mage::getModel('tig_myparcel/carrier_myParcel')->collectRates($quote);
            $rates = $rates->getAllRates();
            $rate = $rates[0];
            $basePrice = (float)$rate->getData('price');

            $price = $basePrice;

            if ($delivery !== null){

                $priceComment = $delivery['time'][0]['price_comment'];
                switch ($priceComment) {
                    case ('morning'):
                        $price += (float)$helper->getConfig('morningdelivery_fee', 'morningdelivery');
                        break;
                    case ('avond'):
                        $price += (float)$helper->getConfig('eveningdelivery_fee', 'eveningdelivery');
                        break;
                }

                /**
                 * not pickup
                 */
                $return = $request->getPost('mypa-only-recipient', '') === 'on' ? 1 : false;
                if ($return) {
                    $delivery['home_address_only'] = true;
                    $price += (float)$helper->getConfig('only_recipient_fee', 'delivery');
                }

                $signed = $request->getPost('mypa-signed', '') === 'on' ? 1 : false;
                if ($signed) {
                    $delivery['signed'] = true;
                    $price += (float)$helper->getConfig('signature_fee', 'delivery');
                }

                $data = $delivery;
                $this->removePgAddress($quote);

            } else {
                /**
                 * is pickup
                 */
                $data = json_decode($request->getPost('mypa-pickup-option', ''), true);
                $this->savePgAddress($data, $quote);
            }

            $quote->setMyparcelData(json_encode($data))->save();

        } else {
            $quote->setMyparcelData(null)->save();
            $this->removePgAddress(json_encode($quote));
        }
    }

    /**
     * @param object                 $data
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return $this
     */
    public function savePgAddress($data, Mage_Sales_Model_Quote $quote)
    {
        $helper = Mage::helper('tig_myparcel');

        /**
         * Delete old pg address
         */
        $this->removePgAddress($quote);

        /**
         * Create a new address and add the address data.
         */
        $pgAddress = Mage::getModel('sales/quote_address');
        $pgAddress->setAddressType($helper::PG_ADDRESS_TYPE)
                  ->setCity($data['city'])
                  ->setCountryId('NL')
                  ->setPostcode($data['postal_code'])
                  ->setCompany($data['location'])
                  ->setFirstname('Ophalen op een PostNL locatie')
                  ->setLastname('')
                  ->setTelephone($data['phone_number'])
                  ->setStreet($data['street'] . "\n" . $data['number']);

        /**
         * Add the address to the quote and save the quote.
         */
        $quote->addAddress($pgAddress)
              ->save();

        /**
         * Save the address.
         * This should not be required, but we've encountered a few cases where this was not done automatically by
         * saving the quote.
         */
        $pgAddress->save();

        /**
         * If this quote has been deactivated, check if it has an order.
         *
         * This is required for OneStepCheckout.
         */
        if (!$quote->getIsActive()) {
            /**
             * @var Mage_Sales_Model_Order $order
             */
            $order = Mage::getModel('sales/order')->load($quote->getId(), 'quote_id');
            if ($order && $order->getId()) {
                /**
                 * Save the PakjeGemak address to the order.
                 */
                $this->copyAddressToOrder($order, $pgAddress);
            }
        }

        return $this;
    }

    /**
     * Copies a given address to the order.
     *
     * @param Mage_Sales_Model_Order         $order
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return $this
     *
     * @throws Exception
     */
    public function copyAddressToOrder(Mage_Sales_Model_Order $order, Mage_Sales_Model_Quote_Address $address)
    {
        /**
         * Convert the quote address to an order address and add it to the order.
         */
        $address->load($address->getId());
        $orderAddress = Mage::getModel('sales/convert_quote')->addressToOrderAddress($address);

        $order->addAddress($orderAddress)
              ->save();

        /**
         * This is a fix for the order address missing a parent ID.
         */
        if (!$orderAddress->getParentId()) {
            $orderAddress->setParentId($order->getId());
        }

        /**
         * This is required for some PSP extensions which will not save the PakjeGemak address otherwise.
         */
        $orderAddress->save();

        return $this;
    }

    /**
     * Removes a saved PakjeGemak address from the quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return $this
     * @throws Exception
     */
    public function removePgAddress(Mage_Sales_Model_Quote $quote)
    {
        $addresses = $quote->getAllAddresses();

        /** @var Mage_Sales_Model_Quote_Address $address */
        foreach ($addresses as $address) {
            if ($address->getAddressType() == TIG_MyParcel2014_Helper_Data::PG_ADDRESS_TYPE) {
                $address->delete();
            }
        }

        return $this;
    }
}