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
var iframeDataLoaded, myParcelToggleOptions;
(function () {
    var observer, saveShippingMethodTimeout;
    observer = parent.mypajQuery.extend({
        input: "#mypa-input",
        onlyRecipient: "input:checkbox[name='mypa-only-recipient']",
        signed: "input:checkbox[name='mypa-signed']",
        magentoMethods: "input:radio[name='shipping_method']",
        magentoMethodMyParcel: "input:radio[id^='s_method_myparcel']",
        payment: "input[name='payment[method]']",
        billingPostalCode: "input[name='billing[postcode]']",
        billingStreet1: "input[name='billing[street][0]']",
        billingStreet2: "input[name='billing[street][1]']",
        billingCountry: "select[name='billing[country_id]']",
        postalCode: "input[name='billing[postcode]']",
        street1: "input[name='shipping[street][0]']",
        street2: "input[name='shipping[street][1]']",
        country: "select[name='shipping[country_id]']"
    }, window.mypa.observer);

    iframeDataLoaded = function () {

        if (mypajQuery(observer.magentoMethodMyParcel).is(":checked") == false && mypajQuery("input:radio[name='shipping_method']").is(":checked") == true) {
            mypajQuery('#mypa-input').val(null).change();
        } else {
            if(mypajQuery('#mypa-input').val() != '') {
                if(typeof mypajQuery(observer.magentoMethodMyParcel)[0] !== 'undefined') {
                    mypajQuery(observer.magentoMethodMyParcel)[0].checked = true;
                }
            }
        }

        if (typeof  window.mypa.fn.fnCheckout != 'undefined') {
            window.mypa.fn.fnCheckout.saveShippingMethod();
        }

        /**
         * If method is MyParcel
         */
        mypajQuery('#mypa-load').on('change', function () {
            if(mypajQuery('#mypa-input').val() != '') {
                mypajQuery(observer.magentoMethodMyParcel)[0].checked = true;
            }
            if (typeof  window.mypa.fn.fnCheckout != 'undefined') {

                /** saveShippingMethodTimeout because he should not execute this function eight times in 1/10 seconds */
                clearTimeout(saveShippingMethodTimeout);
                saveShippingMethodTimeout = setTimeout(function () {
                    window.mypa.fn.fnCheckout.saveShippingMethod();
                }, 100);

                setTimeout(
                    window.mypa.fn.fnCheckout.hideLoader
                    , 2000);
            }
        });

        /**
         * If method not is MyParcel
        */
        mypajQuery(observer.magentoMethods).on('click', function () {
            if (mypajQuery(observer.magentoMethodMyParcel).is(":checked") == false) {
                if (myParcelToggleOptions) {
                    mypajQuery('#mypa-load').hide();
                } else {
                    mypajQuery('#mypa-input').val(null).change();
                }
            } else {
                mypajQuery('#mypa-load').show();
            }
        });
    };
})();


function iframeLoaded() {

    var iFrameID = document.getElementById('myparcel-iframe');
    if(iFrameID) {
        iFrameID.height = "";
        iFrameID.height = iFrameID.contentWindow.document.body.scrollHeight + 5 + "px";
    }

    setTimeout(function () {
        var iFrameID = document.getElementById('myparcel-iframe');
        if(iFrameID) {
            // here you can make the height, I delete it first, then I make it again
            iFrameID.height = "";
            iFrameID.height = iFrameID.contentWindow.document.body.scrollHeight + 5 + "px";
        }
    }, 500);

    setTimeout(function () {
        var iFrameID = document.getElementById('myparcel-iframe');
        if(iFrameID) {
            // here you can make the height, I delete it first, then I make it again
            iFrameID.height = "";
            iFrameID.height = iFrameID.contentWindow.document.body.scrollHeight + 5 + "px";
        }
    }, 3000);
}