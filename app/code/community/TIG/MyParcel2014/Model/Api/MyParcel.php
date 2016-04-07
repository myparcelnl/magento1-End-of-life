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
    const REQUEST_TYPE_CREATE_CONSIGNMENTS  = 'create-consignments';
    const REQUEST_TYPE_REGISTER_CONFIG      = 'register-config';
    const REQUEST_TYPE_RETRIEVE_LABEL        = 'shipment_labels';
    const REQUEST_TYPE_RETRIEVE_STATUS      = 'retrieve-status';
    const REQUEST_TYPE_CONSIGNMENT_CREDIT   = 'consignment-credit';
    const REQUEST_TYPE_CREATE_RETOURLINK    = 'create-retourlink';
    const REQUEST_TYPE_GET_LOCATIONS        = 'pickup';

    const REQUEST_HEADER_SHIPMENT           = 'Content-Type: application/vnd.shipment+json; ';
    const REQUEST_HEADER_RETURN             = 'Content-Type: application/vnd.return_shipment+json; ';
    const REQUEST_HEADER_UNRALED_RETURN     = 'Content-Type: application/vnd.unrelated_return_shipment+json; ';

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
    protected $requestHash = '';

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
        $header[] = 'Authorization: basic ' . base64_encode('MYSNIzQWqNrYaDeFxJtVrujS9YEuF9kiykBxf8Sj');

        $this->requestHeader   = $header;

        $this->_hashRequest();

        return $this;
    }

    /**
     * send the created request to MyParcel
     *
     * @param string $method
     *
     * @return $this|false|array|string
     */
    public function sendRequest($method = 'POST')
    {
        if (!$this->_checkConfigForRequest() && $method == 'POST') {
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

        //decode the json response
        $aResult = json_decode($response, true);

        if(is_array($aResult)){

            //log the response
            $helper->log(json_decode($aResult, true));

            //check if there are curl-errors
            if ($response === false) {
                $error              = $request->getError();
                $this->requestError = $error;
                //$this->requestErrorDetail = $error;
                return $this;
            }

            //check if the response has errors codes
            if(isset($aResult['errors'][0]['code'])){
                $this->requestError = $aResult['errors'][0]['code'];
                $this->requestErrorDetail = $aResult['errors'][0]['code'];
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
     * @TODO for use in massaction
     * @param array $shippingIds
     */
    public function createConsignmentsRequest($shippingIds = array()){}

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
     * create a request string for checking the status of a consignment
     *
     * @param $consignmentId
     * @return $this
     */
    public function createRetrieveStatusRequest($consignmentId)
    {
        $data = array(
            'consignment_id' => $consignmentId,
        );

        $requestString = $this->_createRequestString($data);

        $this->_setRequestParameters($requestString, self::REQUEST_TYPE_RETRIEVE_STATUS);

        return $this;
    }

    /**
     * create a request string for crediting a consignment
     *
     * @param $consignmentId
     * @return $this
     */
    public function createConsignmentCreditRequest($consignmentId)
    {
        $data = array(
            'consignment_id' => $consignmentId,
        );

        $requestString = $this->_createRequestString($data);

        $this->_setRequestParameters($requestString, self::REQUEST_TYPE_CONSIGNMENT_CREDIT);

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
        $data = array(
            'consignment_id' => $consignmentId,
        );

        $requestString = $this->_createRequestString($data);

        $this->_setRequestParameters($requestString, self::REQUEST_TYPE_CREATE_RETOURLINK);

        return $this;
    }

    /**
     * create a request string for getting the locations
     *
     * @param array $data
     *
     * @return $this
     */
    public function createGetLocationsRequest($data)
    {
        if (empty($data['courier'])) {
            $data['courier'] = 'postnl';
        }
        if (empty($data['courier'])) {
            $data['country'] = 'nl';
        }

        $requestString = $this->_createRequestString($data);

        $this->_setRequestParameters($requestString, self::REQUEST_TYPE_GET_LOCATIONS);

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

        if(empty($this->requestHash)){
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
     */
    protected function _getConsignmentData(TIG_MyParcel2014_Model_Shipment $myParcelShipment)
    {
        $helper = Mage::helper('tig_myparcel');
        $order = $myParcelShipment->getOrder();
        $storeId = $order->getStore()->getId();

        if($storeId != $this->getStoreId()){
            $this->apiUsername = $helper->getConfig('username', 'api', $storeId);
            $this->apiKey      = $helper->getConfig('key', 'api', $storeId, true);
        }

        $shippingAddress = $myParcelShipment->getShippingAddress();
        $streetData      = $helper->getStreetData($shippingAddress,$storeId);
        $email           = $myParcelShipment->getOrder()->getCustomerEmail();

        $data = array(
            'recipient'     => array(
                'cc'    =>      $shippingAddress->getCountry(),
                'person'        => trim($shippingAddress->getName()),
                'company'       => $shippingAddress->getCompany(),
                'postal_code'  => trim($shippingAddress->getPostcode()),
                'street'        => trim($streetData['streetname']),
                'number'        => trim($streetData['housenumber']),
                'number_suffix' => $streetData['housenumberExtension'],
                'city'          => $shippingAddress->getCity(),
                'phone'          => '',
                'email'         => $email,
            ),
            'options'    => $this->_getOptionsData($myParcelShipment),
        );

        if($shippingAddress->getCountry() != 'NL')
        {
            $data['recipient']['eps_postal_code"'] = $data['recipient']['postal_code"'];
            $data['recipient']['street'] = trim(str_replace('  ', ' ', implode(' ', $streetData)));
            unset($data['recipient']['postal_code"']);
            unset($data['recipient']['number']);
            unset($data['recipient']['number_suffix']);
        }

        // add customs data for EUR3 and World shipments
        if($helper->countryNeedsCustoms($shippingAddress->getCountry()))
        {
            $data['customs_shipment_type'] = $helper->getConfig('customs_type', 'shipment', $storeId);
            $data['customs_invoice']       = $order->getIncrementId();
            $data['CustomsContent']        = array();

            $customsContentType = $helper->getConfig('customs_hstariffnr', 'shipment', $storeId);
            if($myParcelShipment->getCustomsContentType()){
                $customsContentType = $myParcelShipment->getCustomsContentType();
            }

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

                        // TODO: check for multiple test cases with configurable and bundled products
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

                    $data['CustomsContent'][$i] = array(
                        'Description'     => $item->getName(),
                        'Quantity'        => $qty,
                        'Weight'          => $weight,
                        'Value'           => $price,
                        'HSTariffNr'      => $customsContentType,
                        'CountryOfOrigin' => Mage::getStoreConfig('general/country/default', $storeId),
                    );

                    if(++$i >= 5) {
                        break; // max 5 entries
                    }
                }
            }
            $data['weight'] = $totalWeight;
        }

        /**
         * If the customer has chosen to pick up their order at a PakjeGemak location, add the PakjeGemak address.
         */
        $pgAddress      = $helper->getPgAddress($myParcelShipment);
        $shippingMethod = $order->getShippingMethod();

        if ($pgAddress && $helper->shippingMethodIsPakjegemak($shippingMethod)) {
            $pgStreetData      = $helper->getStreetData($pgAddress,$storeId);
            $data['PgAddress'] = array(
                'cc'    => $pgAddress->getCountry(),
                'person'            => $pgAddress->getName(),
                'business'        => $pgAddress->getCompany(),
                'postal_code"'        => trim($pgAddress->getPostcode()),
                'street'          => $pgStreetData['streetname'],
                'number'    => $pgStreetData['housenumber'],
                'number_suffix' => $pgStreetData['housenumberExtension'],
                'city'            => $pgAddress->getCity(),
                'email'           => $email,
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
            'large_format'          => 0,
            'only_recipient'        => $myParcelShipment->getHomeAddressOnly(),
            'signature'             => $myParcelShipment->getSignatureOnReceipt(),
            'return'                => $myParcelShipment->getReturnIfNoAnswer(),
            'label_description'     => $myParcelShipment->getOrder()->getIncrementId(),
        );
        if($myParcelShipment->getInsured() === 1){
            $data['insurance']['amount'] = $this->_getInsuredAmount($myParcelShipment) * 100;
            $data['insurance']['currency'] = 'EUR';
        }


        if($myParcelShipment->getShippingAddress()->getCountry() != 'NL')
        {
            // strip all Dutch domestic options if shipment is not NL
            unset($data['only_recipient']);
            unset($data['signature']);
            unset($data['return']);
            unset($data['insured']);
        }


        return $data;
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
     *
     * @return string
     */
    protected function _createRequestString(array $data)
    {
        $requestData['data']['shipments'][] = $data;

        return json_encode($requestData);

    }

    /**
     * Creates a hash to ensure security, sets the $requestHash class-variable
     *
     * @return $this
     */
    protected function _hashRequest()
    {
        //generate hash
        $this->requestHash = hash_hmac('sha1', 'POST&' . urlencode($this->requestString), $this->apiKey);

        return $this;
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
            case 1: $aPositions[] = 1;
            case 2: $aPositions[] = 2;
            case 3: $aPositions[] = 3;
            case 4: $aPositions[] = 4;
                break;
        }

        return implode(';',$aPositions);
    }
}
