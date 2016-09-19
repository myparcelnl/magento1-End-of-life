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
if(window.mypa == null || window.mypa == undefined){
    window.mypa = {};
}
if(window.mypa.observer == null || window.mypa.observer == undefined){
    window.mypa.observer = {};
}
if(window.mypa.fn == null || window.mypa.fn == undefined){
    window.mypa.fn = {};
}
window.mypa.settings = {};
(function () {
    var $, load, info, objRegExp, price, data, excludeDeliveryTypes, getData, observer;

    $ = jQuery.noConflict();

    observer = $.extend({
        subItem: "label.mypa-row-subitem",
        deliveryDate: "input:radio[class='mypa-date']",
        deliveryType: "input:radio[name='mypa-delivery-type']",
        deliveryTime: "input:radio[name='mypa-delivery-time']",
        onlyRecipient: "input:checkbox[name='mypa-only-recipient']",
        signed: "input:checkbox[name='mypa-signed']",
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

    window.mypa.settings.base_url = 'https://api.myparcel.nl/delivery_options';

    window.mypa.fn.load = load = function () {
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
            load();
        });

        var ajaxOptions = {
            url: BASE_URL + 'myparcel2014/checkout/info/',
            success: function (response) {

                info = response;

                var address = info.data['address'];
                if (address && address['country'] == 'NL' && typeof $(observer.magentoMethodMyParcel)[0] != '') {
                    $(observer.magentoMethodMyParcel)[0].checked = true;
                    getData();


                    if (address['street']) {
                        window.mypa.settings = $.extend(window.mypa.settings, {
                            postal_code: address['postal_code'],
                            street: address['street'],
                            number: address['number'],
                            cutoff_time: data.general['cutoff_time'],
                            dropoff_days: data.general['dropoff_days'],
                            dropoff_delay: data.general['dropoff_delay'],
                            deliverydays_window: data.general['deliverydays_window'],
                            exclude_delivery_type: excludeDeliveryTypes.length > 0 ? excludeDeliveryTypes.join(';') : null,
                            price: price,
                            text: {signed: data.delivery.signature_title, only_recipient: data.delivery.only_recipient_title},
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
                            mypajQuery('#mypa-load').off('click').on('click', function () {
                                if(mypajQuery('#mypa-input').val() != '') {
                                    mypajQuery(observer.magentoMethodMyParcel)[0].checked = true;
                                }
                            });

                            /**
                             * If method not is MyParcel
                             */
                            $('.sp-methods').off('click').on('click', function () {
                                mypajQuery('#mypa-input').val(null).change();
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
            }
        };
        $.ajax(ajaxOptions);

    };


    getData = function () {

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

        if (data.delivery['only_recipient_active'] == false) {
            price['only_recipient'] = 'disabled';
        } else if (data.delivery['only_recipient_fee'] !== 0) {
            price['only_recipient'] = '+ &#8364; ' + data.delivery['only_recipient_fee'].toFixed(2).replace(".", ",");
        }

        if (data.delivery['signature_active'] == false) {
            price['signed'] = 'disabled';
        } else if (data.delivery['signature_fee'] !== 0) {
            price['signed'] = '+ &#8364; ' + data.delivery['signature_fee'].toFixed(2).replace(".", ",");
        }

        if (data.delivery['signature_and_only_recipient'] > 0) {
            price['combi_options'] = '+ &#8364; ' + data.delivery['signature_and_only_recipient'].toFixed(2).replace(".", ",");
        }

        /**
         * Exclude delivery types
         */
        excludeDeliveryTypes = [];

        if (data.morningDelivery['active'] == false) {
            excludeDeliveryTypes.push('1');
        }
        if (data.eveningDelivery['active'] == false) {
            excludeDeliveryTypes.push('3');
        }
        if (data.pickup['active'] == false) {
            excludeDeliveryTypes.push('4');
        }
        if (data.pickupExpress['active'] == false) {
            excludeDeliveryTypes.push('5');
        }
    };


})();
