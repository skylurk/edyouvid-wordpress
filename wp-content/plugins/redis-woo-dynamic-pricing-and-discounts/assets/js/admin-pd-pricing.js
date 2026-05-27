jQuery(document).ready(function () {
    'use strict';
    jQuery('.vi-ui.menu .item').vi_tab({
        history: true,
        historyType: 'hash'
    });
    jQuery('.tab:not(.viredis-tab-wrap-rule) .vi-ui.checkbox').off().checkbox();
    jQuery('.tab:not(.viredis-tab-wrap-rule) .vi-ui.dropdown').off().dropdown();
    viredis_set_value_number(jQuery('.viredis-tab-wrap-general input[type = "number"]'));

    jQuery('.viredis-pd_limit_discount_type').off().dropdown({
        onChange: function (val) {
            if (val === '0') {
                jQuery('#viredis-pd_limit_discount_value').attr('max', 100);
                if (jQuery('#viredis-pd_limit_discount_value').val() > 100) {
                    jQuery('#viredis-pd_limit_discount_value').val(100);
                }
            } else {
                jQuery('#viredis-pd_limit_discount_value').attr('max', '');
            }
        }
    });
    jQuery('.tab:not(.viredis-tab-wrap-rule) input[type="checkbox"]').unbind().on('change', function () {
        if (jQuery(this).prop('checked')) {
            jQuery(this).parent().find('input[type="hidden"]').val(1);
            if (jQuery(this).hasClass('viredis-pd_limit_discount-checkbox')) {
                jQuery('.viredis-pd_limit_discount-enable').removeClass('viredis-hidden');
            }
            if (jQuery(this).hasClass('viredis-pd_pricing_table-checkbox')) {
                jQuery('.viredis-pd_pricing_table-enable').removeClass('viredis-hidden');
            }
            if (jQuery(this).hasClass('viredis-pd_change_price_on_list-checkbox')) {
                jQuery('.viredis-pd_change_price_on_list-enable').removeClass('viredis-hidden');
            }
            if (jQuery(this).hasClass('viredis-pd_change_price_on_single-checkbox')) {
                jQuery('.viredis-pd_change_price_on_single-enable').removeClass('viredis-hidden');
            }
        } else {
            jQuery(this).parent().find('input[type="hidden"]').val(0);
            if (jQuery(this).hasClass('viredis-pd_limit_discount-checkbox')) {
                jQuery('.viredis-pd_limit_discount-enable').addClass('viredis-hidden');
            }
            if (jQuery(this).hasClass('viredis-pd_pricing_table-checkbox')) {
                jQuery('.viredis-pd_pricing_table-enable').addClass('viredis-hidden');
            }
            if (jQuery(this).hasClass('viredis-pd_change_price_on_list-checkbox')) {
                jQuery('.viredis-pd_change_price_on_list-enable').addClass('viredis-hidden');
            }
            if (jQuery(this).hasClass('viredis-pd_change_price_on_single-checkbox')) {
                jQuery('.viredis-pd_change_price_on_single-enable').addClass('viredis-hidden');
            }
        }
    });
    jQuery('.viredis-pricing-rule-wrap').sortable({
        connectWith: ".viredis-pricing-rule-wrap",
        handle: ".viredis-accordion-info",
        cancel: ".viredis-pd_active-wrap,.viredis-accordion-action,.title,.content",
        placeholder: "viredis-placeholder",
    });

    jQuery('.viredis-pd_display_price').off().dropdown({
        onChange: function (val) {
            if (val === '0') {
                jQuery('.viredis-change-display-price-enable').addClass('viredis-hidden');
            } else {
                jQuery('.viredis-change-display-price-enable').removeClass('viredis-hidden');
                if (!jQuery('.viredis-pd_change_price_on_list-checkbox').prop('checked')) {
                    jQuery('.viredis-pd_change_price_on_list-enable').addClass('viredis-hidden');
                }
                if (!jQuery('.viredis-pd_change_price_on_single-checkbox').prop('checked')) {
                    jQuery('.viredis-pd_change_price_on_single-enable').addClass('viredis-hidden');
                }
                if (val === 'new_price') {
                    jQuery('.viredis-pd_on_sale_badge-enable').addClass('viredis-hidden');
                }
            }
        }
    });
    jQuery('.viredis-pricing-rule-wrap .viredis-accordion-wrap:not(.viredis-accordion-wrap-init)').each(function () {
        jQuery(this).addClass('viredis-accordion-wrap-init').viredis_rule();
    });
    jQuery(document).on('click', '.viredis-save', function () {
        let button = jQuery(this);
        button.addClass('loading');
        jQuery('.viredis-wrap-warning').removeClass('viredis-wrap-warning');
        let z, v, accordion = jQuery('.viredis-accordion-wrap'),
            name_arr = jQuery('input[name="pd_name[]"]'),
            pricing_arr = jQuery('[name="pd_type[]"]');
        let condition_arr = accordion.find('.viredis-condition-wrap:not(.viredis-hidden) .viredis-condition-value');
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
        for (z = 0; z < pricing_arr.length; z++) {
            let error_message, pricing_type = pricing_arr.eq(z).val(), current_accordion = accordion.eq(z);
            switch (pricing_type) {
                case 'bulk_qty':
                    let bulk_qty = current_accordion.find('.viredis-pd_bulk_qty_range-content-wrap .viredis-pd_bulk_qty_range-quantity');
                    for (v = 0; v < bulk_qty.length; v++) {
                        let val = parseInt(bulk_qty.eq(v).val()),
                            pre_qty = v > 0 ? parseInt(bulk_qty.eq(v - 1).val()) : -1;
                        if (bulk_qty.eq(v).hasClass('viredis-pd_bulk_qty_range-from')) {
                            if (pre_qty === 0 || val < pre_qty) {
                                error_message = 'Quantity range cannot overlap!';
                                break;
                            } else if (val === pre_qty) {
                                error_message = 'Quantity must be increase!';
                                break;
                            } else if (v && val > pre_qty + 1) {
                                error_message = 'Quantity to must be increase!';
                                break;
                            }
                        } else {
                            if ((val > 0 && val < pre_qty)) {
                                error_message = 'Quantity must be increase!';
                                break;
                            }
                        }
                    }
                    if (error_message) {
                        alert(error_message);
                        current_accordion.addClass('viredis-wrap-warning');
                        bulk_qty.eq(v).addClass('viredis-wrap-warning');
                        bulk_qty.eq(v - 1).addClass('viredis-wrap-warning');
                        button.removeClass('loading');
                        return false;
                    }
                    break;
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
        jQuery('.viredis-pd_bulk_qty_range-quantity').prop('disabled', false);
        button.attr('type', 'submit');
    });
});


viredis_rule_init.prototype.change_value = function (rule) {
    rule.find('.viredis-pd-rule-wrap').sortable({
        connectWith: ".viredis-pd-rule-wrap-" + rule.data('rule_id'),
        handle: ".viredis-condition-move",
        placeholder: "viredis-placeholder",
    });
    rule.find('.viredis-pd_type-wrap .viredis-condition-wrap-wrap:not(.viredis-condition-wrap-wrap-init)').each(function () {
        jQuery(this).addClass('viredis-condition-wrap-wrap-init').viredis_product_prices();
    });
    rule.find('.viredis-pd_bulk_qty_range-add-range-btn').off().on('click', function (e) {
        e.stopPropagation();
        rule.find('.viredis-wrap-warning').removeClass('viredis-wrap-warning');
        let last_range = rule.find('.viredis-pd_type-wrap .viredis-condition-wrap-wrap').last();
        if (!last_range.find('.viredis-pd_bulk_qty_range-to').val()) {
            alert('Please enter Qty to value');
            last_range.find('.viredis-pd_bulk_qty_range-to').addClass('viredis-wrap-warning');
            return false;
        }
        let newRow = last_range.clone();
        newRow.find('.viredis-dropdown-init').removeClass('viredis-dropdown-init');
        newRow.find('.viredis-inputnumber-init').removeClass('viredis-inputnumber-init');
        newRow.addClass('viredis-condition-wrap-wrap-init').viredis_product_prices();
        newRow.find('.viredis-pd_bulk_qty_range-from').prop('disabled', true).val(parseFloat(last_range.find('.viredis-pd_bulk_qty_range-to').val()) + 1);
        newRow.find('.viredis-pd_bulk_qty_range-to').val('');
        newRow.insertAfter(last_range);
        e.stopPropagation();
    });
    rule.find('.viredis-pd_name').on('keyup', function () {
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
};
viredis_rule_init.prototype.dropdown = function (rule) {
    rule.find('.viredis-pd_type').unbind().dropdown({
        onChange: function (val) {
            rule.find('.viredis-pd_type-wrap').addClass('viredis-hidden');
            rule.find('.viredis-pd_type-wrap.viredis-pd_type-' + val).removeClass('viredis-hidden');
        }
    });
    rule.find('.viredis-pd_basic_type').unbind().dropdown({
        onChange: function (val) {
            if (val === '0') {
                rule.find('.viredis-pd_basic_price').attr('max', 100);
                if (rule.find('.viredis-pd_basic_price').val() > 100) {
                    rule.find('.viredis-pd_basic_price').val(100);
                }
            } else {
                rule.find('.viredis-pd_basic_price').attr('max', '');
            }
        }
    });
};
jQuery.fn.viredis_product_prices = function () {
    new viredis_product_prices_init(this);
    return this;
};
var viredis_product_prices_init = function (rule) {
    this.rule = rule;
    this.init();
};
viredis_product_prices_init.prototype.init = function () {
    let rule = this.rule;
    if (rule.prev('.viredis-condition-wrap-wrap').length) {
        rule.find('.viredis-pd_bulk_qty_range-from').prop('disabled', true);
    }
    rule.find('.vi-ui.dropdown:not(.viredis-dropdown-init)').addClass('viredis-dropdown-init').dropdown();
    rule.find('input[type =number]:not(.viredis-inputnumber-init):not(viredis-pd_bulk_qty_range-quantity)').addClass('viredis-inputnumber-init').each(function () {
        viredis_set_value_number(jQuery(this));
    });
    rule.find('.viredis-pd_bulk_qty_range-quantity').each(function (k, v) {
        jQuery(this).on('change', function (e) {
            e.preventDefault();
            e.stopPropagation();
            let pre_rule = rule.prev('.viredis-condition-wrap-wrap'),
                next_rule = rule.next('.viredis-condition-wrap-wrap');
            if (!jQuery(this).val() && jQuery(this).data('allow_empty') && !next_rule.length) {
                return false;
            }
            let new_val, min, max, val = parseFloat(jQuery(this).val() || 0);
            new_val = val;
            if (jQuery(this).hasClass('viredis-pd_bulk_qty_range-from')) {
                min = parseFloat(pre_rule.find('.viredis-pd_bulk_qty_range-to').val()) + 1;
                max = rule.find('.viredis-pd_bulk_qty_range-to').val() ? parseFloat(rule.find('.viredis-pd_bulk_qty_range-from').val()) - 1 : '';
            } else {
                min = parseFloat(rule.find('.viredis-pd_bulk_qty_range-from').val());
                max = next_rule.length && next_rule.find('.viredis-pd_bulk_qty_range-to').val() ? parseFloat(next_rule.find('.viredis-pd_bulk_qty_range-to').val()) - 1 : '';
            }
            max = max && max < 1 ? 1 : max;
            min = min < max ? min : max;
            if (min > val) {
                new_val = min;
                alert('The quantity must not be less than ' + min);
            } else if (max && max < val) {
                new_val = max;
                alert('The quantity must not be greater than ' + max);
            }
            jQuery(this).val(new_val);
            if (jQuery(this).hasClass('viredis-pd_bulk_qty_range-to')) {
                next_rule.find('.viredis-pd_bulk_qty_range-from').val(new_val + 1)
            }
        });
    });
    rule.find('.viredis-product-price-type').unbind().dropdown({
        onChange: function (val) {
            let input_discount = jQuery(this).parent().find('input[type="number"]');
            if (val === '0') {
                input_discount.attr('max', 100);
                if (input_discount.val() > 100) {
                    input_discount.val(100);
                }
            } else {
                input_discount.attr('max', '');
            }
        }
    });
    rule.find('.viredis-product-price-remove').unbind().on('click', function (e) {
        if (rule.parent().find('.viredis-product-price-remove').length === 1) {
            alert('You can not remove the last item.');
            return false;
        }
        if (confirm("Would you want to remove this?")) {
            let next = rule.next('.viredis-condition-wrap-wrap');
            next.find('.viredis-pd_bulk_qty_range-from').val(parseFloat(rule.find('.viredis-pd_bulk_qty_range-from').val()));
            rule.remove();
        }
        e.stopPropagation();
    });
};