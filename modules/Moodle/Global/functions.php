<?php

//modif Francois: Moodle integrator


//local_getcontexts_get_contexts function
function local_getcontexts_get_contexts_object()
{
	//first, gather the necessary variables
	// !! for this function, the variables must respect this naming convention !!
	global $moodle_instance, $moodle_contextlevel;
	
	
	//then, convert variables for the Moodle object:
/*
list of ( 
	object {
		contextlevel int   //the context level, for example CONTEXT_COURSE, or CONTEXT_MODULE.
		instance int   //the instance id. For contextlevel = CONTEXT_COURSE, this would be $course->id, for contextlevel = CONTEXT_MODULE, this would be $cm->id. And so on.
	} 
)
*/

	$contexts = array(
					array(
						'contextlevel' => $moodle_contextlevel,
						'instance' => $moodle_instance,
					)
				);
	
	return array($contexts);
}


function local_getcontexts_get_contexts_response($response)
{
/*
list of ( 
	object {
		id int   //context id
		contextlevel int   //the context level
		instance int   //the instance id
		path string   //path to context
		depth int 
	} 
)
*/
	return $response;
}

?>