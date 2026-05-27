jQuery(document).ready(function () {
    'use strict';
    viredis_init();
    jQuery('.viredis-cart-discount-rule-wrap .viredis-accordion-wrap:not(.viredis-accordion-wrap-init)').each(function () {
        jQuery(this).addClass('viredis-accordion-wrap-init').viredis_rule();
    });
    jQuery(document).on('click', '.viredis-save', function () {
        let button = jQuery(this);
        button.addClass('loading');
        jQuery('.viredis-wrap-warning').removeClass('viredis-wrap-warning');
        let z, v, accordion = jQuery('.viredis-accordion-wrap'),
            name_arr = jQuery('input[name="cart_name[]"]'),
            title_arr = jQuery('input[name="cart_discount_title[]"]');
        let condition_arr = accordion.find('.viredis-condition-wrap:not(.viredis-hidden) .viredis-condition-value');
        if (!jQuery('.viredis-cart_combine_all_discount_title').val()){
            alert("'Combine all discounts title' cannot be empty!");
            jQuery('[data-tab="general"]').trigger('click');
            jQuery('.viredis-cart_combine_all_discount_title').addClass('viredis-wrap-warning').focus();
            button.removeClass('loading');
            return false;
        }
        for (z = 0; z < name_arr.length; z++) {
            if (!name_arr.eq(z).val()) {
                alert("Rule name cannot be empty!");
                name_arr.eq(z).addClass('viredis-wrap-warning');
                accordion.eq(z).addClass('viredis-wrap-warning');
                button.removeClass('loading');
                return false;
            }
        }
        for (z = 0; z < name_arr.length; z++) {
            for (v = z + 1; v < name_arr.length; v++) {
                if (name_arr.eq(z).val() === name_arr.eq(v).val()) {
                    alert("Rule name are unique!");
                    name_arr.eq(v).addClass('viredis-wrap-warning');
                    accordion.eq(v).addClass('viredis-wrap-warning');
                    button.removeClass('loading');
                    return false;
                }
            }
        }
        for (z = 0; z < title_arr.length; z++) {
            if (!title_arr.eq(z).val()) {
                alert("Discount title cannot be empty!");
                title_arr.eq(z).addClass('viredis-wrap-warning');
                accordion.eq(z).addClass('viredis-wrap-warning');
                button.removeClass('loading');
                return false;
            }
        }
        for (z = 0; z < title_arr.length; z++) {
            for (v = z + 1; v < title_arr.length; v++) {
                if (title_arr.eq(z).val().trim() === title_arr.eq(v).val().trim()) {
                    alert("Discount title are unique!");
                    title_arr.eq(v).addClass('viredis-wrap-warning');
                    accordion.eq(v).addClass('viredis-wrap-warning');
                    button.removeClass('loading');
                    return false;
                }
            }
        }
        for (z = 0; z < condition_arr.length; z++) {
            if (!condition_arr.eq(z).val()) {
                if (condition_arr.eq(z).hasClass('viredis-condition-date-from') || condition_arr.eq(z).hasClass('viredis-condition-date-to')) {
                    continue;
                }
                // if (condition_arr.eq(z).hasClass('viredis-condition-date-from')) {
                //     if (condition_arr.eq(z).closest('.viredis-condition-wrap').find('.viredis-condition-date-to').val()) {
                //         continue;
                //     }
                // }
                // if (condition_arr.eq(z).hasClass('viredis-condition-date-to')) {
                //     if (condition_arr.eq(z).closest('.viredis-condition-wrap').find('.viredis-condition-date-from').val()) {
                //         continue;
                //     }
                // }
                if (condition_arr.eq(z).data('allow_empty')) {
                    continue;
                }
                alert("Condition cannot be empty!");
                condition_arr.eq(z).closest('.viredis-condition-wrap').addClass('viredis-wrap-warning');
                condition_arr.eq(z).closest('.viredis-accordion-wrap').addClass('viredis-wrap-warning');
                button.removeClass('loading');
                return false;
            }
        }
        button.attr('type', 'submit');
    });
});

function viredis_init() {
    jQuery('.vi-ui.vi-ui-main.tabular.menu .item').vi_tab({
        history: true,
        historyType: 'hash'
    });
    jQuery('.tab:not(.viredis-tab-wrap-rule) .vi-ui.checkbox').unbind().checkbox();
    jQuery('.tab:not(.viredis-tab-wrap-rule) .vi-ui.dropdown').unbind().dropdown();
    viredis_set_value_number(jQuery('.tab:not(.viredis-tab-wrap-rule) input[type = "number"]'));
    jQuery('.tab:not(.viredis-tab-wrap-rule) input[type="checkbox"]').unbind().on('change', function () {
        if (jQuery(this).prop('checked')) {
            jQuery(this).parent().find('input[type="hidden"]').val(1);
            if (jQuery(this).hasClass('viredis-cart_limit_discount-checkbox')) {
                jQuery('.viredis-cart_limit_discount-enable').removeClass('viredis-hidden');
            }
            if (jQuery(this).hasClass('viredis-cart_combine_all_discount-checkbox')) {
                jQuery('.viredis-cart_combine_all_discount-wrap-enable').removeClass('viredis-hidden');
            }
        } else {
            jQuery(this).parent().find('input[type="hidden"]').val(0);
            if (jQuery(this).hasClass('viredis-cart_limit_discount-checkbox')) {
                jQuery('.viredis-cart_limit_discount-enable').addClass('viredis-hidden');
            }
            if (jQuery(this).hasClass('viredis-cart_combine_all_discount-checkbox')) {
                jQuery('.viredis-cart_combine_all_discount-wrap-enable').addClass('viredis-hidden');
            }
        }
    });
    jQuery('.viredis-cart_apply_rule').unbind().dropdown({
        onChange: function (val) {
            if (val !== '0') {
                jQuery('.viredis-cart_combine_all_discount-wrap').addClass('viredis-hidden');
            } else {
                jQuery('.viredis-cart_combine_all_discount-wrap').removeClass('viredis-hidden');
                if (!jQuery('.viredis-cart_combine_all_discount-checkbox').prop('checked')) {
                    jQuery('.viredis-cart_combine_all_discount-wrap-enable').addClass('viredis-hidden');
                }
            }
        }
    });
    jQuery('.viredis-cart_limit_discount_type').unbind().dropdown({
        onChange: function (val) {
            if (val === '0') {
                jQuery('#viredis-cart_limit_discount_value').attr('max', 100);
                if (jQuery('#viredis-cart_limit_discount_value').val() > 100) {
                    jQuery('#viredis-cart_limit_discount_value').val(100);
                }
            } else {
                jQuery('#viredis-cart_limit_discount_value').attr('max', '');
            }
        }
    });
    jQuery('.viredis-cart-discount-rule-wrap').sortable({
        connectWith: ".viredis-cart-discount-rule-wrap",
        handle: ".viredis-accordion-info",
        cancel: ".viredis-cart_active-wrap,.viredis-accordion-action,.title,.content",
        placeholder: "viredis-placeholder",
    });

}

viredis_rule_init.prototype.change_value = function (rule) {
    rule.find('.viredis-cart_name').on('keyup', function () {
        let val = jQuery(this).val();
        jQuery(this).closest('.viredis-accordion-wrap').find('.viredis-accordion-name').html(val);
    });
    rule.find('input[type="checkbox"]').unbind().on('change', function () {
        if (jQuery(this).prop('checked')) {
            jQuery(this).parent().find('input[type="hidden"]').val('1');
        } else {
            jQuery(this).parent().find('input[type="hidden"]').val('');
        }
    });

    rule.find('input[type="date"]').on('change', function () {
        let val = jQuery(this).val();
        if (jQuery(this).hasClass('viredis-condition-date-from')) {
            jQuery(this).closest('.content').find('.viredis-condition-date-to').attr('min', val);
        }
        if (jQuery(this).hasClass('viredis-condition-date-to')) {
            jQuery(this).closest('.content').find('.viredis-condition-date-from').attr('max', val);
        }
    });
    rule.find('.viredis-cart_discount_type').unbind().dropdown({
        onChange: function (val) {
            if (val === '0') {
                jQuery('.viredis-cart_discount_value').attr('max', 100);
                if (jQuery('.viredis-cart_discount_value').val() > 100) {
                    jQuery('.viredis-cart_discount_value').val(100);
                }
            } else {
                jQuery('.viredis-cart_discount_value').attr('max', '');
            }
        }
    });
};