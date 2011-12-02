<?php

include_once 'lib/functions.php';

function spam_throttle_init(){
	global $CONFIG;
	
	// Load the language file
	register_translations($CONFIG->pluginspath . "spam_throttle/languages/");
	
	register_page_handler('spam_throttle','spam_throttle_page_handler');
	
	register_elgg_event_handler('create', 'object', 'spam_throttle_check');
	register_elgg_event_handler('create', 'annotation', 'spam_throttle_check');
	register_elgg_event_handler('pagesetup','system','spam_throttle_pagesetup');
	
	elgg_register_plugin_hook_handler('register', 'menu:user_hover', 'spam_throttle_hover_menu', 1000);
}

function spam_throttle_pagesetup() {

	if (get_context() == 'admin' && isadminloggedin()) {
		global $CONFIG;
		add_submenu_item(elgg_echo('spam_throttle:settings'), $CONFIG->wwwroot . 'pg/spam_throttle/');
	}
}


function spam_throttle_page_handler()
{
	global $CONFIG;

	include($CONFIG->pluginspath . "spam_throttle/pages/edit.php");
}

//register action to save our plugin settings
register_action("spam_throttle/settings", false, $CONFIG->pluginspath . "spam_throttle/actions/spam_throttle_settings.php", true);
register_action("spam_throttle/unsuspend", false, $CONFIG->pluginspath . "spam_throttle/actions/unsuspend.php", true);

register_elgg_event_handler('init', 'system', 'spam_throttle_init');