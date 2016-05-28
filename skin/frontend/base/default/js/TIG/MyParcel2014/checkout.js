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
(function() {

    /* Set up the mutation observer */
    var myParcelObserver;
    myParcelObserver = new MutationObserver(function (mutations, me) {

        var canvas = document.getElementById('s_method_myparcel_flatrate');
        if (canvas) {
            jQuery(document).ready(
                myParcelOptions.initialize(),
                me.disconnect() /* stop observing */
            )
        }

    });

    /* start observing */
    myParcelObserver.observe(document, {
        childList: true,
        subtree: true
    });

    var myParcelOptions;
    myParcelOptions = {

        initialize: function () {

            /* Load MyParcel html frame */
            jQuery('#s_method_myparcel_flatrate').parents(':eq(2)').hide();
            jQuery('#checkout-shipping-method-load').before(jQuery('#mypa-delivery-options-container'));


            jQuery("input:radio[name='mypa-delivery-type']").on('change', function () {
                //noinspection JSValidateTypes
                jQuery(this).parent().find("input:radio[name='mypa-delivery-time']").first().prop("checked", true);
            });

            jQuery("input:radio[name='mypa-delivery-time']").on('change', function () {
                console.log(jQuery(this).id)
            });

            jQuery("input:checkbox[name='mypa-onoffswitch']").on('change', function () {
                console.log(jQuery(this).checked)
            });

        }

    };

}).call(this);
