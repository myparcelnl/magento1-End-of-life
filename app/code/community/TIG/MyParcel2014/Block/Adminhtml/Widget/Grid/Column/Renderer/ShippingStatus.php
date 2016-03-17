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

        // if methode == bolcom_bolcom change all shipping methods to bolcom_fratrate
        if('bolcom_bolcom' == $shippingMethod){

            $orders = Mage::getModel('sales/order')->getCollection();
            try {
                $orders->addAttributeToFilter('shipping_method', array('eq' => 'bolcom_bolcom'))->load();
                foreach($orders as $order)
                {
                    $order->setShippingMethod('bolcom_flatrate')->save();
                    $shippingMethod = 'bolcom_flatrate';
                }

            } catch (Exception $e){
                echo $e->getMessage();
            }

        }

        if (!Mage::helper('tig_myparcel')->shippingMethodIsMyParcel($shippingMethod)) {
            return '';
        }

        $countryCode = $row->getData(self::COUNTRY_ID_COLUMN);
        /**
         * Check if any data is available.
         */
        $value = $row->getData($this->getColumn()->getIndex());
        if (!$value) {

            $orderSendUrl = Mage::helper('adminhtml')->getUrl("adminhtml/sales_order_shipment/start", array('order_id' => $row->getId()));
            return  ' <a class="scalable go" href="' . $orderSendUrl . '" style="">' . $this->__('Send'). '</a> ' . $countryCode;
        }

        /**
         * Create a track & trace URL based on shipping destination
         */
        $postcode = $row->getData(self::POSTCODE_COLUMN);
        $destinationData = array(
            'countryCode' => $countryCode,
            'postcode'    => $postcode,
        );

        $barcodeData = array();
        $barcodes = explode(',', $row->getData(self::BARCODE_COLUMN));
        $statusses = explode(',', $value);
        foreach ($statusses as $key => $status) {
            if (!empty($barcodes[$key])) {
                $barcodeUrl = Mage::helper('tig_myparcel')->getBarcodeUrl($barcodes[$key], $destinationData, false, true);
                $oneBarcodeData = "<a href='{$barcodeUrl}' target='_blank'>{$barcodes[$key]}</a> - <small>{$status}</small>";
                if(!in_array($oneBarcodeData, $barcodeData)) {
                    $barcodeData[] = $oneBarcodeData;
                }
            }
        }

        $barcodeHtml = implode('<br />', $barcodeData);

        return $barcodeHtml;
    }
}
