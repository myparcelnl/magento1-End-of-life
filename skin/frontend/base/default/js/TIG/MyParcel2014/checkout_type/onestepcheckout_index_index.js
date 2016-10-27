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
            mypajQuery('.payment-methods dl').hide();
            mypajQuery('.payment-methods').append('<div class="loading-ajax">&nbsp;</div>');
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

function checkPendingRequest() {
    if (mypajQuery.active > 0) {
        window.setTimeout(checkPendingRequest, 200);
    } else {
        mypajQuery("input[name='payment[method]']")[0].click();
        mypajQuery("input[name='payment[method]']")[0].checked = false;
        mypajQuery('.payment-methods dl').show();
        mypajQuery('.payment-methods .loading-ajax').remove();
    }
};

setTimeout(function () {

    mypajQuery(".onestepcheckout-summary").mouseup(function() {
        timeout = setTimeout(function () {
            window.mypa.fn.load();
        }, 500);
    });

    mypajQuery([
        "input[id='billing:street1']",
        "input[id='billing:street2']",
        "input[id='billing:postcode_housenumber']",
        "input[id='billing:postcode']",
        "input[id='shipping:street1']",
        "input[id='shipping:street2']",
        "input[id='shipping:postcode_housenumber']",
        "input[id='shipping:postcode']"
    ].join()).on('change', function () {
        setTimeout(function () {
            $('billing:country_id').triggerEvent('change');
        }, 500);
    });
}, 2000);