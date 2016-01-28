<?php
/**
 * Get raw $_POST variables function
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Get raw `$_POST` variables
 * Bypass `strip_tags` on the `$_REQUEST` vars,
 * prefer `$_GET` or `$_POST` but use them with care!
 *
 * @see used to get TinyMCE textarea content
 *
 * @param string $key $_POST array key.
 *
 * @return Raw $_POST array value
 */
function GetRawPOSTvar( $key )
{
	$rawpost = '&' . file_get_contents( 'php://input' ); 

	$pos = preg_match( '/&' . $key . '=([^&]*)/i', $rawpost, $regs );

	if ( $pos == 1 )
	{
		return urldecode( $regs[1] );
	else
	{
		return null;
	}
}
