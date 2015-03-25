<?php

namespace MBeckett\Spam\Throttle;

use ElggUser;

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
