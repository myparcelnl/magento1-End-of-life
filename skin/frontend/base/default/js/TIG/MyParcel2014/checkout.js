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
    var $ = jQuery.noConflict();
    var options, myParcelObserver, load;

    /**
     *  Set up the mutation observer
     */
    myParcelObserver = new MutationObserver(function (mutations, me) {
        var canvas = document.getElementById('s_method_myparcel_flatrate');
        if (canvas) {
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
        $('#s_method_myparcel_flatrate').parents(':eq(2)').hide();
        $('#checkout-shipping-method-load').before($('#mypa-delivery-options-container'));
    };


}).call(this);
