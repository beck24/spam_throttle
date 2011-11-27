<?php

/**
* check if a user is over the threshold for content creation
*
* @param string $event
* @param string $object_type
* @param ElggObject $object
* @return boolean
*/
function spam_throttle_check($event, $object_type, $object) {
	
	// release exempt users
	if(isadminloggedin()){
		return;
	}
	
	$exempt = unserialize(get_plugin_setting('exempt', 'spam_throttle'));
	if(is_array($exempt) && in_array(get_loggedin_userid(), $exempt)){
		return;
	}
	
	// reported content doesn't count (also this prevents an infinite loop...)
	if($object->getSubtype() == 'reported_content'){
		return;
	}
	
	// delete the content and warn them if they are on a suspension
	if(get_loggedin_user()->spam_throttle_suspension > time()){
		$timeleft = get_loggedin_user()->spam_throttle_suspension - time();
		$hours = ($timeleft - ($timeleft % 3600))/3600;
		$minutes = round(($timeleft % 3600)/60);
		register_error(sprintf(elgg_echo('spam_throttle:suspended'), $hours, $minutes));
		return FALSE;
	}
	
	// They've made it this far, time to check if they've exceeded limits or not
	
	// first check for global setting
	$globallimit = get_plugin_setting('global_limit', 'spam_throttle');
	$globaltime = get_plugin_setting('global_time', 'spam_throttle');
	
	if(is_numeric($globallimit) && $globallimit > 0 && is_numeric($globaltime) && $globaltime > 0){
		
		// because 2 are created initially
		if($object->getSubtype() == 'messages'){
			$globallimit++;
		}
		
		// we have globals set, lets give it a test
		$params = array(
			'type' => 'object',
			'created_time_lower' => time() - ($globaltime * 60),
			'owner_guids' => array(get_loggedin_userid()),
			'count' => TRUE,
		);
		
		$entitycount = elgg_get_entities($params);
		$commentcount = count_annotations(0, "", "", "generic_comment", "", "", get_loggedin_userid(), $params['created_time_lower'], 0);
		
		$activitytotal = $entitycount + $commentcount;
		
		if($activitytotal > $globallimit){
			spam_throttle_limit_exceeded($globaltime, $activitytotal, "Activity");
			
			// not returning false in case of false positive
			return;
		}
	}
	
	if($object_type == 'object'){
		// 	if we're still going now we haven't exceeded globals, check for individual types
		$limit = get_plugin_setting($object->getSubtype().'_limit', 'spam_throttle');
		$time = get_plugin_setting($object->getSubtype().'_time', 'spam_throttle');
	
		if(is_numeric($limit) && $limit > 0 && is_numeric($time) && $time > 0){
		
			// because 2 are created initially
			if($object->getSubtype() == 'messages'){
				$limit++;
			}
		
			// 	we have globals set, lets give it a test
			$params = array(
				'type' => 'object',
				'subtypes' => array($object->getSubtype()),
				'created_time_lower' => time() - ($time * 60),
				'owner_guids' => array(get_loggedin_userid()),
				'count' => TRUE,
			);
		
			$entitycount = elgg_get_entities($params);
		
			if($entitycount > $limit){
				spam_throttle_limit_exceeded($time, $entitycount, $object->getSubtype());
			
				// not returning false in case of false positive
				return;
			}
		}
		return;
	}
	
	// now we check for comments
	if($object_type == 'annotation'){
		if($object->name == 'generic_comment'){
			$limit = get_plugin_setting('annotation_generic_comment_limit', 'spam_throttle');
			$time = get_plugin_setting('annotation_generic_comment_time', 'spam_throttle');
	
			if(is_numeric($limit) && $limit > 0 && is_numeric($time) && $time > 0){

				$commentcount = count_annotations(0, "", "", "generic_comment", "", "", get_loggedin_userid(), time() - (60*$time), 0);
			
				if($commentcount > $limit){
					spam_throttle_limit_exceeded($time, $commentcount, 'generic_comment');
				
					// not returning false in case of false positive
					return;
				}
			}
			return;
		}
		return;
	}
}


function spam_throttle_limit_exceeded($time, $created, $type){
		
	$report = new ElggObject;
	$report->subtype = "reported_content";
	$report->owner_guid = get_loggedin_userid();
	$report->title = elgg_echo('spam_throttle');
	$report->address = get_loggedin_user()->getURL();
	$report->description = sprintf(elgg_echo('spam_throttle:reported'), $type, $created, $time);
	$report->access_id = ACCESS_PRIVATE;
	$report->save();
	
	$consequence = get_plugin_setting('consequence', 'spam_throttle');
	
	switch ($consequence){
		case "nothing":
			break;
		
		case "suspend":
			$user = get_loggedin_user();
			$suspensiontime = get_plugin_setting('suspensiontime', 'spam_throttle');
			$user->spam_throttle_suspension = time() + 60*60*$suspensiontime;
			register_error(sprintf(elgg_echo('spam_throttle:suspended'), $suspensiontime, '0'));
			break;
			
		case "ban":
			ban_user(get_loggedin_userid(), elgg_echo('spam_throttle:banned'));
			logout();
			register_error(elgg_echo('spam_throttle:banned'));
			forward($CONFIG->url);
			break;
			
		case "delete":
			$user = get_loggedin_user();
			$user->delete();
			register_error(elgg_echo('spam_throttle:deleted'));
			break;
			
		default:
			break;
	}
}

// checks whether a value is a positive integer
// returns boolean true/false
function spam_throttle_posint($value){
	if(is_numeric($value) && $value > 0){
		return TRUE;
	}
	return FALSE;
}