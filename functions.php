<?php
/**
 * Extend EDD Invoices with custom invoices available only for admin users.
 * Use API from APILayer to get the exchange rate and show in local currency.
 */
namespace SLUG\SLUG_EDD_INVVOICE;

/**
 * Include extend-edd-invoices.php from /inc.
 */
require_once( get_stylesheet_directory() . '/inc/extend-edd-invoices.php' );

/**
 * Enqueue parent and child theme stylesheets.
 */
function enqueue_theme_styles() {
	// Parent theme stylesheet.
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

	// Child theme stylesheet.
	wp_enqueue_style( 'child-style', get_stylesheet_uri() );
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_theme_styles' );
