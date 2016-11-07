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
 */
class TIG_MyParcel2014_Helper_AddressValidation extends TIG_MyParcel2014_Helper_Data
{
    /**
     * Constants containing XML paths to cif address configuration options
     */
    const XPATH_SPLIT_STREET                = 'tig_myparcel/shipment/split_street';
    const XPATH_STREETNAME_FIELD            = 'tig_myparcel/shipment/streetname_field';
    const XPATH_HOUSENUMBER_FIELD           = 'tig_myparcel/shipment/housenr_field';
    const XPATH_SPLIT_HOUSENUMBER           = 'tig_myparcel/shipment/split_housenr';
    const XPATH_HOUSENUMBER_EXTENSION_FIELD = 'tig_myparcel/shipment/housenr_extension_field';

    /**
     * Gets the address field number used for the streetname field.
     *
     * @param int|null $storeId
     *
     * @return int
     */
    public function getStreetnameField($storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore()->getId();
        }

        $streetnameField = (int) Mage::getStoreConfig(self::XPATH_STREETNAME_FIELD, $storeId);
        return $streetnameField;
    }

    /**
     * Gets the address field number used for the housenumber field.
     *
     * @param int|null $storeId
     *
     * @return int
     */

    public function getHousenumberField($storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore()->getId();
        }

        $housenumberField = (int) Mage::getStoreConfig(self::XPATH_HOUSENUMBER_FIELD, $storeId);
        return $housenumberField;
    }

    /**
     * Gets the address field number used for the housenumber extension field.
     *
     * @param int|null $storeId
     *
     * @return int
     */
    public function getHousenumberExtensionField($storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore()->getId();
        }

        $housenumberExtensionField = (int) Mage::getStoreConfig(self::XPATH_HOUSENUMBER_EXTENSION_FIELD, $storeId);
        return $housenumberExtensionField;
    }

    /**
     * Wrapper for the getAttributeValidationClass method to prevent errors in Magento 1.6.
     *
     * @param $attribute
     *
     * @return string
     */
    public function getAttributeValidationClass($attribute)
    {
        $addressHelper = Mage::helper('customer/address');
        if (is_callable(array($addressHelper, 'getAttributeValidationClass'))) {
            return $addressHelper->getAttributeValidationClass($attribute);
        }

        return '';
    }

    /**
     * Get current quote address. This is used in the checkout.
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    public function getQuoteAddress(Mage_Sales_Model_Quote $quote)
    {
        /** @var TIG_MyParcel2014_Helper_Data $helper */
        $helper = Mage::helper('tig_myparcel');

        $address = array();
        $sameAsBilling = $quote->getShippingAddress()->getData('same_as_billing') == '1' ? true : false;

        if (
            $quote->getBillingAddress()->getData('country_id') == $quote->getShippingAddress()->getData('country_id') &&
            $quote->getBillingAddress()->getStreetFull() == $quote->getShippingAddress()->getStreetFull() &&
            $sameAsBilling
        ) {
            $address['full_street'] = $quote->getBillingAddress()->getStreetFull();
            if($address['full_street']){
                $this->removeAddressLines($address['full_street']);
                $address['type'] = 'billing';
                $address['country'] = $quote->getBillingAddress()->getCountry();
                $address['postal_code'] = $quote->getBillingAddress()->getPostcode();
                if (!preg_match('/[0-9]/', $quote->getBillingAddress()->getStreetFull())) {
                    return false;
                }
                $streetData = $helper->getStreetData($quote->getBillingAddress());
                $address['street'] = $streetData['streetname'];
                $address['number'] = $streetData['housenumber'];
            }
        } else {
            $address['full_street'] = $quote->getShippingAddress()->getStreetFull();
            if($address['full_street']){
                $this->removeAddressLines($address['full_street']);
                $address['type'] = 'shipping';
                $address['country'] = $quote->getShippingAddress()->getCountry();
                $address['postal_code'] = $quote->getShippingAddress()->getPostcode();
                if (!preg_match('/[0-9]/', $quote->getShippingAddress()->getStreetFull())) {
                    return false;
                }
                $streetData = $helper->getStreetData($quote->getShippingAddress());
                $address['street'] = $streetData['streetname'];
                $address['number'] = $streetData['housenumber'];
            }
        }
        if (Mage::getSingleton('customer/session')->isLoggedIn() && !$address['full_street']) {
            $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();
            if ($customerAddressId) {
                $address['type'] = 'login';
                $tmpAddress = Mage::getModel('customer/address')->load($customerAddressId);
                $address['country'] = $tmpAddress->getCountry();
                $address['postal_code'] = $tmpAddress->getPostcode();
                $address['full_street'] = $tmpAddress->getStreetFull();
                $this->removeAddressLines($address['full_street']);
                if (!preg_match('/[0-9]/', $address['full_street'])) {
                    return false;
                }
                $streetData = $helper->getStreetData($tmpAddress);
                $address['street'] = $streetData['streetname'];
                $address['number'] = $streetData['housenumber'];
            }
        }

        return $address;
    }

    /**
     * Remove multiple rows. For example, if the house number on a different row.
     *
     * @param $street
     */
    private function removeAddressLines(&$street){

        $street = preg_replace("/[\n\r]/", " ", $street);
    }
}
