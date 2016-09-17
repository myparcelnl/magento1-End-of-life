mypajQuery(document).ready(function () {
    mypajQuery('#onestepcheckout-place-order').on('click', function () {

        var frm = mypajQuery('form');
        mypajQuery.ajax({
            type: 'post',
            url: BASE_URL + 'myparcel2014/checkout/save_shipping_method/',
            data: frm.serialize()
        });

        /*setTimeout(function () {
            mypajQuery('input:checkbox').trigger('click');
        }, 3000);*/
    });
});