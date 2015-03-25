<?php

namespace MBeckett\Spam\Throttle;
use ElggEntity;
use ElggObject;

/**
 * check if a user is over the threshold for content creation
 *
 * @param string $event
 * @param string $object_type
 * @param ElggObject $object
 * @return boolean
 */
function create_check($event, $object_type, $object) {

	if (!($object instanceof \ElggEntity)) {
		return true;
	}

	$user = elgg_get_logged_in_user_entity();

	if (is_exempt($user)) {
		return true;
	}

	// only want to track content they are creating
	// some automated scripts may be triggered on their session
	// also allow messages
	if ((is_registered_entity_type($object->type, $object->getSubtype()) && !elgg_instanceof($object, 'object', 'messages'))
			&& $object->owner_guid != $user->guid) {
		return true;
	}

	// reported content doesn't count (also this prevents an infinite loop...)
	if (elgg_instanceof($object, 'object', 'reported_content')) {
		return true;
	}

	// delete the content and warn them if they are on a suspension
	if ($user->spam_throttle_suspension > time()) {
		$timeleft = $user->spam_throttle_suspension - time();
		$hours = ($timeleft - ($timeleft % 3600)) / 3600;
		$minutes = round(($timeleft % 3600) / 60);
		register_error(elgg_echo('spam_throttle:suspended', array($hours, $minutes)));
		return false;
	}
	
	$typesubtype = $object->type;
	if ($object->getSubtype()) {
		$typesubtype .= ':' . $object->getSubtype();
	}

	// They've made it this far, time to check if they've exceeded limits or not
	// first check for global setting
	$globallimit = (int) elgg_get_plugin_setting('global_limit', PLUGIN_ID);
	$globaltime = (int) elgg_get_plugin_setting('global_time', PLUGIN_ID);

	if ($globallimit && $globaltime) {

		// because 2 are created initially
		if (elgg_instanceof($object, 'object', 'messages')) {
			$globallimit++;
		}

		// we have globals set, lets give it a test
		$default_lowertime = time() - ($globaltime * 60);
		$time_lower = max(array($default_lowertime, (int) $user->spam_throttle_unsuspended));
		$params = array(
			'type' => 'object',
			'created_time_lower' => $time_lower,
			'owner_guids' => array($user->guid),
			'count' => true,
		);

		$entitycount = elgg_get_entities($params);

		if ($entitycount > $globallimit) {
			elgg_unregister_event_handler('shutdown', 'system', __NAMESPACE__ . '\\limit_exceeded');
			elgg_register_event_handler('shutdown', 'system', __NAMESPACE__ . '\\limit_exceeded');
			
			elgg_set_config('spam_throttle_reasons', array(
				'type' => $typesubtype,
				'created' => $entitycount
			));

			return false;
		}
	}

	// 	if we're still going now we haven't exceeded globals, check for individual types
	$limit = (int) elgg_get_plugin_setting($typesubtype . '_limit', PLUGIN_ID);
	$time = (int) elgg_get_plugin_setting($typesubtype . '_time', PLUGIN_ID);

	if ($limit && $time) {

		// because 2 are created initially
		if ($object->getSubtype() == 'messages') {
			$limit++;
		}

		// 	we have globals set, lets give it a test
		$default_lowertime = time() - ($time * 60);
		$time_lower = max(array($default_lowertime, (int) $user->spam_throttle_unsuspended));
		$params = array(
			'type' => $object->type,
			'created_time_lower' => $time_lower,
			'owner_guids' => array($user->guid),
			'count' => true,
		);
		
		if ($object->getSubtype()) {
			$params['subtypes'] = array($object->getSubtype());
		}

		$entitycount = elgg_get_entities($params);

		if ($entitycount > $limit) {
			elgg_unregister_event_handler('shutdown', 'system', __NAMESPACE__ . '\\limit_exceeded');
			elgg_register_event_handler('shutdown', 'system', __NAMESPACE__ . '\\limit_exceeded');
			
			elgg_set_config('spam_throttle_reasons', array(
				'type' => $typesubtype,
				'created' => $entitycount
			));
			return false;
		}
	}

	return true;
}


/**
 * called on shutdown after a user has violated a limit
 * 
 * @return type
 */
function limit_exceeded() {
	$params = elgg_get_config('spam_throttle_reasons');
	if (!is_array($params)) {
		return; // not sure what happened here
	}
	
	$created = $params['created'];
	$type = $params['type'];

	$user = elgg_get_logged_in_user_entity();

	if (!$user) {
		return;
	}

	$reporttime = elgg_get_plugin_setting('reporttime', PLUGIN_ID);
	$time = time();

	$params = array(
		'types' => array('object'),
		'subtypes' => array('reported_content'),
		'owner_guids' => array($user->guid),
		'time_created_lower' => $time - (60 * 60 * $reporttime),
	);

	$reports = elgg_get_entities($params);

	if (!$reports) {
		$reports = array();
	}

	$sendreport = true;
	foreach ($reports as $previousreport) {
		if ($previousreport->title == elgg_echo('spam_throttle')) {
			// we've already been reported
			$sendreport = false;
		}
	}


	if ($sendreport) {
		$report = new \ElggObject;
		$report->subtype = "reported_content";
		$report->owner_guid = $user->guid;
		$report->title = elgg_echo('spam_throttle');
		$report->address = $user->getURL();
		$report->description = elgg_echo('spam_throttle:reported', array($type, $created, $time));
		$report->access_id = ACCESS_PRIVATE;
		$report->save();
	}

	$consequence = elgg_get_plugin_setting($type . '_consequence', PLUGIN_ID);

	switch ($consequence) {
		case "nothing":
			break;

		case "suspend":
			$suspensiontime = elgg_get_plugin_setting('suspensiontime', PLUGIN_ID);
			$user->spam_throttle_suspension = time() + 60 * 60 * $suspensiontime;
			register_error(elgg_echo('spam_throttle:suspended', array($suspensiontime, '0')));
			break;

		case "ban":
			$ia = elgg_set_ignore_access(true);
			ban_user($user->guid, elgg_echo('spam_throttle:banned'));
			elgg_set_ignore_access($ia);
			logout();
			register_error(elgg_echo('spam_throttle:banned'));
			forward();
			break;

		case "delete":
			logout();
			sleep(2); // prevent a race condition before deleting them
			$ia = elgg_set_ignore_access(true);
			$user->delete();
			elgg_set_ignore_access($ia);
			register_error(elgg_echo('spam_throttle:deleted'));
			break;

		default:
			break;
	}
}


/**
 * upgrades
 */
function upgrades() {
	if (elgg_is_admin_logged_in()) {
		elgg_load_library(PLUGIN_ID . ':upgrades');
		run_function_once(__NAMESPACE__ . '\\upgrade_20150323');
	}
}
