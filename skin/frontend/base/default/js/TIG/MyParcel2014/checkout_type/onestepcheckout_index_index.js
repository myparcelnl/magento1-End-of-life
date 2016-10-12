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
            mypajQuery('div.onestepcheckout-summary').html('<div class="loading-ajax">&nbsp;</div>');

            window.setTimeout(checkPendingRequest, 200);

            xhr = mypajQuery.ajax({
                type: 'post',
                url: BASE_URL + 'myparcel2014/checkout/save_shipping_method/',
                data: frm.serialize()
            });
        }, 500);
    },
    'hideLoader': function () {}
};
window.mypa.fn.fnCheckout = fnCheckout;

function checkPendingRequest() {
    if (mypajQuery.active > 0) {
        window.setTimeout(checkPendingRequest, 200);
    } else {
        mypajQuery("input[name='payment[method]']")[0].click();
        mypajQuery("input[name='payment[method]']")[0].checked = false;
    }
};

/* if address change, update shipping method */
Element.prototype.triggerEvent = function(eventName)
{
    if (document.createEvent)
    {
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent(eventName, true, true);

        return this.dispatchEvent(evt);
    }

    if (this.fireEvent)
        return this.fireEvent('on' + eventName);
};

setTimeout(function () {
    mypajQuery(".onestepcheckout-summary").mouseup(function() {
        timeout = setTimeout(function () {
            mypajQuery("input[name='shipping[postcode]']")[0].triggerEvent('change');
        }, 500);
    });
}, 800);