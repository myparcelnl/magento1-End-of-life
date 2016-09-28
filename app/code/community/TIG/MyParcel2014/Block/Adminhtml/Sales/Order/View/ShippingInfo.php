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
class TIG_MyParcel2014_Block_Adminhtml_Sales_Order_View_ShippingInfo extends Mage_Adminhtml_Block_Abstract
{
    /**
     * @var Mage_Sales_Model_Order|TIG_MyParcel2014_Model_Sales_Order
     */
    protected $_order;
    protected $_helper;
    protected $_myParcelShipments;

    public function __construct()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $this->_order = Mage::getModel('sales/order')->load($orderId);
        $this->_helper = Mage::helper('tig_myparcel');

        $this->_myParcelShipments = Mage::getModel('tig_myparcel/shipment')
            ->getCollection()
            ->addFieldToFilter('order_id', $this->_order->getId());
    }

    /**
     * Collect options selected at checkout and calculate type consignment
     *
     * @return string
     */
    public function getCheckoutOptionsHtml()
    {

        $html = false;

        $pgAddress = $this->_helper->getPgAddress($this->_order);
        /** @var object $data Data from checkout */
        $data = $this->_order->getMyparcelData() !== null ? json_decode($this->_order->getMyparcelData(), true) : false;
        $shippingMethod = $this->_order->getShippingMethod();

        if ($pgAddress && $this->_helper->shippingMethodIsPakjegemak($shippingMethod))
        {
            if($data){
                $dateTime = date('d-m-Y H:i', strtotime($data['date'] . ' ' . $data['start_time']));
                $html .= $this->__('PostNL location:') . ' ' . $dateTime;
                if($data['price_comment'] != 'retail')
                    $html .= ', ' . $this->__('TYPE_' . $data['price_comment']);
                $html .= ', ' . $data['location']. ', ' . $data['city']. ' (' . $data['postal_code']. ')';
            } else {
                /** Old data from orders before version 1.6.0 */
                $html .= $this->__('PostNL location:') . ' ' . $pgAddress->getCompany() . ' ' . $pgAddress->getCity();
            }
        } else {

            // Get package type
            $totalWeight = $this->_helper->getTotalWeight($this->_order->getAllVisibleItems());
            if($totalWeight !== false){
                $html .= $this->_helper->getPackageType($totalWeight, $this->_order->getShippingAddress()->getCountryId(), true) . ' ';

                if($data){
                    $dateTime = date('d-m-Y H:i', strtotime($data['date']. ' ' . $data['time'][0]['start']));
                    $html .= $this->__('deliver:') .' ' . $dateTime;

                    if($data['time'][0]['price_comment'] != 'standard')
                        $html .=  ', ' . $this->__('TYPE_' . $data['time'][0]['price_comment']);

                    if($data['home_address_only'])
                        $html .=  ', ' . strtolower($this->__('Home address only'));

                    if($data['signed'])
                        $html .=  ', ' . strtolower($this->__('Signature on receipt'));
                }
            }
        }

        return $html !== false ? '<br>' . $html : '' ;
    }

    /**
     * Get all current MyParcel options
     *
     * @return string
     */
    public function getCurrentOrderOptionsHtml()
    {
        $optionsHtml = '';
        /** @var $myParcelShipment TIG_MyParcel2014_Model_Shipment */
        foreach ($this->_myParcelShipments as $myParcelShipment) {
            $shipmentUrl = Mage::helper('adminhtml')->getUrl("*/sales_shipment/view", array('shipment_id'=>$myParcelShipment->getShipment()->getId()));
            $linkText = $myParcelShipment->getBarcode() ? $myParcelShipment->getBarcode() : $this->__('Shipment');
            $optionsHtml .= '<p><a href="'.$shipmentUrl.'">' . $linkText . '</a>: ' . $this->_helper->getCurrentOptionsHtml($myParcelShipment) .'</p>';
        }

        return $optionsHtml;
    }

    /**
     * Do a few checks to see if the template should be rendered before actually rendering it.
     *
     * @return string
     *
     * @see Mage_Adminhtml_Block_Abstract::_toHtml()
     */
    protected function _toHtml()
    {
        $shippingMethod = $this->_order->getShippingMethod();

        if (!$this->_helper->isEnabled()
            || !$this->_order
            || !$this->_helper->shippingMethodIsMyParcel($shippingMethod)
        ) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Check if the shipment is placed using Pakjegemak
     *
     * @return bool
     */
    public function getIsPakjeGemak()
    {
        $shipment = Mage::registry('current_shipment');

        if($this->_helper->getPgAddress($shipment->getOrder())){
            return true;
        }
        return false;
    }
}
