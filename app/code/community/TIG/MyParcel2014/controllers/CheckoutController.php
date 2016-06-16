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
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_MyParcel2014_CheckoutController extends Mage_Core_Controller_Front_Action
{
    public function getInfoAction()
    {
        $helper = Mage::helper('tig_myparcel');
        $data = array();

        $container = file_get_contents('app/design/frontend/base/default/template/TIG/MyParcel2014/checkout/mypa_container.php');

        $general['cutoffTime'] =               $helper->getConfig('cutoff_time', 'checkout');
        $general['deliverydaysWindow'] =       $helper->getConfig('deliverydays_window', 'checkout');
        $general['dropoffDays'] =              $helper->getConfig('dropoff_days', 'checkout');
        $general['dropoffDelay'] =             $helper->getConfig('dropoff_delay', 'checkout');
        $general['onlyRecipient'] =            $helper->getConfig('only_recipient', 'checkout');
        $general['deliveryTitle'] =            $helper->getConfig('delivery_title', 'checkout');
        $data['general'] = (object)$general;

        $morningDelivery['active'] =            $helper->getConfig('morningdelivery_active', 'morningdelivery');
        $morningDelivery['fee'] =               $helper->getConfig('morningdelivery_fee', 'morningdelivery');
        $morningDelivery['minOrderEnabled'] = $helper->getConfig('morningdelivery_min_order_enabled', 'morningdelivery');
        $morningDelivery['minOrderTotal'] =   $helper->getConfig('morningdelivery_min_order_total', 'morningdelivery');
        $data['morningDelivery'] = (object)$morningDelivery;

        $eveningDelivery['active'] =            $helper->getConfig('eveningdelivery_active', 'eveningdelivery');
        $eveningDelivery['fee'] =               $helper->getConfig('eveningdelivery_fee', 'eveningdelivery');
        $eveningDelivery['minOrderEnabled'] = $helper->getConfig('eveningdelivery_min_order_enabled', 'eveningdelivery');
        $eveningDelivery['minOrderTotal'] =   $helper->getConfig('eveningdelivery_min_order_total', 'eveningdelivery');
        $data['eveningDelivery'] = (object)$eveningDelivery;

        $pickup['active'] =            $helper->getConfig('pickup_active', 'pickup');
        $pickup['title'] =            $helper->getConfig('pickup_title', 'pickup');
        $pickup['fee'] =               $helper->getConfig('pickup_fee', 'pickup');
        $pickup['minOrderEnabled'] = $helper->getConfig('pickup_min_order_enabled', 'pickup');
        $pickup['minOrderTotal'] =   $helper->getConfig('pickup_min_order_total', 'pickup');
        $data['pickup'] = (object)$pickup;

        $pickupExpress['active'] =            $helper->getConfig('pickup_express_active', 'pickup_express');
        $pickupExpress['fee'] =               $helper->getConfig('pickup_express_fee', 'pickup_express');
        $pickupExpress['minOrderEnabled'] = $helper->getConfig('pickup_express_min_order_enabled', 'pickup_express');
        $pickupExpress['minOrderTotal'] =   $helper->getConfig('pickup_express_min_order_total', 'pickup_express');
        $data['pickupExpress'] = (object)$pickupExpress;


        $array = array(
            'data' => (object)$data,
            'container' => $container
        );

        header('Content-Type: application/json');
        echo(json_encode($array));
        exit;
    }

    /**
     * For testing the cron
     */
    public function cronAction()
    {
        $cronController = new TIG_MyParcel2014_Model_Observer_Cron;
        $cronController->checkStatus();
    }

}
