<?php

namespace MBeckett\Spam\Throttle;

function upgrades() {
	if (elgg_is_admin_logged_in()) {
		elgg_load_library(PLUGIN_ID . ':upgrades');
		run_function_once(__NAMESPACE__ . '\\upgrade_20150323');
	}
}
