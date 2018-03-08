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

class TIG_MyParcelBE_Model_System_Config_Source_DelayDays
{
    /**
     * Source model for yes / no setting.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('tig_myparcel');

        $array = array(
            array(
                'value' => 0,
                'label' => $helper->__('No delay'),
            ),
            array(
                'value' => 1,
                'label' => 1 . ' ' . $helper->__('day'),
            ),
        );


        $x = 2;
        while($x <= 14) {
            $array[] = array(
                'value' => $x,
                'label' => $x . ' ' . $helper->__('days')
            );
            $x++;
        }

        return $array;
    }
}
