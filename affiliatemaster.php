<?php

/*
	Plugin Name: Affiliate Master
	Plugin URI: http://www.internetbusiness.bg
	Description: Affiliate Manager by Stanil Dobrev
	Version: 1.0.0
	Author: Stan Dobrev
	Author URI: http://www.internetbusiness.bg
    Copyright 2018 Stanil Dobrev (email : stanil@internetbusiness.bg)
*/


if (defined('ABSPATH') && defined('WPINC')) {
	define('AMPL_DIR', dirname(__FILE__));
	define('AMPL_URL', plugins_url('/', __FILE__));
	define('AMPL_PLUGIN_NAME' , 'Affiliate Master');
	define('AMPL_PLUGIN_SLUG' , 'affiliatemaster');
	require_once(dirname(__FILE__).'/class.main.php');
	$certificatemaster = new AffiliateMaster();
}


register_activation_hook(__FILE__, 'affiliatemasterInstall');
register_uninstall_hook(__FILE__, 'affiliatemasterUnInstall');


function affiliatemasterInstall() {
	add_rewrite_endpoint('partnercommission', EP_PERMALINK | EP_PAGES);
	flush_rewrite_rules();
}



function affiliatemasterUnInstall() {
	flush_rewrite_rules();
}



?>