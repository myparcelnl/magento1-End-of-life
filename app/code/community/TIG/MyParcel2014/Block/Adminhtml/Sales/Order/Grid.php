<?php
class Tig_MyParcel2014_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
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
         * @var TIG_MyParcel2014_Model_Shipment $myParcelShipment
         */
        $orderIds = array();
        $consignmentIds = array();
        $myParcelShipments = array();

        foreach($this->getCollection() as $order)
        {
            $orderIds[] = $order->getId();
        }

        $collection = Mage::getResourceModel('tig_myparcel/shipment_collection');
        $collection->getSelect();
        if ($orderIds)
            $collection->addFieldToFilter('order_id', array('in' => array($orderIds)));

        foreach ($collection as $myParcelShipment){
            if($myParcelShipment->hasConsignmentId()){
                $consignmentId = $myParcelShipment->getConsignmentId();
                $consignmentIds[] = $consignmentId;
                $myParcelShipments[$consignmentId] = $myParcelShipment;
            }
        }


        $apiInfo    = Mage::getModel('tig_myparcel/api_myParcel');
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
