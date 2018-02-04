<?php
/**
 * Implementation for PHP json extension functions not included by default.
 *
 * @since 3.8
 *
 * @copyright WordPress
 *
 * @package RosarioSIS
 * @subpackage functions
 */

if ( !function_exists('json_encode') ) {
	function json_encode( $string ) {
		global $RosarioJSON;

		if ( ! ( $RosarioJSON instanceof Services_JSON ) ) {
			require_once 'classes/PHPCompatibility/Services_JSON.php';
			$RosarioJSON = new Services_JSON();
		}

		return $RosarioJSON->encodeUnsafe( $string );
	}
}

if ( !function_exists('json_decode') ) {
	/**
	 * @global Services_JSON $RosarioJSON
	 * @param string $string
	 * @param bool   $assoc_array
	 * @return object|array
	 */
	function json_decode( $string, $assoc_array = false ) {
		global $RosarioJSON;

		if ( ! ($RosarioJSON instanceof Services_JSON ) ) {
			require_once 'classes/PHPCompatibility/Services_JSON.php';
			$RosarioJSON = new Services_JSON();
		}

		$res = $RosarioJSON->decode( $string );
		if ( $assoc_array )
			$res = _json_decode_object_helper( $res );
		return $res;
	}

	/**
	 * @param object $data
	 * @return array
	 */
	function _json_decode_object_helper($data) {
		if ( is_object($data) )
			$data = get_object_vars($data);
		return is_array($data) ? array_map(__FUNCTION__, $data) : $data;
	}
}

// JSON_PRETTY_PRINT was introduced in PHP 5.4
// Defined here to prevent a notice when using it with wp_json_encode() !! TODO check if notice? !!
if ( ! defined( 'JSON_PRETTY_PRINT' ) ) {
	define( 'JSON_PRETTY_PRINT', 128 );
}

if ( ! function_exists( 'json_last_error_msg' ) ) :
	/**
	 * Retrieves the error string of the last json_encode() or json_decode() call.
	 *
	 * @since 4.4.0
	 *
	 * @internal This is a compatibility function for PHP <5.5
	 *
	 * @return bool|string Returns the error message on success, "No Error" if no error has occurred,
	 *                     or false on failure.
	 */
	function json_last_error_msg() {
		// See https://core.trac.wordpress.org/ticket/27799.
		if ( ! function_exists( 'json_last_error' ) ) {
			return false;
		}

		$last_error_code = json_last_error();

		// Just in case JSON_ERROR_NONE is not defined.
		$error_code_none = defined( 'JSON_ERROR_NONE' ) ? JSON_ERROR_NONE : 0;

		switch ( true ) {
			case $last_error_code === $error_code_none:
				return 'No error';

			case defined( 'JSON_ERROR_DEPTH' ) && JSON_ERROR_DEPTH === $last_error_code:
				return 'Maximum stack depth exceeded';

			case defined( 'JSON_ERROR_STATE_MISMATCH' ) && JSON_ERROR_STATE_MISMATCH === $last_error_code:
				return 'State mismatch (invalid or malformed JSON)';

			case defined( 'JSON_ERROR_CTRL_CHAR' ) && JSON_ERROR_CTRL_CHAR === $last_error_code:
				return 'Control character error, possibly incorrectly encoded';

			case defined( 'JSON_ERROR_SYNTAX' ) && JSON_ERROR_SYNTAX === $last_error_code:
				return 'Syntax error';

			case defined( 'JSON_ERROR_UTF8' ) && JSON_ERROR_UTF8 === $last_error_code:
				return 'Malformed UTF-8 characters, possibly incorrectly encoded';

			case defined( 'JSON_ERROR_RECURSION' ) && JSON_ERROR_RECURSION === $last_error_code:
				return 'Recursion detected';

			case defined( 'JSON_ERROR_INF_OR_NAN' ) && JSON_ERROR_INF_OR_NAN === $last_error_code:
				return 'Inf and NaN cannot be JSON encoded';

			case defined( 'JSON_ERROR_UNSUPPORTED_TYPE' ) && JSON_ERROR_UNSUPPORTED_TYPE === $last_error_code:
				return 'Type is not supported';

			default:
				return 'An unknown error occurred';
		}
	}
endif;

if ( ! interface_exists( 'JsonSerializable' ) ) {
	define( 'WP_JSON_SERIALIZE_COMPATIBLE', true ); // !! TODO check for constant... !!
	/**
	 * JsonSerializable interface.
	 *
	 * Compatibility shim for PHP <5.4
	 *
	 * @link https://secure.php.net/jsonserializable
	 *
	 * @since 4.4.0
	 */
	interface JsonSerializable {
		public function jsonSerialize();
	}
}
