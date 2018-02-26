<?php

class TIG_MyParcel2014_MyparcelAdminhtml_ConfigController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/tig_myparcel');
    }

    /**
     * Download all MyParcel log files as a zip file.
     *
     * @return $this
     */
    public function downloadLogsAction()
    {
        $helper = Mage::helper('tig_myparcel');

        /**
         * Get a zip file containing all valid MyParcel logs.
         */
        try
        {
            $zip = Mage::getModel('tig_myparcel/adminhtml_support_logs')->downloadLogs();
        }
        catch (Exception $e)
        {
            Mage::getSingleton('core/session')->addError($helper->__('The log files cannot be downloaded.'));

            $this->_redirect('adminhtml/system_config/edit', array('section' => 'tig_myparcel'));
            return $this;
        }
        if(empty($zip))
        {
            Mage::getSingleton('core/session')->addError($helper->__('There are no log files to be downloaded.'));

            $this->_redirect('adminhtml/system_config/edit', array('section' => 'tig_myparcel'));
            return $this;
        }

        $zipName = explode(DS, $zip);
        $zipName = end($zipName);

        /**
         * Offer the zip file as a download response. The 'rm' key will cause Magento to remove the zip file from the
         * server after it's finished.
         */
        $content = array(
            'type'  => 'filename',
            'value' => $zip,
            'rm'    => true,
        );
        $this->_prepareDownloadResponse($zipName, $content);

        return $this;
    }

    public function generateRetourmailAction()
    {
        $helper = Mage::helper('tig_myparcel');

        //get Params
        $shipmentId = $this->getRequest()->getParam('shipment_id');

        /**
         * @var TIG_MyParcel2014_Model_Shipment $myparcelShipment
         * @var Mage_Sales_Model_Order_Shipment $shipment
         */
        $myparcelShipment = Mage::getModel('tig_myparcel/shipment')->load($shipmentId, 'shipment_id');
        $shipment         = Mage::getModel('sales/order_shipment')->load($shipmentId);

        $consignmentId = $myparcelShipment->getConsignmentId();

        /**
         * @var TIG_MyParcel2014_Model_Api_MyParcel $api
         */
        $api      = $myparcelShipment->getApi();
        $response = $api->createRetourmailRequest($shipment, $consignmentId)
                        ->setStoreId($shipment->getOrder()->getStoreId())
                        ->sendRequest()
                        ->getRequestResponse();
        $aResponse = json_decode($response, true);

        /**
         * Validate the response.
         */
        if(!is_array($aResponse) || $aResponse['data']['ids'][0]['id'] === null){
            $message = $helper->__('Retourmail is not created, check the log files for details.');
            $helper->addSessionMessage('adminhtml/session','MYPA-0020', 'warning');
            $helper->logException($message);
        }

        //set shipment comment
        $comment = $helper->__('Retour label mailed');
        $shipment->addComment($comment,0,1);
        $shipment->save();

        //add success message
        $helper->addSessionMessage('adminhtml/session', null , 'success', $comment);

        //redirect to previous screen
        $this->_redirectReferer();
    }

    public function generateRetourlinkAction()
    {
        $helper = Mage::helper('tig_myparcel');

        //get Params
        $shipmentId = $this->getRequest()->getParam('shipment_id');

        /**
         * @var TIG_MyParcel2014_Model_Shipment $myparcelShipment
         * @var Mage_Sales_Model_Order_Shipment $shipment
         */
        $myparcelShipment = Mage::getModel('tig_myparcel/shipment')->load($shipmentId, 'shipment_id');
        $shipment         = Mage::getModel('sales/order_shipment')->load($shipmentId);

        $consignmentId = $myparcelShipment->getConsignmentId();

        /**
         * @var TIG_MyParcel2014_Model_Api_MyParcel $api
         */
        $api      = $myparcelShipment->getApi();
        $response = $api->createRetourlinkRequest($consignmentId)
                        ->setStoreId($shipment->getOrder()->getStoreId())
                        ->sendRequest()
                        ->getRequestResponse();
        $aResponse = json_decode($response, true);

        /**
         * Validate the response.
         */
        if(!is_array($aResponse) || $aResponse['data']['download_url'][0]['link'] === null){
            $message = $helper->__('Retourlink is not created, check the log files for details.');
            $helper->addSessionMessage('adminhtml/session','MYPA-0020', 'warning');
            $helper->logException($message);
        }

        //set shipment comment
        $comment = $helper->__('Retour label url: ' . $aResponse['data']['download_url'][0]['link']);
        $shipment->addComment($comment,0,1);
        $shipment->save();

        //add success message
        $helper->addSessionMessage('adminhtml/session', null , 'success', $comment);

        //redirect to previous screen
        $this->_redirectReferer();
    }
}
