var Checkout = Class.create();
Checkout.prototype = {
    initialize: function(accordion, config){
        this.accordion = accordion;
        this.saveApi = config.saveApi;
        this.steps = ['information', 'shipping', 'payment'];
        this.currentSection = this.steps[0];

        //this.form = form;
        //if ($(this.form)) {
        //    $(this.form).observe('submit', function(event){this.save();Event.stop(event);}.bind(this));
        //}
        //this.onAddressLoad = this.fillForm.bindAsEventListener(this);
        this.onSave = this.processResponse.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
        this.onFailure = this.ajaxFailure.bind(this);
        this.loadWaiting = false;

        this.accordion.sections.each(function(section) {
            Event.observe($(section).down('.step-title'), 'click', this._onSectionClick.bindAsEventListener(this));
        }.bind(this));

        Event.observe(window, 'hashchange', this._onHashChange.bindAsEventListener(this));
    },

    /**
     * Section header click handler
     *
     * @param event
     */
    _onSectionClick: function(event) {
        var section = Event.element(event).up('.section');
        if (section.hasClassName('allow')) {
            Event.stop(event);
            var targetStep = section.readAttribute('id').replace('opc-', '');
            var i = this.steps.indexOf(targetStep);
            var j = this.steps.indexOf(this.currentSection);

            window.history.go(i-j);

            //this.gotoSection(section.readAttribute('id').replace('opc-', ''));
            return false;
        }
    },

    _onHashChange: function(event) {
        var parts = window.location.hash.split('-');

        if (parts.length >= 2 && (parts[0] == 'step' || parts[0] == '#step')) {
            var step = parts[1];

            var section = $('opc-' + step);

            if (section && section != this.currentSection) {
                this.gotoSection(parts[1]);
            }

            Event.stop(event);
        } else if (window.location.hash == '' || window.location.hash == '#') {
            this.gotoSection(this.steps[0]);

            Event.stop(event);
        }
    },

    newAddress: function(type, isNew){
        var form = type + '-new-address-form';

        if (isNew) {
            this.resetSelectedAddress(type);
            Element.show(form);
        } else {
            Element.hide(form);
        }
    },

    resetSelectedAddress: function(type){
        var selectElement = $(type+'-address-select')
        if (selectElement) {
            selectElement.value='';
        }
    },

    switchPayment: function(method){
        if (this.currentMethod && $('payment_form_'+this.currentMethod)) {
            this.changeVisible(this.currentMethod, true);
            $('payment_form_'+this.currentMethod).fire('payment-method:switched-off', {method_code : this.currentMethod});
        }
        if ($('payment_form_'+method)){
            this.changeVisible(method, false);
            $('payment_form_'+method).fire('payment-method:switched', {method_code : method});
        } else {
            //Event fix for payment methods without form like "Check / Money order"
            document.body.fire('payment-method:switched', {method_code : method});
        }
        if (method) {
            this.lastUsedMethod = method;
        }
        this.currentMethod = method;
    },

    gotoSection: function(section)
    {
        if (section == this.currentSection) {
            return;
        }

        var sectionElement = $('opc-'+section);
        sectionElement.addClassName('allow');
        this.accordion.openSection('opc-'+section);

        hide = false;

        for (var i = 0; i < this.steps.length; ++i) {
            var step = this.steps[i];
            if (step == section) hide = true;

            var element = $('checkout-step-' + step + '-summary');

            if (element) {
                if (hide) {
                    Element.hide(element);
                } else {
                    Element.show(element);
                }
            }
        }

        this.currentSection = section;

        if (section == this.steps[0]) {
            if (window.location.hash != '' && window.location.hash != '#') {
                window.location.hash = '';
            }
        } else {
            window.location.hash = 'step-' + section;
            var scroll = 'opc-' + section;
        }

        if (window.ga) {
            window.ga('send', 'pageview', {
                'page': window.location.pathname + window.location.search  + window.location.hash
            });
            if(typeof scroll ==  'undefined'){
                $(window).scrollTop();
            }else{
                $(scroll).scrollTo();
            }

        }
    },

    save: function(step) {
        if (this.loadWaiting!=false) return;

        var formElement = $('co-' + step + '-form');
        var saveUrl = this.saveApi + 'save' + step;
        var validator = new Validation(formElement);

        if (validator.validate()) {
            var parameters = Form.serialize(formElement, true);
            var event = Event.fire(formElement, 'checkout:save', { parameters: parameters });

            if (event.defaultPrevented) {
                return false;
            }

            // Update parameters from event
            parameters = event.memo.parameters;

            this.setLoadWaiting(step);

            var request = new Ajax.Request(
                saveUrl,
                {
                    method: 'post',
                    onComplete: this.onComplete,
                    onSuccess: this.onSave,
                    onFailure: this.onFailure,
                    parameters: parameters
                }
            );
        }
    },

    back: function(){
        if (this.loadWaiting) return;

        history.go(-1);

        /*for (section in this.accordion.sections) {
            var prevIndex = parseInt(section)-1;
            if (this.accordion.sections[section].id == this.accordion.currentSection && this.accordion.sections[prevIndex]){
                this.gotoSection(this.accordion.sections[prevIndex].readAttribute('id').replace('opc-', ''));
                return;
            }
        }*/
    },

    processResponse: function(transport) {
        if (transport && transport.responseText){
            try{
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
        }

        if (response.error){
            if ((typeof response.message) == 'string') {
                alert(response.message);
            } else {
                alert(response.message.join("\n"));
            }

            return false;
        }

        if (response.update_summary) {
            for (section in response.update_summary) {
                var element = $('checkout-step-'+section+'-summary');
                element.update(response.update_summary[section]);
            }
        }

        if (response.update_step) {
            for (section in response.update_step) {
                var element = $('checkout-step-'+section+'');
                element.update(response.update_step[section]);
            }
        }

        if (response.update_sidebar) {
            var element = $('checkout-progress-wrapper');
            element.update(response.update_sidebar);
        }

        if (response.goto_section) {
            this.gotoSection(response.goto_section);
            return true;
        }
        if (response.redirect) {
            location.href = response.redirect;
            return true;
        }
        return false;
    },

    resetLoadWaiting: function() {
        this.setLoadWaiting(false);
    },

    ajaxFailure: function() {

    },

    setLoadWaiting: function(step, keepDisabled) {
        if (step) {
            if (this.loadWaiting) {
                this.setLoadWaiting(false);
            }
            var container = $(step+'-buttons-container');
            container.addClassName('disabled');
            container.setStyle({opacity:.5});
            this._disableEnableAll(container, true);
            Element.show(step+'-please-wait');
        } else {
            if (this.loadWaiting) {
                var container = $(this.loadWaiting+'-buttons-container');
                var isDisabled = (keepDisabled ? true : false);
                if (!isDisabled) {
                    container.removeClassName('disabled');
                    container.setStyle({opacity:1});
                }
                this._disableEnableAll(container, isDisabled);
                Element.hide(this.loadWaiting+'-please-wait');
            }
        }
        this.loadWaiting = step;
    },

    _disableEnableAll: function(element, isDisabled) {
        var descendants = element.descendants();
        for (var k in descendants) {
            descendants[k].disabled = isDisabled;
        }
        element.disabled = isDisabled;
    },

    changeVisible: function(method, mode) {
        var block = 'payment_form_' + method;
        [block + '_before', block, block + '_after'].each(function(el) {
            element = $(el);
            if (element) {
                element.style.display = (mode) ? 'none' : '';
                element.select('input', 'select', 'textarea', 'button').each(function(field) {
                    field.disabled = mode;
                });
            }
        });
    }
}


var ShippingMethod = Class.create();
ShippingMethod.prototype = {
    initialize: function(form, saveUrl){
        this.form = form;
        if ($(this.form)) {
            $(this.form).observe('submit', function(event){this.save();Event.stop(event);}.bind(this));
        }
        this.saveUrl = saveUrl;
        this.validator = new Validation(this.form);
        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    validate: function() {
        var methods = document.getElementsByName('shipping_method');
        if (methods.length==0) {
            alert(Translator.translate('Your order cannot be completed at this time as there is no shipping methods available for it. Please make necessary changes in your shipping address.').stripTags());
            return false;
        }

        if(!this.validator.validate()) {
            return false;
        }

        for (var i=0; i<methods.length; i++) {
            if (methods[i].checked) {
                return true;
            }
        }
        alert(Translator.translate('Please specify shipping method.').stripTags());
        return false;
    },

    save: function(){

        if (checkout.loadWaiting!=false) return;
        if (this.validate()) {
            checkout.setLoadWaiting('shipping-method');
            var request = new Ajax.Request(
                this.saveUrl,
                {
                    method:'post',
                    onComplete: this.onComplete,
                    onSuccess: this.onSave,
                    onFailure: checkout.ajaxFailure.bind(checkout),
                    parameters: Form.serialize(this.form)
                }
            );
        }
    },

    resetLoadWaiting: function(transport){
        checkout.setLoadWaiting(false);
    },

    nextStep: function(transport){
        if (transport && transport.responseText){
            try{
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
        }

        if (response.error) {
            alert(response.message);
            return false;
        }

        if (response.update_section) {
            $('checkout-'+response.update_section.name+'-load').update(response.update_section.html);
        }

        payment.initWhatIsCvvListeners();

        if (response.goto_section) {
            checkout.gotoSection(response.goto_section, true);
            checkout.reloadProgressBlock();
            return;
        }

        if (response.payment_methods_html) {
            $('checkout-payment-method-load').update(response.payment_methods_html);
        }

        checkout.setShippingMethod();
    }
}
