<?php

use uncanny_learndash_codes\SharedFunctionality;

/**
 * Class WC_Product_Courses
 */
class WC_Product_Automator_Codes extends WC_Product {

	/**
	 * Get the product if ID is passed, otherwise the product is new and empty.
	 * This class should NOT be instantiated, but the wc_get_product() function
	 * should be used. It is possible, but the wc_get_product() is preferred.
	 *
	 * @param int|WC_Product|object $product Product to init.
	 */
	public function __construct( $product = 'automator_codes' ) {
		parent::__construct( $product );
	}

	/**
	 * Get internal type. Should return string and *should be overridden* by child classes.
	 *
	 * The product_type property is deprecated but is used here for BW compat with child classes which may be defining
	 * product_type and not have a get_type method.
	 *
	 * @return string
	 * @since 3.0.0
	 */
	public function get_type() {
		return 'automator_codes';
	}

	/**
	 * Get the add to cart button text
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		$text = $this->is_purchasable() ? esc_html__( 'Add to cart', 'woocommerce' ) : esc_html__( 'Read More', 'uncanny-learndash-groups' );

		return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
	}

	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		return true;
	}

	/**
	 * Set the add to cart button URL used on the /shop/ page
	 *
	 * @return string
	 * @since 1.3.1
	 */
	public function add_to_cart_url() {
		$url = $this->is_purchasable() && $this->is_in_stock() ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->id ) ) : get_permalink( $this->id );

		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}

	/**
	 * Get virtual.
	 *
	 * @param string $context
	 *
	 * @return bool
	 * @since 3.0.0
	 *
	 */
	public function is_virtual( $context = 'view' ) {
		return apply_filters( 'ulc_automator_codes_is_virtual', $this->get_prop( 'virtual', $context ), $this );
	}

	/**
	 * Get virtual.
	 *
	 * @param string $context
	 *
	 * @return bool
	 * @since 3.0.0
	 *
	 */
	public function is_downloadable( $context = 'view' ) {
		return apply_filters( 'ulc_automator_codes_is_downloadable', $this->get_prop( 'downloadable', $context ), $this );
	}

	/**
	 * Returns false if the product is taxable.
	 *
	 * @return bool
	 */
	public function is_taxable() {
		return 'taxable';
	}

	/**
	 * @param string $context
	 *
	 * @return bool
	 */
	public function get_manage_stock( $context = 'view' ) {
		return apply_filters( 'ulc_automator_codes_managing_stock', true, $this );
	}

	/**
	 * @return bool|mixed|void
	 */
	public function managing_stock() {
		return apply_filters( 'ulc_automator_codes_managing_stock', true, $this );
	}

	/**
	 * @return bool|mixed|void
	 */
	public function backorders_allowed() {
		return apply_filters( 'ulc_automator_codes_backorder_allowed', false, $this );
	}

	/**
	 * @param string $context
	 *
	 * @return int|mixed|null
	 */
	public function get_stock_quantity( $context = 'view' ) {
		return SharedFunctionality::get_available_codes_by_group_id( $this->get_id() );
	}

	/**
	 * @param $manage_stock
	 *
	 * @return true
	 */
	public function set_manage_stock( $manage_stock ) {
		return true;
	}

	public function get_tax_status( $context = 'view' ) {
		return $this->get_prop( 'tax_status', $context );
	}

	public function get_tax_class( $context = 'view' ) {
		return $this->get_prop( 'tax_class', $context );
	}
}
