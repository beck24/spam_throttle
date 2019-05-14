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


/**
 * fix the global count due to messages structure differences
 * 
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 */
function global_messages_count_correction($hook, $type, $return, $params) {
	$wrong_messages = elgg_get_entities(array(
		'type' => 'object',
		'subtype' => 'messages',
		'owner_guids' => $params['owner_guids'],
		'created_time_lower' => $params['created_time_lower'],
		'count' => true
	));
	
	$ia = elgg_set_ignore_access(true);
	$from_guid = elgg_get_logged_in_user_guid();
	$right_messages = elgg_get_entities(array(
		'type' => 'object',
		'subtype' => 'messages',
		'metadata_name_value_pairs' => array(
			'name' => 'fromId',
			'value' => $from_guid
		),
		'wheres' => array(
			"e.owner_guid != {$from_guid}"
		),
		'created_time_lower' => $params['created_time_lower'],
		'count' => true
	));
	elgg_set_ignore_access($ia);
	
	$corrected_count = $return - $wrong_messages + $right_messages;

	return $corrected_count;
}

/**
 * fix the global count due to messages structure differences
 * 
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 */
function messages_count_correction($hook, $type, $return, $params) {
	
	if (!isset($params['subtypes']) || $params['subtypes'][0] != 'messages') {
		return $return;
	}
	
	$from_guid = elgg_get_logged_in_user_guid();
	
	$ia = elgg_set_ignore_access(true);
	$right_messages = elgg_get_entities(array(
		'type' => 'object',
		'subtype' => 'messages',
		'metadata_name_value_pairs' => array(
			'name' => 'fromId',
			'value' => $from_guid
		),
		'wheres' => array(
			"e.owner_guid != {$from_guid}"
		),
		'created_time_lower' => $params['created_time_lower'],
		'count' => true
	));
	elgg_set_ignore_access($ia);

	return $right_messages;
}
