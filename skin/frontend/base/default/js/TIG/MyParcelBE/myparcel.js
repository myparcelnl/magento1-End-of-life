MyParcel = {

    /*
     * Init
     *
     * Initialize the MyParcel checkout.
     *
     * From commit 59767dd26fb 22/03/2018
     */

    init: function()
    {
        /* Simple mobilesque detector */
        isMobile     = true;
        if(mypajQuery( window ).width() > 980 ) {
            isMobile = false;
        }


        /* Prices BPost */
        if(myParcelConfig.carrierCode == 2){
            mypajQuery('#mypa-price-bpost-signature').html(' +€' + myParcelConfig.priceBpostAutograph);
            mypajQuery('#mypa-delivery-bpost-saturday-price').html(' +€' + myParcelConfig.priceBpostSaturdayDelivery);
            if(parseFloat(myParcelConfig.pricePickup) > 0){
                mypajQuery('#mypa-price-pickup').html(' +€' + myParcelConfig.pricePickup);
            }
        }

        /* Prices PostNL */
        else if(myParcelConfig.carrierCode == 1){
            MyParcel.showPostNlPrices();
        }

        /* Call delivery options */
        MyParcel.callDeliveryOptions();

        /* Engage defaults */
        MyParcel.hideBpostSaturday();
        MyParcel.hideDelivery();
        MyParcel.hideBpostSignature();
        mypajQuery('#method-myparcel-flatrate').click();

        // BPost Delivery Options
        if(myParcelConfig.allowBpostAutograph && myParcelConfig.carrierCode == 2){
            MyParcel.showBpostSignature();
        }

        // PostNL delivery Options
        if(myParcelConfig.carrierCode == 1){
            mypajQuery('#mypa-delivery-selectors-be').css('display','none'); /* should be somewhere else */;
            mypajQuery('#mypa-delivery-selectors-be').hide();
            mypajQuery('#mypa-delivery-selectors-be').css('background-color','green'); /* should be somewhere else */;
            mypajQuery('#mypa-bpost-flat-fee-delivery').hide();
            MyParcel.showDelivery();
            MyParcel.showPostNlDeliveryDates();
        }

    },

    /*
     * Bind
     *
     * Bind actions to selectors.
     * 
     */

    bind: function ()
    {
        mypajQuery('#mypa-submit').on('click', function(e)
        {
            e.preventDefault();
            MyParcel.exportDeliveryOptionToWebshop();
        });

        mypajQuery('#mypa-signature-selector').on('change', function(e)
        {
            MyParcel.toggleDeliveryOptions();
        });

        mypajQuery('#mypa-recipient-only-selector').on('change', function()
        {
            MyParcel.toggleDeliveryOptions();
        });

        mypajQuery('#mypa-deliver-pickup-deliver').on('click', function(){
            MyParcel.showDelivery();
        });

        mypajQuery('#mypa-deliver-pickup-deliver-bpost-saturday').on('click', function(){
            MyParcel.showDelivery();
        });

        mypajQuery('#mypa-deliver-pickup-pickup').on('click', function(){
            MyParcel.hideDelivery();
        });

        mypajQuery('#mypa-delivery-date-postnl').on('change', function(){
            MyParcel.showPostNlDeliveryTimes()
        });


        /* Mobile specific triggers */
        if(isMobile){
            mypajQuery('#mypa-show-location-details').on('click', function(){
                MyParcel.showLocationDetails();
            });

            mypajQuery('.mypa-help').on('click', function(e)
            {
                e.preventDefault();
                MyParcel.showHelp(e);
            });
        }

        /* Desktop specific triggers */
        else {
            mypajQuery('#mypa-show-location-details').on('mouseenter', function(){
                MyParcel.showLocationDetails();
            });

            mypajQuery('.mypa-help').on('click', function(e)
            {
                e.preventDefault();
            });

            mypajQuery('.mypa-help').on('mouseenter', function(e)
            {
                MyParcel.showHelp(e);
            });
        }

        mypajQuery('#mypa-location-details').on('click', function(){
            MyParcel.hideLocationDetails();
        });

        mypajQuery('#mypa-pickup-location').on('change', function(){
            mypajQuery('#mypa-deliver-pickup-pickup').click();
        });

        /* External webshop triggers */
        mypajQuery(triggerPostalCode).on('change', function(){
            MyParcel.callDeliveryOptions();
        });

        mypajQuery(triggerHouseNumber).on('change', function(){
            MyParcel.callDeliveryOptions();
        });

        mypajQuery(triggerStreetName).on('change', function(){
            MyParcel.callDeliveryOptions();
        });
    },

    /*
     * toggleDeliveryOptions
     *
     * Shows and hides the display options that are valid for the recipient only and signature required pre-selectors
     *
     */

    toggleDeliveryOptions: function()
    {
        var recipientOnly     = mypajQuery('#mypa-recipient-only-selector').is(':checked');
        var signatureRequired = mypajQuery('#mypa-signature-selector').is(':checked');

        MyParcel.hideAllDeliveryOptions();
        if(recipientOnly && signatureRequired){
            mypajQuery('.method-myparcel-delivery-signature-and-only-recipient-fee-div').show();
            mypajQuery('#method-myparcel-delivery-signature-and-only-recipient-fee').click();
        }

        else if (recipientOnly && !signatureRequired){
            mypajQuery('.method-myparcel-delivery-only-recipient-div').show();
            mypajQuery('#method-myparcel-delivery-only-recipient').click();
        }

        else if (!recipientOnly && signatureRequired){
            mypajQuery('.method-myparcel-delivery-signature-div').show();
            mypajQuery('.method-myparcel-delivery-evening-signature-div').show();
            mypajQuery('.method-myparcel-morning-signature-div').show();
            mypajQuery('#method-myparcel-delivery-signature').click();
        }

        /* No pre selection, show everything. */
        else {
            MyParcel.showAllDeliveryOptions();
            mypajQuery('#method-myparcel-flatrate').click();
        }
    },

    showPostNlPrices: function()
    {
        var priceMap = {
            "pricePostNLFlatrate"              : "mypa-postnl-flatrate-price",
            "pricePostNLSignature"             : "mypa-postnl-signature-price",
            "pricePostNLRecipientOnly"         : "mypa-postnl-recipient-only-price",
            "pricePostNLRecipientOnlySignature": "mypa-postnl-recipient-only-signature-price",
            "pricePostNLEvening"               : "mypa-postnl-evening-price",
            "pricePostNLPickupExpresse"        : "mypa-postnl-pickup-express-price",
            "pricePostNLMorning"               : "mypa-postnl-morning-price",
            "pricePostNLMorningSignature"      : "mypa-postnl-morning-signature-price",

            "pricePostNLEveningSignature"      : "mypa-postnl-evening-signature-price",

            "pricePostNLMorning"		   : "mypa-postnl-morning-price",
            "pricePostNLFlatrate"              : "mypa-postnl-standard-price",
            "pricePostNLEvening"               : "mypa-postnl-avond-price", /* yes, this one is dutch :( */
        };

        mypajQuery.each(priceMap, function(config, cssId){

            var price = 0;

            if(myParcelConfig[config]){
                price = parseFloat(myParcelConfig[config]);
            }

            if(price > 0){
                mypajQuery('.'+cssId).html("+€ " + price);
            }
        });
    },


    showPostNlDeliveryDates: function()
    {
        if(typeof(MyParcel.storeDeliveryOptions) === 'undefined'){
            return;
        }
        var deliveryDates = MyParcel.storeDeliveryOptions.data.delivery;
        var html          = "";
        mypajQuery.each(deliveryDates, function(key, value){
            var date = MyParcel.dateToObject(value.date);

            html += '<option value="' + value.date + '">' + MyParcel.dateToString(value.date) + '</option>';
        });
        mypajQuery('#mypa-delivery-date-postnl').html(html);



    },

    showPostNlDeliveryTimes: function()
    {
        if(typeof(MyParcel.storeDeliveryOptions) === 'undefined'){
            return;
        }
        var selectedDate  = mypajQuery('#mypa-delivery-date-postnl').val();
        var deliveryDates = MyParcel.storeDeliveryOptions.data.delivery;

        var timesForSelectedDates = []
        mypajQuery.each(deliveryDates, function(key, value){
            console.debug(selectedDate + ' ' + value.date);
            if(value.date === selectedDate){
                console.debug('ja');
                timesForSelectedDates = value.time;
                console.debug(timesForSelectedDates);
            }
        });

        var html = '';
        mypajQuery.each(timesForSelectedDates, function(key, value){
            html += '<div class="mypa-delivery-time-div">';
            html += '<input type="radio" id="mypa-delivery-time-postnl-select-' + value.price_comment  + '" name="mypa-delivery-time-postnl" value="';
            html +=  value.price_comment + '">';
            html += '<label class="mypa-delivery-time-postnl-label" for="mypa-delivery-time-postnl-select-' + value.price_comment  + '">';
            html += '<span class="mypa-delivery-time-postnl-comment">' + translateENtoNL[value.price_comment] + '</span>';
            html += '<span class="mypa-delivery-time-postnl-time">(' + value.start.slice(0,-3) + '-' + value.end.slice(0,-3) + ')</span>';
            html += '<span class="mypa-method-price mypa-delivery-time-postnl-price mypa-postnl-' + value.price_comment + '-price"></span>';
            html += '</label>';
            html += '</div>';
        });
        mypajQuery('#mypa-delivery-time-postnl').html(html);
        mypajQuery('#mypa-delivery-selector').css('height','190px');
    },

    /*
     * hideMessage
     *
     * Hides pop-up message.
     * 
     */

    hideMessage: function()
    {
        mypajQuery('#mypa-message').hide();
        mypajQuery('#mypa-message').html(' ');
    },

    /*
     * hideMessage
     *
     * Hides pop-up essage.
     * 
     */

    showMessage: function(message)
    {
        var html = '<div class="mypa-close-message"><span class="fas fa-times-circle"></span></div>' + message;
        mypajQuery('#mypa-message').html(html);
        mypajQuery('#mypa-message').show();
    },

    /*
     * hideDelivery
     *
     * Hides interface part for delivery.
     * 
     */

    hideDelivery: function()
    {
        mypajQuery('#mypa-pre-selectors-nl').hide();
        mypajQuery('#mypa-delivery-selectors-nl').hide();
        mypajQuery('#mypa-delivery-selectors-be').hide();
    },

    /*
     * showDelivery
     *
     * Shows interface part for delivery.
     * 
     */

    showDelivery: function()
    {
        mypajQuery('#mypa-pre-selectors-' +      myParcelConfig.countryCode.toLowerCase()).show();
        mypajQuery('#mypa-delivery-selectors-' + myParcelConfig.countryCode.toLowerCase()).show();

        MyParcel.hideBpostSignature();
        if(myParcelConfig.allowBpostAutograph && myParcelConfig.carrierCode == 2){
            MyParcel.showBpostSignature();
        }
    },

    /*
     * showAllDeliveryOptions
     *
     * Shows all available MyParcel delivery options.
     *
     */

    showAllDeliveryOptions: function()
    {
        mypajQuery('.mypa-delivery-option').show();
    },

    /*
     * hideAllDeliveryOptions
     *
     * Hides all available MyParcel delivery options.
     *
     */

    hideAllDeliveryOptions: function()
    {
        mypajQuery('.mypa-delivery-option').hide();
        mypajQuery('#mypa-delivery-selectors-be').hide();
    },


    /*
     * showSpinner
     *
     * Shows the MyParcel spinner.
     *
     */

    showSpinner: function()
    {
        mypajQuery('#mypa-spinner').show();
    },


    /*
     * hideSpinner
     *
     * Hides the MyParcel spinner.
     *
     */

    hideSpinner: function()
    {
        mypajQuery('#mypa-spinner').hide();
    },

    /*
         * shopwPostnlSignatureAndRecipientOnly
     *
         * Shows the postnl signature and recipient only delivery option.
         *
         */

    showPostNlSignatureAndRecipientOnly: function()
    {
        mypajQuery('#mypa-postnl-signature-recipient-only').show();
    },

    /*
         * hidePostnlSignatureAndRecipientOnly
     *
         * Shows the postnl signature and recipient only delivery option.
         *
         */

    hidePostNlSignatureAndRecipientOnly: function()
    {
        mypajQuery('#mypa-postnl-signature-recipient-only').hide();
    },

    /*
         * showPostNlRecipientOnly
     *
         * Shows the postnl recipient only delivery option.
         *
         */

    showPostNlRecipientOnly: function()
    {
        mypajQuery('#mypa-postnl-recipient-only').show();
    },

    /*
         * hidePostNlRecipientOnly
     *
         * Hide the postnl recipient only delivery option
         *
         */

    hidePostNlRecipientOnly: function()
    {
        mypajQuery('#mypa-postnl-recipient-only').hide();
    },

    /*
         * showPostNlSignature
     *
         * Shows the postnl signature delivery option 
         *
         */

    showPostNlSignature: function()
    {
        mypajQuery('#mypa-postnl-signature').show();
    },

    /*
         * hidePostNlSignature
     *
         * Shows the postnl signature delivery option
         *
         */

    hidePostNlSignature: function()
    {
        mypajQuery('#mypa-postnl-signature').hide();
    },

    /* 
         * showBpostSignature
         *
         * Shows the Bpost signature delivery option
         *
         */

    showBpostSignature: function()
    {
        mypajQuery('#mypa-delivery-selectors-be').show();
    },

    /* 
         * hideBpostSignature
         *
         * Hides the Bpost signature delivery option
         *
         */

    hideBpostSignature: function()
    {
        mypajQuery('#mypa-delivery-selectors-be').hide();
    },

    /*
     * showBpostSaturday
     *
     * Show Bpost saturday delivery for extra fee. 
     *
     */

    showBpostSaturday: function(date)
    {
        if(myParcelConfig.allowBpostSaturdayDelivery) {
            mypajQuery('#mypa-delivery-date-only-bpost-saturday').val(date);
            mypajQuery('#mypa-delivery-date-bpost-saturday').val(date);
            mypajQuery('#mypa-delivery-bpost-saturday-price').html(myParcelConfig.priceBpostSaturdayDelivery);
            mypajQuery('#mypa-bpost-saturday-delivery').show();
        }
    },

    /*
     * hideBpostSaturday
     *
     * Hide Bpost saturday delivery. 
     *
     */

    hideBpostSaturday: function()
    {
        mypajQuery('#mypa-bpost-saturday-delivery').hide();
        mypajQuery('#mypa-delivery-date-bpost-saturday').val(' ');
        mypajQuery('#mypa-delivery-bpost-saturday-price').html(myParcelConfig.priceBpostSaturdayDelivery);
    },

    /*
     * dateToObject
     * 
     * Convert api date string format YYYY-MM-DD to object
     *
     */

    dateToObject: function(apiDate)
    {
        var deliveryDate = apiDate;
        var dateArr      = deliveryDate.split('-');
        return new Date(dateArr[0],dateArr[1]-1,dateArr[2]);
    },

    /*
     * dateToString
     * 
     * Convert api date string format to human readable string format
     *
     */

    dateToString: function(apiDate)
    {
        var deliveryDate = apiDate;
        var dateArr      = deliveryDate.split('-');
        var dateObj      = new Date(dateArr[0],dateArr[1]-1,dateArr[2]);
        var month        = dateObj.getMonth();
        month++;
        return txtWeekDays[dateObj.getDay()] + " " + dateObj.getDate() + "-" + month + "-" + dateObj.getFullYear();
    },

    /*
     * showDeliveryDates
     *
     * Show possible delivery dates.
     * 
     */

    showDeliveryDates: function(deliveryOptions)
    {
        var dateString   = MyParcel.dateToString(deliveryOptions.data.delivery[0].date);
        var dateObj      = MyParcel.dateToObject(deliveryOptions.data.delivery[0].date);

        /* If there is a bPost saturday delivery also present the next option
           that has the standard fee */
        if(dateObj.getDay() == 5 && myParcelConfig.carrierCode == 2){
            MyParcel.showBpostSaturday(dateString);
            if(typeof deliveryOptions.data.delivery[1] !== 'undefined'){
                dateString = dateToString(deliveryOptions.data.delivery[1].date);
            }
        }

        /* All other deliveries */
        mypajQuery('#mypa-delivery-date-bpost').val(dateString);
        mypajQuery('#mypa-delivery-date-only-bpost').val(deliveryOptions.data.delivery[0].date);
    },

    /*
     * clearPickupLocations
     *
     * Clear pickup locations and show a non-value option.
     *
     */

    clearPickUpLocations: function()
    {
        var html = '<option value="">---</option>';
        mypajQuery('#mypa-pickup-location').html(html);
    },


    /*
     * hidePickupLocations
     *
     * Hide the pickup location option.
     *
     */

    hidePickUpLocations: function()
    {
        mypajQuery('#mypa-pickup-location-selector').hide();
        mypajQuery('.mel-style').css('border-bottom', '0');
    },


    /*
     * showPickupLocations
     *
     * Shows possible pickup locations, from closest to most distance.
     *
     */

    showPickUpLocations: function(deliveryOptions)
    {
        var html = "";
        mypajQuery.each(deliveryOptions.data.pickup, function(key, value){
            html += '<option value="' + value.location_code + '">' + value.location + ', ' + value.street +
                ' ' + value.number + ", " + value.city + " (" + value.distance  + " M) </option>\n";
        });
        mypajQuery('#mypa-pickup-location').html(html);
        mypajQuery('#mypa-pickup-location-selector').show();
    },

    /*
     * hideLocationDetails
     *
     * Hide the detailed information pop-up for selected location.
     *
     */

    hideLocationDetails: function()
    {
        mypajQuery('#mypa-location-details').hide();
    },

    /*
     * showLocationDetails
     *
     * Shows the detailed information pop-up for the selected pick-up location.
     */

    showLocationDetails: function()
    {
        var locationId      = mypajQuery('#mypa-pickup-location').val();
        var currentLocation = MyParcel.getPickupByLocationId(MyParcel.storeDeliveryOptions.data.pickup, locationId);
        var startTime       = currentLocation.start_time;

        /* Strip seconds if present */
        if(startTime.length > 5){
            startTime = startTime.slice(0,-3);
        }


        var html = '<div class="mypa-close-message"><span class="fas fa-times-circle"></span></div>';
        html += '<span class="mypa-pickup-location-details-location"><h3>' + currentLocation.location  + '</h3></span>'
        html += '<span class="mypa-pickup-location-details-street">' + currentLocation.street + '&nbsp;' + currentLocation.number + '</span>';
        html += '<span class="mypa-pickup-location-details-city">' + currentLocation.postal_code + '&nbsp;' + currentLocation.city + '</span>';
        if(currentLocation.phone_number){
            html += '<span class="mypa-pickup-location-details-phone">&nbsp;' + currentLocation.phone_number  + '</span>'
        }
        html += '<span class="mypa-pickup-location-details-time">Ophalen vanaf:&nbsp;' + startTime + '</span>'
        html += '<h3>Openingstijden</h3>';
        mypajQuery.each(
            currentLocation.opening_hours, function(weekday, value){
                html += '<span class="mypa-pickup-location-details-day">' + translateENtoNL[weekday] + "</span> ";
                mypajQuery.each(value, function(key2, times){
                    html +=  '<span class="mypa-time">' + times + "</span>";
                });
                html += "<br>";
            });
        mypajQuery('#mypa-location-details').html(html);
        mypajQuery('#mypa-location-details').show();
    },


    /*
         * getPickupByLocationId
         *
         * Find the location by id and return the object.
         *
         */

    getPickupByLocationId: function(obj, locationId)
    {
        var object;

        mypajQuery.each(obj, function(key, info) {
            if (info.location_code === locationId) {
                object = info;
                return false;
            };
        });

        return object;
    },

    /*
     * retryPostalcodeHouseNumber
     *
     * After detecting an unrecognised postcal code / house number combination the user can try again.
     * This function copies the newly entered data back into the webshop forms.
     *
     */

    retryPostalcodeHouseNumber: function()
    {
        mypajQuery(triggerPostalCode).val( mypajQuery('#mypa-error-postcode').val() );
        mypajQuery(triggerHouseNumber).val( mypajQuery('#mypa-error-number').val() );
        MyParcel.hideMessage();
        MyParcel.callDeliveryOptions();
        mypajQuery('#mypa-deliver-pickup-deliver').click();
    },

    /*
     * showFallBackDelivery
     *
     * If the API call fails and we have no data about delivery or pick up options 
     * show the customer an "As soon as possible" option.
     *
     */

    showFallBackDelivery: function()
    {
        MyParcel.hidePickUpLocations();
        mypajQuery('#mypa-delivery-date-bpost').val('Zo snel mogelijk.');
        mypajQuery('#mypa-deliver-pickup-deliver').click();
    },


    /*
     * showRetry
     *
     * If a customer enters an unrecognised postal code housenumber combination show a 
     * pop-up so they can try again.
     *
     */

    showRetry: function()
    {
        var html =
            '<h3>Huisnummer/postcode combinatie onbekend</h3>' +
            '<div class="full-width mypa-error">'+
            '<label for="mypa-error-postcode">Postcode</label>' +
            '<input type="text" name="mypa-error-postcode" id="mypa-error-postcode" value="'+mypajQuery(triggerPostalCode).val() + '">' +
            '</div><div class="full-width mypa-error">' +
            '<label for="mypa-error-number">Huisnummer</label>' +
            '<input type="text" name="mypa-error-number" id="mypa-error-number" value="'+mypajQuery(triggerHouseNumber).val() + '">' +
            '<br><button id="mypa-error-try-again">Opnieuw</button>' +
            '</div>';
        MyParcel.showMessage(html);


        /* remove trigger that closes message */
        mypajQuery('#mypa-message').off('click');

        /* bind trigger to new button */
        mypajQuery('#mypa-error-try-again').on('click', function(){
            MyParcel.retryPostalcodeHouseNumber();
        });
    },


    /*
     * callDeliveryOptions
     *
     * Calls the MyParcel API to retrieve the pickup and delivery options for given house number and
     * Postal Code.
     *
     */

    callDeliveryOptions: function()
    {
        MyParcel.showSpinner();
        MyParcel.clearPickUpLocations();

        var postalCode       = mypajQuery(triggerPostalCode).val();
        var houseNumber      = mypajQuery(triggerHouseNumber).val();
        var houseNumberExtra = mypajQuery(triggerHouseNumberExtra).val();
        var streetName       = mypajQuery(triggerStreetName).val();

        if(houseNumberExtra){
            houseNumber = houseNumber + houseNumberExtra;
        }

        /* Don't call API unless both PC and House Number are set */
        if(!houseNumber || !postalCode) {
            MyParcel.hideSpinner();
            MyParcel.showFallBackDelivery();
            return;
        }

        var data;
        /* add streetName for Belgium */
        mypajQuery.get(myParcelConfig.apiBaseUrl + "delivery_options",
            {
                /* deliverydays_window:    myParcelConfig.deliverydaysWindow, */
                dropoff_days: 		myParcelConfig.dropOffDays,
                cutofff_time: 		myParcelConfig.cutoffTime,
                street:       		streetName,
                carrier:      		myParcelConfig.carrierCode,
                cc:           		myParcelConfig.countryCode,
                number:       		houseNumber,
                postal_code:  		postalCode,
            })
            .done(function(data){
                if(data.errors){
                    mypajQuery.each(data.errors, function(key, value){
                        /* Postalcode housenumber combination not found or not 
                   recognised. */
                        if(value.code == '3212' || value.code == '3505'){
                            MyParcel.showRetry();
                        }

                        /* Any other error */
                        else {
                            MyParcel.showFallBackDelivery();
                        }
                    });
                }

                /* No errors */
                else {
                    MyParcel.showPickUpLocations(data);
                    MyParcel.showDeliveryDates(data);
                    MyParcel.storeDeliveryOptions = data;

                    if(myParcelConfig.carrierCode == '1'){
                        MyParcel.showPostNlDeliveryDates();
                        MyParcel.showPostNlDeliveryTimes();
                        mypajQuery('#mypa-deliver-pickup-deliver').click();
                        mypajQuery('#mypa-delivery-time-postnl-select-standard').click();
                        MyParcel.showPostNlPrices();
                    }

                    if(myParcelConfig.carrierCode == '2'){
                        mypajQuery('#mypa-deliver-pickup-deliver-bpost').click();
                    }

                }
            })
            .fail(function(){
                MyParcel.showFallBackDelivery();
            })
            .always(function(){
                MyParcel.hideSpinner();
                MyParcel.hideMessage();
            });
    },

    /*
     * exportDeliverOptionToWebshop
     *
     * Exports the selected deliveryoption to the webshop.
     *
     */

    exportDeliveryOptionToWebshop: function()
    {
        var deliveryOption = "";
        var selected       = mypajQuery("#mypa-delivery-option-form").find("input[type='radio']:checked");
        if (selected.length > 0) {
            deliveryOption = selected.val();
        }

        /* XXX Send to appropriate webshop field */
    }
}