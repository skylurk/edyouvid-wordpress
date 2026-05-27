<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_Admin_Settings {
	public function __construct() {
		$ajax_events = array(
			'viredis_search_product',
			'viredis_search_category',
			'viredis_search_attribute',
			'viredis_search_tag',
			'viredis_search_coupon',
			'viredis_search_user',
		);
		foreach ( $ajax_events as $event ) {
			add_action( 'wp_ajax_' . $event, array( $this, $event ) );
		}
		$update_prefix = array(
			'woocommerce_after_order_object_save',
			'woocommerce_trash_order',
			'woocommerce_delete_order',
		);
		foreach ( $update_prefix as $action ) {
			add_action( $action, array( $this, 'session_update_prefix' ) );
		}
	}
	public function session_update_prefix(){
		VIREDIS_DATA::set_data_prefix();
		VIREDIS_DATA::set_data_prefix('cart');
	}
	public function viredis_search_user() {
		if (!check_ajax_referer('_viredis_settings_cart_action','nonce', false) && !check_ajax_referer('_viredis_settings_product_action', 'nonce', false)){
			die();
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}
		$keyword = isset( $_REQUEST['keyword'] ) ? sanitize_text_field( $_REQUEST['keyword'] ) : '';
		if ( empty( $keyword ) ) {
			die();
		}
		$arg       = array(
			'search'         => '*' . $keyword . '*',
			'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
		);
		$woo_users = get_users( $arg );
		$items     = array();
		if ( $woo_users && is_array( $woo_users ) && count( $woo_users ) ) {
			foreach ( $woo_users as $user ) {
				$items[] = array(
					'id'   => $user->ID,
					'text' => $user->display_name,
				);
			}
		}
		wp_reset_postdata();
		wp_send_json( $items );
		die;
	}
	public function viredis_search_coupon() {
		if (!check_ajax_referer('_viredis_settings_cart_action','nonce', false) && !check_ajax_referer('_viredis_settings_product_action', 'nonce', false)){
			die();
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}
		$keyword = isset( $_REQUEST['keyword'] ) ? sanitize_text_field( $_REQUEST['keyword'] ) : '';
		if ( empty( $keyword ) ) {
			die();
		}
		$arg       = array(
			'post_status'    => 'publish',
			'post_type'      => array( 'shop_coupon' ),
			'posts_per_page' => 50,
			's'              => $keyword
		);
		$the_query = new WP_Query( $arg );
		$items     = array();
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$coupon_id   = get_the_ID();
				$coupon_code = wc_get_coupon_code_by_id( $coupon_id );
				$item        = array(
					'id'   => strtolower( $coupon_code ),
					'text' => $coupon_code
				);
				$items[]     = $item;
			}
		}
		wp_reset_postdata();
		wp_send_json( $items );
	}
	public function viredis_search_tag() {
		if (!check_ajax_referer('_viredis_settings_cart_action','nonce', false) && !check_ajax_referer('_viredis_settings_product_action', 'nonce', false)){
			die();
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}
		$keyword = isset( $_REQUEST['keyword'] ) ? sanitize_text_field( $_REQUEST['keyword'] ) : '';
		if ( empty( $keyword ) ) {
			die();
		}
		$tags  = get_terms(
			array(
				'taxonomy'   => 'product_tag',
				'orderby'    => 'name',
				'order'      => 'ASC',
				'search'     => $keyword,
				'hide_empty' => false
			)
		);
		$items = array();
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$items[] = array(
					'id'   => $tag->term_id,
					'text' => $tag->name
				);
			}
		}
		wp_send_json( $items );
		die();
	}
	public function viredis_search_attribute() {
		if (!check_ajax_referer('_viredis_settings_cart_action','nonce', false) && !check_ajax_referer('_viredis_settings_product_action', 'nonce', false)){
			die();
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}
		$keyword = isset( $_REQUEST['keyword'] ) ? sanitize_text_field( $_REQUEST['keyword'] ) : '';
		if ( empty( $keyword ) ) {
			die();
		}
		global $wc_product_attributes;
		$attribute_taxonomies = $attribute_taxonomies_t = array();
		foreach ( $wc_product_attributes as $attr_k => $attr_v ) {
			$check = strtolower( $attr_v->attribute_label );
			if ( strlen( strstr( $check, $keyword ) ) ) {
				$attribute_taxonomies_t[ $attr_k ] = $attr_v->attribute_label;
			}
			$attribute_taxonomies[ $attr_k ] = $attr_v->attribute_label;
		}
		$items = array();
		if ( count( $attribute_taxonomies_t ) ) {
			foreach ( $attribute_taxonomies_t as $attr_k => $attr_v ) {
				$terms = get_terms( $attr_k, 'hide_empty=0' );// phpcs:ignore WordPress.WP.DeprecatedParameters.Get_termsParam2Found
				if ( $terms ) {
					foreach ( $terms as $term ) {
						$items[] = array(
							'id'   => $term->term_id,
							'text' => $term->name . ' (' . $attr_v . ')'
						);
					}
				}
			}
		} else {
			foreach ( $attribute_taxonomies as $attr_k => $attr_v ) {
				$terms = get_terms( $attr_k, 'hide_empty=0' );// phpcs:ignore WordPress.WP.DeprecatedParameters.Get_termsParam2Found
				if ( $terms ) {
					foreach ( $terms as $term ) {
						$check = strtolower( $term->name );
						if ( strlen( strstr( $check, $keyword ) ) ) {
							$items[] = array(
								'id'   => $term->term_id,
								'text' => $term->name . ' ( ' . $attr_v . ' )'
							);
						}
					}
				}
			}
		}
		wp_send_json( $items );
		die;
	}
	public function viredis_search_category() {
		if (!check_ajax_referer('_viredis_settings_cart_action','nonce', false) && !check_ajax_referer('_viredis_settings_product_action', 'nonce', false)){
			die();
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}
		$keyword = isset( $_REQUEST['keyword'] ) ? sanitize_text_field( $_REQUEST['keyword'] ) : '';
		if ( empty( $keyword ) ) {
			die();
		}
		$categories = get_terms(
			array(
				'taxonomy' => 'product_cat',
				'orderby'  => 'name',
				'order'    => 'ASC',
				'search'   => $keyword,
				'number'   => 100
			)
		);
		$items      = array();
		if ( count( $categories ) ) {
			foreach ( $categories as $category ) {
				$item       = array(
					'id'   => $category->term_id,
					'text' => $category->name
				);
				$items[]    = $item;
				$item_child = get_terms(
					array(
						'taxonomy' => 'product_cat',
						'orderby'  => 'name',
						'order'    => 'ASC',
						'child_of' => $item['id'],
						'number'   => 100
					)
				);
				if ( count( $item_child ) ) {
					foreach ( $item_child as $child ) {
						$items[] = array(
							'id'   => $child->term_id,
							'text' => $item['text'] . ' - ' . $child->name
						);
					}
				}
			}
		}
		wp_send_json( $items );
		die;
	}
	public function viredis_search_product() {
		if (!check_ajax_referer('_viredis_settings_cart_action','nonce', false) && !check_ajax_referer('_viredis_settings_product_action', 'nonce', false)){
			die();
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			die();
		}
		$keyword = isset( $_REQUEST['keyword'] ) ? sanitize_text_field( $_REQUEST['keyword'] ) : '';
		if ( empty( $keyword ) ) {
			die();
		}
		$arg            = array(
			'post_status'    => 'publish',
			'post_type'      => array( 'product' ),
			'posts_per_page' => 50,
			's'              => $keyword
		);
		$the_query      = new WP_Query( $arg );
		$found_products = array();
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$prd           = wc_get_product( get_the_ID() );
				$product_id    = get_the_ID();
				$product_title = $prd->get_formatted_name();
				if ( strpos( $product_title, '(#' . $product_id . ')' ) === false ) {
					$product_title .= '(#' . $product_id . ')';
				}
				if ( ! $prd->is_in_stock() ) {
					continue;
				}
				if ( $prd->has_child() && $prd->is_type( 'variable' ) ) {
					$product_title    .= '(#VARIABLE)';
					$product          = array( 'id' => $product_id, 'text' => $product_title );
					$found_products[] = $product;
					$product_children = $prd->get_children();
					if ( count( $product_children ) ) {
						foreach ( $product_children as $product_child ) {
							$child_wc       = wc_get_product( $product_child );
							$get_atts       = $child_wc->get_variation_attributes();
							$attr_name      = array_values( $get_atts )[0];
							$child_wc_title = $child_wc->get_formatted_name();
							if ( strpos( $child_wc_title, '(#' . $product_child . ')' ) === false ) {
								$child_wc_title .= '(#' . $product_child . ')';
							}
							$product          = array(
								'id'   => $product_child,
								'text' => $child_wc_title
							);
							$found_products[] = $product;
						}
					}
				} else {
					$product          = array( 'id' => $product_id, 'text' => $product_title );
					$found_products[] = $product;
				}
			}
		}
		wp_reset_postdata();
		wp_send_json( $found_products );
		die;
	}
	public static function remove_other_script() {
		global $wp_scripts;
		if ( isset( $wp_scripts->registered['jquery-ui-accordion'] ) ) {
			unset( $wp_scripts->registered['jquery-ui-accordion'] );
			wp_dequeue_script( 'jquery-ui-accordion' );
		}
		if ( isset( $wp_scripts->registered['accordion'] ) ) {
			unset( $wp_scripts->registered['accordion'] );
			wp_dequeue_script( 'accordion' );
		}
		$scripts = $wp_scripts->registered;
		foreach ( $scripts as $k => $script ) {
			preg_match( '/^\/wp-/i', $script->src, $result );
			if ( count( array_filter( $result ) ) ) {
				preg_match( '/^(\/wp-content\/plugins|\/wp-content\/themes)/i', $script->src, $result1 );
				if ( count( array_filter( $result1 ) ) ) {
					wp_dequeue_script( $script->handle );
				}
			} else {
				if ( $script->handle != 'query-monitor' ) {
					wp_dequeue_script( $script->handle );
				}
			}
		}
	}
	public static function enqueue_style( $handles = array(), $srcs = array(), $des = array(), $type = 'enqueue' ) {
		if ( empty( $handles ) || empty( $srcs ) ) {
			return;
		}
		$action = $type === 'enqueue' ? 'wp_enqueue_style' : 'wp_register_style';
		foreach ( $handles as $i => $handle ) {
			if ( ! $handle || empty( $srcs[ $i ] ) ) {
				continue;
			}
			$action( $handle, VIREDIS_CSS . $srcs[ $i ], $des[ $i ] ?? array(), VIREDIS_VERSION );
		}
	}
	public static function enqueue_script( $handles = array(), $srcs = array(), $des = array(), $type = 'enqueue' ) {
		if ( empty( $handles ) || empty( $srcs ) ) {
			return;
		}
		$action = $type === 'enqueue' ? 'wp_enqueue_script' : 'wp_register_script';
		foreach ( $handles as $i => $handle ) {
			if ( ! $handle || empty( $srcs[ $i ] ) ) {
				continue;
			}
			$action( $handle, VIREDIS_JS . $srcs[ $i ], $des[ $i ] ?? array( 'jquery' ), VIREDIS_VERSION );
		}
	}
}