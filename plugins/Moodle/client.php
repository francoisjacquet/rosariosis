<?php
/**
 * Moodle integrator
 *
 * XMLRPC client for Moodle 2
 */

// Load cURL class.
require_once 'classes/curl.php';

// @since 7.6 PHP8 no xmlrpc ext: load xmlrpc compat functions.
require_once 'ProgramFunctions/PHPCompatibility/xmlrpc.php';

/**
 * XML-RPC Call
 *
 * @param  string $functionname Webservice function name
 * @param  array  $object       Object to POST to function
 *
 * @return bool                 false if failure or empty object, else null (Response function answer)
 */
function moodle_xmlrpc_call( $functionname, $object )
{
	$serverurl = MOODLE_URL . '/webservice/xmlrpc/server.php?wstoken=' . MOODLE_TOKEN;

	$curl = new curl;

	$curl->setHeader( 'Content-type: text/xml' );

	//var_dump($object);

	if ( empty( $object ) )
	{
		return false;
	}

	$post = xmlrpc_encode_request(
		$functionname,
		$object,
		[ 'encoding' => 'utf-8', 'escaping' => 'markup' ]
	);

	$resp = xmlrpc_decode( $curl->post( $serverurl, $post ), 'utf-8' );

	if ( get_xmlrpc_error( $resp ) )
	{
		// Handle the positive response.
		return call_user_func( $functionname . '_response', $resp );
	}

	return false;
}

/**
 * Get the XML RPC error if any
 * Adds the error message to the global $error variable
 *
 * @param  string|array $resp cURL POST response.
 *
 * @return bool               false on error, else true
 */
function get_xmlrpc_error( $resp )
{
	global $error;

	if ( is_array( $resp )
		&& xmlrpc_is_fault( $resp ) )
	{
		$message = 'Moodle: ' . $resp['faultCode'] . ' - ' . $resp['faultString'];

		$error[] = $message;

		return false;
	}
	elseif ( is_string( $resp )
		&& ! empty( $resp ) )
	{
		$error[] = $resp;

		return false;
	}

	return true;
}
