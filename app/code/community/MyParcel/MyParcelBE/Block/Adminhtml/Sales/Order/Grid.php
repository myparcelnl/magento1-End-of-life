<?php
class MyParcel_MyParcelBE_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
    /**
     * Get consignment id's from all shipments in the order list
     *
     * @return $this
     */
    protected function _afterLoadCollection()
    {
        /**
         * @var Mage_Sales_Model_Order $order
         * @var MyParcel_MyParcelBE_Model_Shipment $myParcelShipment
         */
        $consignmentIds = array();
        $myParcelShipments = array();

        $collection = Mage::getResourceModel('myparcel_be/shipment_collection');
        $collection->getSelect();
        if ($this->getCollection()->getAllIds())
            $collection->addFieldToFilter('order_id', array('in' => array($this->getCollection()->getAllIds())));

        foreach ($collection as $myParcelShipment){
            if($myParcelShipment->hasConsignmentId()){
                $consignmentId = $myParcelShipment->getConsignmentId();
                $consignmentIds[] = $consignmentId;
                $myParcelShipments[$consignmentId] = $myParcelShipment;
            }
        }

        $apiInfo    = Mage::getModel('myparcel_be/api_myParcel');
        $responseShipments = $apiInfo->getConsignmentsInfoData($consignmentIds);

        if($responseShipments){
            foreach($responseShipments as $responseShipment){
                $myParcelShipment = $myParcelShipments[$responseShipment->id];
                $myParcelShipment->updateStatus($responseShipment);
            }
        }

        return $this;
    }
}
