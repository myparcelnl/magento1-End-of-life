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

            window.setTimeout(updatePriceTable, 1500);

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

function updatePriceTable() {
    mypajQuery("input[name='payment[method]']")[0].click();
    mypajQuery("input[name='payment[method]']")[0].checked = false;
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
        "input[name='billing[postcode]']",
        "input[name='shipping[street][0]']",
        "input[name='shipping[street][1]']",
        "input[name='shipping[housenumber]']",
        "input[name='shipping[postcode]']"
    ].join()).on('change', function () {
        $('billing:country_id').triggerEvent('change');
    });
}, 800);