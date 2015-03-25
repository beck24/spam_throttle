<?php

/*
 * 	This is the form to set the plugin settings
 */


// preamble & explanation
echo elgg_echo('spam_throttle:explanation') . "<br><br>";
echo elgg_echo('spam_throttle:consequence:explanation');
echo "<ul><li><b>" . elgg_echo('spam_throttle:nothing') . "</b> - " . elgg_echo('spam_throttle:nothing:explained') . "<br></li>";
echo "<li><b>" . elgg_echo('spam_throttle:suspend') . "</b> - " . elgg_echo('spam_throttle:suspend:explained') . "<br></li>";
echo "<li><b>" . elgg_echo('spam_throttle:ban') . "</b> - " . elgg_echo('spam_throttle:ban:explained') . "<br></li>";
echo "<li><b>" . elgg_echo('spam_throttle:delete') . "</b> - " . elgg_echo('spam_throttle:delete:explained') . "</li></ul><br>";

// globals
$title = elgg_echo('spam_throttle:settings:global');
$body = elgg_view('input/text', array(
	'name' => 'params[global_limit]',
	'value' => $vars['entity']->global_limit,
));
$body .= " " . elgg_echo('spam_throttle:helptext:limit', array(elgg_echo('spam_throttle:new_content'))) . "<br>";

$body .= elgg_view('input/text', array(
	'name' => 'params[global_time]',
	'value' => $vars['entity']->global_time,
));
$body .= " " . elgg_echo('spam_throttle:helptext:time') . '<br><br>';

// action to perform if threshold is broken
$body .= elgg_view('input/dropdown', array(
	'name' => 'params[global_consequence]',
	'value' => $vars['entity']->global_consequence ? $vars['entity']->global_consequence : 'suspend',
	'options_values' => array(
		'nothing' => elgg_echo('spam_throttle:nothing'),
		'suspend' => elgg_echo('spam_throttle:suspend'),
		'ban' => elgg_echo('spam_throttle:ban'),
		'delete' => elgg_echo('spam_throttle:delete')
	)
));
$body .= elgg_echo('spam_throttle:consequence:title', array(elgg_echo('spam_throttle:global')));
echo elgg_view_module('main', $title, $body);



// loop through all of our object subtypes
$registered_types = get_registered_entity_types();
$registered_types['object'][] = 'messages';

foreach ($registered_types as $type => $subtypes) {
	if ($subtypes) {
		foreach ($subtypes as $subtype) {
			$attr = $type . ':' . $subtype . '_limit';
			$title = elgg_echo('spam_throttle:settings:subtype', array(elgg_echo("item:{$type}:{$subtype}")));
			$body = elgg_view('input/text', array(
				'name' => "params[{$attr}]",
				'value' => $vars['entity']->$attr,
			));
			$body .= ' ' . elgg_echo('spam_throttle:helptext:limit', array(elgg_echo("item:{$type}:{$subtype}"))) . "<br>";

			$attr = $type . ':' . $subtype . '_time';
			$body .= elgg_view('input/text', array(
				'name' => "params[{$attr}]",
				'value' => $vars['entity']->$attr,
			));
			$body .= " " . elgg_echo('spam_throttle:helptext:time') . '<br><br>';
		
			// action to perform if threshold is broken
			$attr = $type . ':' . $subtype . '_consequence';
			$body .= elgg_view('input/dropdown', array(
				'name' => "params[{$attr}]",
				'value' => $vars['entity']->$attr ? $vars['entity']->$attr : 'suspend',
				'options_values' => array(
					'nothing' => elgg_echo('spam_throttle:nothing'),
					'suspend' => elgg_echo('spam_throttle:suspend'),
					'ban' => elgg_echo('spam_throttle:ban'),
					'delete' => elgg_echo('spam_throttle:delete')
				)
			));
			$body .= elgg_echo('spam_throttle:consequence:title', array(elgg_echo("item:{$type}:{$subtype}")));
			echo elgg_view_module('main', $title, $body);
		}
	}
	else {
		$attr = $type . '_limit';
		$title = elgg_echo('spam_throttle:settings:subtype', array(ucfirst($type)));
		$body = elgg_view('input/text', array(
			'name' => "params[{$attr}]",
			'value' => $vars['entity']->$attr,
		));
		$body .= ' ' . elgg_echo('spam_throttle:helptext:limit', array(ucfirst($type))) . "<br>";

		$attr = $type . '_time';
		$body .= elgg_view('input/text', array(
			'name' => "params[{$attr}]",
			'value' => $vars['entity']->$attr,
		));
		$body .= " " . elgg_echo('spam_throttle:helptext:time') . '<br><br>';
		
		// action to perform if threshold is broken
		$attr = $type . '_consequence';
		$body .= elgg_view('input/dropdown', array(
			'name' => "params[{$attr}]",
			'value' => $vars['entity']->$attr ? $vars['entity']->$attr : 'suspend',
			'options_values' => array(
				'nothing' => elgg_echo('spam_throttle:nothing'),
				'suspend' => elgg_echo('spam_throttle:suspend'),
				'ban' => elgg_echo('spam_throttle:ban'),
				'delete' => elgg_echo('spam_throttle:delete')
			)
		));
		$body .= elgg_echo('spam_throttle:consequence:title', array(ucfirst($type)));
		echo elgg_view_module('main', $title, $body);
	}
}


// length of time of a suspension
echo "<h2>" . elgg_echo('spam_throttle:suspensiontime') . "</h2><br>";
echo elgg_view('input/text', array(
	'name' => 'params[suspensiontime]',
	'value' => isset($vars['entity']->suspensiontime) ? $vars['entity']->suspensiontime : 24,
));
echo " " . elgg_echo('spam_throttle:helptext:suspensiontime') . "<br><br>";

// period for reporting, once in x hours to pre
echo "<h2>" . elgg_echo('spam_throttle:reporttime') . "</h2><br>";
echo elgg_view('input/text', array(
	'name' => 'params[reporttime]',
	'value' => isset($vars['entity']->reporttime) ? $vars['entity']->reporttime : 24
));
echo " " . elgg_echo('spam_throttle:helptext:reporttime') . "<br><br>";

?>

<script>
	$('input[type="text"]').css('width', '50px');
</script>