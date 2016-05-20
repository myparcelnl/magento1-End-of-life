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

    public function __construct()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $this->_order = Mage::getModel('sales/order')->load($orderId);
        $this->_helper = Mage::helper('tig_myparcel');
    }

    public function getPgAddressHtml()
    {
        $res = array();
        $pgAddress = $this->_helper->getPgAddress($this->_order);
        $shippingMethod = $this->_order->getShippingMethod();

        if ($pgAddress && $this->_helper->shippingMethodIsPakjegemak($shippingMethod))
        {
            $res = array(
                $pgAddress->getCompany(),
                implode(' ', $pgAddress->getStreet()),
                $pgAddress->getPostcode() . ' ' . $pgAddress->getCity() . ' (' . $pgAddress->getCountry() . ')',
            );
        }

        return empty($res) ? '' : '<p>' . implode('<br/>', $res) . '</p>';
    }

    /**
     * Get all current MyParcel options
     *
     * @return string
     */
    public function getCurrentOrderOptionsHtml()
    {
        $optionsHtml = '';
        $myParcelShipments = Mage::getModel('tig_myparcel/shipment')
            ->getCollection()
            ->addFieldToFilter('order_id', $this->_order->getId());

        foreach ($myParcelShipments as $myParcelShipment) {
            $shipmentUrl = Mage::helper('adminhtml')->getUrl("*/sales_shipment/view", array('shipment_id'=>$myParcelShipment->getShipment()->getId()));
            $optionsHtml .= '<br><a href="'.$shipmentUrl.'">' . $myParcelShipment->getBarcode() . '</a>: ' . $this->_helper->getCurrentOptionsHtml($myParcelShipment);
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
