<?php

namespace uncanny_learndash_codes;
use WP_Post;

/**
 * Class Automator
 * @package uncanny_learndash_codes
 */
class Automator extends Config {

	/**
	 * Sample constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_filter( 'ulc_codes_batch_redirect_url', array( $this, 'redirect_to_automator_recipe' ), 99, 3 );
		add_action( 'plugins_loaded', array( $this, 'maybe_add_custom_querystring' ), 20 );
	}

	/**
	 * If Automator is active and the code-for is Automator,
	 * generate a query that redirect to Automator recipe page
	 * which will add Uncanny Codes trigger with Batch pre-selected.
	 *
	 * @param $url
	 * @param $group_id
	 * @param $data
	 *
	 * @return mixed
	 */
	public function redirect_to_automator_recipe( $url, $group_id, $data ) {

		if ( ! isset( $data['dependency'] ) || 'automator' !== (string) $data['dependency'] ) {
			return $url;
		}

		if ( ! is_plugin_active( 'uncanny-automator/uncanny-automator.php' ) ) {
			return $url;
		}

		$raw_url = array(
			'post_type'            => 'uo-recipe',
			'action'               => 'add-new-trigger',
			'item_code'            => 'UCBATCH',
			'optionCode'           => 'UNCANNYCODESBATCH',
			'optionValue'          => urlencode( $group_id ),
			'optionValue_readable' => urlencode( $data['group-name'] ),
			'nonce'                => wp_create_nonce( 'Uncanny Automator' ),
		);
		$qry     = http_build_query( $raw_url );
		$url     = admin_url( 'post-new.php?' ) . $qry;

		return $url;
	}

	/**
	 * Try to add query string if trigger is populated from URL
	 *
	 * @return array|void
	 */
	public function maybe_add_custom_querystring() {
		if ( ! is_plugin_active( 'uncanny-automator/uncanny-automator.php' ) ) {
			return;
		}
		global $pagenow;
		if ( ( 'post.php' !== $pagenow ) ) {
			return;
		}
		$recipe_id = (int) SharedFunctionality::ulc_filter_input( 'post' );
		$post      = get_post( $recipe_id );
		if ( $post instanceof WP_Post && 'uo-recipe' === $post->post_type ) {
			if ( 'yes' === (string) get_post_meta( $recipe_id, 'uncanny-code-notice-shown', true ) ) {
				return;
			}
			global $uncanny_automator;
			$recipe_data = $uncanny_automator->get_recipe_data( 'uo-trigger', $recipe_id );
			if ( ! $recipe_data ) {
				return;
			}
			$found        = 0;
			$trigger_data = array_shift( $recipe_data );
			if ( ! isset( $trigger_data['meta'] ) || ! key_exists( 'UNCANNYCODESBATCH', $trigger_data['meta'] ) ) {
				return;
			}
			update_post_meta( $recipe_id, 'uncanny-code-notice-shown', 'yes' );
			$edit_url = admin_url( "post.php?post={$recipe_id}&action=edit&ecommerce-available=yes" );
			wp_safe_redirect( $edit_url );
			die();
		}
	}
}
