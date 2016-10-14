<?php
/**
 *  Rewrite getShippingAddress to retrieve pakjegemak address if possible.
 *  MDN_AdvancedStock_Model_Sales_Order is a fix for a MDN plugin
 *
 *
 *  @author     Reindert Vetter <info@myparcel.nl>
 *  @copyright  2016 MyParcelNL
 *  @since      File available since Release 1.5.0
 *  @license    http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 *
 *  Class TIG_MyParcel2014_Model_Sales_Order
 */
    if (file_exists('MDN/AdvancedStock/Model/Sales/Order.php') && class_exists('MDN_AdvancedStock_Model_Sales_Order')) {
        class TIG_MyParcel2014_Model_Sales_Order_OverrideCheck extends MDN_AdvancedStock_Model_Sales_Order { }
    } else {
        class TIG_MyParcel2014_Model_Sales_Order_OverrideCheck extends Mage_Sales_Model_Order { }
    }

    class TIG_MyParcel2014_Model_Sales_Order extends TIG_MyParcel2014_Model_Sales_Order_OverrideCheck
    {

        /**
         * Retrieve order shipping address
         *
         * @return Mage_Sales_Model_Order_Address
         */
        public function getShippingAddress() {
            $helper = Mage::helper('tig_myparcel');
            $usePgAddress = $helper->getConfig('pakjegemak_use_shipment_address') === '1';

            $parentFunctions = debug_backtrace();
            if($parentFunctions[3]['class'] != 'TIG_Afterpay_Model_PaymentFee_Observer'){
                $usePgAddress = false;
            }
            if($parentFunctions[3]['function'] == '_getConsignmentData') {
                $usePgAddress = false;
            }

            $pgAddress = false;
            $shippingAddress = false;

            foreach ($this->getAddressesCollection() as $address) {

                /**
                 * Get Pakjegemak address
                 */
                if ($address->getAddressType()=='pakje_gemak' && !$address->isDeleted() && $usePgAddress) {
                    $pgAddress = $address;
                }
                if ($address->getAddressType()=='shipping' && !$address->isDeleted()) {
                    $shippingAddress = $address;
                }
            }

            if($pgAddress) {
                return $pgAddress;
            }

            if($shippingAddress) {
                return $shippingAddress;
            }

            return false;
        }

    }