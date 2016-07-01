var fnCheckout =
{
    'saveShippingMethod': function(){
        IWD.OPC.Shipping.saveShippingMethod();
    },
    'abortAjax': function(){
        console.log('test');
        IWD.OPC.Checkout.abortAjax();
    }
};
window.mypa.fn = window.mypa.fn !== null ? window.mypa.fn : [];
window.mypa.fn.fnCheckout  = fnCheckout;
