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
     *
     */

    class TIG_MyParcel2014_Model_Carrier_MyParcel extends Mage_Shipping_Model_Carrier_Abstract
        implements Mage_Shipping_Model_Carrier_Interface
    {
        /**
         * Rate type (tablerate or flatrate).
         */
        const XML_PATH_RATE_TYPE = 'carriers/myparcel/rate_type';

    /**
     * MyParcel Carrier code
     *
     * @var string
     */
    protected $_code = TIG_MyParcel2014_Model_Shipment::MYPARCEL_CARRIER_CODE;

    /**
     * Fixed price flag
     *
     * @var boolean
     */
    protected $_isFixed = true;

    /**
     * @var string
     */
    protected $_default_condition_name = 'package_weight';

    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * @return boolean
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Get tracking information.
     *
     * @param string $tracking
     *
     * @return Mage_Shipping_Model_Tracking_Result_Status
     */
    public function getTrackingInfo($tracking)
    {
        $statusModel = Mage::getModel('shipping/tracking_result_status');
        $track = $this->_getTrackByNumber($tracking);
        $shipment = $track->getShipment();

        $shippingAddress = $shipment->getShippingAddress();
        $barcodeUrl = Mage::helper('tig_myparcel')->getBarcodeUrl(
            $track->getTrackNumber(),
            $shippingAddress
        );

        $statusModel->setCarrier($track->getCarrierCode())
            ->setCarrierTitle($this->getConfigData('name'))
            ->setTracking($track->getTrackNumber())
            ->setPopup(1)
            ->setUrl($barcodeUrl);

        return $statusModel;
    }

    /**
     * Load track object by tracking number
     *
     * @param string $number
     *
     * @return Mage_Sales_Model_Order_Shipment_Track
     */
    protected function _getTrackByNumber($number)
    {
        $coreResource = Mage::getSingleton('core/resource');
        $readConn = $coreResource->getConnection('core_read');

        $trackSelect = $readConn->select();
        $trackSelect->from($coreResource->getTableName('sales/shipment_track'), array('entity_id'));
        $trackSelect->where('track_number = ?', $number);

        $trackId = $readConn->fetchOne($trackSelect);

        $track = Mage::getModel('sales/order_shipment_track')->load($trackId);

        return $track;
    }

    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     *
     * @return bool|Mage_Shipping_Model_Rate_Result|mixed|null
     * @throws TIG_MyParcel2014_Exception
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $helper = Mage::helper('tig_myparcel');

        /**
         * @todo Change this so it also works when creating orders in the backend.
         */
        $rateType = Mage::getStoreConfig(self::XML_PATH_RATE_TYPE, Mage::app()->getStore()->getId());

        $result = false;

        if ($rateType == 'flat') {
            $result = $this->_getFlatRate($request);
        }

        if ($rateType == 'table') {
            $result = $this->_getTableRate($request);
        }

        if (!$result) {
            throw new TIG_MyParcel2014_Exception(
                $helper->__('Unknown rate type specified: %s.', $rateType),
                'MYPA-0014'
            );
        }

        // add PakjeGemak if country is NL and not in admin
        if (!$helper->isAdmin()
            && 'NL' === $request->getDestCountryId()
            && $helper->getShippingMethodConfig('pakjegemak', 'active')
            && $this->_shippingMethodValidOrderAmount('pakjegemak')
        ) {
            $currentRate = current($result->getRatesByCarrier($this->_code));

            if ($currentRate) {
                $currentPrice = $currentRate->getPrice();
                $pakjegemakPrice = floatval($helper->getShippingMethodConfig('pakjegemak', 'fee'));

                // use a modified clone of the configured shipping rate
                $pakjegemakRate = clone $currentRate;

                $pakjegemakRate->setMethod('pakjegemak');
                $pakjegemakRate->setMethodTitle($helper->getShippingMethodConfig('pakjegemak', 'title'));
                $pakjegemakRate->setPrice($currentPrice + $pakjegemakPrice);

                $result->append($pakjegemakRate);
            }
        }

        return $result;
    }

    /**
     * Checks config values and quote to see if the total order value is valid for PakjeGemak
     *
     * @return bool
     */
    protected function _shippingMethodValidOrderAmount($method)
    {

        $helper = Mage::helper('tig_myparcel');

        if (!$helper->getShippingMethodConfig($method, 'min_order_enabled')) {
            return true;
        }

        $minOrderTotal = $helper->getShippingMethodConfig($method, 'min_order_total');

        if (!$minOrderTotal) {
            return true;
        }

        /**
         * We have to collect the totals because they have not always been loaded at this point
         * @var Mage_Sales_Model_Quote $quote
         */
        $totals = Mage::getSingleton('checkout/cart')->getQuote()->getTotals();
        $subtotal = $totals["subtotal"]->getValue();

        if ($subtotal < $minOrderTotal) {
            return false;
        }

        return true;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array(
            'flatrate' => $this->getConfigData('name') . ' flat',
            'tablerate' => $this->getConfigData('name') . ' table',
            'pakjegemak' => $this->getConfigData('name') . ' pakjegemak',
        );
    }

    /**
     * @param Mage_Shipping_Model_Rate_Request $request
     *
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function _getFlatRate(Mage_Shipping_Model_Rate_Request $request)
    {
        $freeBoxes = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {

                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeBoxes += $item->getQty() * $child->getQty();
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeBoxes += $item->getQty();
                }
            }
        }
        $this->setFreeBoxes($freeBoxes);

        $result = Mage::getModel('shipping/rate_result');
        if ($this->getConfigData('type') == 'O') { // per order
            $shippingPrice = $this->getConfigData('price');
        } elseif ($this->getConfigData('type') == 'I') { // per item
            $shippingPrice = ($request->getPackageQty() * $this->getConfigData('price')) - ($this->getFreeBoxes() * $this->getConfigData('price'));
        } else {
            $shippingPrice = false;
        }

        $shippingPrice = $this->getFinalPriceWithHandlingFee($shippingPrice);

        if ($shippingPrice !== false) {
            $method = Mage::getModel('shipping/rate_result_method');

            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod('flatrate');
            $method->setMethodTitle($this->getConfigData('name'));

            if ($request->getFreeShipping() === true || $request->getPackageQty() !== null && $request->getPackageQty() == $this->getFreeBoxes()) {
                $shippingPrice = '0.00';
            }

            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);

            $result->append($method);
        }

        return $result;
    }

    /**
     * @param Mage_Shipping_Model_Rate_Request $request
     *
     * @return mixed
     */
    protected function _getTableRate(Mage_Shipping_Model_Rate_Request $request)
    {
        // exclude Virtual products price from Package value if pre-configured
        if (!$this->getConfigFlag('include_virtual_price') && $request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getProduct()->isVirtual()) {
                            $request->setPackageValue($request->getPackageValue() - $child->getBaseRowTotal());
                        }
                    }
                } elseif ($item->getProduct()->isVirtual()) {
                    $request->setPackageValue($request->getPackageValue() - $item->getBaseRowTotal());
                }
            }
        }

        // Free shipping by qty
        $freeQty = 0;
        $freePackageValue = false;
        if ($request->getAllItems()) {
            $freePackageValue = 0;
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeShipping = is_numeric($child->getFreeShipping()) ? $child->getFreeShipping() : 0;
                            $freeQty += $item->getQty() * ($child->getQty() - $freeShipping);
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeShipping = is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : 0;
                    $freeQty += $item->getQty() - $freeShipping;
                    $freePackageValue += $item->getBaseRowTotal();
                }
            }
            $oldValue = $request->getPackageValue();
            $request->setPackageValue($oldValue - $freePackageValue);
        }

        if ($freePackageValue) {
            $request->setPackageValue($request->getPackageValue() - $freePackageValue);
        }

        $conditionName = $this->getConfigData('condition_name');
        $request->setConditionName($conditionName ? $conditionName : $this->_default_condition_name);

        // Package weight and qty free shipping
        $oldWeight = $request->getPackageWeight();
        $oldQty = $request->getPackageQty();

        $request->setPackageWeight($request->getFreeMethodWeight());
        $request->setPackageQty($oldQty - $freeQty);

        $result = Mage::getModel('shipping/rate_result');
        $rate = $this->getRate($request);

        $request->setPackageWeight($oldWeight);
        $request->setPackageQty($oldQty);

        $method = Mage::getModel('shipping/rate_result_method');

        if (!empty($rate) && $rate['price'] >= 0) {
            if ($request->getFreeShipping() === true || ($request->getPackageQty() == $freeQty)) {
                $shippingPrice = 0;
            } else {
                $shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);
            }

            $price = $shippingPrice;
            $cost = $rate['cost'];
        } elseif (empty($rate) && $request->getFreeShipping() === true) {
            /**
             * was applied promotion rule for whole cart
             * other shipping methods could be switched off at all
             * we must show table rate method with 0$ price, if grand_total more, than min table condition_value
             * free setPackageWeight() has already was taken into account
             */
            $request->setPackageValue($freePackageValue);
            $request->setPackageQty($freeQty);
            $rate = $this->getRate($request);
            if (!empty($rate) && $rate['price'] >= 0) {
                $method = Mage::getModel('shipping/rate_result_method');
            }

            $price = 0;
            $cost = 0;
        } else {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier('tablerate');
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $result->append($error);
            return $result;
        }

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('tablerate');
        $method->setMethodTitle($this->getConfigData('name'));

        $method->setPrice($price);
        $method->setCost($cost);

        $result->append($method);

        return $result;
    }

    /**
     * @param Mage_Shipping_Model_Rate_Request $request
     *
     * @return array|bool
     */
    public function getRate(Mage_Shipping_Model_Rate_Request $request)
    {
        //$websiteId = $request->getWebsiteId();
        //$website = Mage::getModel('core/website')->load($websiteId);

        return Mage::getResourceModel('shipping/carrier_tablerate')->getRate($request);
    }
}
