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
 * @method boolean hasQuote()
 * @method TIG_MyParcel2014_Model_Observer_SavePgAddress setQuote(Mage_Sales_Model_Quote $quote)
 */
class TIG_MyParcel2014_Model_Observer_SaveConfig extends Varien_Object
{
    /**
     * Saves the verssion numbers of the extension, store and php
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     *
     * @event controller_action_postdispatch_adminhtml_system_config_save
     *
     * @observer tig_myparcel_save_config
     */
    public function registerConfig(Varien_Event_Observer $observer)
    {
        /**
         * Check if the MyParcel extension is enabled.
         */
        if (!Mage::helper('tig_myparcel')->isEnabled()) {
            return $this;
        }

        /**
         * @var Mage_Core_Controller_Varien_Front $controller
         */
        $controller    = $observer->getControllerAction();
        $section = $controller->getRequest()->getParam('section');

        if($section == 'myparcel')
        {
            $api = Mage::getModel('tig_myparcel/api_myParcel');

            $api->createRegisterConfigRequest()->sendRequest(); // no getRequestResponse()
        }

        return $this;
    }
}
