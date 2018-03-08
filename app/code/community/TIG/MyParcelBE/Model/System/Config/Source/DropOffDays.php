<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@myparcel.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@myparcel.nl for more information.
 *
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

class TIG_MyParcelBE_Model_System_Config_Source_DropOffDays
{
    /**
     * Source model for customs setting.
     *
     * @return array
     */
    public function toOptionArray($isMultiSelect = false, $isActiveOnlyFlag = false)
    {
        $helper = Mage::helper('tig_myparcel');

        $array = array(
            array(
                'value' => 1,
                'label' => $helper->__('Monday'),
            ),
            array(
                'value' => 2,
                'label' => $helper->__('Tuesday'),
            ),
            array(
                'value' => 3,
                'label' => $helper->__('Wednesday'),
            ),
            array(
                'value' => 4,
                'label' => $helper->__('Thursday'),
            ),
            array(
                'value' => 5,
                'label' => $helper->__('Friday'),
            ),
            array(
                'value' => 6,
                'label' => $helper->__('Saturday'),
            ),
        );
        return $array;
    }
}
