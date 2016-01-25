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
 * @copyright   Copyright (c) 2015 Total Internet Group B.V. (http://www.tig.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_MyParcel2014_Model_Checkout_Validate
{
    /**
     * Validation regular expressions.
     */
    const HOUSENR_REGEX      = "#^[0-9]*$#";
    const POSTCODE_REGEX     = "#^[1-9][0-9]{3}[\s]*[a-zA-Z]{2}$#";
    const CITY_NAME_REGEX    = '#^[a-zA-Z]+(?:(?:\\s+|-)[a-zA-Z]+)*$#';
    const STREET_NAME_REGEX  = "#^[a-zA-Z0-9\s,'-]*$#";
    const PHONE_NUMBER_REGEX = '#^(((\+31|0|0031)){1}[1-9]{1}[0-9]{8})$#i';

    /**
     * Validate getLocations post data and return the validated data as an array.
     *
     * @param array $data
     *
     * @return string[]|false
     */
    public function validateGetLocationsData(array $data)
    {
        /**
         * The housenr and postcode fields are required.
         */
        if (empty($data['housenr']) || empty($data['postcode'])) {
            return false;
        }

        /**
         * Trim the housenr and postcode values.
         */
        $houseNr = trim($data['housenr']);
        $postcode = trim($data['postcode']);

        /**
         * Validate that the housenr and postcode are valid values.
         */
        $houseNrValidator = new Zend_Validate_Regex(array('pattern' => self::HOUSENR_REGEX));
        $postcodeValidator = new Zend_Validate_Regex(array('pattern' => self::POSTCODE_REGEX));

        if (!$houseNrValidator->isValid($houseNr) || !$postcodeValidator->isValid($postcode)) {
            return false;
        }

        /**
         * Return the validated data.
         */
        $validData = array(
            'housenr' => $houseNr,
            'postcode' => $postcode,
        );

        return $validData;
    }

    /**
     * Validate saveLocation post data and return the validated data as an array.
     *
     * @param array $data
     *
     * @return string[]|false
     */
    public function validateSaveLocationData(array $data)
    {
        if (empty($data['address'])) {
            return false;
        }

        $address = Mage::helper('core')->jsonDecode($data['address']);

        /**
         * These fields are required.
         */
        if (empty($address['city'])
            || empty($address['name'])
            || empty($address['postcode'])
            || empty($address['street'])
            || empty($address['housenr'])
        ) {
            return false;
        }

        $city     = $address['city'];
        $name     = $address['name'];
        $postcode = $address['postcode'];
        $street   = $address['street'];
        $houseNr  = $address['housenr'];

        /**
         * Validate the required fields.
         */
        $cityValidator     = new Zend_Validate_Regex(array('pattern' => self::CITY_NAME_REGEX));
        $postcodeValidator = new Zend_Validate_Regex(array('pattern' => self::POSTCODE_REGEX));
        $streetValidator   = new Zend_Validate_Regex(array('pattern' => self::STREET_NAME_REGEX));
        $houseNrValidator  = new Zend_Validate_Regex(array('pattern' => self::HOUSENR_REGEX));

        if (!$cityValidator->isValid($city)
            || !$postcodeValidator->isValid($postcode)
            || !$streetValidator->isValid($street)
            || !$houseNrValidator->isValid($houseNr)
        ) {
            return false;
        }

        /**
         * Names are essentially impossible to build a regex for. Eventually you will run into a name that the regex
         * thinks is 'wrong' and you will have offended someone. Better to just strip any tags to prevent XSS attacks.
         */
        $name = Mage::helper('core')->stripTags($name);

        /**
         * Add the validated fields to an array of valid data.
         */
        $validData = array(
            'city'     => $city,
            'postcode' => $postcode,
            'street'   => $street,
            'housenr'  => $houseNr,
            'name'     => $name,
        );

        /**
         * If a phone number was also supplied, validate it and add it to the valid data array.
         *
         * Since this is an optional field, if it is not valid it will simply be ignored.
         */
        if (!empty($address['telephone'])) {
            $phoneNumber = $address['telephone'];
            $phoneNumber = str_replace(array('-', ' '), '', $phoneNumber);
            $phoneNumberValidator = new Zend_Validate_Regex(array('pattern' => self::PHONE_NUMBER_REGEX));

            if ($phoneNumberValidator->isValid($phoneNumber)) {
                $validData['telephone'] = $phoneNumber;
            }
        }

        return $validData;
    }
}