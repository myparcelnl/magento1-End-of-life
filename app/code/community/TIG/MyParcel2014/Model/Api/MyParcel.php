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
 *
 *
 * Myparcel API class. Contains all the functionality to connect to Myparcel and get information or create consignments
 *
 * @method bool hasStoreId()
 */
class TIG_MyParcel2014_Model_Api_MyParcel extends Varien_Object
{
    /**
     * Supported request types.
     */
    const REQUEST_TYPE_CREATE_CONSIGNMENT   = 'shipments';
    const REQUEST_TYPE_REGISTER_CONFIG      = 'register-config';
    const REQUEST_TYPE_RETRIEVE_LABEL       = 'shipment_labels';
    const REQUEST_TYPE_GET_LOCATIONS        = 'pickup';

    /**
     * Consignment types
     */
    const TYPE_MORNING             = 1;
    const TYPE_STANDARD            = 2;
    const TYPE_NIGHT               = 3;
    const TYPE_RETAIL              = 4;
    const TYPE_RETAIL_EXPRESS      = 5;

    /**
     * API headers
     */
    const REQUEST_HEADER_SHIPMENT           = 'Content-Type: application/vnd.shipment+json; ';
    const REQUEST_HEADER_RETURN             = 'Content-Type: application/vnd.return_shipment+json; ';
    const REQUEST_HEADER_UNRELATED_RETURN   = 'Content-Type: application/vnd.unrelated_return_shipment+json; ';

    /**
     * @var string
     */
    protected $apiUsername = '';

    /**
     * @var string
     */
    protected $apiKey = '';

    /**
     * @var string
     */
    protected $apiUrl = '';

    /**
     * @var string
     */
    protected $requestString = '';

    /**
     * @var string
     */
    protected $requestType = '';

    /**
     * @var string
     */
    protected $requestHeader = '';

    /**
     * @var string
     */
    protected $requestResult = false;

    /**
     * @var string
     */
    protected $requestError = false;

    /**
     * @var string
     */
    protected $requestErrorDetail = false;

    /**
     * sets the api username and api key on construct.
     *
     * @return void
     */
    protected function _construct()
    {
        $storeId  = $this->getStoreId();
        $helper   = Mage::helper('tig_myparcel');
        $username = $helper->getConfig('username', 'api', $storeId);
        $key      = $helper->getConfig('key', 'api', $storeId, true);
        $url   = $helper->getConfig('url');

        if (Mage::app()->getStore()->isCurrentlySecure()) {
            if(!Mage::getStoreConfig('tig_myparcel/general/ssl_handshake')){
                $url = str_replace('http://', 'https://', $url);
            }
        }

        if (empty($username) && empty($key)) {
            return;
        }

        $this->apiUrl      = $url;
        $this->apiUsername = $username;
        $this->apiKey      = $key;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        if ($this->hasStoreId()) {
            return $this->_getData('store_id');
        }

        $storeId = Mage::app()->getStore()->getId();

        $this->setStoreId($storeId);
        return $storeId;
    }

    public function setStoreId($storeId)
    {
        $helper = Mage::helper('tig_myparcel');

        $this->storeId     = $storeId;
        $this->apiUsername = $helper->getConfig('username', 'api', $storeId);
        $this->apiKey      = $helper->getConfig('key', 'api', $storeId, true);

        return $this;
    }

    /**
     * returns the response as an array, when an error occurs it will return the error message as a string
     * @return array
     */
    public function getRequestResponse()
    {
        if(!empty($this->requestError)){
            return $this->requestError;
        }

        return $this->requestResult;
    }

    public function getRequestErrorDetail()
    {
        $errorDetail = $this->requestErrorDetail;

        if(!$errorDetail){

            if(!empty($this->requestError)){
                return $this->requestError;
            }

            return false;
        }

        if(is_string($errorDetail))
        {
            return $errorDetail;
        }

        if(is_array($errorDetail) && !empty($errorDetail))
        {
            $return = $this->requestError.' - ';
            foreach($errorDetail as $key => $errorMessage)
            {
                $return .= $key;
                if(is_string($errorMessage))
                {
                    $return .= ': '.$errorMessage;
                }

                if(is_array($errorMessage) && !empty($errorMessage))
                {
                    $return .= ':<br/>'."\n";
                    foreach($errorMessage as $messageKey => $value)
                    {
                        $return .= $messageKey .' - '.$value[0];
                    }
                }
            }

            if($return == '')
            {
                return false;
            }

            return $return;
        }
        return false;
    }

    /**
     * Sets the parameters for an API call based on a string with all required request parameters and the requested API
     * method.
     *
     * @param string $requestString
     * @param string $requestType
     * @param string $requestHeader
     *
     * @return $this
     */
    protected function _setRequestParameters($requestString, $requestType, $requestHeader = '')
    {
        $this->requestString = $requestString;
        $this->requestType   = $requestType;

        $header[] = $requestHeader . 'charset=utf-8';
        $header[] = 'Authorization: basic ' . base64_encode($this->apiKey);

        $this->requestHeader   = $header;

        return $this;
    }

    /**
     * send the created request to MyParcel
     *
     * @param string $method
     *
     * @throws TIG_MyParcel2014_Exception
     *
     * @return $this|false|array|string
     */
    public function sendRequest($method = 'POST')
    {
        if (!$this->_checkConfigForRequest()) {
            return false;
        }

        //instantiate the helper
        $helper = Mage::helper('tig_myparcel');

        //curl options
        $options = array(
            CURLOPT_POST           => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER    => true,
        );

        $config = array(
            'header'  => 0,
            'timeout' => 60,
        );

        //instantiate the curl adapter
        $request = new TIG_MyParcel2014_Model_Api_Curl();
        //add the options
        foreach($options as $option => $value)
        {
            $request->addOption($option, $value);
        }

        $header = $this->requestHeader;

        //do the curl request
        if($method == 'POST'){

            //curl request string
            $body = $this->requestString;

            //complete request url
            $url = $this->apiUrl . $this->requestType;

            // log the request url
            $helper->log($url);
            $helper->log(json_decode($body));
            $request->setConfig($config)
                ->write(Zend_Http_Client::POST, $url, '1.1', $header, $body);
        } else {

            //complete request url
            $url  = $this->apiUrl;
            $url .= $this->requestType;
            $url .= $this->requestString;

            // log the request url
            $helper->log($url);

            $request->setConfig($config)
                ->write(Zend_Http_Client::GET, $url, '1.1', $header);
        }

        //read the response
        $response = $request->read();

        if ($this->requestType == 'shipment_labels' && !preg_match("/^%PDF-1./", $response)) {
            $pdfError = $helper->__('There was an error when generating a PDF. Please feel free to contact MyParcel.');
            throw new TIG_MyParcel2014_Exception(
                $pdfError . '::' . $url,
                'MYPA-0100'
            );
        }

       $aResult = json_decode($response, true);

        if(is_array($aResult)){

            //log the response
            $helper->log(json_encode($aResult, true));

            //check if there are curl-errors
            if ($response === false) {
                $error              = $request->getError();
                $this->requestError = $error;
                //$this->requestErrorDetail = $error;
                return $this;
            }

            //check if the response has errors codes
            if(isset($aResult['errors']) && isset($aResult['message'])) {
                if(strpos($aResult['message'], 'Access Denied')){
                    $this->requestError = $helper->__('Wrong API key. Go to MyParcel settings to set the API key.');
                } else {
                    foreach ($aResult['errors'] as $tmpError) {
                        $errorMessage = $aResult['message'] . '; ' . $tmpError['fields'][0];
                        $this->requestError = $errorMessage;
                    }
                }
                $request->close();

                return $this;
            } else if (isset($aResult['errors'][0]['code'])){
                $this->requestError = $aResult['errors'][0]['code'] . ' - ' . $aResult['errors'][0]['human'][0];
                $this->requestErrorDetail = $aResult['errors'][0]['code'] . ' - ' . $aResult['errors'][0]['human'][0];
                $request->close();

                return $this;
            }
        }

        $this->requestResult = $response;

        //close the server connection with MyParcel
        $request->close();

        return $this;
    }

    /**
     * Prepares the API for processing a create consignment request.
     *
     * @param TIG_MyParcel2014_Model_Shipment $myParcelShipment
     *
     * @return $this
     */
    public function createConsignmentRequest(TIG_MyParcel2014_Model_Shipment $myParcelShipment)
    {
        $data = $this->_getConsignmentData($myParcelShipment);

        $requestString = $this->_createRequestString($data);

        $this->_setRequestParameters($requestString, self::REQUEST_TYPE_CREATE_CONSIGNMENT, self::REQUEST_HEADER_SHIPMENT);

        return $this;
    }

    /**
     * @param array $consignmentIds
     *
     * @return array $responseShipments|false
     */
    public function getConsignmentsInfoData($consignmentIds = array()){

        if($consignmentIds){

            $apiInfo    = Mage::getModel('tig_myparcel/api_myParcel');
            $responseData = $apiInfo->createConsignmentsInfoRequest($consignmentIds)
                ->sendRequest('GET')
                ->getRequestResponse();

            $responseData = json_decode($responseData);

            $responseShipments = $responseData->data->shipments;

            return $responseShipments;

        } else {
            return false;
        }
    }

    /**
     * @param array $consignmentIds
     *
     * @return $this
     */
    public function  createConsignmentsInfoRequest($consignmentIds = array()){


        $requestString = '/' . implode(';',$consignmentIds);

        $this->_setRequestParameters($requestString, self::REQUEST_TYPE_CREATE_CONSIGNMENT, self::REQUEST_HEADER_SHIPMENT);

        return $this;

    }

    /**
     * Prepares the API for retrieving pdf's for an array of consignment IDs.
     *
     * @param array       $consignmentIds
     * @param int|string  $start
     * @param string      $perpage
     *
     * @return $this
     */
    public function createRetrievePdfsRequest($consignmentIds = array(), $start = 1, $perpage = 'A4')
    {
        $positions = '';

        if($perpage == 'A4') {
            $positions = '&positions=' . $this->_getPositions((int) $start);
        }
        $data = implode(';',$consignmentIds);
        $getParam = '/' . $data . '?format=' . $perpage . $positions;

        $this->_setRequestParameters($getParam, self::REQUEST_TYPE_RETRIEVE_LABEL);

        return $this;
    }

    /**
     * Prepares the API for retrieving pdf's for a consignment ID.
     *
     * @return $this
     */
    public function createRegisterConfigRequest()
    {
        $data = array(
            'webshop_version' => 'Magento ' . Mage::getVersion(),
            'plugin_version'  => (string) Mage::getConfig()->getModuleConfig('TIG_MyParcel2014')->version,
            'php_version'     => phpversion(),
        );

        $requestString = $this->_createRequestString($data);

        $this->_setRequestParameters($requestString, self::REQUEST_TYPE_REGISTER_CONFIG);

        return $this;
    }

    /**
     * Send email with return label
     *
     * @param $data array
     *
     * @return $this
     */
    public function sendUnrelatedRetourmailRequest($data)
    {
        $requestString = $this->_createRequestString($data, 'return_shipments');

        $this->_setRequestParameters($requestString, self::REQUEST_TYPE_CREATE_CONSIGNMENT, self::REQUEST_HEADER_UNRELATED_RETURN);

        return $this;
    }

    /**
     * create a request string for generating a retour-url
     *
     * @param $consignmentId
     * @return $this
     * @var Mage_Sales_Model_Order_Shipment $shipment
     */
    public function createRetourmailRequest($shipment, $consignmentId)
    {
        $data = array(
            'parent' => (int)$consignmentId,
            'carrier' => 1,
            'email' => $shipment->getOrder()->getCustomerEmail(),
            'name' => $shipment->getOrder()->getCustomerName()
        );

        $requestString = $this->_createRequestString($data, 'return_shipments');

        $this->_setRequestParameters($requestString, self::REQUEST_TYPE_CREATE_CONSIGNMENT, self::REQUEST_HEADER_RETURN);

        return $this;
    }

    /**
     * create a request string for generating a retour-url
     *
     * @param $consignmentId
     * @return $this
     */
    public function createRetourlinkRequest($consignmentId)
    {
        $data = array('id' => (int)$consignmentId);

        $requestString = $this->_createRequestString($data, 'parent_shipments');

        $this->_setRequestParameters($requestString, 'create_related_return_shipment_link', self::REQUEST_HEADER_RETURN);

        return $this;
    }

    /**
     * Checks if all the requirements are set to send a request to MyParcel
     *
     * @return bool
     */
    protected function _checkConfigForRequest()
    {
        if(empty($this->apiUsername) || empty($this->apiKey)){
            return false;
        }

        if(empty($this->requestType)){
            return false;
        }

        if(empty($this->requestString)){
            return false;
        }


        return true;
    }

    /**
     * Gets the shipping address and product code data for this shipment.
     *
     * @param TIG_MyParcel2014_Model_Shipment $myParcelShipment
     *
     * @return array
     *
     * @throws TIG_MyParcel2014_Exception
     */
    protected function _getConsignmentData(TIG_MyParcel2014_Model_Shipment $myParcelShipment)
    {
        /** @var TIG_MyParcel2014_Helper_Data $helper */
        $helper = Mage::helper('tig_myparcel');
        $order = $myParcelShipment->getOrder();
        $storeId = $order->getStore()->getId();

        if($storeId != $this->getStoreId()){
            $this->apiUsername = $helper->getConfig('username', 'api', $storeId);
            $this->apiKey      = $helper->getConfig('key', 'api', $storeId, true);
        }

        $shippingAddress = $myParcelShipment->getShippingAddress();
        $streetData      = $helper->getStreetData($shippingAddress);
        $email           = $myParcelShipment->getOrder()->getCustomerEmail();

        $data = array(
            'recipient'     => array(
                'cc'    =>      $shippingAddress->getCountry(),
                'person'        => trim($shippingAddress->getName()),
                'company'       => (string) trim($shippingAddress->getCompany()),
                'postal_code'  => trim($shippingAddress->getPostcode()),
                'street'        => trim($streetData['streetname']),
                'number'        => trim($streetData['housenumber']),
                'number_suffix' => trim($streetData['housenumberExtension']),
                'city'          => trim($shippingAddress->getCity()),
                'email'         => $email,
            ),
            'options'    => $this->_getOptionsData($myParcelShipment),
        );

        if ($myParcelShipment->getShippingAddress()->getCountry() != 'NL') {
            $phone           = $order->getBillingAddress()->getTelephone();
            if ($phone)
                $data['recipient']['phone'] = $phone;

            $data['recipient']['street'] = trim(str_replace('  ', ' ', implode(' ', $streetData)));
            unset($data['recipient']['number']);
            unset($data['recipient']['number_suffix']);
        }

        // add customs data for EUR3 and World shipments
        if($helper->countryNeedsCustoms($shippingAddress->getCountry()))
        {

            $customsContentType = null;
            if($myParcelShipment->getCustomsContentType()){
                $customsContentType = explode(',', $myParcelShipment->getCustomsContentType());
            }

            if($data['options']['package_type'] == 2){
                throw new TIG_MyParcel2014_Exception(
                    $helper->__('International shipments can not be sent by') . ' ' . strtolower($helper->__('Letter box')),
                    'MYPA-0027'
                );
            }

            $data['customs_declaration']                        = array();
            $data['customs_declaration']['items']               = array();
            $data['customs_declaration']['invoice']             = $order->getIncrementId();
            $customType = (int)$helper->getConfig('customs_type', 'shipment', $storeId);
            $data['customs_declaration']['contents']            = $customType == 0 ? 1 : $customType;

            $totalWeight = 0;
            $items = $myParcelShipment->getOrder()->getAllItems();
            $i = 0;
            foreach($items as $item) {
                if($item->getProductType() == 'simple') {
                    $parentId = $item->getParentItemId();
                    $weight = floatval($item->getWeight());
                    $price = floatval($item->getPrice());
                    $qty = intval($item->getQtyOrdered());

                    if(!empty($parentId)) {
                        $parent = Mage::getModel('sales/order_item')->load($parentId);

                        if (empty($weight)) {
                            $weight = $parent->getWeight();
                        }

                        if (empty($price)) {
                            $price = $parent->getPrice();
                        }
                    }

                    $weight *= $qty;
                    $weight = max(array(1, $weight));
                    $totalWeight += $weight;

                    $price *= $qty;

                    if(empty($customsContentType)){
                        $customsContentTypeItem = $helper->getHsCode($item, $storeId);
                    } else {
                        $customsContentTypeItem = key_exists($i, $customsContentType) ? $customsContentType[$i] : $customsContentType[0];
                    }
                    if(!$customsContentTypeItem) {
                        throw new TIG_MyParcel2014_Exception(
                            $helper->__('No Customs Content HS Code found. Go to the MyParcel plugin settings to set this code.'),
                            'MYPA-0026'
                        );
                    }

                    $data['customs_declaration']['items'][] = array(
                        'description'       => $item->getName(),
                        'amount'            => $qty,
                        'weight'            => (int)$weight * 1000,
                        'item_value'        => array('amount' => $price * 100, 'currency' => 'EUR'),
                        'classification'      => $customsContentTypeItem,
                        'country' => Mage::getStoreConfig('general/country/default', $storeId),

                    );

                    if(++$i >= 5) {
                        break; // max 5 entries
                    }
                }
            }
            $data['customs_declaration']['weight'] = (int)$totalWeight;
            $data['physical_properties']['weight'] = (int)$totalWeight;
        }

        /**
         * If the customer has chosen to pick up their order at a PakjeGemak location, add the PakjeGemak address.
         */
        $pgAddress      = $helper->getPgAddress($myParcelShipment);
        $shippingMethod = $order->getShippingMethod();

        if ($pgAddress && $helper->shippingMethodIsPakjegemak($shippingMethod)) {
            $pgStreetData      = $helper->getStreetData($pgAddress);
            $data['options']['signature'] = 1;
            $data['pickup'] = array(
                'postal_code'       => trim($pgAddress->getPostcode()),
                'street'            => trim($pgStreetData['streetname']),
                'city'              => trim($pgAddress->getCity()),
                'number'            => trim($pgStreetData['housenumber']),
                'location_name'     => trim($pgAddress->getCompany()),
            );
        }

        $data['carrier'] = 1;
        return $data;
    }

    /**
     * Gets the product code parameters for this shipment.
     *
     * @param TIG_MyParcel2014_Model_Shipment $myParcelShipment
     *
     * @return array
     */
    protected function _getOptionsData(TIG_MyParcel2014_Model_Shipment $myParcelShipment)
    {


        /**
         * Add the shipment type parameter.
         */
        switch ($myParcelShipment->getShipmentType()) {
            case $myParcelShipment::TYPE_LETTER_BOX:
                $packageType = 2;
                break;
            case $myParcelShipment::TYPE_UNPAID:
                $packageType = 3;
                break;
            case $myParcelShipment::TYPE_NORMAL:
            default:
                $packageType = 1;
        }

        $data = array(
            'package_type'          => $packageType,
            'large_format'          => (int)$myParcelShipment->isXL(),
            'only_recipient'        => (int)$myParcelShipment->isHomeAddressOnly(),
            'signature'             => (int)$myParcelShipment->isSignatureOnReceipt(),
            'return'                => (int)$myParcelShipment->getReturnIfNoAnswer(),
            'label_description' => $myParcelShipment->getOrder()->getIncrementId(),
        );

        $checkoutData = json_decode($myParcelShipment->getOrder()->getMyparcelData(), true);

        if ($checkoutData !== null) {

            if (key_exists('price_comment', $checkoutData['time'][0]) && $checkoutData['time'][0]['price_comment'] !== null) {
                switch ($checkoutData['time'][0]['price_comment']) {
                    case 'morning':
                        $data['delivery_type'] = self::TYPE_MORNING;
                        break;
                    case 'standard':
                        $data['delivery_type'] = self::TYPE_STANDARD;
                        break;
                    case 'night':
                        $data['delivery_type'] = self::TYPE_NIGHT;
                        break;
                }

                if ($checkoutData['date'] !== null) {


                    $checkoutDateTime = $checkoutData['date'] . ' 00:00:00';
                    $currentDateTime = new dateTime();
                    if (date_parse($checkoutDateTime) >= $currentDateTime) {
                        $data['delivery_date'] = $checkoutDateTime;
                    } else {
                        $currentDateTime->modify('+1 day');
                        $nextDeliveryDay = $this->getNextDeliveryDay($currentDateTime);
                        $data['delivery_date'] = $nextDeliveryDay->format('Y-m-d H:i:s');
                    }

                    $dateTime = date_parse($checkoutData['date']);
                    $data['label_description'] = $data['label_description'] . ' (' . $dateTime['day'] . '-' . $dateTime['month'] . ')';
                }

            } elseif ($checkoutData['price_comment'] !== null) {
                switch ($checkoutData['price_comment']) {
                    case 'retail':
                        $data['delivery_type'] = self::TYPE_RETAIL;
                        break;
                    case 'retailexpress':
                        $data['delivery_type'] = self::TYPE_RETAIL_EXPRESS;
                        break;
                }
            }
        }

        if ((int)$myParcelShipment->getInsured() === 1) {
            $data['insurance']['amount'] = $this->_getInsuredAmount($myParcelShipment) * 100;
            $data['insurance']['currency'] = 'EUR';
        }


        if ($myParcelShipment->getShippingAddress()->getCountry() != 'NL') {
            // strip all Dutch domestic options if shipment is not NL
            unset($data['only_recipient']);
            unset($data['signature']);
            unset($data['return']);
            unset($data['delivery_type']);
            unset($data['delivery_date']);
        }

        return $data;
    }

    /**
     * @param dateTime $dateTime
     *
     * @return mixed
     */
    private function getNextDeliveryDay($dateTime)
    {
        $weekDay = date('w', strtotime($dateTime));
        if ($weekDay == 0 || $weekDay == 6) {
            $dateTime->modify('+1 day');
            $dateTime = $this->getNextDeliveryDay($dateTime);
        }

        return $dateTime;
    }

    /**
     * Get the insured amount for this shipment.
     *
     * @param TIG_MyParcel2014_Model_Shipment $myParcelShipment
     *
     * @return int
     */
    protected function _getInsuredAmount(TIG_MyParcel2014_Model_Shipment $myParcelShipment)
    {
        if ($myParcelShipment->getInsured()) {
            return (int) $myParcelShipment->getInsuredAmount();
        }

        return 0;
    }

    /**
     * Creates a url-encoded request string.
     *
     * @param array $data
     * @param string $dataType
     *
     * @return string
     */
    protected function _createRequestString(array $data, $dataType = 'shipments')
    {
        $requestData['data'][$dataType][] = $data;

        return json_encode($requestData);
    }

    /**
     * Generating positions for A4 paper
     *
     * @param int $start
     * @return string
     */
    protected function _getPositions($start)
    {
        $aPositions = array();
        switch ($start){
            case 1:
                $aPositions[] = 1;
            case 2:
                $aPositions[] = 2;
            case 3:
                $aPositions[] = 3;
            case 4:
                $aPositions[] = 4;
                break;
        }

        return implode(';',$aPositions);
    }
}
