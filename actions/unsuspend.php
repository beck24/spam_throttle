<?php

$user_id = get_input('guid');

$user = get_user($user_id);

if (!($user instanceof ElggUser)) {
	register_error(elgg_echo('spam_throttle:invalid:id'));
	forward(REFERER);
}

$user->spam_throttle_suspension = 0;
$user->spam_throttle_unsuspended = time();

system_message(elgg_echo('spam_throttle:unsuspended'));
forward(REFERER);