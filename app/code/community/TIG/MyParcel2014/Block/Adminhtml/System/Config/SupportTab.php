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
class TIG_MyParcel2014_Block_Adminhtml_System_Config_SupportTab
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{

    /**
     * Css files loaded for MyParcel's system > config section
     */
    const SYSTEM_CONFIG_EDIT_CSS_FILE = 'css/TIG/MyParcel2014/system_config_edit_myparcel.css';
    const MAGENTO_16_CSS_FILE         = 'css/TIG/MyParcel2014/system_config_edit_myparcel_magento16.css';

    /**
     * @var string
     */
    protected $_template = 'TIG/MyParcel2014/system/config/supportTab.phtml';

    protected function _prepareLayout()
    {
        $this->getLayout()
            ->getBlock('head')
            ->addCss(self::SYSTEM_CONFIG_EDIT_CSS_FILE);

        /**
         * For Magento 1.6 and 1.11 we need to add another css file.
         */
        $helper = Mage::helper('tig_myparcel');
        $isEnterprise = $helper->isEnterprise();

        /**
         * Get the minimum version requirement for the current Magento edition.
         */
        if($isEnterprise) {
            $minimumVersion = '1.12.0.0';
        } else {
            $minimumVersion = '1.7.0.0';
        }

        /**
         * Check if the current version is below the minimum version requirement.
         */
        $isBelowMinimumVersion = version_compare(Mage::getVersion(), $minimumVersion, '<');
        if ($isBelowMinimumVersion) {
            $this->getLayout()
                ->getBlock('head')
                ->addCss(self::MAGENTO_16_CSS_FILE);
        }

        return parent::_prepareLayout();
    }

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}
