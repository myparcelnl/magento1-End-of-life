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
    const POSTCODE_COLUMN        = 'postcode';
    const COUNTRY_ID_COLUMN      = 'country_id';
    const BARCODE_COLUMN         = 'barcode';
    const STATUS_COLUMN          = 'status';

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

        /**
         * The shipment was not shipped using MyParcel
         */
        $shippingMethod = $row->getData(self::SHIPPING_METHOD_COLUMN);

        if (!Mage::helper('tig_myparcel')->shippingMethodIsMyParcel($shippingMethod)) {
            return '';
        }

        $countryCode = $row->getData(self::COUNTRY_ID_COLUMN);

        /**
         * Check if any data is available.
         * If not available, show send link and country code
         */
        $value = $row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel('sales/order')->load($row->getId());

        if (!$value) {
            if ($order->canShip()) {

                $orderSendUrl = Mage::helper('adminhtml')->getUrl("adminhtml/sales_order_shipment/start", array('order_id' => $row->getId()));

                $data = json_decode($order->getMyparcelData(), true);
                $dateTime = strtotime($data['date'] . ' 00:00:00');

                $dropOffDate = $this->_getDropOffDay($dateTime);

                $sDropOff =  Mage::app()->getLocale()->date($dropOffDate)->toString('d MMM');

                if (date('Ymd') == date('Ymd', $dropOffDate)) {
                    $dropOff = $this->__('Today') . ' <a class="scalable go" href="' . $orderSendUrl . '" style="">' . $this->__('Send') . '</a> ';
                } else if (date('Ymd') > date('Ymd', $dropOffDate)) {
                    $dropOff = $sDropOff . ' <a class="scalable go" href="' . $orderSendUrl . '" style="">' . $this->__('Send') . '</a> !';
                } else {
                    $dropOff = $sDropOff;
                }

                return $countryCode . ' - ' . $dropOff;

            } else {

                return $countryCode;

            }
        }

        /**
         * Create a track & trace URL based on shipping destination
         */
        $postcode = $row->getData(self::POSTCODE_COLUMN);
        $destinationData = array(
            'countryCode' => $countryCode,
            'postcode' => $postcode,
        );

        $barcodeData = array();
        $barcodes = explode(',', $row->getData(self::BARCODE_COLUMN));
        $statusses = explode(',', $value);

        foreach ($statusses as $key => $status) {

            if (!empty($barcodes[$key])) {
                $barcodeUrl = Mage::helper('tig_myparcel')->getBarcodeUrl($barcodes[$key], $destinationData, false, true);
                $oneBarcodeData = "<a href='{$barcodeUrl}' target='_blank'>{$barcodes[$key]}</a> - <small>" . $this->__('status_' . $status) . "</small>";
                if (!in_array($oneBarcodeData, $barcodeData)) {
                    $barcodeData[] = $oneBarcodeData;
                }
            } else {
                $barcodeData[] = "<small>" . $this->__('status_' . $status) . "</small>";
            }
        }

        $barcodeHtml = implode('<br />', $barcodeData);

        return $barcodeHtml;
    }

    /**
     * Get drop off day
     *
     * @param $dateTime int
     *
     * @return int
     */
    private function _getDropOffDay($dateTime)
    {
        $weekDay = date('N', $dateTime);

        switch ($weekDay) {
            case (1):
                $dropOff = strtotime("-3 day", $dateTime);
                break;
            case (2):
                $dropOff = strtotime("-2 day", $dateTime);
                break;
            case (3):
            case (4):
            case (5):
            case (6):
            case (7):
                $dropOff = strtotime("-1 day", $dateTime);
                break;
        }

        return $dropOff;
    }
}
