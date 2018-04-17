<?php

namespace MBeckett\Spam\Throttle;

const PLUGIN_ID = 'spam_throttle';
const PLUGIN_VERSION = 20180416;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/hooks.php';
require_once __DIR__ . '/lib/events.php';

elgg_register_event_handler('init', 'system', function() {
	elgg_register_event_handler('create', 'all', __NAMESPACE__ . '\\create_check');
	
	elgg_register_plugin_hook_handler('register', 'menu:user_hover', __NAMESPACE__ . '\\hover_menu', 1000);
	
	elgg_register_action("spam_throttle/unsuspend", __DIR__ . "/actions/unsuspend.php", 'admin');
	
	elgg_register_plugin_hook_handler('spam_throttle', 'entity_count:global', __NAMESPACE__ . '\\global_messages_count_correction');
	elgg_register_plugin_hook_handler('spam_throttle', 'entity_count', __NAMESPACE__ . '\\messages_count_correction');
});
