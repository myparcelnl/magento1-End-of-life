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
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright   Copyright (c) 2015 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
if (typeof isEmptyObj == 'undefined') {
    function isEmptyObj(obj) {
        var keys = Object.keys(obj).length;

        return keys === 0;
    }
}

var MyParcelCheckout = Class.create();
MyParcelCheckout.prototype = {
    /**
     * Core config options.
     */
    _config    : {
        get_locations_url                : '',
        save_location_url                : '',
        use_shipping_or_billing_checkbox : false,
        save_location_on_select          : false,
        shipping_methods_load_callback   : function() {},
        select_location_callback         : function() {},
        save_location_success_callback   : function() {},
        save_location_error_callback     : function() {}
    },

    /**
     * Selectors for elements used by MyParcel checkout.
     * Modifying these should allow compatibility with other checkout extensions or themes.
     */
    _selectors : {
        overlay_container          : '#myparcel_checkout_overlay_container',
        shipping_methods_container : '#checkout-shipping-method-load',
        shipping_method            : '#s_method_tig_myparcel_pakjegemak',
        shipping_street_field      : '#shipping\\:street1',
        shipping_housenr_field     : '#shipping\\:street2',
        shipping_postcode_field    : '#shipping\\:postcode',
        billing_street_field      : '#billing\\:street1',
        billing_housenr_field      : '#billing\\:street2',
        billing_postcode_field     : '#billing\\:postcode',
        billing_or_shipping_field  : ''
    },

    /**
     * Templates. Should be an object containing Prototype Template objects.
     */
    _templates : {},

    /**
     * Parameters to be used with the templates. These will be supplemented dynamically.
     */
    _templateParams : {
        overlay : {
            overlay_id         : 'myparcel_checkout_overlay',
            postcode_field_id  : 'myparcel_postcode_field',
            housenr_field_id   : 'myparcel_housenr_field',
            search_button_id   : 'myparcel_search_button',
            overlay_window_id  : 'overlay_window',
            location_loader_id : 'locations_loader',
            location_list_id   : 'location_list'
        },
        location : {
            location_class                : 'location',
            location_id_prefix            : 'location_',
            location_info_id_prefix       : 'location_info_',
            select_location_onclick_class : 'myParcelCheckout'
        },
        selected_location : {
            selected_location_id : 'myparcel_checkout_selected_location'
        }
    },

    /**
     * Locations retrieved through the MyParcel API.
     */
    _locations : {},

    /**
     * Selected location.
     */
    _selectedLocation : false,

    /**
     * Registered observers.
     */
    _observers : {},

    /**
     * The overlay window.
     */
    _overlay : false,

    /**
     * Constructor method.
     *
     * @param config
     * @param selectors
     * @param templates
     * @param templateParams
     */
    initialize : function(config, selectors, templates, templateParams) {
        /**
         * Set configuration options.
         */
        this._config         = Object.extend(this._config, config || {});
        this._selectors      = Object.extend(this._selectors, selectors || {});
        this._templates      = Object.extend(this._templates, templates || {});
        this._templateParams = Object.extend(this._templateParams, templateParams || {});

        /**
         * Verify that all required templates are present.
         */
        this._verifyTemplates();

        /**
         * Register and start the core observers.
         */
        this._registerObservers();
        this._initObservers(this._selectors);
    },

    /**
     * Verifies that all required templates are defined.
     *
     * @returns {MyParcelCheckout}
     * @private
     */
    _verifyTemplates : function() {
        var templates = this._templates;
        if (!templates.overlay || !templates.location || !templates.location_error || !templates.selected_location) {
            throw 'Error: missing templates.';
        }

        return this;
    },

    /**
     * Registers observers for the core MyParcel checkout functionality.
     *
     * @private
     */
    _registerObservers : function() {
        this._observers['shipping_methods_load'] = this.shippingMethodsLoad.bindAsEventListener(this);
        this._observers['shipping_method'] = this.shippingMethodOnClick.bindAsEventListener(this);
        this._observers['search_field'] = this.searchFieldOnClick.bindAsEventListener(this);
        this._observers['overlay_hide'] = this.overlayOnClick.bindAsEventListener(this);
        this._observers['housenr_keypress'] = this.searchFieldOnKeypress.bindAsEventListener(this);
    },

    /**
     * Initialize the core observers.
     *
     * @param selectors
     * @returns {MyParcelCheckout}
     * @private
     */
    _initObservers : function(selectors) {
        /**
         * This observer observes changes to the dom structure of the shipping methods container.
         */
        var shippingMethodContainer = $$(selectors.shipping_methods_container)[0];
        var observer = new MutationObserver(this._observers.shipping_methods_load);
        var config = {
            attributes            : true,
            childList             : true,
            characterData         : true,
            subTree               : false,
            attributeOldValue     : false,
            characterDataOldValue : false,
            attributeFilter       : []
        };

        observer.observe(shippingMethodContainer, config);

        /**
         * Observe the shipping method.
         */
        var shippingMethod = $$(selectors.shipping_method);
        shippingMethod.invoke('observe', 'click', this._observers.shipping_method);

        return this;
    },

    /**
     * Initialize observers for the overlay window.
     *
     * @returns {MyParcelCheckout}
     * @private
     */
    _initOverlayObservers : function() {
        var overlay = this._overlay;
        var templateParams = this._templateParams.overlay;

        var searchButton = overlay.select('#' + templateParams.search_button_id)[0];
        searchButton.stopObserving('click', this._observers.search_field);
        searchButton.observe('click', this._observers.search_field);

        var housenrField = overlay.select('#' + templateParams.housenr_field_id)[0];
        housenrField.stopObserving('keypress', this._observers.housenr_keypress);
        housenrField.observe('keypress', this._observers.housenr_keypress);

        overlay.select('.close').invoke('stopObserving', 'click', this._observers.overlay_hide);
        overlay.select('.close').invoke('observe', 'click', this._observers.overlay_hide);

        return this;
    },

    /**
     * Get the postcode and house number values as entered in the previous steps.
     *
     * @returns {{postcode: null, housenr: null}}
     * @private
     */
    _getSearchFieldValues : function() {
        var postcodeField;
        var houseNrField;
        var houseNrValue;
        var regex = /(.*?)\s+(\d+\s[a-zA-Z]?|\d+[a-zA-Z]{0,1}\s{0,1}[-]{1}\s{0,1}\d*[a-zA-Z]{0,1}|\d+[a-zA-Z-]{0,1}\d*[a-zA-Z]{0,1})/;
        var houseNrFieldUsed = false;
        var streetAndHouseNrField;

        /**
         * By default no postcode and house number are entered.
         *
         * @type {{postcode: null, housenr: null}}
         */
        var values = {
            postcode : null,
            housenr  : null
        };


        /**
         * This checks if the house number field is used.
         * If this is not the case, the house number needs to be extracted from the street address line later on.
         */
        houseNrField  = $$(this._selectors.billing_housenr_field)[0];
        if(houseNrField){
            if(houseNrField.getValue()) {
                houseNrFieldUsed = true;
            }
        }


        /**
         * If the current checkout page uses a checkbox to switch between billing and shipping addresses, this option
         * will allow that.
         */
        if (this._config.use_shipping_or_billing_checkbox) {
            var useBilling = $$(this._selectors.billing_or_shipping_field)[0].getValue();
            if (useBilling) {
                postcodeField = $$(this._selectors.billing_postcode_field)[0];
                if (houseNrFieldUsed) {
                    houseNrField = $$(this._selectors.billing_housenr_field)[0];
                } else {
                    streetAndHouseNrField = $$(this._selectors.billing_street_field)[0]
                }
            } else {
                postcodeField = $$(this._selectors.shipping_postcode_field)[0];
                if (houseNrFieldUsed) {
                    houseNrField = $$(this._selectors.shipping_housenr_field)[0];
                } else {
                    streetAndHouseNrField = $$(this._selectors.shipping_street_field)[0]
                }
            }
        } else {
            postcodeField = $$(this._selectors.shipping_postcode_field)[0];
            if (houseNrFieldUsed) {
                houseNrField = $$(this._selectors.shipping_housenr_field)[0];
            } else {
                streetAndHouseNrField = $$(this._selectors.shipping_street_field)[0]
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