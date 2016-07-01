var fnCheckout =
{
    'saveShippingMethod': function(){
        IWD.OPC.Shipping.saveShippingMethod();
    }
};
window.mypa.fn = window.mypa.fn !== null ? window.mypa.fn : [];
window.mypa.fn.fnCheckout  = fnCheckout;
