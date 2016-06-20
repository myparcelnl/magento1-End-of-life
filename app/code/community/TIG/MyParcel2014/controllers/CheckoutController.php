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

        $general['cutoff_time'] =                   str_replace(',', ':', $helper->getConfig('cutoff_time', 'checkout'));
        $general['deliverydays_window'] =           $helper->getConfig('deliverydays_window', 'checkout');
        $general['dropoff_days'] =                  $helper->getConfig('dropoff_days', 'checkout');
        $general['dropoff_delay'] =                 $helper->getConfig('dropoff_delay', 'checkout');
        $data['general'] = (object)$general;

        $delivery['delivery_title'] =               $helper->getConfig('delivery_title', 'delivery');
        $delivery['only_recipient_active'] =        $helper->getConfig('only_recipient_active', 'delivery');
        $delivery['only_recipient_title'] =         $helper->getConfig('only_recipient_title', 'delivery');
        $delivery['only_recipient_fee'] =           $helper->getConfig('only_recipient_fee', 'delivery');
        $data['delivery'] = (object)$delivery;

        $morningDelivery['active'] =                $helper->getConfig('morningdelivery_active', 'morningdelivery');
        $morningDelivery['fee'] =                   $helper->getConfig('morningdelivery_fee', 'morningdelivery');
        $morningDelivery['min_order_enabled'] =     $helper->getConfig('morningdelivery_min_order_enabled', 'morningdelivery');
        $morningDelivery['min_order_total'] =       $helper->getConfig('morningdelivery_min_order_total', 'morningdelivery');
        $data['morningDelivery'] = (object)$morningDelivery;

        $eveningDelivery['active'] =                $helper->getConfig('eveningdelivery_active', 'eveningdelivery');
        $eveningDelivery['fee'] =                   $helper->getConfig('eveningdelivery_fee', 'eveningdelivery');
        $eveningDelivery['min_order_enabled'] =     $helper->getConfig('eveningdelivery_min_order_enabled', 'eveningdelivery');
        $eveningDelivery['min_order_total'] =       $helper->getConfig('eveningdelivery_min_order_total', 'eveningdelivery');
        $data['eveningDelivery'] = (object)$eveningDelivery;

        $pickup['active'] =                         $helper->getConfig('pickup_active', 'pickup');
        $pickup['title'] =                          $helper->getConfig('pickup_title', 'pickup');
        $pickup['fee'] =                            $helper->getConfig('pickup_fee', 'pickup');
        $pickup['min_order_enabled'] =              $helper->getConfig('pickup_min_order_enabled', 'pickup');
        $pickup['min_order_total'] =                $helper->getConfig('pickup_min_order_total', 'pickup');
        $data['pickup'] = (object)$pickup;

        $pickupExpress['active'] =                  $helper->getConfig('pickup_express_active', 'pickup_express');
        $pickupExpress['fee'] =                     $helper->getConfig('pickup_express_fee', 'pickup_express');
        $pickupExpress['min_order_enabled'] =       $helper->getConfig('pickup_express_min_order_enabled', 'pickup_express');
        $pickupExpress['min_order_total'] =         $helper->getConfig('pickup_express_min_order_total', 'pickup_express');
        $data['pickupExpress'] = (object)$pickupExpress;

        ob_start();
        include_once('app/design/frontend/base/default/template/TIG/MyParcel2014/checkout/mypa_container.php');
        $container = ob_get_contents();
        ob_clean();


        $info = array(
            'data' => (object)$data,
            'container' => $container
        );

        header('Content-Type: application/json');
        echo(json_encode($info));
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
