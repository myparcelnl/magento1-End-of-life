if (typeof window.mypa == 'undefined') {
    window.mypa = {};
    window.mypa.observer = {};
    window.mypa.fn = {};
}
var timeout, timeout2, xhr;
var fnCheckout = {
    'saveShippingMethod': function () {

        var frm = mypajQuery('form');
        clearTimeout(timeout);
        timeout = setTimeout(function () {
            if (xhr && xhr.readyState != 4) {
                xhr.abort();
            }

            mypajQuery('.onestepcheckout-column-right').append('<div class="onestepcheckout-place-order-loading mypa-details-loading" style="height: 20px;margin-top: -50px;position: absolute;margin-left: 17px;"></div>');
            mypajQuery('.onestepcheckout-summary').hide();
            window.setTimeout(checkPendingRequest, 1000);

            xhr = mypajQuery.ajax({
                type: 'post',
                url: BASE_URL + 'myparcel2014/checkout/save_shipping_method/',
                data: frm.serialize()
            });
        }, 500);
        clearTimeout(timeout2);
        timeout2 = setTimeout(function () {
            mypajQuery("input[name='payment[method]']")[0].click();
            mypajQuery("input[name='payment[method]']")[0].checked = false;
        }, 1000);
    },
    'hideLoader': function () {}
};
window.mypa.fn.fnCheckout = fnCheckout;

function checkPendingRequest() {
    if (mypajQuery.active > 0) {
        window.setTimeout(checkPendingRequest, 500);
    }
    else {
        setTimeout(function () {
            mypajQuery('.mypa-details-loading').remove();
            mypajQuery('.onestepcheckout-summary').show();
        }, 800);
    }
};