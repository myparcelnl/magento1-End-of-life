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
            xhr = mypajQuery.ajax({
                type: 'post',
                url: BASE_URL + 'myparcel2014/checkout/save_shipping_method/',
                data: frm.serialize()
            });
            window.setTimeout(checkPendingRequest, 200);
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
            window.mypa.fn.load();
        }, 500);
    });

    mypajQuery([
        "input[id='billing:postcode_housenumber']"
    ].join()).on('change', function () {
        setTimeout(function () {
            if(RUN_MYPARCEL_OPTIONS == true){
                $('billing:country_id').triggerEvent('change');
                RUN_MYPARCEL_OPTIONS = false;
            }
        }, 500);
    });
}, 1000);