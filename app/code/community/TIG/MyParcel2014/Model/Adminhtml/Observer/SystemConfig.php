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
class TIG_MyParcel2014_Model_Adminhtml_Observer_SystemConfig
{
    /**
     * Adds a button to the system > config page for the MyParcel section, allowing the admin to download all Myparcel
     * debug logs.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     *
     * @event controller_action_layout_render_before_adminhtml_system_config_edit
     *
     * @observer myparcel_add_download_log_button
     */
    public function addDownloadLogButton(Varien_Event_Observer $observer)
    {
        $section = Mage::app()->getRequest()->getParam('section');
        if ($section !== 'tig_myparcel') {
            return $this;
        }

        $configEditBlock = false;
        $contentBlocks = Mage::getSingleton('core/layout')->getBlock('content')->getChild();

        /**
         * @var Mage_Core_Block_Abstract $block
         */
        foreach ($contentBlocks as $block) {
            if ($block instanceof Mage_Adminhtml_Block_System_Config_Edit) {
                $configEditBlock = $block;
                break;
            }
        }

        if (!$configEditBlock) {
            return $this;
        }

        $helper = Mage::helper('tig_myparcel');

        $onClickUrl = $configEditBlock->getUrl('adminhtml/myparcelAdminhtml_config/downloadLogs');
        $onClick = "setLocation('{$onClickUrl}')";

        $button = $configEditBlock->getLayout()->createBlock('adminhtml/widget_button');
        $button->setData(
            array(
                'label'   => $helper->__('Download log files'),
                'onclick' => $onClick,
                'class'   => 'download',
            )
        );

        $configEditBlock->setChild('download_logs_button', $button);
        $configEditBlock->setTemplate('TIG/MyParcel2014/system/config/edit.phtml');

        return $this;
    }
}
