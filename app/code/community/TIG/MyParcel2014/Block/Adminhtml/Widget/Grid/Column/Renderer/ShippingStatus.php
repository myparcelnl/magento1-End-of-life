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
class TIG_MyParcel2014_Block_Adminhtml_Widget_Grid_Column_Renderer_ShippingStatus
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    /**
     * Additional column names used
     */
    const SHIPPING_METHOD_COLUMN = 'shipping_method';
    const POSTCODE_COLUMN = 'postcode';
    const COUNTRY_ID_COLUMN = 'country_id';
    const BARCODE_COLUMN = 'barcode';
    const STATUS_COLUMN = 'status';

    /**
     * Renders the barcode column. This column will be empty for non-MyParcel shipments.
     * If the shipment has been confirmed, it will be displayed as a track& trace URL.
     * Otherwise the bare code will be displayed.
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        /** @var TIG_MyParcel2014_Helper_Data $helper */
        $helper = $this->helper('tig_myparcel');
        $html = '';

        $order = Mage::getModel('sales/order')->load($row->getId());
        $myParcelShipments = Mage::getModel('tig_myparcel/shipment')
            ->getCollection()
            ->addFieldToFilter('order_id', $row->getId());

        /** @var Mage_Sales_Model_Order $order */
        $shippingMethod = $order->getShippingMethod();
        if($shippingMethod === null)
            return '';

        /**
         * The shipment was not shipped using MyParcel
         */
        if (!$helper->shippingMethodIsMyParcel($shippingMethod) || $order->getIsVirtual())
            return '';

        $countryCode = $order->getShippingAddress()->getCountryId();

        /**
         * Check if any data is available.
         * If not available, show send link and country code
         */

        if ($order->canShip()) {
            $orderSendUrl = Mage::helper('adminhtml')->getUrl("adminhtml/sales_order_shipment/start", array('order_id' => $row->getId()));
            $data = json_decode($order->getMyparcelData(), true);
            if (key_exists('date', $data) && $data['date'] !== null) {
                $dateTime = strtotime($data['date'] . ' 00:00:00');
                $dropOffDate = $helper->getDropOffDay($dateTime);
                $sDropOff = Mage::app()->getLocale()->date($dropOffDate)->toString('d MMM');

                /**
                 * Show info text plus link to send
                 */
                if (date('Ymd') == date('Ymd', $dropOffDate)) {
                    $actionHtml = '<a class="scalable go" href="' . $orderSendUrl . '" style="">' . $this->__('Today') . ' ' . strtolower($this->__('Send')) . '</a> ';
                } else if (date('Ymd') > date('Ymd', $dropOffDate)) {
                    $actionHtml = $sDropOff . ' <a class="scalable go" href="' . $orderSendUrl . '" style="">' . strtolower($this->__('Send')) . '</a> <span style="color:red;font-size: 115%;">&#x2757;</span>';
                } else {
                    $actionHtml = $sDropOff . ' <span style="font-size: 115%;">&#8987;</span>';
                }
            } else {
                $actionHtml = ' <a class="scalable go" href="' . $orderSendUrl . '" style="">' . strtolower($this->__('Send')) . '</a>';
            }

            $html .= '<small>';

            // Letterbox or normal package
            $shippingMethod = $order->getShippingMethod();
            if ($helper->shippingHasExtraOptions($shippingMethod)) {
                $html .= $this->__('Normal') . ' ';
            } else {
                $type = $helper->getPackageType($order->getAllVisibleItems(), $order->getShippingAddress()->getCountryId(), true);
                $html .= $type . ' ';
            }

            $html .= $countryCode . ' - </small>' . $actionHtml;
        }

        if ($myParcelShipments) {
            /**
             * Create a track & trace URL based on shipping destination
             */
            $postcode = $order->getShippingAddress()->getPostcode();
            $destinationData = array(
                'countryCode' => $countryCode,
                'postcode' => $postcode,
            );

            /** @var TIG_MyParcel2014_Model_Shipment $myParcelShipment */
            $i = 0;
            foreach ($myParcelShipments as $myParcelShipment) {

                if ($i++ == 1)
                    $html .= "<br />";

                $barcodeUrl = $helper->getBarcodeUrl($myParcelShipment->getBarcode(), $destinationData, false, true);
                if($myParcelShipment->getBarcode())
                    $html .= "<a href='{$barcodeUrl}' target='_blank'>{$myParcelShipment->getBarcode()}</a>";

                if($myParcelShipment->getConsignmentId() && in_array($myParcelShipment->getShipment()->getShippingAddress()->getCountry(), $helper->getReturnCountries())) {
                    $shipmentUrl = Mage::helper('adminhtml')->getUrl("*/sales_shipment/view", array('shipment_id'=>$myParcelShipment->getShipment()->getId()));
                    $html .= " <a href='{$shipmentUrl}' style='text-decoration:none;' title='{$helper->__('Shipment')}'><img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAMAAABFjsb+AAAAe1BMVEUAAADqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgEq7PRIAAAAKHRSTlMAAQIHCA8RFRkeIy5BRE9fYWNmaWxveYKLjJWanZ6qsLnBzM/X6O3xz27B/QAAAHdJREFUGFd1zssSwUAYROEe4i5CiGtMCOK8/xNaTGqU+ctZdX2rlv7k/NEStaXUnAe6pvGX/WrmvhR7rweSan57ZFKZGHdJVYqLHp9FsalOLQAHBey/ZDugVcD4bwldWOU23r3xUlrO1dic3NiEobHp2ZBGY2uSPuP7Ek2Y8RzhAAAAAElFTkSuQmCC' style='height: 15px; margin-bottom: -4px;'></a>";
                }

                $html .= '&nbsp; <small>' . $this->__('status_' . $myParcelShipment->getStatus()) . "</small>";
            }
        }

        if (!$html)
            $html = $countryCode;

        return $html;
    }
}
