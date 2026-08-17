<?php
/**
 * Plugin Name: Digech Custom Permalink
 * Description: Custom permalink management for WordPress and WooCommerce.
 * Version: 1.0.0
 * Author: Digech
 * License: Proprietary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get WooCommerce product base.
 *
 * Example:
 * /item/
 */
function digech_get_product_base() {

	if ( ! function_exists( 'wc_get_permalink_structure' ) ) {
		return '/product/';
	}

	$permalinks = wc_get_permalink_structure();

	$base = isset( $permalinks['product_base'] )
		? $permalinks['product_base']
		: '/product/';

	return '/' . trim( $base, '/' ) . '/';
}


/**
 * Generate WooCommerce product permalink.
 *
 * Example:
 * /item/7155/
 */
add_filter( 'post_type_link', function ( $url, $post ) {

	if ( 'product' !== $post->post_type ) {
		return $url;
	}

	return home_url(
		digech_get_product_base() . $post->ID . '/'
	);

}, 10, 2 );


/**
 * Rewrite product ID URL.
 *
 * Example:
 * /item/7155/
 */
add_action( 'init', function () {

	$base = trim( digech_get_product_base(), '/' );

	add_rewrite_rule(
		'^' . preg_quote( $base, '#' ) . '/([0-9]+)/?$',
		'index.php?post_type=product&p=$matches[1]',
		'top'
	);

} );


/**
 * Flush rewrite rules when activated.
 */
register_activation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );


/**
 * Flush rewrite rules when deactivated.
 */
register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );


/**
 * Plugin Update Checker.
 */
require_once __DIR__ . '/plugin-update-checker-master/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/digech-dev/digech-custom-permlink/',
	__FILE__,
	'digech-custom-permlink'
);

$update_checker->getVcsApi()->enableReleaseAssets();


/**
 * Enable automatic updates.
 */
add_filter( 'auto_update_plugin', function ( $update, $item ) {

	if (
		isset( $item->plugin ) &&
		$item->plugin === plugin_basename( __FILE__ )
	) {
		return true;
	}

	return $update;

}, 10, 2 );

/**
 * Redirect old product ID URLs to the current product base.
 *
 * Example:
 * /product/7155/
 * -> /item/7155/
 */
add_action( 'template_redirect', function () {

	if ( is_admin() ) {
		return;
	}

	$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' );
	$request_path = wp_parse_url( $request_uri, PHP_URL_PATH );

	if ( ! $request_path ) {
		return;
	}

	// Remove WordPress installation path.
	$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home_path = '/' . trim( (string) $home_path, '/' );

	if ( '/' !== $home_path ) {
		$request_path = preg_replace(
			'#^' . preg_quote( $home_path, '#' ) . '#',
			'',
			$request_path
		);
	}

	$request_path = '/' . ltrim( $request_path, '/' );

	// Current WooCommerce product base.
	$current_base = trim( digech_get_product_base(), '/' );

	// No redirect needed if the current base is /product/.
	if ( 'product' === $current_base ) {
		return;
	}

	// Match old URL: /product/7155/
	if ( ! preg_match( '#^/product/([0-9]+)/?$#', $request_path, $matches ) ) {
		return;
	}

	$product_id = absint( $matches[1] );

	if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
		return;
	}

	$new_url = home_url(
		digech_get_product_base() . $product_id . '/'
	);

	wp_safe_redirect( $new_url, 301 );
	exit;

}, 1 );
