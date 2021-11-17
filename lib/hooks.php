<?php

// hook for menu:user_hover
function hover_menu(\Elgg\Hook $hook) {
	$return = $hook->getValue();
	$params = $hook->getParams();
	
	$user = $params['entity'];
	
	if ($user->spam_throttle_suspension > time() && elgg_is_admin_logged_in()) {
	
		$return['spam_throttle_unsuspend'] = \ElggMenuItem::factory([
			'name' => "spam_throttle_unsuspend",
			'icon' => 'edit',
			'text' => elgg_echo("spam_throttle:unsuspend"),
			'href' => elgg_generate_action_url("spam_throttle/unsuspend", [
				"guid" => $user->guid
				]),
			'confirm' => true,
			'section' => 'admin',
		]);
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
function global_messages_count_correction(\Elgg\Hook $hook) {
	$return = $hook->getValue();
	$params = $hook->getParams();
	
	$wrong_messages = elgg_get_entities(array(
		'type' => 'object',
		'subtype' => 'messages',
		'owner_guids' => $params['owner_guids'],
		'created_time_lower' => $params['created_time_lower'],
		'count' => true
	));
	
	$right_messages = elgg_call(ELGG_IGNORE_ACCESS, function() use ($params) {
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
		return $right_messages;
	});
	
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
function messages_count_correction(\Elgg\Hook $hook) {
	$return = $hook->getValue();
	$params = $hook->getParams();
	
	if (!isset($params['subtypes']) || $params['subtypes'][0] != 'messages') {
		return $return;
	}
	
	$right_messages = elgg_call(ELGG_IGNORE_ACCESS, function() use ($params) {
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
		return $right_messages;
	});

	return $right_messages;
}
