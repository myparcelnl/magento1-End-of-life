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
            /*
             * @todo; remove if not necessary after test
            // move order column myparcel send date to order grid
            $orgOrder = Mage::getModel('sales/order')->load($order->getId());
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $query = "UPDATE " . $resource->getTableName('sales/order_grid') . " SET myparcel_send_date = '" . $orgOrder->getMyparcelSendDate() . "' WHERE `" . $resource->getTableName('sales/order_grid') . "`.`entity_id` = " . (int)$order->getId() . ";";
            $results = $writeConnection->query($query);
            */
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
