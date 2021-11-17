<?php

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

	$default = false;

	// trusted users are exempt
	if (elgg_is_active_plugin('trusted_users') && is_callable('trusted_users_is_trusted')) {
		if (trusted_users_is_trusted($user)) {
			$default = true;
		};
	}

	// give other plugins a chance to weigh in
	$params = ['user' => $user];
	return elgg_trigger_plugin_hook('spam_throttle', 'is_exempt', $params, $default);
}
