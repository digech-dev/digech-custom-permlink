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
 * WooCommerce product permalink
 * /product/12345/
 */
add_filter( 'post_type_link', function ( $url, $post, $leavename ) {

	if ( 'product' !== $post->post_type ) {
		return $url;
	}

	return home_url( '/product/' . $post->ID . '/' );

}, 10, 3 );

/**
 * Rewrite /product/{ID}/ to WooCommerce product.
 */
add_action( 'init', function () {

	add_rewrite_rule(
		'^product/([0-9]+)/?$',
		'index.php?post_type=product&p=$matches[1]',
		'top'
	);

} );

/**
 * Flush rewrite rules when plugin is activated.
 */
register_activation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

/**
 * Flush rewrite rules when plugin is deactivated.
 */
register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );
