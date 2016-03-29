/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright   Copyright (c) 2015 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
if (typeof isEmptyObj == 'undefined') {
    function isEmptyObj(obj) {
        var keys = Object.keys(obj).length;

        return keys === 0;
    }
}

var MyParcelCheckout = Class.create();
MyParcelCheckout.prototype = {

    _settings: '',

    initialize : function() {



        /**
         * Send an AJAX request to reseve chechout settings.
         */
        new Ajax.Request(MyParcelGetInfoUrl, {
            method : 'post',
            onSuccess : this._get_settings_success_callback,
            onFailure : this._get_error_callback


        });

        return true;
    },


    _get_settings_success_callback: function(settings) {
        this._settings = JSON.parse(settings.responseText);
        console.log(this._settings);

        $('checkout-shipping-method-load').insert({ after: this._settings.template_shipping_method });
    },

    _get_error_callback: function(error) {
        console.log(error);
        alert('Er gaat iets fout, zie console voor meer informatie.');
    }
};

document.observe('dom:loaded', function() {
    MyParcelCheckout.prototype.initialize();
});