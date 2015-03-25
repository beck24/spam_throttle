<?php

namespace MBeckett\Spam\Throttle;

use ElggUser;
use ElggEntity;

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

	// They've made it this far, time to check if they've exceeded limits or not
	// first check for global setting
	$globallimit = elgg_get_plugin_setting('global_limit', 'spam_throttle');
	$globaltime = elgg_get_plugin_setting('global_time', 'spam_throttle');

	if (is_numeric($globallimit) && $globallimit > 0 && is_numeric($globaltime) && $globaltime > 0) {

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
			limit_exceeded($globaltime, $entitycount, 'global');

			// not returning false in case of false positive
			return true;
		}
	}

	// 	if we're still going now we haven't exceeded globals, check for individual types
	$attr = $object->type;
	if ($object->getSubtype()) {
		$attr .= ':' . $object->getSubtype();
	}
	$limit = (int) elgg_get_plugin_setting($attr . '_limit', PLUGIN_ID);
	$time = (int) elgg_get_plugin_setting($attr . '_time', PLUGIN_ID);

	if ($limit && $time) {

		// because 2 are created initially
		if ($object->getSubtype() == 'messages') {
			$limit++;
		}

		// 	we have globals set, lets give it a test
		$default_lowertime = time() - ($time * 60);
		$time_lower = max(array($default_lowertime, (int) $user->spam_throttle_unsuspended));
		$params = array(
			'type' => 'object',
			'subtypes' => array($object->getSubtype()),
			'created_time_lower' => $time_lower,
			'owner_guids' => array($user->guid),
			'count' => true,
		);

		$entitycount = elgg_get_entities($params);

		if ($entitycount > $limit) {
			limit_exceeded($time, $entitycount, $object->getSubtype());

			// not returning false in case of false positive
			return true;
		}
	}

	return true;
}

function limit_exceeded($time, $created, $type) {

	$user = elgg_get_logged_in_user_entity();

	if (!$user) {
		return;
	}

	$reporttime = elgg_get_plugin_setting('reporttime', 'spam_throttle');
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

	$sendreport = TRUE;
	foreach ($reports as $previousreport) {
		if ($previousreport->title == elgg_echo('spam_throttle')) {
			// we've already been reported
			$sendreport = FALSE;
		}
	}


	if ($sendreport) {
		$report = new ElggObject;
		$report->subtype = "reported_content";
		$report->owner_guid = $user->guid;
		$report->title = elgg_echo('spam_throttle');
		$report->address = $user->getURL();
		$report->description = elgg_echo('spam_throttle:reported', array($type, $created, $time));
		$report->access_id = ACCESS_PRIVATE;
		$report->save();
	}

	$consequence = elgg_get_plugin_setting($type . '_consequence', 'spam_throttle');

	switch ($consequence) {
		case "nothing":
			break;

		case "suspend":
			$suspensiontime = elgg_get_plugin_setting('suspensiontime', 'spam_throttle');
			$user->spam_throttle_suspension = time() + 60 * 60 * $suspensiontime;
			register_error(elgg_echo('spam_throttle:suspended', array($suspensiontime, '0')));
			break;

		case "ban":
			ban_user($user, elgg_echo('spam_throttle:banned'));
			logout();
			register_error(elgg_echo('spam_throttle:banned'));
			forward();
			break;

		case "delete":
			$user = elgg_get_logged_in_user_entity();
			$user->delete();
			register_error(elgg_echo('spam_throttle:deleted'));
			break;

		default:
			break;
	}
}

/**
 * Is the user exempt from throttling?
 * 
 * @param \ElggUser $user
 * @return boolean
 */
function is_exempt($user) {
	if (!($user instanceof \ElggUser)) {
		return false;
	}

	if (elgg_is_admin_logged_in()) {
		return true;
	}

	// trusted users are exempt
	if (elgg_is_active_plugin('trusted_users') && is_callable('trusted_users_is_trusted')) {
		return trusted_users_is_trusted($user);
	}

	return false;
}
