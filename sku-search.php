<?php
/**
 * Plugin Name:	SKU Search
 * Description:	SKU Search provides users the ability to search products by SKU.	This plugin is compatible with WooCommerce.
 * Version:			1.1
 * Requires PHP: 7.2
 * Author:			 York Computer Solutions LLC
 * Author URI:	 https://yorkcs.com
 * License:			GPLv2 or later
 * License URI:	https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:	sku-search
 */

 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if directly accessed

 if ( ! defined( 'SKU_SEARCH_VERSION' ) ) {
	define( 'SKU_SEARCH_VERSION', 1.1 );
}

 class Search_By_SKU_Plugin {

	public static function init() {
		$class = __CLASS__;
		new $class;
	}

	public function __construct() {

		add_filter( 'pre_get_posts', array( $this, 'sku_search' ), 15 );

		add_filter( 'woocommerce_dropdown_variation_attribute_options_args', array( $this, 'override_dropdown_variation' ), 10, 2 );

		add_filter( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

	}

	public function register_scripts() {
		wp_enqueue_script( 'search-by-sku-js', plugins_url( 'search-by-sku/js/search-by-sku.js' ), array( 'jquery' ), SKU_SEARCH_VERSION );
	}

	public function override_dropdown_variation( $args ) {

		if ( isset($_COOKIE['sku'] )) {
			$sku = $_COOKIE['sku'];

			$product = wc_get_product($this->get_product_id_by_sku($sku));

			if ($product->is_type( 'variable' )) {

				$variations = $product->get_available_variations();

				$variation_found = null;
				for ($i = 0; $i < count($variations); $i++ ) {
					if ($variations[$i]['sku'] == $sku) {
						$variation_found = $variations[$i];
					}
				}

				if (isset($variation_found)) {

					// Lookup slug of current attribute from variation
					$attributes = $product->get_attributes();

					$attribute_slug = null;

					foreach ( $attributes as $key => $val ) {
						if ($val->get_name() == $args['attribute']) {
							$attribute_slug = 'attribute_' . $key;
						}
					}

					foreach ($variation_found['attributes'] as $key => $val) {
						if ($key == $attribute_slug) {
							if (empty($args['selected'])) {
								$args['selected'] = $val; 
							}
						}
					}
				}
			}
		}

		//echo '<pre>' . var_export($args, true) . '</pre>';

		return $args;
	}

	function get_product_id_by_sku( $sku ) {

		$args = array(
			'post_type'	=> 'product_variation',
			'meta_query' => array(
				array(
						'key'	 => '_sku',
						'value' => $sku,
				)
			)
		);


		// Get the posts for the sku
		$posts = get_posts( $args );
		if ($posts) {
				return $posts[0]->post_parent;
		} else {
				return false;
		}

	}

	/**
	 * Helps search by SKU better in WooCommerce
	 */
	public function sku_search( $wp ) {

		global $wpdb;
		global $wp_query;

		// don't change search results in the admin dashboard
		if ( $wp->is_admin ) {
			return $wp;
		}

		$sku = $wpdb->esc_like( wc_clean( $wp->query['s'] ) );

		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value = %s;", $sku ) );

		if ( isset( $_COOKIE['sku'] ) ) {
			unset( $_COOKIE['sku'] );
			setcookie( 'sku', '' );
		}

		if ( count($ids) == 0 ) {
			return;
		}
		else if ( count( $ids ) > 0 ) {
			unset( $wp->query['s'] );
			unset( $wp->query_vars['s'] );
		}

		wp_redirect( get_permalink( $ids[0] ));
		exit;
	}
}

add_action( 'plugins_loaded', array( 'Search_By_SKU_Plugin', 'init' ) );
