;
(function ($) {
    'use strict';

    $.plugin('stripe', {

        defaults: {
            publishableKey: 'YOUR-PUBLISHABLE-API-KEY',
            formSelector: '#shippingPaymentForm, form.payment',
            buttonSelector: 'button[form=shippingPaymentForm]',
            inputSelector: 'input[data-stripe]',
            errorContainerClass: 'alert is--error is--rounded',
            errorContentClass: 'alert--content',
            errorClass: 'has--error',
            animationSpeed: 400
        },

        init: function () {
            var me = this,
                opts = me.opts,
                $el = me.$el;

            me.applyDataAttributes();

            me.lib = window.Stripe;
            me.$form = $el.parents(opts.formSelector);

            me._on(window, 'ajaxComplete', $.proxy(me.onAjaxComplete, me));

            if ($el.is(":hidden")) {
                $el.empty();
                return;
            }

            me.$button = $(opts.buttonSelector);
            me.$inputs = $el.find(opts.inputSelector);
            me.$useAccount = $el.find('#stripeUseAccount');
            me.$panel = $el.find('.stripe-panel');

            me._on(me.$inputs, 'blur', $.proxy(me.onValidateInput, me));
            me._on(me.$form, 'submit', $.proxy(me.onSubmit, me));
            me._on(me.$useAccount, 'change', $.proxy(me.onEdit, me));

            if (me.$useAccount.is(':checked')) {
                me.$panel.slideUp();
                me.$inputs.attr('required', false);
            }

            me.lib.setPublishableKey(me.opts.publishableKey);
        },

        onValidateInput: function (event) {
            var me = this,
                $el = $(event.currentTarget),
                id = $el.attr('data-stripe'),
                val = $el.val();

            if (!val) {
                me.setFieldAsError($el);
            } else if (!me.validateStripe(id, val)) {
                me.setFieldAsError($el);
            } else {
                me.setFieldAsSuccess($el);
            }
        },

        validateStripe: function (id, val) {
            var me = this,
                validateFunc,
                val2;
            switch (id) {
                case 'exp-month':
                case 'exp-year':
                    validateFunc = me.lib.validateExpiry;
                    if (id == 'exp-year') {
                        val2 = val;
                        val = me.$el.find('input[data-stripe=exp-month]').val();
                    } else {
                        val2 = me.$el.find('input[data-stripe=exp-year]').val();
                    }
                    if (val > 12 || val < 1) {
                        return false;
                    }
                    if (!val2) {
                        return true;
                    }
                    break;
                case 'cvc':
                    validateFunc = me.lib.validateCVC;
                    break;
                case 'number':
                    validateFunc = me.lib.validateCardNumber;
                    break;
                default:
                    return false;
            }
            return validateFunc(val, val2);
        },

        setFieldAsError: function ($el) {
            var me = this;
            $el.addClass(me.opts.errorClass);
        },

        setFieldAsSuccess: function ($el) {
            var me = this;
            $el.removeClass(me.opts.errorClass);
        },

        onSubmit: function () {
            var me = this;

            if (me.$useAccount.is(':checked')) {
                me.$el.empty();
                return;
            }

            me.$button.attr('disabled', 'disabled');
            me.lib.createToken(me.$form, $.proxy(me.stripeResponseHandler, me));
            return false;
        },

        onEdit: function () {
            var me = this,
                checked = me.$useAccount.is(':checked'),
                func = checked ? 'slideUp' : 'slideDown';
            me.$panel[func](me.opts.animationSpeed, function () {
                me.$inputs.attr('required', checked ? false : 'required');
            });
        },

        onAjaxComplete: function (event, xhr, settings) {
            var me = this;
            if (settings.url === me.$form.attr('action')) {
                me._destroy();
                $('*[data-stripe-payment="true"]').stripe();
            }
        },

        stripeResponseHandler: function (status, response) {
            var me = this,
                opts = me.opts;

            // remove old messages
            me.$el.find('.error').remove();

            if (response.error) {
                var $container = $('<div />').addClass(opts.errorContainerClass),
                    $content = $('<div />').addClass(opts.errorContentClass).text(response.error.message);

                $container.append($content);
                me.$el.append($container);

                me.$button.attr('disabled', false);
            } else {
                // response contains id and card, which contains additional card details
                var token = response.id;
                // Insert the token into the form so it gets submitted to the server
                me.$form.append($('<input type="hidden" name="stripeToken" />').val(token));
                // and submit
                me.$form.get(0).submit();
            }
        }
    });
})(jQuery);
