<?php
/**
 * Implementation for PHP xmlrpc extension functions not included by default.
 * PHP8 has dropped support for xmlrpc extension.
 *
 * @since 7.6
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

// @since 7.6 PHP8 no xmlrpc ext: load XML_RPC compat classes.
require_once 'classes/PHPCompatibility/Xmlrpc/XML_RPC.php';
require_once 'classes/PHPCompatibility/Xmlrpc/XmlrpcDecoder.php';
require_once 'classes/PHPCompatibility/Xmlrpc/XmlrpcEncoder.php';

if ( ! function_exists( 'xmlrpc_decode' ) ) :

	function xmlrpc_decode( $xml, $encoding = 'iso-8859-1' )
	{
		return XML_RPC::xmlrpc_decode( $xml, $encoding );
	}

endif;

if ( ! function_exists( 'xmlrpc_is_fault' ) ) :

	function xmlrpc_is_fault( $arg )
	{
		return XML_RPC::xmlrpc_is_fault( $arg );
	}

endif;

if ( ! function_exists( 'xmlrpc_encode_request' ) ) :

	function xmlrpc_encode_request( $method, $params, $options = [] )
	{
		return XML_RPC::xmlrpc_encode_request( $method, $params, $options );
	}

endif;
