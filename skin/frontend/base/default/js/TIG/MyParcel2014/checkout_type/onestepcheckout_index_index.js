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

            mypajQuery('.mypa-details-loading').remove();
            mypajQuery('.onestepcheckout-column-right').append('<div class="onestepcheckout-place-order-loading mypa-details-loading" style="height: 20px;margin-top: -50px;position: absolute;margin-left: 17px;"></div>');
            mypajQuery('.onestepcheckout-summary')[0].hide();
            window.setTimeout(checkPendingRequest, 1000);

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
        window.setTimeout(checkPendingRequest, 500);
    } else {
        mypajQuery("input[name='payment[method]']")[0].click();
        mypajQuery("input[name='payment[method]']")[0].checked = false;
        setTimeout(function () {
            mypajQuery('.mypa-details-loading').remove();
            mypajQuery('.onestepcheckout-summary').show();
        }, 800);
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
    mypajQuery([
        "input[name='billing[street][0]']",
        "input[name='billing[street][1]']",
        "input[name='billing[housenumber]']",
        "input[name='billing[postcode][2]']",
        "input[name='shipping[street][0]']",
        "input[name='shipping[street][1]']",
        "input[name='shipping[housenumber]']",
        "input[name='shipping[postcode]']"
    ].join()).on('change', function () {
        $('billing:country_id').triggerEvent('change');
    });
}, 800);