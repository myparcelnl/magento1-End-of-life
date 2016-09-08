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
