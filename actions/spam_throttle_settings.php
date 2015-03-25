<?php

namespace MBeckett\Spam\Throttle;

$settings = get_input('settings');
$exempt = get_input('members');

// save settings
foreach($settings as $name => $value){
	if($name != "consequence"){
		(int) $value;
		if($value ){
			elgg_set_plugin_setting($name, abs($value), PLUGIN_ID);
		}
	}
}

foreach($settings['consequence'] as $type => $consequence){
	elgg_set_plugin_setting($type.'_consequence', $consequence, PLUGIN_ID);
}

if(!$error){
	// set success message
	system_message(elgg_echo('spam_throttle:settings:success'));
}

forward(REFERER);