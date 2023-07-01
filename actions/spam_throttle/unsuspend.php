<?php

$user_id = get_input('guid');

$user = get_user($user_id);

if (!($user instanceof ElggUser)) {
	return elgg_error_response(elgg_echo('spam_throttle:invalid:id'), REFERRER);
}

$user->spam_throttle_suspension = 0;
$user->spam_throttle_unsuspended = time();

return elgg_ok_response('', elgg_echo('spam_throttle:unsuspended'), REFERRER);