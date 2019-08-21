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
 * @method boolean hasShipment()
 * @method boolean hasShipmentBaseGrandTotal()
 * @method string  hasDestinationCountry()
 *
 * @method TIG_MyParcel2014_Block_Adminhtml_Sales_Order_Shipment_Create_ConsignmentOptions setShipment(Mage_Sales_Model_Order_Shipment $value)
 * @method TIG_MyParcel2014_Block_Adminhtml_Sales_Order_Shipment_Create_ConsignmentOptions setShipmentBaseGrandTotal(float $value)
 * @method TIG_MyParcel2014_Block_Adminhtml_Sales_Order_Shipment_Create_ConsignmentOptions setDestinationCountry(string $value)
 *
 */
class TIG_MyParcel2014_Block_Adminhtml_Sales_Order_Shipment_Create_ConsignmentOptions extends Mage_Adminhtml_Block_Abstract
{
    /**
     * Do a few checks to see if the template should be rendered before actually rendering it.
     *
     * @return string
     *
     * @see Mage_Adminhtml_Block_Abstract::_toHtml()
     */
    protected function _toHtml()
    {
        $shipment = Mage::registry('current_shipment');

        $helper = Mage::helper('tig_myparcel');
        if (!$shipment
            || !$helper->shippingMethodIsMyParcel($shipment->getOrder()->getShippingMethod())
            || $this->getShipment()->getOrder()->getIsVirtual()
        ) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Get current shipment
     *
     * @return Mage_Sales_Model_Order_Shipment.
     */
    public function getShipment()
    {
        if ($this->hasShipment()) {
            return $this->_getData('shipment');
        }

        $shipment = Mage::registry('current_shipment');

        $this->setShipment($shipment);
        return $shipment;
    }

    /**
     * Calculates a shipment's base grand total based on it's shipment items
     *
     * @return float|null
     */
    public function getOrderTotal()
    {
        if ($this->hasShipmentBaseGrandTotal()) {
            return $this->_getData('shipment_base_grand_total');
        }

        /**
         * Check if this Myparcel shipment has a linked Mage_Sales_Model_Order_Shipment object
         */
        $shipment = $this->getShipment();
        if (!$shipment) {
            return null;
        }

        /**
         * Loop through all associated shipment items and add each item's row total to the shipment's total
         */
        $baseGrandTotal = 0;
        $shipmentItems = $shipment->getAllItems();

        /**
         * @var Mage_Sales_Model_Order_Shipment_Item $shipmentItem
         */
        foreach ($shipmentItems as $shipmentItem) {
            $qty = $shipmentItem->getQty();

            /**
             * The base price of a shipment item is only available through it's associated order item
             */
            $basePrice = $shipmentItem->getOrderItem()->getBasePrice();

            /**
             * Calculate and add the shipment item's row total
             */
            $totalBasePrice = $basePrice * $qty;
            $baseGrandTotal += $totalBasePrice;
        }

        $this->setShipmentBaseGrandTotal($baseGrandTotal);
        return $baseGrandTotal;
    }

    /**
     * Get this shipment's country of destination;
     *
     * @return mixed
     */
    public function getDestinationCountry()
    {
        if ($this->hasDestinationCountry()) {
            $countryCode = $this->_getData('destination_country');
            return $countryCode;
        }

        $shipment = $this->getShipment();

        $shippingAddress = $shipment->getShippingAddress();

        $countryCode = $shippingAddress->getCountry();

        $this->setDestinationCountry($countryCode);
        return $countryCode;
    }

    /**
     * Check if shipment country needs customs
     *
     * @return bool
     */
    public function countryNeedsCustoms()
    {
        $helper = Mage::helper('tig_myparcel');

        $countryCode = $this->getDestinationCountry();

        return $helper->countryNeedsCustoms($countryCode);

    }

    /**
     * get storeid from where the order was placed
     *
     * @return int
     */
    public function getOrderStoreId()
    {
        $shipment = $this->getShipment();

        return $shipment->getOrder()->getStoreId();
    }

    /**
     * get the customs type array
     *
     * @return array
     */
    public function getCustomsTypeOptions()
    {
        return Mage::getModel('tig_myparcel/system_config_source_customs')->toOptionArray();
    }

    /**
     * @param $shipmentOption
     *
     * @return string
     */
    public function getIsSelected($shipmentOption)
    {
        $helper = Mage::helper('tig_myparcel');
        $storeId = $this->getOrderStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        $configValue = $helper->getConfig($shipmentOption,'shipment',$storeId);
        if(!empty($configValue) && $configValue > 0){
            if($orderTotalShipped >= $configValue){
                return 'checked="checked"';
            }
        }
        return '';
    }

    /**
     * @return string
     */
    public function getIsHomeSelected()
    {
        $checkoutData = $this->getShipment()->getOrder()->getMyparcelData();
        if($checkoutData !== null) {
            $aData = json_decode($checkoutData, true);
            if(key_exists('home_address_only', $aData) && $aData['home_address_only']){
                return 'checked="checked"';
            } else {
                return $this->getIsSelected('home_address_only');
            }
        } else {
            return $this->getIsSelected('home_address_only');
        }
    }

    /**
     * @return string
     */
    public function getIsSignatureOnReceipt()
    {
        $checkoutData = $this->getShipment()->getOrder()->getMyparcelData();
        if($checkoutData !== null) {
            $aData = json_decode($checkoutData, true);
            if(key_exists('signed', $aData) && $aData['signed']){
                return 'checked="checked"';
            } else {
                return $this->getIsSelected('signature_on_receipt');
            }
        } else {
            return $this->getIsSelected('signature_on_receipt');
        }
    }

    /**
     * @return string
     */
    public function getIsReturnOnNoAnswer()
    {
        return $this->getIsSelected('return_if_no_answer');
    }

    /**
     * @return string
     */
    public function getIsXl()
    {
        return $this->getIsSelected('is_xl');
    }

    /**
     * @return string
     */
    public function getIsInsured()
    {

        //load helper, store id and orderTotal
        $helper            = Mage::helper('tig_myparcel');
        $storeId           = $this->getOrderStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        //get the insured values
        $insuredType100    = $helper->getConfig('insured_100',  'shipment', $storeId);
        $insuredType250    = $helper->getConfig('insured_250', 'shipment', $storeId);
        $insuredType500    = $helper->getConfig('insured_500', 'shipment', $storeId);

        //check if the values are not empty/zero
        $insuredType100    = (!empty($insuredType100) && $insuredType100 > 0)? $insuredType100 : false;
        $insuredType250    = (!empty($insuredType250) && $insuredType250 > 0)? $insuredType250 : false;
        $insuredType500    = (!empty($insuredType500) && $insuredType500 > 0)? $insuredType500 : false;

        //if nothing is filled in, then set the default values, but do not pre-select
        $selected = 'checked="checked"';
        if(
            false === $insuredType100 &&
            false === $insuredType250 &&
            false === $insuredType500
        ){
            $insuredType100 = 100;
            $insuredType250 = 250;
            $insuredType500 = 500;
            $selected = 0;
        }

        if(false !== $insuredType500 && $orderTotalShipped > $insuredType500){
            $insuredValue = $insuredType500;
            $insuredUpTo = 500;
        }elseif(false !== $insuredType250 && $orderTotalShipped > $insuredType250){
            $insuredValue = $insuredType250;
            $insuredUpTo = 250;
        }elseif(false !== $insuredType100 && $orderTotalShipped > $insuredType100){
            $insuredValue = $insuredType100;
            $insuredUpTo = 100;
        }else{
            $insuredValue = 0;
            $insuredUpTo = 0;
            $selected = 0;
        }

        $returnArray = array(
            'option'         => 'insured',
            'selected'       => $selected,
            'insuredAmount' => 0,
            'insuredUpTo'   => 0,
        );

        if($insuredValue > 0){
            $returnArray = array(
                'option'        => 'insured',
                'selected'      => $selected,
                'insuredAmount' => $insuredValue,
                'insuredUpTo'   => $insuredUpTo,
            );
        }

        return $returnArray;
    }


    /**
     * @param $items
     *
     * @return array
     */
    public function getIsDigitalStamp($items)
    {
        $itemWeight = 0;
        foreach ($items as $item) {
            $qty = $item->getQty();
            $qty = $qty == null ? $item->getData('qty_ordered') : $qty;
            if ($item instanceof Mage_Sales_Model_Order_Shipment_Item) {
                /** @var Mage_Sales_Model_Order_Item $item */
                $id = $item->getProductId();
            } else {
                /** @var Mage_Sales_Model_Quote_Address_Item $item */
                $id = $item->getProduct()->getId();
            }

            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($id);
            if (empty($parentIds)) {
                $itemWeight += $item->getWeight() * $qty;
            }
        }

        // check in which category the weight is and calculate it in grams
        $itemWeight = $itemWeight * 1000;
        if ($itemWeight <= 20){
            $digitalStampUpTo = 20;
        } else if ($itemWeight <= 50){ // weight between 20 and 50 gram
            $digitalStampUpTo = 50;
        } else if ($itemWeight <= 100){ // weight between 50 and 100 gram
            $digitalStampUpTo = 100;
        } else if ($itemWeight <= 350){ // weight between 100 and 350 gram
            $digitalStampUpTo = 350;
        } else if ($itemWeight <= 2000){ // weight between 350 and 2000 gram
            $digitalStampUpTo = 2000;
        }

        $result = array(
            'digitalStampUpTo'  => $digitalStampUpTo,
            'selected'          => 'checked="checked"',
        );

        return $result;
    }

    public function getMultiCollo()
    {
        // For now the default multi colli amount is always 1
        $colloAmount = 1;

        $result = array(
            'colloAmount' => $colloAmount,
            'selected'    => 'checked="checked"',
        );

        return $result;
    }

    /**
     * Check if the shipment is placed using Pakjegemak
     *
     * @return bool
     */
    public function getIsPakjeGemak()
    {
        $helper   = Mage::helper('tig_myparcel');
        $shipment = Mage::registry('current_shipment');

        if($helper->getPgAddress($shipment->getOrder())){
            return true;
        }
        return false;
    }

    /**
     * Check if the shipment has extra options
     *
     * @return bool
     */
    public function hasExtraOptions()
    {
        /**
         * @var \TIG_MyParcel2014_Helper_Data $helper
         * @var \TIG_MyParcel2014_Model_Shipment $shipment
         */
        $helper   = Mage::helper('tig_myparcel');
        $shipment = Mage::registry('current_shipment');

        if($helper->shippingHasExtraOptions($shipment->getOrder()->getShippingMethod())){
            return true;
        }
        return false;
    }
}
