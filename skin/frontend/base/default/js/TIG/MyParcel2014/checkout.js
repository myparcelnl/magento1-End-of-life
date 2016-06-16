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
        deliveryDate:           "input:radio[name='mypa-date']",
        deliveryType:           "input:radio[name='mypa-delivery-type']",
        deliveryTime:           "input:radio[name='mypa-delivery-time']",
        directReturn:           "input:checkbox[name='mypa-onoffswitch']",
        pickupType:             "input:radio[name='mypa-pickup-option']",
        magentoMethods:         "input:radio[id^='s_method']",
        magentoMethodMyParcel:  "input:radio[id^='s_method_myparcel']",
        postalCode:             "input[id='shipping:postcode']",
        street1:                 "input[id='billing:street1']",
        street2:                 "input[id='billing:street2']"
    };

    $.extend( window.mypa.settings, {
        postal_code: 'holder',
        number: 0,
        //base_url: 'https://api.myparcel.nl/delivery_options'
        base_url: 'https://ui.staging.myparcel.nl/api/delivery_options'
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
                url: 'http://127.0.0.1/magento/index.php/myparcel2014/checkout/getInfo/',
                success: function (response) {
                    console.log(response);
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

        /**
         * If method is MyParcel
         */
            // Start update postcode
            var fullStreet, objRegExp, streetParts;
            objRegExp = /(.*?)\s?(([\d]+)-?([a-zA-Z/\s]{0,5}$|[0-9/]{0,4}$))$/;
            fullStreet = $(observer.street1).val();
            if (typeof $(observer.street2).val() != 'undefined' && $(observer.street2).val() != ''){
                fullStreet += ' ' + $(observer.street2).val()
            }
            streetParts = fullStreet.match(objRegExp);

            var data = info.data;
            var price;
                price['pickup'] = data.pickup['fee'];
                price['pickup_express'] = data.pickupExpress['fee'];
            $.extend( window.mypa.settings, {
                postal_code: $(observer.postalCode).val(),
                street: streetParts[1],
                number: streetParts[2],

                // delivery_time: data.,
                // delivery_date: data.,
                cutoff_time: data.general.cutoffTime,
                dropoff_days: data.general.dropOffDays,
                dropoff_delay: data.general.dropOffDelay,
                deliverydays_window: data.deliverydaysWindow,
                // exlude_delivery_type: data.,

                price: price
            });

            window.mypa.fn.updatePage();
        // End update postcode

        $(observer.magentoMethodMyParcel)[0].checked = true;

        /**
         * If method is MyParcel
         */
        $([
            observer.deliveryType,
            observer.pickupType
        ].join()).on('change', function () {
            $(observer.magentoMethodMyParcel)[0].checked = true;
        });

        /**
         * If method not is MyParcel
         */
        $(observer.magentoMethods).on('change', function () {
            console.log('n mp');
            $(observer.deliveryType + ':checked')[0].checked = false;
        });

        /**
         * If zip isset
         */
        $(observer.magentoMethods).on('change', function () {
            console.log('n mp');
            $(observer.deliveryType + ':checked')[0].checked = false;
        });
    };


}).call(this);
