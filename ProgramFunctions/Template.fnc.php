<?php
/**
 * Template functions
 * Templates let you save a per user template.
 * Useful for Document, Letter, or Email related programs.
 * Default templates must be saved to DB under STAFF_ID 0.
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Get Template
 * For program and user.
 *
 * @since 3.6
 *
 * @example GetTemplate( $_REQUEST['modname'], User( 'STAFF_ID' ) )
 *
 * @param string  $modname Specify program name (optional) defaults to current program.
 * @param integer $staff_id User ID (optional), default to logged in User.
 *
 * @return string Empty if no template found, else Default or user template.
 */
function GetTemplate( $modname = '', $staff_id = 0 ) {

	if ( ! $modname )
	{
		$modname = $_REQUEST['modname'];
	}

	if ( ! $staff_id )
	{
		$staff_id = User( 'STAFF_ID' );
	}

	$template_RET = DBGet( "SELECT TEMPLATE,STAFF_ID
		FROM TEMPLATES
		WHERE MODNAME='" . $modname . "'
		AND STAFF_ID IN(0,'" . $staff_id . "')", array(), array( 'STAFF_ID' ) );

	if ( ! $template_RET )
	{
		return '';
	}

	if ( ! isset( $template_RET[ $staff_id ] ) )
	{
		// User has no saved template yet, get the default one.
		$staff_id = 0;
	}

	return $template_RET[ $staff_id ][1]['TEMPLATE'];
}


/**
 * Save Template
 *
 * @since 3.6
 *
 * @param string  $template Temaplate text or HTML (use SanitizeHTML() first!).
 * @param string  $modname  Specify program name (optional) defaults to current program.
 * @param integer $staff_id User ID (optional), defaults to logged in User, use 0 for default template.
 *
 * @return boolean False if no template found, else true if saved.
 */
function SaveTemplate( $template, $modname = '', $staff_id = -1 )
{
	if ( ! $modname )
	{
		$modname = $_REQUEST['modname'];
	}

	if ( $staff_id < 0 )
	{
		$staff_id = User( 'STAFF_ID' );
	}

	$is_template_update = DBGet( "SELECT STAFF_ID
		FROM TEMPLATES
		WHERE MODNAME='" . $modname . "'
		AND STAFF_ID IN(0,'" . $staff_id . "')", array(), array( 'STAFF_ID' ) );

	if ( ! $is_template_update )
	{
		// Default template not found for modname.
		return false;
	}

	if ( ! isset( $is_template_update[ $staff_id ] ) )
	{
		// Default template only, insert user template.
		DBQuery( "INSERT INTO TEMPLATES (MODNAME,STAFF_ID,TEMPLATE)
			VALUES('" . $modname . "','" . $staff_id . "',
			'" . $template . "')" );
	}
	else
	{
		// Update user template.
		DBQuery( "UPDATE TEMPLATES
			SET TEMPLATE='" . $template . "'
			WHERE MODNAME='" . $modname . "'
			AND STAFF_ID='" . $staff_id . "'" );
	}

	return true;
}

