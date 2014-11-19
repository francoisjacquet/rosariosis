<?php
//modif Francois: Moodle integrator

//XMLRPC client for Moodle 2
require_once('plugins/Moodle/curl.php');

// XML-RPC CALL
function moodle_xmlrpc_call($functionname, $object)
{
	$serverurl = MOODLE_URL . '/webservice/xmlrpc/server.php'. '?wstoken=' . MOODLE_TOKEN;
	$curl = new curl;
	//var_dump($object);
	if (empty($object))
		return null;
	$post = xmlrpc_encode_request($functionname, $object, array('encoding' => 'utf-8', 'escaping' => 'markup'));
	$resp = xmlrpc_decode($curl->post($serverurl, $post), 'utf-8');
	if (get_xmlrpc_error($resp))
		//handle the positive response
		return call_user_func($functionname.'_response', $resp);
	else
		return false;
}

//adds the error message to the global $error variable
function get_xmlrpc_error($resp)
{
	global $error;

	if (is_array($resp) && xmlrpc_is_fault($resp))
	{
		$message = 'Moodle Integrator - '.$resp['faultCode'].' - '.$resp['faultString'];
		$error[] = $message;
		return false;
	}
	
	return true;
}
