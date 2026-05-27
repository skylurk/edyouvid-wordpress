jQuery(document).ready(function () {
    'use strict';
    jQuery(document).on('show_variation viwpvs_show_variation', 'form.cart,.variations_form', function (event, variation) {
        if (variation.viredis_pricing_table) {
            jQuery(this).closest(viredis_single.product_content_wrap).find('.viredis-pricing-table-wrap').replaceWith(variation.viredis_pricing_table);
        } else {
            jQuery(this).closest(viredis_single.product_content_wrap).find('.viredis-pricing-table-wrap').addClass('viredis-hidden');
        }
        if (viredis_single.pd_dynamic_price) {
            jQuery(this).find('input[name="quantity"]').trigger('change');
        }
    });
    jQuery(document).on('hide_variation viwpvs_hide_variation', 'form.cart,.variations_form', function () {
        jQuery(this).closest(viredis_single.product_content_wrap).find('.viredis-pricing-table-wrap').addClass('viredis-hidden');
        if (jQuery(this).data('viredis_old_price')) {
            jQuery(this).closest(viredis_single.product_content_wrap).find('.viredis_current_price').html(jQuery(this).data('viredis_old_price'));
        }
    });
    if (viredis_single.pd_dynamic_price) {
        let dynamic_price;
        jQuery(document.body).on('wc_fragments_refreshed added_to_cart removed_from_cart', function () {
            jQuery('form.cart input[name="quantity"]').trigger('change');
        });
        jQuery(document).on('change', 'form.cart input[name="quantity"]', function () {
            if (dynamic_price) {
                dynamic_price.abort();
            }
            let form = jQuery(this).closest('form'),
                product_wrap = jQuery(this).closest(viredis_single.product_content_wrap);
            let product_price_wrap, product_id, qty = jQuery(this).val();
            if (!qty || qty === '0') {
                return;
            }
            if (form.find('[name=variation_id]').length) {
                product_id = form.find('input[name=variation_id]').val();
                product_price_wrap = form.find('.price');
            } else if (form.find('[name=product_id]').length) {
                product_id = form.find('input[name=product_id]').val();
            } else {
                product_id = form.find('[name="add-to-cart"]').val();
            }
            product_id = parseInt(product_id);
            if (!product_id) {
                return;
            }
            if (!product_price_wrap || !product_price_wrap.length) {
                if (!product_wrap.length && viredis_single.product_content_wrap === '.summary'){
                    if (form.closest('[data-block-name="woocommerce/add-to-cart-form"]').length){
                        product_wrap = form.closest('[data-block-name="woocommerce/add-to-cart-form"]').parent();
                        product_price_wrap = product_wrap.find('.wc-block-components-product-price')
                    }
                    if (!product_wrap.length){
                        return;
                    }
                }
                if (!product_price_wrap || !product_price_wrap.length) {
                    product_price_wrap = product_wrap.find('.price');
                }
            }
            if (!product_price_wrap.length) {
                return;
            }
            product_price_wrap.addClass('viredis_current_price');
            if (!form.data('viredis_old_price')) {
                form.data('viredis_old_price', product_price_wrap.html());
            }

            let ajax_data = {
                product_id: product_id,
                qty: qty,
                viredis_nonce: viredis_single.nonce,
            };
            dynamic_price =  jQuery.ajax({
                url: viredis_single.wc_ajax_url.toString().replace('%%endpoint%%', 'viredis_get_dynamic_price_html'),
                type: 'POST',
                data: ajax_data,
                dataType: 'json',
                beforeSend:function (){
                    form.find('.single_add_to_cart_button').attr('disabled', 'disabled');
                },
                success: function (response) {
                    form.find('.single_add_to_cart_button').removeAttr('disabled');
                    if (response.status === 'success') {
                        product_price_wrap.html(response.price_html);
                    } else {
                        product_price_wrap.html(form.data('viredis_old_price'));
                        form.data('viredis_old_price','');
                    }
                },
                error: function (err) {
                    console.log(err)
                }
            });
        });
    }
});