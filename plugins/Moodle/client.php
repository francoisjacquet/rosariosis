<?php
/**
 * Moodle integrator
 *
 * API client for Moodle 3.1+
 */

// Load cURL class.
require_once 'classes/curl.php';

/**
 * Moodle API Call
 *
 * @since 11.5
 *
 * @param  string $functionname Webservice function name
 * @param  array  $object       Object to POST to function
 *
 * @return bool                 false if failure or empty object, else null (Response function answer)
 */
function MoodleAPICall( $functionname, $object )
{
	global $error;

	if ( MOODLE_API_PROTOCOL === 'rest' )
	{
		return MoodleRESTCall( $functionname, $object );
	}
	elseif ( MOODLE_API_PROTOCOL === 'xmlrpc' )
	{
		// @since 7.6 PHP8 no xmlrpc ext: load xmlrpc compat functions.
		// @deprecated since RosarioSIS 10.6 Use REST API instead
		require_once 'ProgramFunctions/PHPCompatibility/xmlrpc.php';

		return moodle_xmlrpc_call( $functionname, $object );
	}

	$error[] = 'Moodle: unknown API protocol "' . MOODLE_API_PROTOCOL . '"';

	return false;
}

/**
 * REST Call
 *
 * @since 11.5
 *
 * @link https://github.com/llagerlof/MoodleRest/blob/master/MoodleRest.php
 *
 * @param  string $functionname Webservice function name
 * @param  array  $object       Object to POST to function
 *
 * @return bool                 false if failure or empty object, else null (Response function answer)
 */
function MoodleRESTCall( $functionname, $object )
{
	global $error;

	$serverurl = MOODLE_URL . '/webservice/rest/server.php?wstoken=' . MOODLE_TOKEN .
		'&moodlewsrestformat=json&wsfunction=' . $functionname;

	$curl = new curl;

	$curl->setHeader( 'Content-type: application/x-www-form-urlencoded' );

	//var_dump($object);

	if ( empty( $object ) )
	{
		return false;
	}

	$post = http_build_query( $object );

	$resp = $curl->post( $serverurl, $post );

	$resp_decoded = json_decode( $resp, true );

	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		$error[] = $resp;

		return false;
	}

	//var_dump($resp_decoded);

	if ( ! empty( $resp_decoded['exception'] ) )
	{
		$error[] = 'Moodle: ' . $resp_decoded['errorcode'] . ' - ' . $resp_decoded['message'];

		return false;
	}

	// Handle the positive response.
	return call_user_func( $functionname . '_response', $resp_decoded );
}

/**
 * XML-RPC Call
 *
 * @deprecated since 10.5 Use REST API instead
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
 * @deprecated since 10.5 Use REST API instead
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
