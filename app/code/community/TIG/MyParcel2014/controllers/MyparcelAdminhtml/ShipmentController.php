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
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_MyParcel2014_MyparcelAdminhtml_ShipmentController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Used module name in current adminhtml controller.
     */
    protected $_usedModuleName = 'TIG_MyParcel2014';

    /**
     * @var array
     */
    protected $_warnings = array();

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/tig_myparcel');
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->_warnings;
    }

    /**
     * @param array $warnings
     *
     * @return $this
     */
    public function setWarnings(array $warnings)
    {
        $this->_warnings = $warnings;

        return $this;
    }

    /**
     * @param array|string $warning
     *
     * @return $this
     */
    public function addWarning($warning)
    {
        if (!is_array($warning)) {
            $warning = array(
                'entity_id'   => null,
                'code'        => null,
                'description' => $warning,
            );
        }

        $warnings = $this->getWarnings();
        $warnings[] = $warning;

        $this->setWarnings($warnings);
        return $this;
    }

    /**
     * Get shipment Ids from the request.
     *
     * @return array
     *
     * @throws TIG_MyParcel2014_Exception
     */
    protected function _getShipmentIds()
    {
        $shipmentIds = $this->getRequest()->getParam('shipment_ids', array());

        /**
         * Check if a shipment was selected.
         */
        if (!is_array($shipmentIds) || empty($shipmentIds)) {
            throw new TIG_MyParcel2014_Exception(
                $this->__('Please select one or more shipments.'),
                'MYPA-0001'
            );
        }

        return $shipmentIds;
    }

    /**
     * Get order Ids from the request.
     *
     * @return array
     *
     * @throws TIG_MyParcel2014_Exception
     */
    protected function _getOrderIds()
    {
        $orderIds = $this->getRequest()->getParam('order_ids', array());
        $orderId = $this->getRequest()->getParam('order_id', array());

        /**
         * Check if the request came from the order detail page.
         */
        if(!empty($orderId)) {
            $orderIds[] = $orderId;
        } else {
            /**
             * Request came from the order overview
             * Check if an order was selected.
             */
            if (!is_array($orderIds) || empty($orderIds)) {
                throw new TIG_MyParcel2014_Exception(
                    $this->__('Please select one or more orders.'),
                    'MYPA-0002'
                );
            }
        }

        return $orderIds;
    }

    /**
     * Creates shipments for a supplied array of orders. This action is triggered by a massaction in the sales > order
     * grid.
     *
     * @return $this
     */
    public function massCreateShipmentsAction()
    {
        $helper = Mage::helper('tig_myparcel');

        try {
            $orderIds = $this->_getOrderIds();

            /**
             * Create the shipments.
             */
            $errors = 0;
            foreach ($orderIds as $orderId) {
                try {
                    $this->_createShipment($orderId);
                } catch (TIG_MyParcel2014_Exception $e) {
                    $helper->logException($e);
                    $this->addWarning(
                        array(
                            'entity_id'   => Mage::getResourceModel('sales/order')->getIncrementId($orderId),
                            'code'        => $e->getCode(),
                            'description' => $e->getMessage(),
                        )
                    );
                    $errors++;
                } catch (Exception $e) {
                    $helper->logException($e);
                    $this->addWarning(
                        array(
                            'entity_id'   => Mage::getResourceModel('sales/order')->getIncrementId($orderId),
                            'code'        => null,
                            'description' => $e->getMessage(),
                        )
                    );
                    $errors++;
                }
            }
        } catch (TIG_MyParcel2014_Exception $e) {
            $helper->logException($e);
            $helper->addExceptionSessionMessage('adminhtml/session', $e);

            $this->_redirect('adminhtml/sales_order/index');
            return $this;
        } catch (Exception $e) {
            $helper->logException($e);
            $helper->addSessionMessage(
                'adminhtml/session',
                null,
                'error',
                $this->__('An error occurred while processing this action.')
            );

            $this->_redirect('adminhtml/sales_order/index');
            return $this;
        }

        /**
         * Check for warnings.
         */
        $this->_checkForWarnings();

        /**
         * Add either a success or failure message and redirect the user accordingly.
         */
        if ($errors < count($orderIds)) {
            $helper->addSessionMessage(
                'adminhtml/session', null, 'success',
                $this->__('The shipments were successfully created.')
            );

            $this->_redirect('adminhtml/sales_order/index');
        } else {
            $helper->addSessionMessage(
                'adminhtml/session', null, 'error',
                $this->__('None of the shipments could be created. Please check the error messages for more details.')
            );

            $this->_redirect('adminhtml/sales_order/index');
        }

        return $this;
    }

    /**
     * Creates a single consignment, for an already existing Magento Shipping.
     * This action is triggered in shipment-view page
     *
     * @return $this
     */
    public function createConsignmentAction()
    {
        $helper = Mage::helper('tig_myparcel');

        //get post variables
        $selectedConsignmentOptions = $this->getRequest()->getPost('tig_myparcel');
        $shipmentId                 = $this->getRequest()->getPost('shipment_id');

        $error            = false;
        $myParcelShipment = false;

        //check if shipment id is present and set the shipmentId to the MyParcel Shipment model
        try{

            if(!empty($shipmentId)){
                /** @var TIG_MyParcel2014_Model_Shipment $myParcelShipment */
                $myParcelShipment = Mage::getModel('tig_myparcel/shipment')->setShipmentId($shipmentId);

            }else{
                throw new TIG_MyParcel2014_Exception(
                    $helper->__('Please select one or more shipments.'),
                    'MYPA-0001'
                );
            }
        }catch (TIG_MyParcel2014_Exception $e) {
            $error = true;
            $helper->logException($e);
            $helper->addExceptionSessionMessage('adminhtml/session', $e);

            $this->_redirect('adminhtml/sales_shipment/view', array('shipment_id' => $shipmentId));
            return $this;

        }

        //check if consigment options are selected and matches with the type of consignment and Magento shipment
        if (!empty($selectedConsignmentOptions['shipment_type'])) {
            $shipmentType = $selectedConsignmentOptions['shipment_type'];

            /**
             * check if it is an pakjegemak-shipment && the shipment type is not equal to normal
             * pakjegemak shipments can only be created with the normal shipment type
             */
            try {
                /** @var Mage_Sales_Model_Order_Shipment $shipment */
                $shipment = $myParcelShipment->getShipment();
                if($helper->getPgAddress($shipment->getOrder()) && ($shipmentType != TIG_MyParcel2014_Model_Shipment::TYPE_NORMAL && $shipmentType != 'default')){
                    $shipment_url = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order_shipment/view',array('shipment_id' => $shipment->getShipment()->getId()));
                    throw new TIG_MyParcel2014_Exception(
                        $helper->__('The selected shipment type cannot be used. Pakjegemak shipments can only be created with the normal shipment type.<br/> The Magento shipment has been created without a MyParcel shipment, select a different shipment type or go to the shipment page to create a single MyParcel shipment. <a target="_blank" href="%s">View shipment</a>',$shipment_url),
                        'MYPA-0023'
                    );
                }
            } catch(TIG_MyParcel2014_Exception $e) {
                $error = true;
                $helper->logException($e);
                $helper->addExceptionSessionMessage('adminhtml/session', $e);

                $this->_redirect('adminhtml/sales_shipment/view', array('shipment_id' => $shipmentId));
                return $this;
            }

            //if not the normal shipment-type, no extra options are needed, so reset the consignment options
            if ($shipmentType != TIG_MyParcel2014_Model_Shipment::TYPE_NORMAL) {
                $selectedConsignmentOptions = array(
                    'shipment_type' => $shipmentType
                );
            }

            //register the consignment options
            Mage::register('tig_myparcel_consignment_options', $selectedConsignmentOptions);

            /**
             * consignment options are set, try if we can create a myparcel consignment
             */
            try{
                $myParcelShipment->setConsignmentOptions()->createConsignment()->save();
                $barcode = $myParcelShipment->getBarcode();
                if ($barcode) {
                    $myParcelShipment->addTrackingCodeToShipment($barcode);
                }
            }catch (TIG_MyParcel2014_Exception $e) {
                $error = true;
                $helper->logException($e);
                $helper->addExceptionSessionMessage('adminhtml/session', $e);
            } catch (Exception $e) {
                $error = true;
                $helper->logException($e);
                $helper->addSessionMessage(
                    'adminhtml/session',
                    null,
                    'error',
                    $this->__('An error occurred while processing this action.')
                );
            }
        }

        if(true !== $error){
            $helper->addSessionMessage(
                'adminhtml/session', null, 'success',
                $this->__('The MyParcel consignment is successfully created.')
            );
        }

        $this->_redirect('adminhtml/sales_shipment/view', array('shipment_id' => $shipmentId));
        return $this;
    }

    /**
     * Print shipping labels for all selected orders.
     *
     * @return $this
     *
     * @throws TIG_MyParcel2014_Exception
     */
    public function massPrintLabelsAction()
    {
        $helper = Mage::helper('tig_myparcel');
        $orderIds = $this->_getOrderIds();

        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
                                  ->addFieldToSelect(array('entity_id','order_id'))
                                  ->addFieldToFilter('order_id', array('in', $orderIds));

        $shipmentIds      = $shipmentCollection->getColumnValues('entity_id');
        $shipmentOrderIds = $shipmentCollection->getColumnValues('order_id');

        Mage::register('tig_myparcel_consignment_options', array(
            'create_consignment' => '1',
            'type_consignment' => $this->getRequest()->getParam('type_consignment'),
        ));

        /**
         * create new shipments if not yet created
         */
        $hasNoShipments = array_diff($orderIds, $shipmentOrderIds);
        $newShipments   = array();

        $errors = 0;
        if(!empty($hasNoShipments)){
            foreach($hasNoShipments as $orderId)
            {
                /**
                 * returns a shipment object
                 */
                try {
                    $newShipments[] = $this->_createShipment($orderId, true);
                } catch (TIG_MyParcel2014_Exception $e) {
                    $helper->logException($e);

                    $helper->addSessionMessage(
                        'adminhtml/session',
                        null,
                        'error',
                        'Order: '.Mage::getResourceModel('sales/order')->getIncrementId($orderId). ' - ' .$e->getMessage()
                    );

                    $errors++;
                } catch (Exception $e) {
                    $helper->logException($e);

                    $helper->addSessionMessage(
                        'adminhtml/session',
                        null,
                        'error',
                        'Order: '.Mage::getResourceModel('sales/order')->getIncrementId($orderId). ' - ' .$e->getMessage()
                    );

                    $errors++;
                }
            }
        }

        // if new shipments are created, refresh the collection of shipments for the orders
        if(!empty($newShipments))
        {
            $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
                                      ->addFieldToSelect(array('entity_id','order_id'))
                                      ->addFieldToFilter('order_id', array('in', $orderIds));

            $shipmentIds = $shipmentCollection->getColumnValues('entity_id');
        }

        /**
         * Load the shipments and check if they are valid.
         * returns an array with shipment objects
         */
        $shipments = $this->_loadAndCheckShipments($shipmentIds, true, true, false);

        /**
         * Get the labels from CIF.
         *
         * @var TIG_MyParcel2014_Model_Shipment $shipment
         */
        $consignmentIds = array();

        $type = $this->getRequest()->getParam('type_consignment');
        $type = $type ? $type : 'default';

        foreach ($shipments as $shipment) {
            try {
                if (!$shipment->hasConsignmentId()) {

                    if($helper->getPgAddress($shipment->getOrder()) && $type != TIG_MyParcel2014_Model_Shipment::TYPE_NORMAL && $type != 'default'){
                        $shipment_url = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order_shipment/view',array('shipment_id' => $shipment->getShipment()->getId()));
                        throw new TIG_MyParcel2014_Exception(
                            $helper->__('The selected shipment type cannot be used. Pakjegemak shipments can only be created with the normal shipment type.<br/> The Magento shipment has been created without a MyParcel shipment, select a different shipment type or go to the shipment page to create a single MyParcel shipment. <a target="_blank" href="%s">View shipment</a>',$shipment_url),
                            'MYPA-0023'
                        );
                    }

                    $consignmentOptions = array('shipment_type' => $type);
                    if (Mage::registry('tig_myparcel_consignment_options')) {
                        $consignmentOptions = array_merge(
                            $consignmentOptions,
                            Mage::registry('tig_myparcel_consignment_options')
                        );
                        Mage::unregister('tig_myparcel_consignment_options');
                    }
                    Mage::register('tig_myparcel_consignment_options', $consignmentOptions);
                    $shipment->setShipmentId($shipment->getShipment()->getId())
                        ->setConsignmentOptions($consignmentOptions)
                        ->createConsignment()
                        ->save();
                }

                $consignmentIds[] = $shipment->getConsignmentId();
            } catch (Exception $e) {
                $helper->logException($e);

                $helper->addSessionMessage(
                    'adminhtml/session',
                    null,
                    'error',
                    'Order: '.$shipment->getOrder()->getIncrementId(). ' - ' .$e->getMessage()
                );
            }
        }

        if (!$consignmentIds) {
            $this->_redirect('adminhtml/sales_order/index');
            return $this;
        }

        $storeId = $shipment->getOrder()->getStoreId();
        $api     = Mage::getModel('tig_myparcel/api_myParcel');
        $api->setStoreId($storeId);
        $start   = $this->getRequest()->getParam('myparcel_print_labels_start', 1);
        $perpage = $helper->getConfig('print_orientation');
        $pdfData = $api->createRetrievePdfsRequest($consignmentIds, $start, $perpage)
                       ->sendRequest('GET')
                       ->getRequestResponse();

        $fileName = 'MyParcel Shipping Labels '
            . date('Ymd-His', Mage::getSingleton('core/date')->timestamp())
            . '.pdf';

        $this->_preparePdfResponse($fileName, $pdfData);

        /**
         * We need to check for warnings before the label download response.
         */
        $this->_checkForWarnings();

        /**
         * Load the shipments and check if they are valid.
         * returns an array with shipment objects
         *
         * @var TIG_MyParcel2014_Model_Shipment $shipment
         */
        $shipments = $this->_loadAndCheckShipments($shipmentIds, true, false);

        $apiInfo    = Mage::getModel('tig_myparcel/api_myParcel');
        $apiInfo    ->setStoreId($storeId);
        $responseShipments = $apiInfo->getConsignmentsInfoData($consignmentIds);

        foreach($responseShipments as $responseShipment){
            $shipment = $shipments[$responseShipment->id];
            $shipment->updateStatus($responseShipment);
        }

        return $this;
    }

    /**
     * Print shipping labels for all selected shipments.
     *
     * @return $this
     *
     * @throws TIG_MyParcel2014_Exception
     */
    public function massPrintShipmentLabelsAction()
    {
        $helper = Mage::helper('tig_myparcel');
        $shipmentIds = $this->_getShipmentIds();

        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
            ->addFieldToSelect(array('entity_id','order_id'))
            ->addFieldToFilter('entity_id', array('in', $shipmentIds));

        $shipmentIds      = $shipmentCollection->getColumnValues('entity_id');

        Mage::register('tig_myparcel_consignment_options', array(
            'create_consignment' => '1',
            'type_consignment' => $this->getRequest()->getParam('type_consignment'),
        ));

        /**
         * Load the shipments and check if they are valid.
         * returns an array with shipment objects
         */
        $shipments = $this->_loadAndCheckShipments($shipmentIds, true, false, false);

        /**
         * Get the labels from CIF.
         *
         * @var TIG_MyParcel2014_Model_Shipment $shipment
         */
        $consignmentIds = array();
        foreach ($shipments as $shipment) {
            try {
                if (!$shipment->hasConsignmentId()) {
                    $type = $this->getRequest()->getParam('type_consignment');

                    if($helper->getPgAddress($shipment->getOrder()) && $type != TIG_MyParcel2014_Model_Shipment::TYPE_NORMAL && $type != 'default'){
                        $shipment_url = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order_shipment/view',array('shipment_id' => $shipment->getShipment()->getId()));
                        throw new TIG_MyParcel2014_Exception(
                            $helper->__('The selected shipment type cannot be used. Pakjegemak shipments can only be created with the normal shipment type.<br/> The Magento shipment has been created without a MyParcel shipment, select a different shipment type or go to the shipment page to create a single MyParcel shipment. <a target="_blank" href="%s">View shipment</a>',$shipment_url),
                            'MYPA-0023'
                        );
                    }

                    $consignmentOptions = array('shipment_type' => $type);
                    if (Mage::registry('tig_myparcel_consignment_options')) {
                        $consignmentOptions = array_merge(
                            $consignmentOptions,
                            Mage::registry('tig_myparcel_consignment_options')
                        );
                        Mage::unregister('tig_myparcel_consignment_options');
                    }
                    Mage::register('tig_myparcel_consignment_options', $consignmentOptions);
                    $shipment->setShipmentId($shipment->getShipment()->getId())
                        ->setConsignmentOptions($consignmentOptions)
                        ->createConsignment()
                        ->save();
                }

                $consignmentIds[] = $shipment->getConsignmentId();
            } catch (Exception $e) {
                $helper->logException($e);

                $helper->addSessionMessage(
                    'adminhtml/session',
                    null,
                    'error',
                    'Order: '.$shipment->getOrder()->getIncrementId(). ' - ' .$e->getMessage()
                );
            }
        }

        if (!$consignmentIds) {
            $this->_redirect('adminhtml/sales_shipment/index');
            return $this;
        }

        $storeId = $shipment->getOrder()->getStoreId();
        $api     = Mage::getModel('tig_myparcel/api_myParcel');
        $api->setStoreId($storeId);
        $start   = $this->getRequest()->getParam('myparcel_print_labels_start', 1);
        $perpage = $helper->getConfig('print_orientation');
        $pdfData = $api->createRetrievePdfsRequest($consignmentIds, $start, $perpage)
            ->sendRequest('GET')
            ->getRequestResponse();

        $fileName = 'MyParcel Shipping Labels '
            . date('Ymd-His', Mage::getSingleton('core/date')->timestamp())
            . '.pdf';

        $this->_preparePdfResponse($fileName, $pdfData);

        /**
         * We need to check for warnings before the label download response.
         */
        $this->_checkForWarnings();

        return $this;
    }

    /**
     * Print one shipping label.
     *
     * @return boolean
     */
    public function printShipmentLabelAction(){
        return $this->massPrintLabelsAction();
    }

    public function sendReturnMailAction()
    {
        /**
         * @var TIG_MyParcel2014_Model_Api_MyParcel $api
         */
        $helper = Mage::helper('tig_myparcel');
        $error = null;
        $message = &$error;
        $request = $this->getRequest();
        $name = $request->getParam('myparcel_name');
        $email = $request->getParam('myparcel_email');
        $labelDescription = $request->getParam('myparcel_label_description');

        if (!$email)
            $error = $helper->__('You did not specify a email');

        if (!$name)
            $error = $helper->__('You did not specify a name');

        if ($error == null) {

            $data = array(
                'cc' => 'NL',
                'carrier' => 1,
                'email' => $email,
                'name' => $name,
                'options' => array(
                    'package_type' => 1,
                    'label_description' => $labelDescription
                )
            );

            $api = Mage::getModel('tig_myparcel/api_myParcel');
            $response = $api->sendUnrelatedRetourmailRequest($data)
                ->sendRequest()
                ->getRequestResponse();
            $aResponse = json_decode($response, true);
            if ($aResponse) {
                $message = $helper->__('Mail send to') . ' ' . $email;
            } else {
                $error = 'Something goes wrong with your request. Please feel free to contact MyParcel.';
            }
        }

        header('Content-Type: application/json');
        echo json_encode(array(
            'message' => $message
        ));
        exit;
    }

    /**
     * @return Mage_Core_Controller_Varien_Action
     * @throws TIG_MyParcel2014_Exception
     */
    public function printPackingSlipAction(){

        $orderIds = $this->_getOrderIds();
        $flag = false;
        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                    ->setOrderFilter($orderId)
                    ->load();
                if ($shipments->getSize()) {
                    $flag = true;
                    if (!isset($pdf)){
                        $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipments);
                    } else {
                        $pages = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipments);
                        $pdf->pages = array_merge ($pdf->pages, $pages->pages);
                    }
                }
            }
            if ($flag) {
                return $this->_prepareDownloadResponse(
                    'packingslip'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(),
                    'application/pdf'
                );
            } else {
                $this->_getSession()->addError($this->__('There are no printable documents related to selected orders.'));
                $this->_redirect('*/*/');
            }
        }
        $this->_redirect('*/*/');

    }

    /**
     * Load an array of shipments based on an array of shipmentIds and check if they're shipped using MyParcel
     *
     * @param array|int $shipmentIds
     * @param boolean   $loadMyParcelShipments Flag that determines whether the shipments will be loaded as
     *                                         Mage_Sales_Model_Shipment or TIG_MyParcel2014_Model_Shipment objects.
     * @param boolean   $throwException        Flag whether an exception should be thrown when loading the shipment fails.
     * @param bool $keyIsConsignmentId         When creating a new shipment there is no consignment_id. Other times it
     *                                         is necessary to use consignment_id as the key.
     *
     * @return array
     * @throws TIG_MyParcel2014_Exception
     */
    protected function _loadAndCheckShipments($shipmentIds, $loadMyParcelShipments = false, $throwException = true, $keyIsConsignmentId = true)
    {
        if (!is_array($shipmentIds)) {
            $shipmentIds = array($shipmentIds);
        }

        $shipments = array();
        foreach ($shipmentIds as $shipmentId) {
            /**
             * Load the shipment.
             *
             * @var Mage_Sales_Model_Order_Shipment|TIG_MyParcel2014_Model_Shipment|boolean $shipment
             */
            $shipment = $this->_loadShipment($shipmentId, $loadMyParcelShipments);

            if (!$shipment && $throwException) {
                throw new TIG_MyParcel2014_Exception(
                    $this->__(
                        'This action is not available for shipment #%s, because it was not shipped using MyParcel.',
                        $shipmentId
                    ),
                    'MYPA-0003'
                );
            } elseif (!$shipment) {
                $this->addWarning(
                    array(
                        'entity_id'   => $shipmentId,
                        'code'        => 'MYPA-0003',
                        'description' => $this->__(
                            'This action is not available for shipment #%s, because it was not shipped using MyParcel.',
                            $shipmentId
                        ),
                    )
                );

                continue;
            }

            if ($keyIsConsignmentId) {
                $shipments[$shipment->getData('consignment_id')] = $shipment;
            } else {
                $shipments[] = $shipment;
            }
        }

        return $shipments;
    }

    /**
     * Load a shipment based on a shipment ID.
     *
     * @param int     $shipmentId
     * @param boolean $loadMyParcelShipment
     *
     * @return boolean|Mage_Sales_Model_Order_Shipment|TIG_MyParcel2014_Model_Shipment
     */
    protected function _loadShipment($shipmentId, $loadMyParcelShipment)
    {
        if ($loadMyParcelShipment === false) {
            /**
             * @var Mage_Sales_Model_Order_Shipment $shipment
             */
            $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
            if (!$shipment || !$shipment->getId()) {
                return false;
            }

            $shippingMethod = $shipment->getOrder()->getShippingMethod();
        } else {
            /**
             * @var TIG_MyParcel2014_Model_Shipment $shipment
             */
            $shipment = $this->_getMyParcelShipment($shipmentId);
            if (!$shipment || !$shipment->getId()) {
                $shipment->setShipmentId($shipmentId);
            }

            $shippingMethod = $shipment->getShipment()->getOrder()->getShippingMethod();
        }

        /**
         * Check if the shipping method used is allowed
         */
        if (!Mage::helper('tig_myparcel')->shippingMethodIsMyParcel($shippingMethod) || $shipment->getShipment()->getOrder()->getIsVirtual()) {
            return false;
        }

        return $shipment;
    }

    /**
     * Gets the MyParcel shipment associated with a Magento shipment.
     *
     * @param int $shipmentId
     *
     * @return TIG_MyParcel2014_Model_Shipment
     */
    protected function _getMyParcelShipment($shipmentId)
    {
        $myParcelShipment = Mage::getModel('tig_myparcel/shipment')->load($shipmentId, 'shipment_id');

        return $myParcelShipment;
    }

    /**
     * Creates a shipment of an order containing all available items
     *
     * @param int $orderId
     * @param boolean $returnShipment
     *
     * @return int|TIG_MyParcel2014_Exception
     *
     *
     * @throws TIG_MyParcel2014_Exception
     */
    protected function _createShipment($orderId, $returnShipment = false)
    {
        /**
         * @var Mage_Sales_Model_Order $order
         */
        $order = Mage::getModel('sales/order')->load($orderId);

        if($order->isCanceled()){
            throw new TIG_MyParcel2014_Exception(
                $this->__('Order %s cannot be shipped, because it is cancelled.', $order->getIncrementId()),
                'MYPA-0004'
            );
        }

        if (!$order->canShip()) {
            throw new TIG_MyParcel2014_Exception(
                $this->__('Order #%s cannot be shipped at this time.', $order->getIncrementId()),
                'MYPA-0004'
            );
        }

        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = Mage::getModel('sales/service_order', $order)
            ->prepareShipment($this->_getItemQtys($order));

        $shipment->register();
        $this->_saveShipment($shipment);

        if($returnShipment){
            return $shipment;
        }

        return $shipment->getId();
    }

    /**
     * Save shipment and order in one transaction
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     *
     * @return $this
     */
    protected function _saveShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);
        Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $this;
    }

    /**
     * Checks if any warnings were received while processing the shipments and/or orders. If any warnings are found they
     * are added to the adminhtml session as a notice.
     *
     * @return $this
     */
    protected function _checkForWarnings()
    {
        /**
         * Check if any warnings were registered
         */
        $cifWarnings = Mage::registry('myparcel_api_warnings');

        if (is_array($cifWarnings) && !empty($cifWarnings)) {
            $this->_addWarningMessages($cifWarnings, $this->__('MyParcel replied with the following warnings:'));
        }

        $warnings = $this->getWarnings();

        if (!empty($warnings)) {
            $this->_addWarningMessages(
                $warnings,
                $this->__('The following shipments or orders could not be processed:')
            );
        }

        return $this;
    }

    /**
     * Add an array of warning messages to the adminhtml session.
     *
     * @param        $warnings
     * @param string $headerText
     *
     * @return $this
     * @throws TIG_MyParcel2014_Exception
     */
    protected function _addWarningMessages($warnings, $headerText = '')
    {
        $helper = Mage::helper('tig_myparcel');

        /**
         * Create a warning message to display to the merchant.
         */
        $warningMessage = $headerText;
        $warningMessage .= '<ul class="myparcel-warning">';

        /**
         * Add each warning to the message.
         */
        foreach ($warnings as $warning) {
            /**
             * Warnings must have a description.
             */
            if (!array_key_exists('description', $warning)) {
                continue;
            }

            /**
             * Codes are optional for warnings, but must be present in the array. If no code is found in the warning we
             * add an empty one.
             */
            if (!array_key_exists('code', $warning)) {
                $warning['code'] = null;
            }

            /**
             * Get the formatted warning message.
             */
            $warningText = $helper->getSessionMessage(
                $warning['code'],
                'warning',
                $this->__($warning['description'])
            );

            /**
             * Prepend the warning's entity ID if present.
             */
            if (!empty($warning['entity_id'])) {
                $warningText = $warning['entity_id'] . ': ' . $warningText;
            }

            /**
             * Build the message proper.
             */
            $warningMessage .= '<li>' . $warningText . '</li>';
        }

        $warningMessage .= '</ul>';

        /**
         * Add the warnings to the session.
         */
        $helper->addSessionMessage('adminhtml/session', null, 'notice',
            $warningMessage
        );

        return $this;
    }

    /**
     * Output the specified string as a pdf.
     *
     * @param string $filename
     * @param string $output
     *
     * @return $this
     * @throws Zend_Controller_Response_Exception
     */
    protected function _preparePdfResponse($filename, $output)
    {
        $this->getResponse()
             ->setHttpResponseCode(200)
             ->setHeader('Pragma', 'public', true)
             ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate', true)
             ->setHeader('Content-type', 'application/pdf', true)
             ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
             ->setHeader('Last-Modified', date('r'))
             ->setBody($output);

        return $this;
    }

    /**
     * Initialize shipment items QTY
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    protected function _getItemQtys($order)
    {
        $itemQtys = array();

        /**
         * @var Mage_Sales_Model_Order_Item $item
         */
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            /**
             * the qty to ship is the total remaining (not yet shipped) qty of every item
             */
            $itemQty = $item->getQtyOrdered() - $item->getQtyShipped();

            $itemQtys[$item->getId()] = $itemQty;
        }

        return $itemQtys;
    }

}
