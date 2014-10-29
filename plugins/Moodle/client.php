<?php
//modif Francois: Moodle integrator

//XMLRPC client for Moodle 2
require_once('modules/Moodle/config.inc.php');
require_once('modules/Moodle/curl.php');

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
	$error = get_xmlrpc_error($resp);
	if (empty($error))
		//handle the positive response
		return call_user_func($functionname.'_response', $resp);
	return $error;
}


function get_xmlrpc_error($resp)
{
	if (is_array($resp) && xmlrpc_is_fault($resp))
	{
		$message = 'Moodle Integrator - '.$resp['faultCode'].' - '.$resp['faultString'];
		return ErrorMessage(array($message), 'error');
	}
	
	return null;
}