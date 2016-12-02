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
var iframeDataLoaded, iframeLoaded, myParcelToggleOptions;
(function () {
    var observer, resizeIframeWidth, resizeIframeInterval, checkMyParcelMethod, checkMethod;
    observer = parent.mypajQuery.extend({
        input: "#mypa-input",
        onlyRecipient: "input:checkbox[name='mypa-only-recipient']",
        signed: "input:checkbox[name='mypa-signed']",
        magentoMethods: "input:radio[name='shipping_method']",
        myParcelMethods: ".myparcel_method",
        myParcelExtraMethods: ".myparcel_extra_method",
        myParcelBaseMethod: ".myparcel_base_method"
    }, window.mypa.observer);

    iframeDataLoaded = function () {

        if (mypajQuery(observer.myParcelMethods).is(":checked") == false && mypajQuery("input:radio[name='shipping_method']").is(":checked") == true) {
            if (myParcelToggleOptions) {
                mypajQuery('#mypa-load').hide();
            } else if (mypajQuery('#mypa-input').val() != '') {
                mypajQuery(observer.myParcelBaseMethod).prop("checked", false);
                mypajQuery('#mypa-input').val(null).change();
            }
        } else {
            if(typeof mypajQuery(observer.myParcelBaseMethod) !== 'undefined') {
                mypajQuery(observer.myParcelBaseMethod).prop("checked", true);
                mypajQuery('#mypa-load').show();
            }
        }

        if (typeof  window.mypa.fn.fnCheckout != 'undefined') {
            window.mypa.fn.fnCheckout.saveShippingMethod();
        }

        /**
         * If method is MyParcel
         */
        mypajQuery('#mypa-load').on('change', function () {
            setTimeout(function () {
                if (mypajQuery('#mypa-input').val() != '') {
                    checkMyParcelMethod();
                }
            }, 200);
            if (typeof  window.mypa.fn.fnCheckout != 'undefined') {
                window.mypa.fn.fnCheckout.saveShippingMethod();

                setTimeout(
                    window.mypa.fn.fnCheckout.hideLoader
                    , 2000);
            }
        });

        /**
         * If method not is MyParcel
         */
        mypajQuery(observer.magentoMethods).on('click', function () {
            if (mypajQuery(observer.myParcelMethods + ':checked').length == 0) {
                if (myParcelToggleOptions) {
                    mypajQuery('#mypa-load').hide();
                } else if (mypajQuery('#mypa-input').val() != '') {
                    mypajQuery(observer.onlyRecipient).prop("checked", false).change();
                    mypajQuery(observer.signed).prop("checked", false).change();
                    mypajQuery('#mypa-input').val(null).change();
                }
            } else {
                iframeLoaded();
            }
        });
    };


    iframeLoaded = function () {
        if (mypajQuery(observer.myParcelMethods).is(":checked") && myParcelToggleOptions) {
            mypajQuery('#mypa-load').show();
        }

        clearInterval(resizeIframeInterval);
        resizeIframeWidth();

        resizeIframeInterval = setInterval(function () {
            resizeIframeWidth();
        }, 500);
    };

    /**
     * Resizes the given iFrame width so it fits its content
     */
    resizeIframeWidth = function () {
        var iframe = mypajQuery('#myparcel-iframe');
        if (iframe && iframe.contents()){
            iframe.height(10).height(iframe.contents().height());
        }
    };

    checkMyParcelMethod = function() {
        var recipientOnly = mypajQuery('#mypa-recipient-only').is(":checked");
        var signed = mypajQuery('#mypa-signed').is(":checked");
        var type;

        json = jQuery.parseJSON(parent.mypajQuery('#mypa-input').val());
        if (typeof json.time[0].price_comment != 'undefined') {
            type = json.time[0].price_comment;
        } else {
            type = json.price_comment;
        }

        switch (type) {
            case "morning":
                if (signed) {
                    checkMethod('#s_method_myparcel_morning_signature');
                } else {
                    checkMethod('#s_method_myparcel_morning');
                }
                break;
            case "standard":
                if (signed && recipientOnly) {
                    checkMethod('#s_method_myparcel_delivery_signature_and_only_recipient_fee');
                } else {
                    if (signed) {
                        checkMethod('#s_method_myparcel_evening_signature');
                    } else if (recipientOnly) {
                        checkMethod('#s_method_myparcel_delivery_only_recipient');
                    } else {
                        checkMethod(observer.myParcelBaseMethod);
                    }
                }
                break;
            case "night":
                if (signed) {
                    checkMethod('#s_method_myparcel_evening_signature');
                } else {
                    checkMethod('#s_method_myparcel_evening');
                }
                break;
            case "retail":
                checkMethod('#s_method_myparcel_pickup');
                break;
            case "retailexpress":
                checkMethod('#s_method_myparcel_pickup_express');
                break;
            case "mailbox":
                checkMethod('#s_method_myparcel_mailbox');
                break;
        }
    };

    checkMethod = function (selector){
        if(myParcelToggleOptions) {
            mypajQuery('.myparcel_holder > ul > li').hide();
            mypajQuery(selector).parent().show();
        }
        mypajQuery(selector).prop("checked", true).change();
    }

})();

