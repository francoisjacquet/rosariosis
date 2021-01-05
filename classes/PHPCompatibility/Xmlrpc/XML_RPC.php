<?php
/**
 * XML-RPC Bundle
 *
 * An XML-RPC bundle for laravel I made this as I needed one for CellSix a project I'm
 * working on creating a web interface for Atheme IRC Platform. Didn't see one, so I figured I would share it.
 * Hope someone gets some use out of it. >.<
 *
 * Note:
 * This bundle will try first to use PHP's native xmlrpc_encode_request function however I know a lot
 * of people don't have that installed. This was one of the issues I faced on my project, so if it's not found it
 * will nicely fallback to the functions I've writtin below. This way the user doesn't need to install the PHP-XMLRPC module
 * and this class will still work.
 *
 * @category    Bundle
 * @package     XML-RPC
 * @author      Joseph Newing <jnewing@gmail.com>
 * @license     MIT License <http://www.opensource.org/licenses/mit>
 * @copyright   2012 Joseph Newing
 * @see         https://github.com/jnewing/laravel-xmlrpc
 *
 * @copyright   2020 François Jacquet
 * Add xmlrpc_decode() & xmlrpc_is_fault() functions compatibility.
 * Note: Use XmlrpcDecoder & XmlrpcEncoder classes.
 */

class XML_RPC
{
	/**
	 * Decode()
	 * Get the response returned as a native PHP variable
	 *
	 * @param string $xml      - XML response returned by XMLRPC method.
	 * @param string $encoding - Input encoding supported by iconv.
	 * @return mixed Returns either an array, or an integer, or a string, or a boolean according to the response returned by the XMLRPC method.
	 */
	public static function Decode($xml, $encoding = 'iso-8859-1')
	{
		// get our response
		if (function_exists('xmlrpc_decode'))
		{
			$response = xmlrpc_decode($xml, $encoding);
		}
		else
		{
			$response = XML_RPC::xmlrpc_decode($xml, $encoding);
		}

		// echo 'Decode: ' . var_dump( $response);

		return $response;
	}

	/**
	 * xmlrpc_decode()
	 * function will take the place of 'xmlrpc_decode' if the user does not have
	 * the PHP-XMLRPC package installed on their system.
	 *
	 * @param string $xmlstring  - XML response returned by XMLRPC method.
	 * @param string $encoding   - Input encoding supported by iconv.
	 * @return mixed Returns either an array, or an integer, or a string, or a boolean according to the response returned by the XMLRPC method.
	 */
	public static function xmlrpc_decode($xmlstring, $encoding = 'iso-8859-1')
	{
		$xmlrpc_decoder = new XmlrpcDecoder();

		try
		{
			$array = $xmlrpc_decoder->decodeResponse($xmlstring);
		}
		catch ( Exception $e )
		{
			return $xmlstring;
		}

		return $array;
	}

	/**
	 * IsFault()
	 * Determines if an array value represents an XMLRPC fault
	 *
	 * @param string $arg      - Array returned by xmlrpc_decode().
	 * @return bool Returns true if the argument means fault, false otherwise. Fault description is available in $arg["faultString"], fault code is in $arg["faultCode"].
	 */
	public static function IsFault($arg)
	{
		// check fault
		if (function_exists('xmlrpc_is_fault'))
		{
			$is_fault = xmlrpc_is_fault($arg);
		}
		else
		{
			$is_fault = XML_RPC::xmlrpc_is_fault($arg);
		}

		return $is_fault;
	}

	/**
	 * xmlrpc_is_fault()
	 * function will take the place of 'xmlrpc_is_fault' if the user does not have
	 * the PHP-XMLRPC package installed on their system.
	 *
	 * @param string $arg      - Array returned by xmlrpc_decode().
	 * @return bool Returns true if the argument means fault, false otherwise. Fault description is available in $arg["faultString"], fault code is in $arg["faultCode"].
	 */
	public static function xmlrpc_is_fault($arg)
	{
		if ( ! is_array( $arg ) )
		{
			return false;
		}

		return isset( $arg['faultString'] ) && isset( $arg['faultCode'] );
	}

	/**
	 * EncodeRequest()
	 * Generates XML for a method request
	 *
	 * @param string $method    - XML-RPC method we wish to call
	 * @param mixed  $params    - additional parameters that acompnay the $method
	 * @param array  $options   - the XML-RPC options
	 * @return string Returns a string containing the XML representation of the request.
	 */
	public static function EncodeRequest($method, $params, $options)
	{
		// build our payload
		if (function_exists('xmlrpc_encode_request'))
		{
			$payload = xmlrpc_encode_request($method, $params, $options);
		}
		else
		{
			$payload = XML_RPC::xmlrpc_encode_request($method, $params, $options);
		}

		// echo 'Encode: ' . var_dump( $payload);

		return $payload;
	}

	/**
	 * xmlrpc_encode_request()
	 * function will take the place of 'xmlrpc_encode_request' if the user does not have
	 * the PHP-XMLRPC package installed on their system.
	 *
	 * @param string $method    - the XML-RPC method they wish to call
	 * @param mixed  $params    - the XML-RPC parameters they wish to use along with it
	 * @param array  $options   - the XML-RPC options
	 * @return string $payload  - the XML-RPC request payload as a string
	 */
	public static function xmlrpc_encode_request($method, $params, $options)
	{
		$encoding = 'iso-8859-1';

		if ( ! empty( $options['encoding'] ) )
		{
			$encoding = $options['encoding'];
		}

		$xmlrpc_encoder = new XmlrpcEncoder( $encoding );

		try
		{
			$payload = $xmlrpc_encoder->encodeCall($method, $params);
		}
		catch ( Exception $e )
		{
			ErrorMessage( 'XmlrpcEncoder::encodeCall error: ' . $e->getMessage(), 'fatal' );
		}

		return $payload;
	}
}
