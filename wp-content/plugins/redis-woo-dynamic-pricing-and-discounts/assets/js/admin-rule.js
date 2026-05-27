'use strict';
function viredis_set_value_number(input) {
    jQuery(input).off().on('blur change', function () {
        if (!jQuery(this).val() && jQuery(this).data('allow_empty')) {
            return false;
        }
        let new_val, min = parseFloat(jQuery(this).attr('min') || 0),
            max = jQuery(this).attr('max') ? parseFloat(jQuery(this).attr('max')) : '',
            val = parseFloat(jQuery(this).val() || 0);
        new_val = val;
        if (min > val) {
            new_val = min;
        }
        if (max && max < val) {
            new_val = max;
        }
        jQuery(this).val(new_val);
    });
}

jQuery.fn.viredis_rule = function () {
    new viredis_rule_init(this);
    return this;
};
var viredis_rule_init = function (rule) {
    this.rule = rule;
    this.init();
};
viredis_rule_init.prototype.init = function () {
    let rule = this.rule;
    rule.villatheme_accordion('refresh');
    rule.find('.vi-ui.dropdown:not(.viredis-dropdown-init)').addClass('viredis-dropdown-init').dropdown();
    rule.find('.vi-ui.checkbox:not(.viredis-checkbox-init)').addClass('viredis-checkbox-init').checkbox();
    rule.find('input[type = "number"]:not(.viredis-inputnumber-init):not(.viredis-pd_bulk_qty_range-quantity)').addClass('viredis-inputnumber-init').each(function () {
        viredis_set_value_number(jQuery(this));
    });
    rule.find('.viredis-rule-wrap .viredis-condition-wrap-wrap:not(.viredis-condition-wrap-wrap-init)').each(function () {
        jQuery(this).addClass('viredis-condition-wrap-wrap-init').viredis_rule_child();
    });
    rule.find('.viredis-cart-rule-wrap').sortable({
        connectWith: ".viredis-cart-rule-wrap-" + rule.data('rule_id'),
        handle: ".viredis-condition-move",
        placeholder: "viredis-placeholder",
    });
    rule.find('.viredis-user-rule-wrap').sortable({
        connectWith: ".viredis-user-rule-wrap-" + rule.data('rule_id'),
        handle: ".viredis-condition-move",
        placeholder: "viredis-placeholder",
    });
    this.change_value(rule);
    this.checkbox(rule);
    this.dropdown(rule);
    this.add_new(rule);
    this.remove(rule);
};

viredis_rule_init.prototype.change_value = function (rule) {
};
viredis_rule_init.prototype.checkbox = function (rule) {
};
viredis_rule_init.prototype.dropdown = function (rule) {
};
viredis_rule_init.prototype.add_new = function (rule) {
    rule.find('.viredis-accordion-clone').unbind().on('click', function (e) {
        e.stopPropagation();
        let newRow = rule.clone(),
            $now = Date.now();
        newRow.attr('data-rule_id', $now);
        newRow.find('.viredis-rule-id').val($now);
        newRow.find('input, select').each(function (k, v) {
            let name = jQuery(v).data('redis_name_default') || '';
            if (name) {
                let prefix = jQuery(v).data('redis_prefix') || '',
                    item_index_default = jQuery(v).closest('.viredis-condition-wrap').data('redis_item_index') || '';
                if (!item_index_default) {
                    item_index_default = $now + k;
                    jQuery(v).closest('.viredis-condition-wrap').attr('data-redis_item_index', item_index_default);
                }
                name = name.replace(/{index_default}/gm, $now).replace(/{prefix_default}/gm, prefix).replace(/{item_index_default}/gm, item_index_default);
                if (jQuery(v).attr('name')) {
                    jQuery(v).attr('name', name);
                }
                jQuery(v).attr('data-redis_name', name);
            }
        });
        for (let i = 0; i < newRow.find('.vi-ui.dropdown').length; i++) {
            let selected = rule.find('.vi-ui.dropdown').eq(i).dropdown('get value');
            newRow.find('.vi-ui.dropdown').eq(i).dropdown('set selected', selected);
        }
        newRow.find('.viredis-condition-wrap-wrap').removeClass('viredis-condition-wrap-wrap-init');
        newRow.find('.viredis-dropdown-init').removeClass('viredis-dropdown-init');
        newRow.find('.viredis-checkbox-init').removeClass('viredis-checkbox-init');
        newRow.find('.viredis-inputnumber-init').removeClass('viredis-inputnumber-init');
        newRow.find('.select2').remove();
        newRow.find('.viredis-search-select2.viredis-search-select2-init').each(function (k, v) {
            let val = rule.find('.viredis-search-select2.viredis-search-select2-init').eq(k).val();
            jQuery(v).removeClass('viredis-search-select2-init').val(val).trigger('change');
        });
        newRow.viredis_rule();
        newRow.insertAfter(rule);
        e.stopPropagation();
    });
    rule.find('.viredis-add-condition-btn').unbind().on('click', function (e) {
        e.stopPropagation();
        jQuery(this).addClass('loading');
        let $now = Date.now(), div_append, condition_index = rule.data('rule_id'),
            condition_prefix = jQuery(this).data('rule_prefix'),
            condition_type = jQuery(this).data('rule_type');
        div_append = rule.find('.viredis-rule-wrap.viredis-' + condition_type + '-rule-wrap');
        let current = rule.closest('.viredis-tab-wrap-rule').find('.viredis-rule-new-wrap .viredis-' + condition_type + '-condition-new-wrap .viredis-condition-wrap-wrap').first();
        let html = current.html(), newRow = jQuery(current).clone();
        html = html.replace(/{index}/gm, condition_index).replace(/{prefix}/gm, condition_prefix).replace(/{item_index}/gm, $now);
        newRow.html(html);
        newRow.appendTo(div_append);
        div_append.find('.viredis-condition-wrap-wrap:not(.viredis-condition-wrap-wrap-init)').each(function () {
            jQuery(this).addClass('viredis-condition-wrap-wrap-init').viredis_rule_child();
        });
        jQuery(this).removeClass('loading');
        e.stopPropagation();
    });
};
viredis_rule_init.prototype.remove = function (rule) {
    rule.find('.viredis-accordion-remove').unbind().on('click', function (e) {
        if (jQuery('.viredis-accordion-remove').length === 1) {
            alert('You can not remove the last item.');
            return false;
        }
        if (confirm("Would you want to remove this?")) {
            rule.remove();
        }
        e.stopPropagation();
    });
};
jQuery.fn.viredis_rule_child = function () {
    new viredis_rule_child_init(this);
    return this;
};
var viredis_rule_child_init = function (condition) {
    this.condition = condition;
    this.init();
};
viredis_rule_child_init.prototype.init = function () {
    let self = this, condition = this.condition;
    condition.find('.vi-ui.dropdown:not(.viredis-dropdown-init)').addClass('viredis-dropdown-init').dropdown();
    condition.find('.vi-ui.checkbox:not(.viredis-checkbox-init)').addClass('viredis-checkbox-init').checkbox();
    condition.find('input[type = "number"]:not(.viredis-inputnumber-init)').addClass('viredis-inputnumber-init').each(function () {
        viredis_set_value_number(jQuery(this));
    });
    condition.find('.viredis-condition-wrap:not(.viredis-hidden) .viredis-search-select2').each(function () {
        self.select2(condition, jQuery(this));
    });
    this.change_value(condition);
    this.dropdown(condition);
    this.remove(condition);
};

viredis_rule_child_init.prototype.change_value = function (condition) {
    condition.find('input[type="date"]').on('change', function () {
        let val = jQuery(this).val();
        if (jQuery(this).hasClass('viredis-condition-date-from')) {
            jQuery(this).closest('.viredis-condition-wrap').find('.viredis-condition-date-to').attr('min', val);
        }
        if (jQuery(this).hasClass('viredis-condition-date-to')) {
            jQuery(this).closest('.viredis-condition-wrap').find('.viredis-condition-date-from').attr('max', val);
        }
    });
};
viredis_rule_child_init.prototype.select2 = function (condition, select) {
    let placeholder = '', action = '', close_on_select = false, min_input = 2, type_select2 = select.data('type_select2');
    switch (type_select2) {
        case 'product':
            placeholder = 'Please fill in your product title';
            action = 'viredis_search_product';
            break;
        case 'category':
            placeholder = 'Please fill in your category title';
            action = 'viredis_search_category';
            break;
        case 'attribute':
            placeholder = 'Please fill in your attribute title';
            action = 'viredis_search_attribute';
            break;
        case 'tag':
            placeholder = 'Please fill in your tag name';
            action = 'viredis_search_tag';
            break;
        case 'coupon':
            placeholder = 'Please fill in your coupon code';
            action = 'viredis_search_coupon';
            break;
        case 'country':
            placeholder = 'Please fill in your country name';
            break;
        case 'user':
            placeholder = 'Please fill in the user name';
            action = 'viredis_search_user';
            break;
        case 'user_role':
            placeholder = 'Please fill in your role name';
            break;
    }
    select.addClass('viredis-search-select2-init').select2(this.select2_params(placeholder, action, close_on_select, min_input));
};
viredis_rule_child_init.prototype.select2_params = function (placeholder = '', action = '', close_on_select = false, min_input = 2) {
    let result = {
        closeOnSelect: close_on_select,
        placeholder: placeholder,
        cache: true
    };
    if (action) {
        result['minimumInputLength'] = min_input;
        result['escapeMarkup'] = function (markup) {
            return markup;
        };
        result['ajax'] = {
            url: "admin-ajax.php?action=" + action,
            dataType: 'json',
            type: "GET",
            quietMillis: 50,
            delay: 250,
            data: function (params) {
                return {
                    keyword: params.term,
                    nonce: jQuery('#_viredis_settings_product').val() || jQuery('#_viredis_settings_cart').val() || '',
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        };
    }
    return result;
};
viredis_rule_child_init.prototype.dropdown = function (condition) {
    let self = this;
    condition.find('.viredis-condition-type').unbind().dropdown({
        onChange: function (val) {
            condition.find('.viredis-condition-wrap').addClass('viredis-hidden');
            condition.find('.viredis-condition-value-wrap-wrap input, .viredis-condition-value-wrap-wrap select').attr('name', '');
            condition.find('.viredis-condition-' + val + '-wrap').removeClass('viredis-hidden');
            condition.find('.viredis-condition-' + val + '-wrap input, .viredis-condition-' + val + '-wrap select').each(function () {
                let name = jQuery(this).data('redis_name');
                jQuery(this).attr('name', name);
                if (jQuery(this).hasClass('viredis-search-select2') && !jQuery(this).hasClass('viredis-search-select2-init')) {
                    self.select2(condition, jQuery(this));
                }
            });
        }
    });
    /*add optgroup to select box semantic*/
    condition.find('.vi-ui.dropdown.selection').has('optgroup').each(function () {
        let $menu = jQuery('<div/>').addClass('menu');
        jQuery(this).find('optgroup').each(function () {
            $menu.append("<div class=\"viredis-dropdown-header\">" + this.label + "</div></div>");
            return jQuery(this).children().each(function () {
                return $menu.append("<div class=\"item\" data-value=\"" + this.value + "\">" + this.innerHTML + "</div>");
            });
        });
        return jQuery(this).find('.menu').html($menu.html());
    });
};
viredis_rule_child_init.prototype.remove = function (condition) {
    condition.find('.viredis-revmove-condition-btn-wrap').unbind().on('click', function (e) {
        if (confirm("Would you want to remove this?")) {
            condition.remove();
        }
        e.stopPropagation();
    });
};