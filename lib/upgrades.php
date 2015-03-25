<?php

namespace MBeckett\Spam\Throttle;


function upgrade_20150323() {
	$version = (int) elgg_get_plugin_setting('version', PLUGIN_ID);
	if ($version >= PLUGIN_VERSION) {
		return true; // already up to date
	}
	
	elgg_set_plugin_setting('version', 20150323, PLUGIN_ID);
}