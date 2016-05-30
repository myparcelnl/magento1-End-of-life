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
    public function cronAction()
    {
        $cronController = new TIG_MyParcel2014_Model_Observer_Cron;
        $cronController->checkStatus();
    }

    /**
     * Save the selected PakjeGemak location.
     *
     * @return $this
     */
    public function saveShippingMethodAction()
    {
        /**
         * This action may only be called using AJAX requests
         */
        if (!$this->getRequest()->isAjax()) {
            $this->getResponse()
                 ->setBody('not_allowed');

            return $this;
        }

        /**
         * Get the submitted post data and validate it.
         */
        $postData = $this->getRequest()->getPost();
        $validData = Mage::getSingleton('tig_myparcel/checkout_validate')->validateSaveLocationData($postData);

        /**
         * Check if the data is valid.
         */
        if (!$validData) {
            $this->getResponse()
                 ->setBody('invalid_data');

            return $this;
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        try {
            /**
             * Save the address of the selected PakjeGemak location in the quote.
             */
            Mage::getModel('tig_myparcel/checkout_service')->savePgAddress($validData, $quote);
        } catch (Exception $e) {
            Mage::helper('tig_myparcel')->logException($e);

            $this->getResponse()
                 ->setBody('error');

            return $this;
        }

        $this->getResponse()
             ->setBody('ok');

        return $this;
    }

}
