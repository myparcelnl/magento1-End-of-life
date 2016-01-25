<?php

class TIG_MyParcel2014_Model_Resource_Shipment_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('tig_myparcel/shipment');
    }
}
