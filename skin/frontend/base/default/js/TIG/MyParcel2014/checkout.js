/**
 * LICENSE: This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 *
 * If you want to add improvements, please create a fork in our GitHub:
 * https://github.com/myparcelnl
 *
 * @author      Reindert Vetter <reindert@myparcel.nl>
 * @copyright   2010-2016 MyParcel
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US  CC BY-NC-ND 3.0 NL
 * @link        https://github.com/myparcelnl/magento1
 * @since       File available since Release 1.6.0
 */
window.mypa.observer = window.mypa.observer != null ? window.mypa.observer : {};
window.mypa.fn = window.mypa.fn != null ? window.mypa.fn : {};
(function () {
    var $, myParcelObserver, load, actionObservers, info, updateCountry, fullStreet, objRegExp, streetParts, price, data, excludeDeliveryTypes, getData, observer;

    $ = jQuery.noConflict();

    observer = $.extend({
        subItem: "label.mypa-row-subitem",
        deliveryDate: "input:radio[class='mypa-date']",
        deliveryType: "input:radio[name='mypa-delivery-type']",
        deliveryTime: "input:radio[name='mypa-delivery-time']",
        onlyRecipient: "input:checkbox[name='mypa-only-recipient']",
        signed: "input:checkbox[name='mypa-signed']",
        pickupType: "input:radio[name='mypa-pickup-option']",
        magentoMethodsContainer: "#checkout-shipping-method-load",
        magentoMethods: ".sp-methods",
        magentoMethodMyParcel: "input:radio[id^='s_method_myparcel']",
        billingPostalCode: "input[id='billing:postcode']",
        billingStreet1: "input[id='billing:street1']",
        billingStreet2: "input[id='billing:street2']",
        billingCountry: "select[id='billing:country_id']",
        postalCode: "input[id='billing:postcode']",
        street1: "input[id='shipping:street1']",
        street2: "input[id='shipping:street2']",
        country: "select[id='shipping:country_id']"
    }, window.mypa.observer);

    $ = jQuery.noConflict();

    $.extend(window.mypa.settings, {
        postal_code: '2231JE',
        number:55,
        //base_url: 'https://api.myparcel.nl/delivery_options'
        base_url: 'https://ui.staging.myparcel.nl/api/delivery_options'
    });

    window.mypa.fn.load = load = function () {
        $(document).ready(
            function () {
                updateCountry();
                var ajaxOptions = {
                    url: BASE_URL + 'myparcel2014/checkout/info/',
                    success: function (response) {
                        info = response;
                        $('#mypa-load').before(info.container);
                        $('#mypa-slider').hide();
                        actionObservers();
                        $(observer.magentoMethodMyParcel)[0].checked = false;
                    }
                };
                $.ajax(ajaxOptions);
            }
        );

    };

    actionObservers = function () {
        /**
         * If address is change
         */
        $([
            observer.billingPostalCode,
            observer.billingStreet1,
            observer.billingStreet2,
            observer.billingCountry,
            observer.postalCode,
            observer.street1,
            observer.street2,
            observer.country
        ].join()).off('change').on('change', function () {
            actionObservers();
        });

        updateCountry();
        if($(observer.billingCountry).val() == 'NL') {
            getData();

            if (streetParts !== null) {

                window.mypa.settings = $.extend(window.mypa.settings, {
                    postal_code: $(observer.postalCode).val(),
                    street: streetParts[1],
                    number: streetParts[3],
                    cutoff_time: data.general['cutoff_time'],
                    dropoff_days: data.general['dropoff_days'],
                    dropoff_delay: data.general['dropoff_delay'],
                    deliverydays_window: data.general['deliverydays_window'],
                    exclude_delivery_type: excludeDeliveryTypes.length > 0 ? excludeDeliveryTypes.join(';') : null,
                    price: price,
                    hvo_title: data.delivery.signature_title,
                    only_recipient_title: data.delivery.only_recipient_title
                });

                $.when(
                    window.mypa.fn.updatePage()
                ).done(function () {

                    $('#mypa-slider').show();
                    $('#mypa-note').hide();

                    /**
                     * If method is MyParcel
                     */
                    $('#mypa-delivery-options-container').off('click').on('click', function () {
                        if (typeof $(observer.deliveryTime + ':checked')[0] !== 'undefined') {
                            $(observer.magentoMethodMyParcel)[0].checked = true;
                        }
                    });

                    /**
                     * If method not is MyParcel
                     */
                    $(observer.magentoMethods).off('click').on('click', function () {
                        $(observer.deliveryType + ':checked')[0].checked = false;
                        $(observer.deliveryTime + ':checked')[0].checked = false;
                    });

                    /**
                     * If the options changed, reload for IWD checkout
                     */
                    $([
                        observer.onlyRecipient,
                        observer.signed
                    ].join()).off('change').on('change', function () {
                        if (typeof  window.mypa.fn.fnCheckout != 'undefined') {
                            window.mypa.fn.fnCheckout.saveShippingMethod();
                        }
                    });

                    /**
                     * If deliveryType change, do not use ajax. Reload only after an option is chosen
                     */
                    $([
                        observer.deliveryDate,
                        observer.deliveryType,
                        observer.deliveryTime
                    ].join()).off('change').on('change', function () {
                        if (typeof  window.mypa.fn.fnCheckout != 'undefined') {
                            setTimeout(
                                window.mypa.fn.fnCheckout.hideLoader
                                , 200);
                            setTimeout(
                                window.mypa.fn.fnCheckout.hideLoader
                                , 400);
                            setTimeout(
                                window.mypa.fn.fnCheckout.hideLoader
                                , 600);
                            setTimeout(
                                window.mypa.fn.fnCheckout.hideLoader
                                , 1000);

                        }
                    });

                });
            } else {
                console.log('Adres niet gevonden (API request mislukt).')
            }

        }
    };


    getData = function () {

        objRegExp = /(.*?)\s?(([\d]+)-?([a-zA-Z/\s]{0,5}$|[0-9/]{0,4}$))$/;
        fullStreet = $(observer.billingStreet1).val();
        if (typeof $(observer.billingStreet2).val() != 'undefined' && $(observer.billingStreet2).val() != '') {
            fullStreet += ' ' + $(observer.billingStreet2).val()
        }
        streetParts = fullStreet.match(objRegExp);

        if(streetParts === null) {
            $('#mypa-note').html('Vul uw adresgegevens in.')
        }

        data = info.data;

        price = [];

        price['default'] = '&#8364; ' + data.general['base_price'].toFixed(2).replace(".", ",");

        if (data.morningDelivery['fee'] != 0) {
            price['morning'] = '&#8364; ' + data.morningDelivery['fee'].toFixed(2).replace(".", ",");
        }

        if (data.eveningDelivery['fee'] != 0) {
            price['night'] = '&#8364; ' + data.eveningDelivery['fee'].toFixed(2).replace(".", ",");
        }

        if (data.pickup['fee'] != 0) {
            price['pickup'] = '&#8364; ' + data.pickup['fee'].toFixed(2).replace(".", ",");
        }

        if (data.pickupExpress['fee'] != 0) {
            price['pickup_express'] = '&#8364; ' + data.pickupExpress['fee'].toFixed(2).replace(".", ",");
        }

        if (data.delivery['only_recipient_fee'] != 0) {
            price['only_recipient'] = '+ &#8364; ' + data.delivery['only_recipient_fee'].toFixed(2).replace(".", ",");
        }

        if (data.delivery['signature_fee'] != 0) {
            price['signed'] = '+ &#8364; ' + data.delivery['signature_fee'].toFixed(2).replace(".", ",");
        }

        /**
         * Exclude delivery types
         */
        excludeDeliveryTypes = [];

        if(data.morningDelivery['active'] == false) {
            excludeDeliveryTypes.push('1');
        }
        if(data.eveningDelivery['active'] == false) {
            excludeDeliveryTypes.push('3');
        }
        if(data.pickup['active'] == false) {
            excludeDeliveryTypes.push('4');
        }
        if(data.pickupExpress['active'] == false) {
            excludeDeliveryTypes.push('5');
        }
    };

    updateCountry = function () {
        var country = $(observer.billingCountry).val();
        if (country == 'NL') {
            $('#mypa-delivery-options-container').show();
            $(observer.magentoMethodMyParcel).closest( "dd").hide().addClass('mypa-hidden').prev().hide().addClass('mypa-hidden');
        } else {
            $('#mypa-delivery-options-container').hide();
            $(observer.magentoMethodMyParcel).closest( "dd").show().removeClass('mypa-hidden').prev().show().removeClass('mypa-hidden');
        }
    }

        if (houseNrFieldUsed) {
            houseNrValue = houseNrField.getValue();
        } else {
            var streetAndHouseNrFieldValue = streetAndHouseNrField.getValue();
            var splitAddress = regex.exec(streetAndHouseNrFieldValue);
            if(splitAddress) {
                houseNrValue = splitAddress[2];
            }
        }

}).call(this);

        /**
         * If the fields were found, get their values.
         */
        if (postcodeField) {
            values['postcode'] = postcodeField.getValue();
        }

            values['housenr'] = houseNrValue;

        return values;
    },

    /**
     * Set the postcode and house number values for the search fields in the overlay.
     *
     * @returns {MyParcelCheckout}
     * @private
     */
    _setSearchFieldValues : function() {
        var values = this._getSearchFieldValues();

        var postcodeField = this._overlay.select('#' + this._templateParams.overlay.postcode_field_id)[0];
        postcodeField.setValue(values['postcode']);

        var houseNrField = this._overlay.select('#' + this._templateParams.overlay.housenr_field_id)[0];
        houseNrField.setValue(values['housenr']);

        return this;
    },

    /**
     * Request available locations from the MyParcel API for the entered postcode and house number values.
     *
     * @returns {*}
     * @private
     */
    _searchForLocations : function() {
        /**
         * Show the loader spinner and remove any available locations found previously.
         */
        this.showLocationLoader();
        this.removeLocations();

        /**
         * Reset the currently selected location.
         *
         * @type {boolean}
         * @private
         */
        this._selectedLocation = false;
        if ($(this._templateParams.selected_location.selected_location_id)) {
            $(this._templateParams.selected_location.selected_location_id).remove();
        }

        /**
         * Get the entered postcode and house number values.
         */
        var postcodeField = this._overlay.select('#' + this._templateParams.overlay.postcode_field_id)[0];
        var postcodeValue = postcodeField.getValue();

        var houseNrField = this._overlay.select('#' + this._templateParams.overlay.housenr_field_id)[0];
        var houseNrValue = houseNrField.getValue();

        if (!houseNrValue || !postcodeValue) {
            this.showLocationsError();
            this.hideLocationLoader();
            return false;
        }

        /**
         * Send an AJAX request to the MyParcel API.
         */
        new Ajax.Request(this._config.get_locations_url, {
            method : 'post',
            parameters : {
                postcode : postcodeValue,
                housenr  : houseNrValue,
                isAjax   : true
            },
            onSuccess : this.renderLocations.bind(this),
            onFailure : this.showLocationsError.bind(this)
        });

        return this;
    },

    /**
     * This method is triggered every time the DOm structure of the shipping methods container is updated.
     *
     * @returns {MyParcelCheckout}
     */
    shippingMethodsLoad : function() {
        var shippingMethod = $$(this._selectors.shipping_method);

        /**
         * Observe the shipping method causing the observer from the _observers object. First stop observing it to
         * prevent double observers.
         */
        shippingMethod.invoke('stopObserving', 'click', this._observers.shipping_method);
        shippingMethod.invoke('observe', 'click', this._observers.shipping_method);

        /**
         * Hide the overlay window if it was somehow still open.
         */
        if (this._overlay) {
            this._overlay.up().hide();
        }

        /**
         * Remove any previously found locations.
         *
         * @type {{}}
         * @private
         */
        this._locations = {};

        /**
         * If the PakjeGemak shipping method was selected, deselect it. The shipping method may only be selected when a
         * location has been chosen.
         */
        if ($$(this._selectors.shipping_method)[0] && $$(this._selectors.shipping_method)[0].checked) {
            $$(this._selectors.shipping_method)[0].checked = false;
        }

        this._config.shipping_methods_load_callback();
        return this;
    },

    /**
     * Open the overlay window.
     *
     * @returns {MyParcelCheckout}
     */
    shippingMethodOnClick : function() {
        this.openOverlay();

        return this;
    },

    /**
     * Search for locations based on the entered postcode and house number.
     *
     * @todo trigger form validation for the postcode and house number fields.
     *
     * @param event
     * @returns {MyParcelCheckout}
     */
    searchFieldOnClick : function(event) {
        event.stop();

        this._searchForLocations();

        return this;
    },

    /**
     * Search for locations based on the entered postcode and house number.
     *
     * @todo trigger form validation for the postcode and house number fields.
     *
     * @param event
     * @returns {MyParcelCheckout}
     */
    searchFieldOnKeypress : function(event) {
        if(event.keyCode == Event.KEY_RETURN || event.which == Event.KEY_RETURN)
        {
            event.stop();
            this._searchForLocations();
        }
        return this;
    },

    /**
     * Close the overlay window when clicked outside the main window.
     *
     * @returns {MyParcelCheckout}
     */
    overlayOnClick : function() {
        this.closeOverlay();

        return this;
    },

    /**
     * Open the overlay window.
     *
     * @returns {MyParcelCheckout}
     */
    openOverlay : function() {
        /**
         * Get the stored overlay element.
         *
         * @type {boolean|HTMLElement}
         */
        var overlay = this._overlay;
        /**
         * If no overlay was stored, create the overlay window.
         */
        if (!overlay) {
            /**
             * Create the overlay by evaluating it's template.
             *
             * @type {string}
             */
            overlay = this._templates.overlay.evaluate(this._templateParams.overlay);

            /**
             * Add the overlay to the overlay container.
             */
            $$(this._selectors.overlay_container)[0].insert(overlay);

            /**
             * Get the overlay as an Element, rather than pure HTML.
             *
             * @type {HTMLElement}
             */
            overlay = $(this._templateParams.overlay.overlay_id);

            /**
             * Store the overlay.
             *
             * @type {HTMLElement}
             * @private
             */
            this._overlay = overlay;

            /**
             * Initialize the overlay observers.
             */
            this._initOverlayObservers();
        }

        /**
         * Set the default values for the postcode and house number fields.
         */
        this._setSearchFieldValues();

        /**
         * Do an immediate search for locations if no locations are stored.
         */
        if (isEmptyObj(this._locations)) {
            this._searchForLocations();
        }

        /**
         * Show the overlay window.
         */
        $$('body')[0].addClassName('overlay_open');
        overlay.up().show();

        return this;
    },

    /**
     * Close the overlay window.
     *
     * @returns {MyParcelCheckout}
     */
    closeOverlay : function() {
        $$('body')[0].removeClassName('overlay_open');
        this._overlay.up().hide();

        /**
         * If no location was selected, deselect the shipping method.
         */
        if (!this._selectedLocation) {
            $$(this._selectors.shipping_method)[0].checked = false;
        }

        return this;
    },

    /**
     * Show the locations loader spinner.
     *
     * @returns {MyParcelCheckout}
     */
    showLocationLoader : function() {
        $(this._templateParams.overlay.location_loader_id).show();

        return this;
    },

    /**
     * Hide the locations loader spinner.
     *
     * @returns {MyParcelCheckout}
     */
    hideLocationLoader : function() {
        $(this._templateParams.overlay.location_loader_id).hide();

        return this;
    },

    /**
     * Render the found locations based on the location template.
     *
     * @param response
     * @returns {MyParcelCheckout}
     */
    renderLocations : function(response) {
        /**
         * Get the found locations from the AJAX response and validate that it's valid JSON.
         * @type {string}
         */
        var json = response.responseText;
        try {
            var result = json.evalJSON(true);
            if (!result.data) {
                this.showLocationsError();
                this.hideLocationLoader();

                return this;
            }
        } catch (e) {
            /**
             * If the result was not valid, show the 'no locations found'-error.
             */
            this.showLocationsError();
            this.hideLocationLoader();

            return this;
        }

        var locationContainer = $(this._templateParams.overlay.location_list_id);
        var newLocationsHtml = '';
        var newLocations = {};

        /**
         * Build the HTML structure for each location found.
         */
        result.data.each(function(location) {
            /**
             * Get the location data and opening hours.
             */
            var locationData = location;
            var openingHours = locationData.opening_hours;

            /**
             * Restructure the opening hours so the template can parse it.
             * @type {string}
             */
            locationData.opening_hours_monday = openingHours.monday.join(', ');
            locationData.opening_hours_tuesday = openingHours.tuesday.join(', ');
            locationData.opening_hours_wednesday = openingHours.wednesday.join(', ');
            locationData.opening_hours_thursday = openingHours.thursday.join(', ');
            locationData.opening_hours_friday = openingHours.friday.join(', ');
            locationData.opening_hours_saturday = openingHours.saturday.join(', ');
            locationData.opening_hours_sunday = openingHours.sunday.join(', ');

            /**
             * Add some additional data to the template parameters.
             */
            var locationTemplateParams = this._templateParams.location;
            locationTemplateParams.location_id = this._templateParams.location.location_id_prefix
                                               + location.location_code;
            locationTemplateParams.location_info_id = this._templateParams.location.location_info_id_prefix
                                                    + location.location_code;
            locationTemplateParams.select_location_onclick = this._templateParams.location.select_location_onclick_class
                                                           + '.selectLocation(this);';

            /**
             * Merge the template parameters with the location data.
             */
            locationData = Object.extend(locationData, locationTemplateParams);

            /**
             * Add the location to the stored locations object and render it's HTML.
             */
            newLocations[location.location_code] = location;
            newLocationsHtml += this._templates.location.evaluate(locationData);
        }.bind(this));

        /**
         * Add the HTML of all found locations to the locations container element.
         */
        locationContainer.update(newLocationsHtml);

        /**
         * Store the found locations.
         * @type {{}}
         * @private
         */
        this._locations = newLocations;

        /**
         * Hide the locations loader spinner.
         */
        this.hideLocationLoader();

        return this;
    },

    /**
     * Remove all previously found locations.
     *
     * @returns {MyParcelCheckout}
     */
    removeLocations : function() {
        $(this._templateParams.overlay.location_list_id).update('');

        return this;
    },

    /**
     * Show the locations error message.
     *
     * @returns {MyParcelCheckout}
     */
    showLocationsError : function() {
        var locationContainer = $(this._templateParams.overlay.location_list_id);

        locationContainer.update(this._templates.location_error.evaluate({}));

        return this;
    },

    /**
     * Select a location.
     *
     * @param {HTMLElement} element
     * @returns {MyParcelCheckout}
     */
    selectLocation : function(element) {
        /**
         * Get the selected location code.
         */
        var locationContainer = element.up('.' + this._templateParams.location.location_class);
        var locationCode = locationContainer.getAttribute('data-location_code');

        /**
         * Get the location object.
         */
        this._selectedLocation = this._locations[locationCode];

        /**
         * Render the selected location for future reference for the user.
         */
        this.renderSelectedLocation();

        /**
         * Close the overlay window.
         */
        this.closeOverlay();

        this._config.select_location_callback();

        if (this._config.save_location_on_select) {
            this.saveSelectedLocation();
        }

        return this;
    },

    /**
     * Render the selected location based on it's template.
     *
     * @returns {*}
     */
    renderSelectedLocation : function() {
        /**
         * Get the selected location.
         * @type {boolean|{}}
         */
        var selectedLocation = this._selectedLocation;
        if (!selectedLocation) {
            return false;
        }

        /**
         * Remove the previously selected location.
         */
        if ($(this._templateParams.selected_location.selected_location_id)) {
            $(this._templateParams.selected_location.selected_location_id).remove();
        }

        /**
         * Parse the selected location template.
         */
        var selectedLocationTemplate = this._templates.selected_location;
        var selectedLocationContainer = $$(this._selectors.shipping_method)[0].up();
        var templateParams = Object.extend(
            selectedLocation,
            this._templateParams.selected_location
        );

        /**
         * Add the selected location at the bottom of the shipping method's parent container.
         */
        selectedLocationContainer.insert({
            bottom : selectedLocationTemplate.evaluate(templateParams)
        });

        return this;
    },

    /**
     * Save the selected location.
     *
     * @returns {boolean}
     */
    saveSelectedLocation : function() {
        /**
         * Get the selected location.
         * @type {boolean|{}}
         */
        var selectedLocation = this._selectedLocation;
        if (!selectedLocation) {
            return true;
        }

        /**
         * Form an object containing the location's address.
         * @type {{city: *, name: *, telephone: *, postcode: *, street: *, housenr: *}}
         */
        var address = {
            city      : selectedLocation.city,
            name      : selectedLocation.location,
            telephone : selectedLocation.phone_number,
            postcode  : selectedLocation.postcode,
            street    : selectedLocation.street,
            housenr   : selectedLocation.street_number
        };
        /**
         * JSON-encode the address for AJAX.
         */
        address = Object.toJSON(address);

        /**
         * Send an AJAX request to save the address.
         */
        new Ajax.Request(this._config.save_location_url, {
            method : 'post',
            parameters : {
                address : address,
                isAjax  : true
            },
            onSuccess : this._config.save_location_success_callback,
            onFailure : this._config.save_location_error_callback
        });

        return true;
    }
};