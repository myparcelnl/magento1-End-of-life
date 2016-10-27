(function() {
    var $, AO_DEFAULT_TEXT, Application, CARRIER, DAYS_OF_THE_WEEK, DAYS_OF_THE_WEEK_TRANSLATED, DEFAULT_DELIVERY, DISABLED, EVENING_DELIVERY, HVO_DEFAULT_TEXT, MORNING_DELIVERY, MORNING_PICKUP, NATIONAL, NORMAL_PICKUP, PICKUP, PICKUP_EXPRESS, PICKUP_TIMES, POST_NL_TRANSLATION, Slider, checkCombination, displayOtherTab, jquery, obj1, orderOpeningHours, preparePickup, renderDeliveryOptions, renderExpressPickup, renderPage, renderPickup, renderPickupLocation, showDefaultPickupLocation, sortLocationsOnDistance, updateDelivery, updateInputField,
        bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; };

    DISABLED = 'disabled';

    HVO_DEFAULT_TEXT = 'Handtekening voor ontvangst';

    AO_DEFAULT_TEXT = 'Alleen geadresseerde';

    NATIONAL = 'NL';

    CARRIER = 1;

    MORNING_DELIVERY = 'morning';

    DEFAULT_DELIVERY = 'default';

    EVENING_DELIVERY = 'night';

    PICKUP = 'pickup';

    PICKUP_EXPRESS = 'pickup_express';

    POST_NL_TRANSLATION = {
        morning: 'morning',
        standard: 'default',
        night: 'night'
    };

    DAYS_OF_THE_WEEK = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    DAYS_OF_THE_WEEK_TRANSLATED = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

    MORNING_PICKUP = '08:30:00';

    NORMAL_PICKUP = '16:00:00';

    PICKUP_TIMES = (
        obj1 = {},
            obj1["" + MORNING_PICKUP] = 'morning',
            obj1["" + NORMAL_PICKUP] = 'normal',
            obj1
    );

    this.MyParcel = Application = (function() {

        /*
         * Setup initial variables
         */
        function Application(options) {
            var base;
            moment.locale(NATIONAL);
            if (window.mypa == null) {
                window.mypa = {
                    settings: {}
                };
            }
            if ((base = window.mypa.settings).base_url == null) {
                base.base_url = "//localhost:8080/api/delivery_options";
            }
            this.el = document.getElementById('myparcel');
            this.$el = jquery('myparcel');
            if (this.shadow == null) {
                this.shadow = this.el.createShadowRoot();
            }
            this.render();
            this.expose(this.updatePage, 'updatePage');
            this.expose(this, 'activeInstance');
        }


        /*
         * Reloads the HTML form the template.
         */

        Application.prototype.render = function() {
            var error, ref;
            this.shadow.innerHTML = document.getElementById('myparcel-template').innerHTML;
            try {
                if ((ref = WebComponents.ShadowCSS) != null) {
                    ref.shimStyling(shadow, 'myparcel');
                }
            } catch (error) {
                //console.log('Cannot shim CSS');
            }
            return this.bindInputListeners();
        };


        /*
         * Puts function in window.mypa effectively exposing the function.
         */

        Application.prototype.expose = function(fn, name) {
            var base;
            if ((base = window.mypa).fn == null) {
                base.fn = {};
            }
            return window.mypa.fn[name] = fn;
        };


        /*
         * Adds the listeners for the inputfields.
         */

        Application.prototype.bindInputListeners = function() {
            jquery('#mypa-signed', parent.document).on('change', (function(_this) {
                return function(e) {
                    return $('#mypa-signed', parent.document).prop('checked', jquery('#mypa-signed', parent.document).prop('checked'));
                };
            })(this));
            jquery('#mypa-recipient-only', parent.document).on('change', (function(_this) {
                return function(e) {
                    return $('#mypa-only-recipient').prop('checked', jquery('#mypa-recipient-only', parent.document).prop('checked'));
                };
            })(this));
            return jquery('#mypa-input', parent.document).on('change', (function(_this) {
                return function(e) {
                    var el, i, json, len, ref;
                    json = jquery('#mypa-input', parent.document).val();
                    if (json === '') {
                        $('input[name=mypa-delivery-time]:checked').prop('checked', false);
                        $('input[name=mypa-delivery-type]:checked').prop('checked', false);
                        return;
                    }
                    ref = $('input[name=mypa-delivery-time]');
                    for (i = 0, len = ref.length; i < len; i++) {
                        el = ref[i];
                        if ($(el).val() === json) {
                            $(el).prop('checked', true);
                            return;
                        }
                    }
                };
            })(this));
        };


        /*
         * Fetches devliery options and an overall page update.
         */

        Application.prototype.updatePage = function(postal_code, number, street) {
            var item, key, options, ref, settings, urlBase;
            ref = window.mypa.settings.price;
            for (key in ref) {
                item = ref[key];
                if (!(typeof item === 'string' || typeof item === 'function')) {
                    throw new Error('Price needs to be of type string');
                }
            }
            settings = window.mypa.settings;
            urlBase = settings.base_url;
            if (number == null) {
                number = settings.number;
            }
            if (postal_code == null) {
                postal_code = settings.postal_code;
            }
            if (street == null) {
                street = settings.street;
            }
            if (!((street != null) || (postal_code != null) || (number != null))) {
                $('#mypa-no-options').html('Adres ophalen...');
                $('.mypa-overlay').removeClass('mypa-hidden');
                return;
            }
            $('#mypa-no-options').html('Bezig met laden...');
            $('.mypa-overlay').removeClass('mypa-hidden');
            $('.mypa-location').html(street + " " + number);
            options = {
                url: urlBase,
                data: {
                    cc: NATIONAL,
                    carrier: CARRIER,
                    number: number,
                    postal_code: postal_code,
                    delivery_time: settings.delivery_time != null ? settings.delivery_time : void 0,
                    delivery_date: settings.delivery_date != null ? settings.delivery_date : void 0,
                    cutoff_time: settings.cutoff_time != null ? settings.cutoff_time : void 0,
                    dropoff_days: settings.dropoff_days != null ? settings.dropoff_days : void 0,
                    dropoff_delay: settings.dropoff_delay != null ? settings.dropoff_delay : void 0,
                    deliverydays_window: settings.deliverydays_window != null ? settings.deliverydays_window : void 0,
                    exclude_delivery_type: settings.exclude_delivery_type != null ? settings.exclude_delivery_type : void 0
                },
                success: renderPage,
                failure: renderPage({"data":{"delivery":[{"date":"2016-10-28","time":[{"start":"08:00:00","end":"12:00:00","price":{"currency":"EUR","amount":1000},"price_comment":"morning","comment":"","type":1},{"start":"16:30:00","end":"18:30:00","price":{"currency":"EUR","amount":0},"price_comment":"standard","comment":"","type":2},{"start":"18:00:00","end":"21:30:00","price":{"currency":"EUR","amount":125},"price_comment":"avond","comment":"","type":3}]},{"date":"2016-10-29","time":[{"start":"15:30:00","end":"17:30:00","price":{"currency":"EUR","amount":0},"price_comment":"standard","comment":"","type":2}]},{"date":"2016-11-01","time":[{"start":"08:00:00","end":"12:00:00","price":{"currency":"EUR","amount":1000},"price_comment":"morning","comment":"","type":1},{"start":"18:00:00","end":"20:00:00","price":{"currency":"EUR","amount":0},"price_comment":"standard","comment":"","type":2},{"start":"18:00:00","end":"21:30:00","price":{"currency":"EUR","amount":125},"price_comment":"avond","comment":"","type":3}]},{"date":"2016-11-02","time":[{"start":"08:00:00","end":"12:00:00","price":{"currency":"EUR","amount":1000},"price_comment":"morning","comment":"","type":1},{"start":"17:00:00","end":"19:00:00","price":{"currency":"EUR","amount":0},"price_comment":"standard","comment":"","type":2},{"start":"18:00:00","end":"21:30:00","price":{"currency":"EUR","amount":125},"price_comment":"avond","comment":"","type":3}]},{"date":"2016-11-03","time":[{"start":"08:00:00","end":"12:00:00","price":{"currency":"EUR","amount":1000},"price_comment":"morning","comment":"","type":1},{"start":"16:30:00","end":"18:30:00","price":{"currency":"EUR","amount":0},"price_comment":"standard","comment":"","type":2},{"start":"18:00:00","end":"21:30:00","price":{"currency":"EUR","amount":125},"price_comment":"avond","comment":"","type":3}]},{"date":"2016-11-04","time":[{"start":"08:00:00","end":"12:00:00","price":{"currency":"EUR","amount":1000},"price_comment":"morning","comment":"","type":1},{"start":"16:30:00","end":"18:30:00","price":{"currency":"EUR","amount":0},"price_comment":"standard","comment":"","type":2},{"start":"18:00:00","end":"21:30:00","price":{"currency":"EUR","amount":125},"price_comment":"avond","comment":"","type":3}]},{"date":"2016-11-05","time":[{"start":"15:30:00","end":"17:30:00","price":{"currency":"EUR","amount":0},"price_comment":"standard","comment":"","type":2}]},{"date":"2016-11-08","time":[{"start":"08:00:00","end":"12:00:00","price":{"currency":"EUR","amount":1000},"price_comment":"morning","comment":"","type":1},{"start":"18:00:00","end":"20:00:00","price":{"currency":"EUR","amount":0},"price_comment":"standard","comment":"","type":2},{"start":"18:00:00","end":"21:30:00","price":{"currency":"EUR","amount":125},"price_comment":"avond","comment":"","type":3}]},{"date":"2016-11-09","time":[{"start":"08:00:00","end":"12:00:00","price":{"currency":"EUR","amount":1000},"price_comment":"morning","comment":"","type":1},{"start":"17:00:00","end":"19:00:00","price":{"currency":"EUR","amount":0},"price_comment":"standard","comment":"","type":2},{"start":"18:00:00","end":"21:30:00","price":{"currency":"EUR","amount":125},"price_comment":"avond","comment":"","type":3}]}],"pickup":[{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Coop","street":"Rijnstraat","number":"78","postal_code":"2223EC","city":"Katwijk","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Pakketpunt. Pakketten die u op werkdagen v\u00f3\u00f3r lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"06-24200405","opening_hours":{"monday":["07:00-21:00"],"tuesday":["07:00-21:00"],"wednesday":["07:00-21:00"],"thursday":["07:00-21:00"],"friday":["07:00-21:00"],"saturday":["07:00-21:00"],"sunday":[]},"distance":"586","location_code":"202425"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}},{"start":"08:30:00","type":5,"price":{"amount":125,"currency":"EUR"}}],"location":"KantoorExpert Katwijk","street":"Scheepmakerstraat","number":"63","postal_code":"2222AB","city":"Katwijk","start_time":"08:30:00","price":125,"price_comment":"retailexpress","comment":"Dit is een Business Point. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-4080800","opening_hours":{"monday":["08:00-18:30"],"tuesday":["08:00-18:30"],"wednesday":["08:00-18:30"],"thursday":["08:00-18:30"],"friday":["08:00-18:30"],"saturday":["08:00-09:00"],"sunday":[]},"distance":"1300","location_code":"159146"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Gamma","street":"Ambachtsweg","number":"23","postal_code":"2222AJ","city":"Katwijk","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Pakketpunt. Pakketten die u op werkdagen v\u00f3\u00f3r lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-4026264","opening_hours":{"monday":["09:00-21:00"],"tuesday":["09:00-21:00"],"wednesday":["09:00-21:00"],"thursday":["09:00-21:00"],"friday":["09:00-21:00"],"saturday":["09:00-18:00"],"sunday":[]},"distance":"1254","location_code":"167542"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"KARWEI Katwijk aan Zee","street":"Ambachtsweg","number":"19","postal_code":"2222AH","city":"Katwijk","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Pakketpunt. Pakketten die u op werkdagen v\u00f3\u00f3r lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-4086616","opening_hours":{"monday":["09:00-21:00"],"tuesday":["09:00-21:00"],"wednesday":["09:00-21:00"],"thursday":["09:00-21:00"],"friday":["09:00-21:00"],"saturday":["09:00-18:00"],"sunday":[]},"distance":"1321","location_code":"171977"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Hoogvliet","street":"Oegstgeesterweg","number":"48","postal_code":"2231AZ","city":"Rijnsburg","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Pakketpunt. Pakketten die u op werkdagen v\u00f3\u00f3r lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-4092980","opening_hours":{"monday":["08:00-20:00"],"tuesday":["08:00-20:00"],"wednesday":["08:00-20:00"],"thursday":["08:00-21:00"],"friday":["08:00-21:00"],"saturday":["08:00-20:00"],"sunday":[]},"distance":"1344","location_code":"203581"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"The Read Shop","street":"Anjelierenstraat","number":"43","postal_code":"2231GT","city":"Rijnsburg","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Postkantoor. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-4023063","opening_hours":{"monday":["08:00-18:00"],"tuesday":["08:00-18:00"],"wednesday":["08:00-18:00"],"thursday":["08:00-18:00"],"friday":["08:00-19:00"],"saturday":["08:00-18:00"],"sunday":[]},"distance":"1357","location_code":"163463"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Hoogvliet","street":"Hoofdstraat","number":"104","postal_code":"2235CK","city":"Valkenburg","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Postkantoor. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-4061040","opening_hours":{"monday":["08:00-20:00"],"tuesday":["08:00-20:00"],"wednesday":["08:00-20:00"],"thursday":["08:00-20:00"],"friday":["08:00-20:00"],"saturday":["08:00-20:00"],"sunday":[]},"distance":"1771","location_code":"161628"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}},{"start":"08:30:00","type":5,"price":{"amount":125,"currency":"EUR"}}],"location":"Primera","street":"Visserijkade","number":"2","postal_code":"2225TV","city":"Katwijk","start_time":"08:30:00","price":125,"price_comment":"retailexpress","comment":"Dit is een Postkantoor. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-4072811","opening_hours":{"monday":["08:00-21:00"],"tuesday":["08:00-21:00"],"wednesday":["08:00-21:00"],"thursday":["08:00-21:00"],"friday":["08:00-21:00"],"saturday":["08:00-20:00"],"sunday":[]},"distance":"2007","location_code":"163106"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Primera","street":"Hoornesplein","number":"23","postal_code":"2221BC","city":"Katwijk","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Pakketpunt. Pakketten die u op werkdagen v\u00f3\u00f3r lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-4081015","opening_hours":{"monday":["13:00-17:30"],"tuesday":["09:00-17:30"],"wednesday":["09:00-17:30"],"thursday":["09:00-17:30"],"friday":["09:00-17:30"],"saturday":["09:00-16:00"],"sunday":[]},"distance":"1839","location_code":"161824"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Hoogvliet","street":"Voorstraat","number":"49","postal_code":"2225EL","city":"Katwijk","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Pakketpunt. Pakketten die u op werkdagen v\u00f3\u00f3r lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-3417710","opening_hours":{"monday":["08:00-20:00"],"tuesday":["08:00-20:00"],"wednesday":["08:00-20:00"],"thursday":["08:00-21:00"],"friday":["08:00-21:00"],"saturday":["08:00-21:00"],"sunday":[]},"distance":"2345","location_code":"202474"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"The Read Shop","street":"Noordzeepassage","number":"105","postal_code":"2225CD","city":"Katwijk","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Pakketpunt. Pakketten die u op werkdagen v\u00f3\u00f3r lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-4016438","opening_hours":{"monday":["11:00-18:00"],"tuesday":["09:00-18:00"],"wednesday":["09:00-18:00"],"thursday":["09:00-21:00"],"friday":["09:00-18:00"],"saturday":["09:00-17:00"],"sunday":[]},"distance":"2519","location_code":"175589"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Primera","street":"Ommevoort","number":"10","postal_code":"2341VV","city":"Oegstgeest","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Postkantoor. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-5156313","opening_hours":{"monday":["12:00-20:00"],"tuesday":["09:00-20:00"],"wednesday":["09:00-20:00"],"thursday":["09:00-20:00"],"friday":["09:00-20:00"],"saturday":["09:00-16:00"],"sunday":["12:00-17:00"]},"distance":"3311","location_code":"161951"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}},{"start":"08:30:00","type":5,"price":{"amount":125,"currency":"EUR"}}],"location":"Formido Leiden","street":"Hoge Morsweg","number":"152","postal_code":"2332HN","city":"Leiden","start_time":"08:30:00","price":125,"price_comment":"retailexpress","comment":"Dit is een Business Point. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-5766966","opening_hours":{"monday":["08:00-20:00"],"tuesday":["08:00-20:00"],"wednesday":["08:00-20:00"],"thursday":["08:00-20:00"],"friday":["08:00-20:00"],"saturday":["08:00-17:00"],"sunday":[]},"distance":"4334","location_code":"160837"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Albert Heijn","street":"Terweeplein","number":"3","postal_code":"2341CZ","city":"Oegstgeest","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Postkantoor. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-5174741","opening_hours":{"monday":["08:00-20:00"],"tuesday":["08:00-20:00"],"wednesday":["08:00-20:00"],"thursday":["08:00-20:00"],"friday":["08:00-21:00"],"saturday":["08:00-20:00"],"sunday":[]},"distance":"3405","location_code":"162047"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Bruna","street":"Ina Boudier-Bakkerstraat","number":"9","postal_code":"2331AX","city":"Leiden","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Postkantoor. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-5763848","opening_hours":{"monday":["09:00-18:00"],"tuesday":["09:00-18:00"],"wednesday":["09:00-18:00"],"thursday":["09:00-18:00"],"friday":["09:00-18:00"],"saturday":["09:00-18:00"],"sunday":[]},"distance":"4606","location_code":"161724"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Primera van Hove","street":"Diamantplein","number":"54","postal_code":"2332HT","city":"Leiden","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Postkantoor. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-5764640","opening_hours":{"monday":["09:00-17:30"],"tuesday":["09:00-17:30"],"wednesday":["09:00-17:30"],"thursday":["09:00-17:30"],"friday":["09:00-17:30"],"saturday":["09:00-16:30"],"sunday":[]},"distance":"4606","location_code":"161708"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"AKO Leiden NS","street":"Stationsplein","number":"3","postal_code":"2312AJ","city":"Leiden","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Pakketpunt. Pakketten die u op werkdagen v\u00f3\u00f3r lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"088-1338017","opening_hours":{"monday":["06:00-21:00"],"tuesday":["06:00-21:00"],"wednesday":["06:00-21:00"],"thursday":["06:00-21:00"],"friday":["06:00-21:00"],"saturday":["07:00-21:00"],"sunday":["09:00-21:00"]},"distance":"4791","location_code":"175384"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Jumbo","street":"Stationsweg","number":"44","postal_code":"2312AV","city":"Leiden","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Pakketpunt. Pakketten die u op werkdagen v\u00f3\u00f3r lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-5161662","opening_hours":{"monday":["07:00-22:00"],"tuesday":["07:00-22:00"],"wednesday":["07:00-22:00"],"thursday":["07:00-22:00"],"friday":["07:00-22:00"],"saturday":["07:00-22:00"],"sunday":["09:00-22:00"]},"distance":"4897","location_code":"161517"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}},{"start":"08:30:00","type":5,"price":{"amount":125,"currency":"EUR"}}],"location":"Bruna","street":"Kerkstraat","number":"23","postal_code":"2201KK","city":"Noordwijk","start_time":"08:30:00","price":125,"price_comment":"retailexpress","comment":"Dit is een Business Point. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"071-3614934","opening_hours":{"monday":["08:00-18:30"],"tuesday":["08:00-18:30"],"wednesday":["08:00-18:30"],"thursday":["08:00-18:30"],"friday":["08:00-18:30"],"saturday":["08:00-17:00"],"sunday":[]},"distance":"5260","location_code":"160719"},{"date":"2016-10-28","time":[{"start":"16:00:00","type":4,"price":{"amount":0,"currency":"EUR"}}],"location":"Albert Heijn","street":"Stadhoudersplein","number":"4","postal_code":"2241EB","city":"Wassenaar","start_time":"16:00:00","price":0,"price_comment":"retail","comment":"Dit is een Postkantoor. Post en pakketten die u op werkdagen v\u00f3\u00f3r de lichtingstijd afgeeft, bezorgen we binnen Nederland de volgende dag.","phone_number":"070-5115344","opening_hours":{"monday":["08:00-21:00"],"tuesday":["08:00-21:00"],"wednesday":["08:00-21:00"],"thursday":["08:00-21:00"],"friday":["08:00-21:00"],"saturday":["08:00-21:00"],"sunday":["12:00-19:00"]},"distance":"5161","location_code":"162058"}]}})
            };
            return jquery.ajax(options);
        };

        return Application;

    })();

    Slider = (function() {

        /*
         * Renders the available days for delivery
         */
        function Slider(deliveryDays) {
            this.slideRight = bind(this.slideRight, this);
            this.slideLeft = bind(this.slideLeft, this);
            var $el, $tabs, date, delivery, deliveryTimes, html, i, index, len, ref;
            this.deliveryDays = deliveryDays;
            if (deliveryDays.length < 1) {
                $('mypa-delivery-row').addClass('mypa-hidden');
                return;
            }
            $('mypa-delivery-row').removeClass('mypa-hidden');
            deliveryDays.sort(this.orderDays);
            deliveryTimes = window.mypa.sortedDeliverytimes = {};
            $el = $('#mypa-tabs').html('');
            window.mypa.deliveryDays = deliveryDays.length;
            index = 0;
            ref = this.deliveryDays;
            for (i = 0, len = ref.length; i < len; i++) {
                delivery = ref[i];
                deliveryTimes[delivery.date] = delivery.time;
                date = moment(delivery.date);
                html = "<input type=\"radio\" id=\"mypa-date-" + index + "\" class=\"mypa-date\" name=\"date\" checked value=\"" + delivery.date + "\">\n<label for='mypa-date-" + index + "' class='mypa-tab active'>\n  <span class='day-of-the-week'>" + (date.format('dddd')) + "</span>\n  <br>\n  <span class='date'>" + (date.format('DD MMMM')) + "</span>\n</label>";
                $el.append(html);
                index++;
            }
            $tabs = $('.mypa-tab');
            if ($tabs.length > 0) {
                $tabs.bind('click', updateDelivery);
                $tabs[0].click();
            }
            $("#mypa-tabs").attr('style', "width:" + (this.deliveryDays.length * 105) + "px");
            this.makeSlider();
        }


        /*
         * Initializes the slider
         */

        Slider.prototype.makeSlider = function() {
            this.slider = {};
            this.slider.currentBar = 0;
            this.slider.bars = window.mypa.deliveryDays * 105 / $('#mypa-tabs-container')[0].offsetWidth;
            $('mypa-tabs').attr('style', "width:" + (window.mypa.deliveryDays * 105) + "px;");
            $('#mypa-date-slider-right').removeClass('mypa-slider-disabled');
            $('#mypa-date-slider-left').unbind().bind('click', this.slideLeft);
            return $('#mypa-date-slider-right').unbind().bind('click', this.slideRight);
        };


        /*
         * Event handler for sliding the date slider to the left
         */

        Slider.prototype.slideLeft = function(e) {
            var $el, left, slider;
            slider = this.slider;
            if (slider.currentBar === 1) {
                $(e.currentTarget).addClass('mypa-slider-disabled');
            } else if (slider.currentBar < 1) {
                return false;
            } else {
                $(e.currentTarget).removeClass('mypa-slider-disabled');
            }
            $('#mypa-date-slider-right').removeClass('mypa-slider-disabled');
            slider.currentBar--;
            $el = $('#mypa-tabs');
            left = slider.currentBar * 100 * -1;
            return $el.attr('style', "left:" + left + "%; width:" + (window.mypa.deliveryDays * 105) + "px");
        };


        /*
         * Event handler for sliding the date slider to the right
         */

        Slider.prototype.slideRight = function(e) {
            var $el, left, slider;
            slider = this.slider;
            if (parseInt(slider.currentBar) === parseInt(slider.bars - 1)) {
                $(e.currentTarget).addClass('mypa-slider-disabled');
            } else if (slider.currentBar >= slider.bars - 1) {
                return false;
            } else {
                $(e.currentTarget).removeClass('mypa-slider-disabled');
            }
            $('#mypa-date-slider-left').removeClass('mypa-slider-disabled');
            slider.currentBar++;
            $el = $('#mypa-tabs');
            left = slider.currentBar * 100 * -1;
            return $el.attr('style', "left:" + left + "%; width:" + (window.mypa.deliveryDays * 105) + "px");
        };


        /*
         * Order function for the delivery array
         */

        Slider.prototype.orderDays = function(dayA, dayB) {
            var dateA, dateB, max;
            dateA = moment(dayA.date);
            dateB = moment(dayB.date);
            max = moment.max(dateA, dateB);
            if (max === dateA) {
                return 1;
            }
            return -1;
        };

        return Slider;

    })();

    if (typeof mypajQuery !== "undefined" && mypajQuery !== null) {
        jquery = mypajQuery;
    }

    if (jquery == null) {
        jquery = $;
    }

    if (jquery == null) {
        jquery = jQuery;
    }

    $ = function(selector) {
        return jquery(document.getElementById('myparcel').shadowRoot).find(selector);
    };

    displayOtherTab = function() {
        return $('.mypa-tab-container').toggleClass('mypa-slider-pos-1').toggleClass('mypa-slider-pos-0');
    };


    /*
     * Starts the render of the delivery options with the preset config
     */

    renderPage = function(response) {
        if (response.data.message === 'No results') {
            $('#mypa-no-options').html('Geen bezorgopties gevonden voor het opgegeven adres.');
            $('.mypa-overlay').removeClass('mypa-hidden');
            return;
        }
        $('.mypa-overlay').addClass('mypa-hidden');
        $('#mypa-delivery-option-check').bind('click', function() {
            return renderDeliveryOptions($('input[name=date]:checked').val());
        });
        new Slider(response.data.delivery);
        preparePickup(response.data.pickup);
        $('#mypa-delivery-options-title').on('click', function() {
            var date;
            date = $('input[name=date]:checked').val();
            renderDeliveryOptions(date);
            return updateInputField();
        });
        $('#mypa-pickup-options-title').on('click', function() {
            $('#mypa-pickup').prop('checked', true);
            return updateInputField();
        });
        return updateInputField();
    };

    preparePickup = function(pickupOptions) {
        var filter, i, j, len, len1, name1, pickupExpressPrice, pickupLocation, pickupPrice, ref, time;
        if (pickupOptions.length < 1) {
            $('#mypa-pickup-row').addClass('mypa-hidden');
            return;
        }
        $('#mypa-pickup-row').removeClass('mypa-hidden');
        pickupPrice = window.mypa.settings.price[PICKUP];
        pickupExpressPrice = window.mypa.settings.price[PICKUP_EXPRESS];
        $('.mypa-pickup-price').html(pickupPrice);
        $('.mypa-pickup-price').toggleClass('mypa-hidden', pickupPrice == null);
        $('.mypa-pickup-express-price').html(pickupExpressPrice);
        $('.mypa-pickup-express-price').toggleClass('mypa-hidden', pickupExpressPrice == null);
        window.mypa.pickupFiltered = filter = {};
        pickupOptions = sortLocationsOnDistance(pickupOptions);
        for (i = 0, len = pickupOptions.length; i < len; i++) {
            pickupLocation = pickupOptions[i];
            ref = pickupLocation.time;
            for (j = 0, len1 = ref.length; j < len1; j++) {
                time = ref[j];
                if (filter[name1 = PICKUP_TIMES[time.start]] == null) {
                    filter[name1] = [];
                }
                filter[PICKUP_TIMES[time.start]].push(pickupLocation);
            }
        }
        if (filter[PICKUP_TIMES[MORNING_PICKUP]] == null) {
            $('#mypa-pickup-express').parent().css({
                display: 'none'
            });
        }
        showDefaultPickupLocation('#mypa-pickup-address', filter[PICKUP_TIMES[NORMAL_PICKUP]][0]);
        if(MORNING_PICKUP && PICKUP_TIMES[MORNING_PICKUP] && filter[PICKUP_TIMES[MORNING_PICKUP]]){
            showDefaultPickupLocation('#mypa-pickup-express-address', filter[PICKUP_TIMES[MORNING_PICKUP]][0]);
        }
        $('#mypa-pickup-address').off().bind('click', renderPickup);
        $('#mypa-pickup-express-address').off().bind('click', renderExpressPickup);
        return $('.mypa-pickup-selector').on('click', updateInputField);
    };


    /*
     * Sorts the pickup options on nearest location
     */

    sortLocationsOnDistance = function(pickupOptions) {
        return pickupOptions.sort(function(a, b) {
            return parseInt(a.distance) - parseInt(b.distance);
        });
    };


    /*
     * Displays the default location behind the pickup location
     */

    showDefaultPickupLocation = function(selector, item) {
        var html;
        html = " - " + item.location + ", " + item.street + " " + item.number;
        $(selector).html(html);
        $(selector).parent().find('input').val(JSON.stringify(item));
        return updateInputField();
    };


    /*
     * Set the pickup time HTML and start rendering the locations page
     */

    renderPickup = function() {
        renderPickupLocation(window.mypa.pickupFiltered[PICKUP_TIMES[NORMAL_PICKUP]]);
        $('.mypa-location-time').html('- Vanaf 16.00 uur');
        $('#mypa-pickup').prop('checked', true);
        return false;
    };


    /*
     * Set the pickup time HTML and start rendering the locations page
     */

    renderExpressPickup = function() {
        renderPickupLocation(window.mypa.pickupFiltered[PICKUP_TIMES[MORNING_PICKUP]]);
        $('.mypa-location-time').html('- Vanaf 08.30 uur');
        $('#mypa-pickup-express').prop('checked', true);
        return false;
    };


    /*
     * Renders the locations in the array order given in data
     */

    renderPickupLocation = function(data) {
        var day_index, html, i, index, j, k, len, location, openingHoursHtml, orderedHours, ref, ref1, time;
        displayOtherTab();
        $('.mypa-onoffswitch-checkbox:checked').prop('checked', false);
        checkCombination();
        $('#mypa-location-container').html('');
        for (index = i = 0, ref = data.length - 1; 0 <= ref ? i <= ref : i >= ref; index = 0 <= ref ? ++i : --i) {
            location = data[index];
            orderedHours = orderOpeningHours(location.opening_hours);
            openingHoursHtml = '';
            for (day_index = j = 0; j <= 6; day_index = ++j) {
                openingHoursHtml += "<div>\n  <div class='mypa-day-of-the-week'>\n    " + DAYS_OF_THE_WEEK_TRANSLATED[day_index] + ":\n  </div>\n  <div class='mypa-opening-hours-list'>";
                ref1 = orderedHours[day_index];
                for (k = 0, len = ref1.length; k < len; k++) {
                    time = ref1[k];
                    openingHoursHtml += "<div>" + time + "</div>";
                }
                if (orderedHours[day_index].length < 1) {
                    openingHoursHtml += "<div><i>Gesloten</i></div>";
                }
                openingHoursHtml += '</div></div>';
            }
            html = "<div for='mypa-pickup-location-" + index + "' class=\"mypa-row-lg afhalen-row\">\n  <div class=\"afhalen-right\">\n    <i class='mypa-info'>\n    </i>\n  </div>\n  <div class='mypa-opening-hours'>\n    " + openingHoursHtml + "\n  </div>\n  <label for='mypa-pickup-location-" + index + "' class=\"afhalen-left\">\n    <div class=\"afhalen-check\">\n      <input id=\"mypa-pickup-location-" + index + "\" type=\"radio\" name=\"mypa-pickup-option\" value='" + (JSON.stringify(location)) + "'>\n      <label for='mypa-pickup-location-" + index + "' class='mypa-row-title'>\n        <div class=\"mypa-checkmark mypa-main\">\n          <div class=\"mypa-circle\"></div>\n          <div class=\"mypa-checkmark-stem\"></div>\n          <div class=\"mypa-checkmark-kick\"></div>\n        </div>\n      </label>\n    </div>\n    <div class='afhalen-tekst'>\n      <span class=\"mypa-highlight mypa-inline-block\">" + location.location + ", <b class='mypa-inline-block'>" + location.street + " " + location.number + "</b>,\n      <i class='mypa-inline-block'>" + (String(Math.round(location.distance / 100) / 10).replace('.', ',')) + " Km</i></span>\n    </div>\n  </label>\n</div>";
            $('#mypa-location-container').append(html);
        }
        return $('input[name=mypa-pickup-option]').bind('click', function(e) {
            var obj, selector;
            displayOtherTab();
            obj = JSON.parse($(e.currentTarget).val());
            selector = '#' + $('input[name=mypa-delivery-time]:checked').parent().find('span.mypa-address').attr('id');
            return showDefaultPickupLocation(selector, obj);
        });
    };

    orderOpeningHours = function(opening_hours) {
        var array, day, i, len;
        array = [];
        for (i = 0, len = DAYS_OF_THE_WEEK.length; i < len; i++) {
            day = DAYS_OF_THE_WEEK[i];
            array.push(opening_hours[day]);
        }
        return array;
    };

    updateDelivery = function(e) {
        var date;
        if ($('#mypa-delivery-option-check').prop('checked') !== true) {
            return;
        }
        date = $("#" + ($(e.currentTarget).prop('for')))[0].value;
        renderDeliveryOptions(date);
        return updateInputField();
    };

    renderDeliveryOptions = function(date) {
        var checked, combinatedPrice, combine, deliveryTimes, html, hvoPrice, hvoText, i, index, json, len, onlyRecipientPrice, onlyRecipientText, price, ref, ref1, time;
        $('#mypa-delivery-options').html('');
        html = '';
        deliveryTimes = window.mypa.sortedDeliverytimes[date];
        index = 0;
        for (i = 0, len = deliveryTimes.length; i < len; i++) {
            time = deliveryTimes[i];
            if (time.price_comment === 'avond') {
                time.price_comment = EVENING_DELIVERY;
            }
            price = window.mypa.settings.price[POST_NL_TRANSLATION[time.price_comment]];
            json = {
                date: date,
                time: [time]
            };
            checked = '';
            if (time.price_comment === 'standard') {
                checked = "checked";
            }
            html += "<label for=\"mypa-time-" + index + "\" class='mypa-row-subitem'>\n  <input id='mypa-time-" + index + "' type=\"radio\" name=\"mypa-delivery-time\" value='" + (JSON.stringify(json)) + "' " + checked + ">\n  <label for=\"mypa-time-" + index + "\" class=\"mypa-checkmark\">\n    <div class=\"mypa-circle mypa-circle-checked\"></div>\n    <div class=\"mypa-checkmark-stem\"></div>\n    <div class=\"mypa-checkmark-kick\"></div>\n  </label>\n  <span class=\"mypa-highlight\">" + (moment(time.start, 'HH:mm:SS').format('H.mm')) + " - " + (moment(time.end, 'HH:mm:SS').format('H.mm')) + " uur</span>";
            if (price != null) {
                html += "<span class='mypa-price'>" + price + "</span>";
            }
            html += "</label>";
            index++;
        }
        hvoPrice = window.mypa.settings.price.signed;
        hvoText = (ref = window.mypa.settings.text) != null ? ref.signed : void 0;
        if (hvoText == null) {
            hvoText = HVO_DEFAULT_TEXT;
        }
        onlyRecipientPrice = window.mypa.settings.price.only_recipient;
        onlyRecipientText = (ref1 = window.mypa.settings.text) != null ? ref1.only_recipient : void 0;
        if (onlyRecipientText == null) {
            onlyRecipientText = AO_DEFAULT_TEXT;
        }
        combinatedPrice = window.mypa.settings.price.combi_options;
        combine = onlyRecipientPrice !== 'disabled' && hvoPrice !== 'disabled' && (combinatedPrice != null);
        if (combine) {
            html += "<div class='mypa-combination-price'><span class='mypa-price mypa-hidden'>" + combinatedPrice + "</span>";
        }
        if (onlyRecipientPrice !== DISABLED) {
            html += "<label for=\"mypa-only-recipient\" class='mypa-row-subitem'>\n  <input type=\"checkbox\" name=\"mypa-only-recipient\" class=\"mypa-onoffswitch-checkbox\" id=\"mypa-only-recipient\">\n  <div class=\"mypa-switch-container\">\n    <div class=\"mypa-onoffswitch\">\n      <label class=\"mypa-onoffswitch-label\" for=\"mypa-only-recipient\">\n        <span class=\"mypa-onoffswitch-inner\"></span>\n        <span class=\"mypa-onoffswitch-switch\"></span>\n      </label>\n    </div>\n  </div>\n  <span>" + onlyRecipientText;
            if (onlyRecipientPrice != null) {
                html += "<span class='mypa-price'>" + onlyRecipientPrice + "</span>";
            }
            html += "</span></label>";
        }
        if (hvoPrice !== DISABLED) {
            html += "<label for=\"mypa-signed\" class='mypa-row-subitem'>\n  <input type=\"checkbox\" name=\"mypa-signed\" class=\"mypa-onoffswitch-checkbox\" id=\"mypa-signed\">\n  <div class=\"mypa-switch-container\">\n    <div class=\"mypa-onoffswitch\">\n      <label class=\"mypa-onoffswitch-label\" for=\"mypa-signed\">\n        <span class=\"mypa-onoffswitch-inner\"></span>\n      <span class=\"mypa-onoffswitch-switch\"></span>\n      </label>\n    </div>\n  </div>\n  <span>" + hvoText;
            if (hvoPrice) {
                html += "<span class='mypa-price'>" + hvoPrice + "</span>";
            }
            html += "</span></label>";
        }
        if (combine) {
            html += "</div>";
        }
        $('#mypa-delivery-options').html(html);
        $('.mypa-combination-price label').on('click', checkCombination);
        $('#mypa-delivery-options label.mypa-row-subitem input[name=mypa-delivery-time]').on('change', function(e) {
            var deliveryType;
            deliveryType = JSON.parse($(e.currentTarget).val())['time'][0]['price_comment'];
            if (deliveryType === MORNING_DELIVERY || deliveryType === EVENING_DELIVERY) {
                $('input#mypa-only-recipient').prop('checked', true).prop('disabled', true);
                $('label[for=mypa-only-recipient] span.mypa-price').html('incl.');
            } else {
                onlyRecipientPrice = window.mypa.settings.price.only_recipient;
                $('input#mypa-only-recipient').prop('disabled', false);
                $('label[for=mypa-only-recipient] span.mypa-price').html(onlyRecipientPrice);
            }
            return checkCombination();
        });
        if ($('input[name=mypa-delivery-time]:checked').length < 1) {
            $($('input[name=mypa-delivery-time]')[0]).prop('checked', true);
        }
        return $('div#mypa-delivery-row label').bind('click', updateInputField);
    };


    /*
     * Checks if the combination of options applies and displays this if needed.
     */

    checkCombination = function() {
        var combination, deliveryType, inclusiveOption, json;
        json = $('#mypa-delivery-options .mypa-row-subitem input[name=mypa-delivery-time]:checked').val();
        if (json != null) {
            deliveryType = JSON.parse(json)['time'][0]['price_comment'];
        }
        inclusiveOption = deliveryType === MORNING_DELIVERY || deliveryType === EVENING_DELIVERY;
        combination = $('input[name=mypa-only-recipient]').prop('checked') && $('input[name=mypa-signed]').prop('checked') && !inclusiveOption;
        $('.mypa-combination-price').toggleClass('mypa-combination-price-active', combination);
        $('.mypa-combination-price > .mypa-price').toggleClass('mypa-price-active', combination);
        $('.mypa-combination-price > .mypa-price').toggleClass('mypa-hidden', !combination);
        return $('.mypa-combination-price label .mypa-price').toggleClass('mypa-hidden', combination);
    };


    /*
     * Sets the json to the selected input field to be with the form
     */

    updateInputField = function() {
        var json;
        json = $('input[name=mypa-delivery-time]:checked').val();
        if (jquery('#mypa-input', parent.document).val() !== json) {
            jquery('#mypa-input', parent.document).val(json);
            jquery('#mypa-input', parent.document).trigger('change');
            parent.mypajQuery('#mypa-input').trigger('change');
        }
        if (jquery('#mypa-signed', parent.document).val() !== $('#mypa-signed', parent.document).prop('checked')) {
            jquery('#mypa-signed', parent.document).prop('checked', $('#mypa-signed', parent.document).prop('checked'));
            jquery('#mypa-signed', parent.document).trigger('change');
            parent.mypajQuery('#mypa-signed').trigger('change');
        }
        if (jquery('#mypa-recipient-only', parent.document).val() !== $('#mypa-recipient-only', parent.document).prop('checked')) {
            jquery('#mypa-recipient-only', parent.document).prop('checked', $('#mypa-only-recipient').prop('checked'));
            parent.mypajQuery('#mypa-recipient-only').trigger('change');
            return jquery('#mypa-recipient-only', parent.document).trigger('change');
        }
    };

}).call(this);