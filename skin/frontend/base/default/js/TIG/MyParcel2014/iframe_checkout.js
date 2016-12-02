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
    var load, info, price, data, excludeDeliveryTypes, getData, updatePageRequest;


    window.mypa.settings.base_url = 'https://api.myparcel.nl/delivery_options';

    window.mypa.fn.load = load = function () {

        var ajaxOptions = {
            url: BASE_URL + 'myparcel2014/checkout/info/',
            success: function (response) {

                info = response;

                var address = info.data['address'];
                if (address && address['country'] == 'NL') {

                    getData();

                    if (address['street']) {
                        window.mypa.settings = externalJQuery.extend(window.mypa.settings, {
                            postal_code: address['postal_code'].replace(/ /g,""),
                            street: address['street'],
                            number: address['number'],
                            cutoff_time: data.general['cutoff_time'],
                            dropoff_days: data.general['dropoff_days'],
                            dropoff_delay: data.general['dropoff_delay'],
                            deliverydays_window: data.general['deliverydays_window'],
                            exclude_delivery_type: excludeDeliveryTypes.length > 0 ? excludeDeliveryTypes.join(';') : null,
                            price: price,
                            text: {
                                signed: data.delivery.signature_title,
                                only_recipient: data.delivery.only_recipient_title
                            }
                        });

                        externalJQuery.when(
                            updatePageRequest()
                        ).done(function () {

                            externalJQuery('#mypa-load').on('change', function () {
                                externalJQuery('#mypa-input').trigger('change');
                            });
                            $('#mypa-mailbox-location').on('change', function () {
                                parent.mypajQuery('#mypa-input').val('{"time":[{"price_comment":"mailbox","type":6}]}').trigger('change');
                                parent.mypajQuery('#mypa-signed').prop('checked', false).trigger('change');
                                parent.mypajQuery('#mypa-recipient-only').prop('checked', false).trigger('change');
                            });
                            parent.iframeDataLoaded();

                        });
                    } else {
                        console.log('Adres niet gevonden (API request mislukt).')
                    }
                }
            }
        };
        externalJQuery.ajax(ajaxOptions);

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

        if (data.delivery['signature_and_only_recipient_fee'] > 0) {
            price['combi_options'] = '+ &#8364; ' + data.delivery['signature_and_only_recipient_fee'].toFixed(2).replace(".", ",");
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

    updatePageRequest = function () {
        if (parent.active > 0) {
            window.setTimeout(updatePageRequest, 100);
        }
        else {
            window.mypa.fn.updatePage();
            parent.iframeLoaded();
        }
    };

})();