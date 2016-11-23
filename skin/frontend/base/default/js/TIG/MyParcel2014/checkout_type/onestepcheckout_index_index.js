if (typeof window.mypa == 'undefined') {
    window.mypa = {};
    window.mypa.observer = {};
    window.mypa.fn = {};
}
var timeout, xhr, latestData;
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
        if (mypajQuery("input[name='shipping_method']:checked").length) {
            mypajQuery("input[name='shipping_method']:checked")[0].click();
        }
    }
};

function myparcelSaveBilling() {
    setTimeout(function() {
            var currentData = mypajQuery("input[id='billing:street1']").val();
            if(mypajQuery("input[id='billing:street2']").length && mypajQuery("input[id='billing:street2']").val().length){
                currentData = currentData + mypajQuery("input[id='billing:street2']").val();
            }
            if(mypajQuery("input[id='shipping:street1']").length && mypajQuery("input[id='shipping:street1']").val().length){
                currentData = currentData + mypajQuery("input[id='shipping:street1']").val();
            }
            if(mypajQuery("input[id='shipping:street2']").length && mypajQuery("input[id='shipping:street2']").val().length){
                currentData = currentData + mypajQuery("input[id='shipping:street2']").val();
            }

            if (latestData == currentData) {
                console.log(currentData);
                myparcelSaveBilling();
            } else {
                get_save_billing_function(BASE_URL + 'onestepcheckout/ajax/save_billing', BASE_URL + 'onestepcheckout/ajax/set_methods_separate', true, true)();

                latestData = mypajQuery("input[id='billing:street1']").val();
                if(mypajQuery("input[id='billing:street2']").length && mypajQuery("input[id='billing:street2']").val().length){
                    latestData = latestData + mypajQuery("input[id='billing:street2']").val();
                }
            }
        }
        , 300);
}

setTimeout(function () {

    mypajQuery(".onestepcheckout-summary > tbody > tr > .editcart").mouseup(function() {
        setTimeout(function() {
            get_save_billing_function(BASE_URL + 'onestepcheckout/ajax/save_billing', BASE_URL + 'onestepcheckout/ajax/set_methods_separate', true, true)();
        }, 500);
    });

    latestData = mypajQuery("input[id='billing:street1']").val();
    if(mypajQuery("input[id='billing:street2']").length && mypajQuery("input[id='billing:street2']").val().length){
        latestData = latestData + mypajQuery("input[id='billing:street2']").val();
    }
    if(mypajQuery("input[id='shipping:street1']").length && mypajQuery("input[id='shipping:street1']").val().length){
        latestData = latestData + mypajQuery("input[id='shipping:street1']").val();
    }
    if(mypajQuery("input[id='shipping:street2']").length && mypajQuery("input[id='shipping:street2']").val().length){
        latestData = latestData + mypajQuery("input[id='shipping:street2']").val();
    }

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
        myparcelSaveBilling();
    });
}, 2000);