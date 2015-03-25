<?php

namespace MBeckett\Spam\Throttle;

// hook for menu:user_hover
function hover_menu($hook, $type, $return, $params) {
	$user = $params['entity'];
	
	if ($user->spam_throttle_suspension > time() && elgg_is_admin_logged_in()) {
	
		$url = "action/spam_throttle/unsuspend?guid={$user->guid}";
		$item = new \ElggMenuItem("spam_throttle_unsuspend", elgg_echo("spam_throttle:unsuspend"), $url);
		$item->setConfirmText(elgg_echo('spam_throttle:unsuspend:confirm'));
		$item->setSection('admin');
	
		$return[] = $item;
	}
	
	return $return;
}