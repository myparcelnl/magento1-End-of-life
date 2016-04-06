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
 * @method boolean hasShipmentId()
 * @method boolean hasOrderId()
 * @method boolean hasConsignmentId()
 * @method boolean hasCreatedAt()
 * @method boolean hasUpdatedAt()
 * @method boolean hasStatus()
 * @method boolean hasBarcode()
 * @method boolean hasIsFinal()
 * @method boolean hasShipment()
 * @method boolean hasOrder()
 * @method boolean hasShippingAddress()
 * @method boolean hasApi()
 * @method boolean hasShipmentIncrementId()
 * @method boolean hasBarcodeSend()
 * @method boolean hasShipmentType()
 *
 * @method string getShipmentId()
 * @method string getConsignmentId()
 * @method string getCreatedAt()
 * @method string getUpdatedAt()
 * @method string getStatus()
 * @method string getBarcode()
 * @method string getIsFinal()
 * @method int    getHomeAddressOnly()
 * @method int    getSignatureOnReceipt()
 * @method int    getReturnIfNoAnswer()
 * @method int    getInsured()
 * @method int    getInsuredAmount()
 * @method int    getBarcodeSend()
 * @method int    getCustomsContentType()
 * @method string getShipmentType()
 *
 * @method TIG_MyParcel2014_Model_Shipment setShipmentId(int $value)
 * @method TIG_MyParcel2014_Model_Shipment setOrderId(int $value)
 * @method TIG_MyParcel2014_Model_Shipment setConsignmentId(int $value)
 * @method TIG_MyParcel2014_Model_Shipment setCreatedAt(string $value)
 * @method TIG_MyParcel2014_Model_Shipment setUpdatedAt(string $value)
 * @method TIG_MyParcel2014_Model_Shipment setStatus(string $value)
 * @method TIG_MyParcel2014_Model_Shipment setBarcode(string $value)
 * @method TIG_MyParcel2014_Model_Shipment setIsFinal(int $value)
 * @method TIG_MyParcel2014_Model_Shipment setShipment(Mage_Sales_Model_Order_Shipment $value)
 * @method TIG_MyParcel2014_Model_Shipment setOrder(Mage_Sales_Model_Order $value)
 * @method TIG_MyParcel2014_Model_Shipment setShippingAddress(Mage_Sales_Model_Order_Address $value)
 * @method TIG_MyParcel2014_Model_Shipment setApi(TIG_MyParcel2014_Model_Api_MyParcel $value)
 * @method TIG_MyParcel2014_Model_Shipment setShipmentIncrementId(string $value)
 * @method TIG_MyParcel2014_Model_Shipment setBarcodeSend(int $value)
 * @method TIG_MyParcel2014_Model_Shipment setRetourlink(string $value)
 * @method TIG_MyParcel2014_Model_Shipment setIsCredit(int $value)
 * @method TIG_MyParcel2014_Model_Shipment setCustomsContentType(int $value)
 * @method TIG_MyParcel2014_Model_Shipment setShipmentType(string $value)
 *
 */
class TIG_MyParcel2014_Model_Shipment extends Mage_Core_Model_Abstract
{
    /**
     * Carrier code used by MyParcel.
     */
    const MYPARCEL_CARRIER_CODE = 'myparcel';

    /**
     * Statusses used by MyParcel shipments.
     */
    const STATUS_NEW       = 'new';
    const STATUS_CONFIRMED = 'Aangemeld';

    /**
     * Supported shipment types.
     */
    const TYPE_LETTER_BOX = 'letter_box';
    const TYPE_NORMAL     = 'normal';
    const TYPE_UNPAID  = 'unstamped';

    /**
     * Initialize the shipment
     */
    public function _construct()
    {
        $this->_init('tig_myparcel/shipment');
    }

    /**
     * Gets the Magento shipment associated with this MyParcel shipment.
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        if ($this->hasShipment()) {
            return $this->_getData('shipment');
        }

        /**
         * @var Mage_Sales_Model_Order_Shipment $shipment
         */
        $shipmentId = $this->getShipmentId();
        $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);

        $this->setShipment($shipment);
        return $shipment;
    }

    /**
     * Gets this shipment's order ID.
     *
     * @return mixed
     */
    public function getOrderId()
    {
        if ($this->hasOrderId()) {
            return $this->_getData('order_id');
        }

        $orderId = $this->getShipment()->getOrderId();

        $this->setOrderId($orderId);
        return $orderId;
    }

    /**
     * Gets the Magento order associated with this MyParcel shipment.
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->hasOrder()) {
            return $this->_getData('order');
        }

        /**
         * @var Mage_Sales_Model_Order $order
         */
        $orderId = $this->getOrderId();
        $order = Mage::getModel('sales/order')->load($orderId);

        $this->setOrder($order);
        return $order;
    }

    /**
     * Gets the shipping address of this shipment.
     *
     * @return Mage_Sales_Model_Order_Address
     */
    public function getShippingAddress()
    {
        if ($this->hasShippingAddress()) {
            return $this->_getData('shipping_address');
        }

        $shipment        = $this->getShipment();
        $shippingAddress = $shipment->getShippingAddress();

        $this->setShippingAddress($shippingAddress);
        return $shippingAddress;
    }

    /**
     * Gets the increment ID of this shipment's Magento shipment if available.
     *
     * @return null|string
     */
    public function getShipmentIncrementId()
    {
        if ($this->hasShipmentIncrementId()) {
            return $this->_getData('shipment_increment_id');
        }

        $shipment = $this->getShipment(false);
        if (!$shipment || !$shipment->getIncrementId()) {
            return null;
        }

        $incrementId = $shipment->getIncrementId();

        $this->setShipmentIncrementId($incrementId);
        return $incrementId;
    }

    /**
     * Calculates a shipment's base grand total based on it's shipment items
     *
     * @return float|null
     */
    public function getOrderTotal()
    {
        /**
         * Check if this MyParcel shipment has a linked Mage_Sales_Model_Order_Shipment object
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
        return $baseGrandTotal;
    }

    /**
     * Gets the MyParcel API object.
     *
     * @return TIG_MyParcel2014_Model_Api_MyParcel
     */
    public function getApi()
    {
        if ($this->hasApi()) {
            return $this->_getData('api');
        }

        $storeId = $this->getShipment()->getStoreId();
        $api     = Mage::getModel('tig_myparcel/api_myParcel',array('store_id' => $storeId));

        $this->setApi($api);
        return $api;
    }

    /**
     * @return array
     */
    public function getHomeAddressOnlyOption()
    {
        $consignmentOption = 'home_address_only';

        $helper = Mage::helper('tig_myparcel');
        $storeId = $this->getOrder()->getStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        $configValue = $helper->getConfig($consignmentOption,'shipment',$storeId);
        if(!empty($configValue) && $configValue > 0){
            if($orderTotalShipped >= $configValue){
                return array(
                    'option' => $consignmentOption,
                    'selected' => 1,
                );
            }
        }
        return array(
            'option' => $consignmentOption,
            'selected' => 0,
        );

    }

    /**
     * @return array
     */
    public function getSignatureOnReceiptOption()
    {
        $consignmentOption = 'signature_on_receipt';

        $helper = Mage::helper('tig_myparcel');
        $storeId = $this->getOrder()->getStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        $configValue = $helper->getConfig($consignmentOption,'shipment',$storeId);
        if(!empty($configValue) && $configValue > 0){
            if($orderTotalShipped >= $configValue){
                return array(
                    'option' => $consignmentOption,
                    'selected' => 1,
                );
            }
        }
        return array(
            'option' => $consignmentOption,
            'selected' => 0,
        );
    }

    /**
     * @return array
     */
    public function getReturnIfNoAnswerOption()
    {
        $consignmentOption = 'return_if_no_answer';

        $helper = Mage::helper('tig_myparcel');
        $storeId = $this->getOrder()->getStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        $configValue = $helper->getConfig($consignmentOption,'shipment',$storeId);
        if(!empty($configValue) && $configValue > 0){
            if($orderTotalShipped >= $configValue){
                return array(
                    'option' => $consignmentOption,
                    'selected' => 1,
                );
            }
        }
        return array(
            'option' => $consignmentOption,
            'selected' => 0,
        );
    }

    /**
     * get the insured amount
     *
     * @return array
     */
    public function getInsuredOption()
    {
        //load helper, store id and orderTotal
        $helper            = Mage::helper('tig_myparcel');
        $storeId           = $this->getOrderStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        //get the insured values
        $insuredType50     = $helper->getConfig('insured_50','shipment',$storeId);
        $insuredType250    = $helper->getConfig('insured_250','shipment',$storeId);
        $insuredType500    = $helper->getConfig('insured_500','shipment',$storeId);

        //check if the values are not empty/zero.
        $insuredType50     = (!empty($insuredType50) && $insuredType50 > 0)? $insuredType50 : false;
        $insuredType250    = (!empty($insuredType250) && $insuredType250 > 0)? $insuredType250 : false;
        $insuredType500    = (!empty($insuredType500) && $insuredType500 > 0)? $insuredType500 : false;


        if(false !== $insuredType500 && $orderTotalShipped > $insuredType500){
            $insuredValue = 500;
        }elseif(false !== $insuredType250 && $orderTotalShipped > $insuredType250){
            $insuredValue = 250;
        }elseif(false !== $insuredType50 && $orderTotalShipped > $insuredType50){
            $insuredValue = 50;
        }else{
            $insuredValue = 0;
        }

        $returnArray = array(
            'option'         => 'insured',
            'selected'       => 0,
            'insured_amount' => 0,
        );

        if($insuredValue > 0){
            $returnArray = array(
                'option'         => 'insured',
                'selected'       => 1,
                'insured_amount' => $insuredValue,
            );
        }

        return $returnArray;
    }

    /**
     * @return $this
     */
    public function calculateConsignmentOptions()
    {
        $homeAddressOnly     = $this->getHomeAddressOnlyOption();
        $signtatureOnReceipt = $this->getSignatureOnReceiptOption();
        $returnIfNoAnswer    = $this->getReturnIfNoAnswerOption();
        $insured             = $this->getInsuredOption();

        $this->setDataUsingMethod($homeAddressOnly['option'], $homeAddressOnly['selected']);
        $this->setDataUsingMethod($signtatureOnReceipt['option'], $signtatureOnReceipt['selected']);
        $this->setDataUsingMethod($returnIfNoAnswer['option'], $returnIfNoAnswer['selected']);
        $this->setDataUsingMethod($insured['option'], $insured['selected']);
        $this->setDataUsingMethod('insured_amount', $insured['insured_amount']);

        return $this;
    }

    /**
     * Sets an array of consignment options. If any options were set in the registry, those will be used as well.
     *
     * @param array $consignmentOptions
     *
     * @return $this
     */
    public function setConsignmentOptions($consignmentOptions = array())
    {
        /**
         * If any consignment options were set in the registry, those will be added as well.
         */
        $registryOptions = Mage::registry('tig_myparcel_consignment_options');

        $filteredOptions = $registryOptions;
        unset($filteredOptions['create_consignment']);
        unset($filteredOptions['type_consignment']);



        if (!empty($filteredOptions) && is_array($filteredOptions)) {
            $consignmentOptions = array_merge($consignmentOptions, $registryOptions);
        }

        /**
         * is only empty when the myparcel shipment is created in a mass-action
         */

        if(empty($consignmentOptions) && empty($filteredOptions)){
            $this->calculateConsignmentOptions();
            $this->setDataUsingMethod('shipment_type', $registryOptions['type_consignment']);
            return $this;
        }

        /**
         * Add the options.
         */
        foreach ($consignmentOptions as $option => $value) {
            /**
             * The insured_amount option is dependant on the 'insured' option.
             */
            if ($option == 'insured_amount'
                && (!isset($registryOptions['insured'])
                    || $registryOptions['insured'] != '1'
                )
            ) {
                continue;
            }

            if ($option == 'shipment_type') {
                if (!$this->_isValidType($value)) {
                    $value = self::TYPE_NORMAL;
                }
            }

            $this->setDataUsingMethod($option, $value);
        }

        return $this;
    }

    /**
     * Checks if a consignment can be created for this current shipment.
     *
     * @return bool
     */
    public function canCreateConsignment()
    {

        if ($this->hasConsignmentId()) {
            return false;
        }

        if ($this->hasStatus() && $this->getStatus() != self::STATUS_NEW) {
            return false;
        }

        return true;
    }

    /**
     * Create a consignment using the MyParcel API.
     *
     * @returns $this
     *
     * @throws TIG_MyParcel2014_Exception
     */
    public function createConsignment()
    {

        if (!$this->canCreateConsignment()) {
            throw new TIG_MyParcel2014_Exception(
                $helper->__('The createConsignment action is currently unavailable.'),
                'MYPA-0011'
            );
        }

        /**
         * Send the createConsignment request using the MyParcel API.
         *
         * @var TIG_MyParcel2014_Model_Api_MyParcel $api
         */
        $api = $this->getApi();
        $response = $api->createConsignmentRequest($this)
                        ->setStoreId($this->getOrder()->getStoreId())
                        ->sendRequest()
                        ->getRequestResponse();

        /**
         * Validate the response.
         */
        if (!is_array($response)
            || !isset($response['data']['ids'][0])
            || !is_numeric($response['data']['ids'][0]['id'])
        ) {
            throw new TIG_MyParcel2014_Exception(
                $helper->__('Invalid createConsignment response: %s', $api->getRequestErrorDetail()),
                'MYPA-0012'
            );
        }

        $status = self::STATUS_NEW;



        /**
         * set status (new or confirmed)
         */
        $this->setStatus($status);

        /**
         * Get the consignment ID and set it.
         */
        $consignmentId = (int) $response['data']['ids'][0]['id'];

        $this->setConsignmentId($consignmentId);
        return $this;
    }

    /**
     * Send barcode mail and set status history comment
     *
     * @return bool
     * @throws Exception
     */
    public function sendBarcodeAfterResponse()
    {

        $helper = Mage::helper('tig_myparcel');
        /**
         * check if barcode is available
         */
        if(isset($response['tracktrace']) && !empty($response['tracktrace'])){
            $barcode  = $response['tracktrace'];
            $shipment = $this;
            $isSend   = $helper->sendBarcodeEmail($barcode,$shipment);
            $this->setBarcode($barcode);
            $status = self::STATUS_CONFIRMED;

            //add comment to order-comment history
            $shippingAddress = $this->getShippingAddress();
            $barcodeUrl      = $helper->getBarcodeUrl($barcode, $shippingAddress);
            if($isSend){
                //add comment to order-comment history
                $comment = $helper->__('Track&amp;Trace e-mail is send: %s', $barcodeUrl);

                //flag the myparcel shipment that barcode
                $this->setBarcodeSend(true);
            } else {
                $comment = $helper->__('Track&amp;Trace link: %s', $barcodeUrl);
            }
            $helper->log($comment);
            $order = $shipment->getOrder();
            $order->addStatusHistoryComment($comment);
            $order->setEmailSent(false);
            $order->save();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Adds Magento tracking information to the order containing the previously retrieved barcode.
     *
     * @param string $trackAndTraceCode
     *
     * @return $this
     *
     * @throws TIG_MyParcel2014_Exception
     */
    public function addTrackingCodeToShipment($trackAndTraceCode)
    {
        $helper = Mage::helper('tig_myparcel');
        $shipment = $this->getShipment();

        if (!$shipment || !$trackAndTraceCode) {
            throw new TIG_MyParcel2014_Exception(
                $helper->__(
                    'Unable to add tracking info: no track&amp;trace code or shipment available.'
                ),
                'MYPA-0013'
            );
        }

        $carrierCode = self::MYPARCEL_CARRIER_CODE;
        $carrierTitle = Mage::getStoreConfig('carriers/' . $carrierCode . '/name', $shipment->getStoreId());

        $data = array(
            'carrier_code' => $carrierCode,
            'title'        => $carrierTitle,
            'number'       => $trackAndTraceCode,
        );

        /**
         * @var Mage_Sales_Model_Order_Shipment_Track $track
         */
        $track = Mage::getModel('sales/order_shipment_track')->addData($data);
        $shipment->addTrack($track);

        /**
         * Save the Mage_Sales_Order_Shipment object
         *
         * @var Mage_Core_Model_Resource_Transaction $transaction
         */
        $transaction = Mage::getModel('core/resource_transaction');
        $transaction->addObject($shipment)
                    ->save();

        return $this;
    }

    /**
     * Checks if the given shipment type is supported by this extension.
     *
     * @param $type
     *
     * @return bool
     */
    protected function _isValidType($type)
    {
        $isValid = false;
        switch ($type) {
            case self::TYPE_NORMAL:
                $isValid = true;
                break;
            case self::TYPE_LETTER_BOX: //no break
            case self::TYPE_UNPAID:
                if ($this->isDutchShipment()) {
                    $isValid = true;
                }
                break;
            //no default
        }

        return $isValid;
    }

    /**
     * Check if this shipment's destination is the Netherlands.
     *
     * @return bool
     */
    public function isDutchShipment()
    {
        $shippingAddress = $this->getShippingAddress();
        $country = $shippingAddress->getCountryId();

        if ($country == 'NL') {
            return true;
        }

        return false;
    }

    /**
     * @return $this
     */
    protected function _beforeSave()
    {
        /**
         * If this object is new and does not yet have a status, set the 'new' status.
         */
        if (!$this->getId() && $this->isObjectNew() && !$this->hasStatus()) {
            $this->setStatus(self::STATUS_NEW);
        }

        return parent::_beforeSave();
    }
}
