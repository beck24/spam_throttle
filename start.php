<?php

include_once 'lib/functions.php';

function spam_throttle_init(){
	global $CONFIG;
	
	// Load the language file
	register_translations($CONFIG->pluginspath . "spam_throttle/languages/");
	
	elgg_register_page_handler('spam_throttle','spam_throttle_page_handler');
	
	elgg_register_event_handler('create', 'object', 'spam_throttle_check');
	elgg_register_event_handler('create', 'annotation', 'spam_throttle_check');
	elgg_register_event_handler('pagesetup','system','spam_throttle_pagesetup');
	
	elgg_register_plugin_hook_handler('register', 'menu:user_hover', 'spam_throttle_hover_menu', 1000);
}

function spam_throttle_pagesetup() {

	if (elgg_get_context() == 'admin' && elgg_is_admin_logged_in()) {
	  $item = new ElggMenuItem('spam_throttle', elgg_echo('spam_throttle:settings'), elgg_get_site_url() . 'spam_throttle/admin/');
	  $item->setParent('settings');
	  elgg_register_menu_item('page', $item);
	}
}


function spam_throttle_page_handler()
{
	global $CONFIG;

	include($CONFIG->pluginspath . "spam_throttle/pages/edit.php");
}

//register action to save our plugin settings
elgg_register_action("spam_throttle/settings", elgg_get_plugins_path() . "spam_throttle/actions/spam_throttle_settings.php", 'admin');
elgg_register_action("spam_throttle/unsuspend", elgg_get_plugins_path() . "spam_throttle/actions/unsuspend.php", 'admin');

elgg_register_event_handler('init', 'system', 'spam_throttle_init');