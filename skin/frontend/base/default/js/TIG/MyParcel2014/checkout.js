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
    var $, options, myParcelObserver, load, actionObservers;

    $ = jQuery.noConflict();

    var observer = {
        deliveryDate:           "input:radio[name='mypa-date']",
        deliveryType:           "input:radio[name='mypa-delivery-type']",
        deliveryTime:           "input:radio[name='mypa-delivery-time']",
        directReturn:           "input:checkbox[name='mypa-onoffswitch']",
        pickupType:             "input:radio[name='mypa-pickup-option']",
        magentoMethods:         "input:radio[id^='s_method']",
        magentoMethodMyParcel:  "input:radio[id^='s_method_myparcel']"
    };

    $.extend( window.mypa.settings, {
        postal_code: '2223HH',
        number: 4,
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

    /**
     * start observing
     */
    myParcelObserver.observe(document, {
        childList: true,
        subtree: true
    });

    load = function () {
        /**
         * Load MyParcel html frame
         */
        //$(observer.magentoMethodMyParcel).parents(':eq(2)').hide();
        $('#checkout-shipping-method-load').before($('#mypa-delivery-options-container'));

        actionObservers();
    };

    actionObservers = function () {

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
    };


}).call(this);
