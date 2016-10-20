if(typeof window.mypa == 'undefined') {
    window.mypa = {};
    window.mypa.observer = {};
    window.mypa.fn = {};
}

var fnCheckout =
{
    'saveShippingMethod': function(){
        IWD.OPC.Shipping.saveShippingMethod();
    },
    'hideLoader': function(){
        IWD.OPC.Checkout.hideLoader();
    }
};
window.mypa.fn.fnCheckout  = fnCheckout;

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
        "input[id='billing:street1']",
        "input[id='billing:street2']",
        "input[id='billing:postcode_housenumber']",
        "input[id='billing:postcode']"
    ].join()).on('change', function () {
        setTimeout(function () {
            console.log(RUN_MYPARCEL_OPTIONS);
            if(RUN_MYPARCEL_OPTIONS == true){
                $('billing:country_id').triggerEvent('change');
                RUN_MYPARCEL_OPTIONS = false;
            }
        }, 500);
    });
}, 1000);