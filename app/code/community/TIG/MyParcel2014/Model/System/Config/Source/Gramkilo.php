<?php

class TIG_MyParcel2014_Model_System_Config_Source_Gramkilo
{
    /**
     * Source model for gram / kilo setting.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('tig_myparcel');

        $array = array(
             array(
                'value' => 'gram',
                'label' => $helper->__('Gram'),
             ),
             array(
                'value' => 'kilo',
                'label' => $helper->__('Kilo'),
             ),
        );
        return $array;
    }
}
