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
    var options, myParcelObserver, load, actionObservers, indexStore, saveOptions;

    window.mypaController = {
        observer: {
            options: {
                deliveryDate:   "input:radio[name='mypa-date']",
                deliveryType:   "input:radio[name='mypa-delivery-type']",
                deliveryTime:   "input:radio[name='mypa-delivery-time']",
                directReturn:   "input:checkbox[name='mypa-onoffswitch']",
                pickupType:     "input:radio[name='mypa-pickup-option']"
            }
        },
        store: {
            deliveryDate:   null,
            deliveryType:   null,
            deliveryTime:   null,
            directReturn:   null,
            pickupType:     null,
            pickupAddress:  null
        }
    };

    options = window.mypaController.observer.options;

    /* Set up the mutation observer */
    myParcelObserver = new MutationObserver(function (mutations, me) {
        var canvas = document.getElementById('s_method_myparcel_flatrate');
        if (canvas) {
            jQuery(document).ready(
                load,
                me.disconnect() /* stop observing */
            )
        }
    });

    /* start observing */
    myParcelObserver.observe(document, {
        childList: true,
        subtree: true
    });

    load = function () {

        /* Load MyParcel html frame */
        jQuery('#s_method_myparcel_flatrate').parents(':eq(2)').hide();
        jQuery('#checkout-shipping-method-load').before(jQuery('#mypa-delivery-options-container'));

        actionObservers();
        indexStore();
    };

    actionObservers = function () {
        jQuery([
            options.deliveryDate,
            options.deliveryType,
            options.deliveryTime,
            options.directReturn,
            options.pickupType
        ].join()).on('change', function () {
            indexStore();
        });
    };

    indexStore = function() {

        console.log('test');
        console.log(window.mypa.data);
        window.mypaController.store = {
            deliveryDate:   jQuery(options.deliveryDate + ':checked').val(),
            deliveryType:   jQuery(options.deliveryType + ':checked').attr('id'),
            deliveryTime:   jQuery(options.deliveryTime + ':checked').attr('id'),
            directReturn:   jQuery(options.directReturn).is(':checked'),
            pickupType:     jQuery(options.pickupType + ':checked').attr('id')
        };
        console.log(window.mypaController.store);
        saveOptions();
    };

    saveOptions = function () {
        console.log('save');
    };


}).call(this);
