mypaObserver ={
    magentoMethodsContainer: "#checkout-shipping-method-load"
};
window.mypa.observer  = mypaObserver;

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
