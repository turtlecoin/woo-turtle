<?php
/*
Plugin Name: Turtlecoin - WooCommerce Gateway
Plugin URI: http://turtlecoin.lol
Description: Extends WooCommerce by adding the Turtlecoin Gateway
Version: 0.1
Author: Fexra
*/
if(!defined('ABSPATH')) {
	exit;
}

//Load Plugin
add_action('plugins_loaded', 'turtlecoin_init', 0 );

function turtlecoin_init() {
	if(!class_exists('WC_Payment_Gateway')) return;
	
	include_once('include/turtlecoin_payments.php');
	require_once('library.php');

    add_filter( 'woocommerce_payment_gateways', 'turtlecoin_gateway');
	function turtlecoin_gateway( $methods ) {
		$methods[] = 'Turtlecoin_Gateway';
		return $methods;
	}
}

//Add action link
add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'turtlecoin_payment');

function turtlecoin_payment($links) {
	$plugin_links = array('<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'turtlecoin_payment') . '</a>',);
	return array_merge($plugin_links, $links);	
}

//Configure currency
add_filter('woocommerce_currencies','add_my_currency');
add_filter('woocommerce_currency_symbol','add_my_currency_symbol', 10, 2);

function add_my_currency($currencies) {
     $currencies['TRTL'] = __('Turtlecoin','woocommerce');
     return $currencies;
}

function add_my_currency_symbol($currency_symbol, $currency) {
    switch($currency) {
        case 'TRTL': $currency_symbol = 'TRTL'; break;
    }
    return $currency_symbol;
}

//Create Database
register_activation_hook(__FILE__,'createDatabase');

$db = $wpdb->prefix . 'woocommerce_turtlecoin';

function createDatabase() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'woocommerce_turtlecoin';
    
	$sql = "CREATE TABLE $table_name (
       `id` INT(32) NOT NULL AUTO_INCREMENT,
	   `oid` INT(32) NOT NULL,
       `pid` VARCHAR(64) NOT NULL,
       `lasthash` VARCHAR(120) NOT NULL,
       `amount` DECIMAL(12, 2) NOT NULL,
       `paid` INT(1) NOT NULL,
       UNIQUE KEY id (id)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta( $sql );
}
