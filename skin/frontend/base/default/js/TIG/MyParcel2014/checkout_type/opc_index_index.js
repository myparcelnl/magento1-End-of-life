
mypaObserver ={
    postalCode: "input[id='shipping:postcode']",
    street1: "input[id='shipping:street1']",
    street2: "input[id='shipping:street2']",
    country: "select[id='shipping:country_id']"
};
window.mypa.observer = window.mypa.observer !== null ? window.mypa.observer : [];
window.mypa.fn.fnCheckout  = mypaObserver;

var fnCheckout =
{
    'saveShippingMethod': function(){
        IWD.OPC.Shipping.saveShippingMethod();
    },
    'hideLoader': function(){
        console.log('test');
        IWD.OPC.Checkout.hideLoader();
    }
};
window.mypa.fn.fnCheckout  = fnCheckout;
