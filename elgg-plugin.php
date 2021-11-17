<?php
const PLUGIN_ID = 'spam_throttle';
const PLUGIN_VERSION = 20180416;

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/hooks.php';
require_once __DIR__ . '/lib/events.php';

return [
	'plugin' => [
		'name' => 'Spam Throttle',
		'version' => '4.0',
		'dependencies' => [],
	],
	'bootstrap' => SpamThrottle::class,
	'actions' => [
		'spam_throttle/unsuspend' => [],
	],
];
