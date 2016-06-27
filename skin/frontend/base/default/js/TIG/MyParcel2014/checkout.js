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
(function () {
    var $, myParcelObserver, load, actionObservers, info;

    $ = jQuery.noConflict();

    var observer = {
        subItem: "label.mypa-row-subitem",
        deliveryDate: "input:radio[name='mypa-date']",
        deliveryType: "input:radio[name='mypa-delivery-type']",
        deliveryTime: "input:radio[name='mypa-delivery-time']",
        directReturn: "input:checkbox[name='mypa-onoffswitch']",
        pickupType: "input:radio[name='mypa-pickup-option']",
        magentoMethods: "input:radio[id^='s_method']",
        magentoMethodMyParcel: "input:radio[id^='s_method_myparcel']",
        postalCode: "input[id='shipping:postcode']",
        street1: "input[id='shipping:street1']",
        street2: "input[id='shipping:street2']"
    };

    $.extend(window.mypa.settings, {
        postal_code: 'holder',
        number: 0,
        base_url: 'https://api.myparcel.nl/delivery_options'
        //base_url: 'https://ui.staging.myparcel.nl/api/delivery_options'
    });

    /**
     *  Set up the mutation observer
     */
    myParcelObserver = new MutationObserver(function (mutations, me) {
        var canvasFlat = document.getElementById('s_method_myparcel_flatrate');
        var canvasTable = document.getElementById('s_method_myparcel_tablerate');
        if (canvasFlat || canvasTable) {
            $(document).ready(
                load,
                me.disconnect() /* stop observing */
            )
        }
    });

    $(document).ready(
        function () {
            var ajaxOptions = {
                url: BASE_URL + 'myparcel2014/checkout/info/',
                success: function (response) {
                    info = response;

                    /**
                     * start observing
                     */
                    myParcelObserver.observe(document, {
                        childList: true,
                        subtree: true
                    });
                }
            };
            $.ajax(ajaxOptions);
        }
    );

    load = function () {
        $('#checkout-shipping-method-load').before(info.container);
        actionObservers();
    };

    actionObservers = function () {

        var fullStreet, objRegExp, streetParts, price, data, excludeDeliveryTypes;
        /**
         * If method is MyParcel
         */
        // Start update postcode
        objRegExp = /(.*?)\s?(([\d]+)-?([a-zA-Z/\s]{0,5}$|[0-9/]{0,4}$))$/;
        fullStreet = $(observer.street1).val();
        if (typeof $(observer.street2).val() != 'undefined' && $(observer.street2).val() != '') {
            fullStreet += ' ' + $(observer.street2).val()
        }
        streetParts = fullStreet.match(objRegExp);

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

        window.mypa.settings = $.extend(window.mypa.settings, {
            postal_code: $(observer.postalCode).val(),
            street: streetParts[1],
            number: streetParts[2],
            cutoff_time: data.general.cutoffTime,
            dropoff_days: data.general.dropOffDays,
            dropoff_delay: data.general.dropOffDelay,
            deliverydays_window: data.general.deliverydaysWindow,
            exclude_delivery_type: excludeDeliveryTypes.length > 0 ? excludeDeliveryTypes.join(';') : null,
            price: price,
            hvo_title: data.delivery.signature_title,
            only_recipient_title: data.delivery.only_recipient_title
        });

        // End update postcode
        $.when( window.mypa.fn.updatePage() ).done(function() {


            /**
             * If address is change
             */
            $([
                observer.postalCode,
                observer.street1,
                observer.street2
            ].join()).off('change').on('change', function () {
                actionObservers();
            });

            /**
             * If method is MyParcel
             */
            $([
                observer.subItem
            ].join()).off('change').on('click', function () {
                console.log('mp');
                $(observer.magentoMethodMyParcel)[0].checked = true;
            });

            /**
             * If method not is MyParcel
             */
            $(observer.magentoMethods).off('change').on('change', function () {
                console.log('n mp');
                $(observer.deliveryType + ':checked')[0].checked = false;
                $(observer.deliveryTime + ':checked')[0].checked = false;
            });

        });
    };


}).call(this);
