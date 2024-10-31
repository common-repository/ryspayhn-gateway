<?php

/*
Plugin Name: Ryspayhn-Gateways
Description: Ryspayhn Payments Gateway for WooCommerce
Version: 1.6.0
Author: Ryspayhn
Author URI: http://ryspayhn.com/
License: GPLv2 or later
*/
defined('ABSPATH') or exit;
add_action('admin_init', 'ryspayhn_requirements');
require_once plugin_dir_path(__FILE__) . 'include/lib/active_ryspayhn.php';
function ryspayhn_requirements()
{
	if (is_admin() && current_user_can('activate_plugins') && !is_plugin_active('woocommerce/woocommerce.php')) {
		add_action('admin_notices', 'ryspayhn_deactive_notice');
		
		deactivate_plugins(plugin_basename(__FILE__));
		
		if (isset($_GET['activate'])) {
			unset($_GET['activate']);
		}
	} else {
		add_action('plugins_loaded', 'wc_ryspayhn_paymentGateway_init', 11);
	}
}

function ryspayhn_deactive_notice()
{
	?>
	<div class="error"><p>Sorry, but Ryspayhn-Gateways plugin requires the WooCommerce plugin to be installed and
	                      active.</p>
	</div><?php
}
