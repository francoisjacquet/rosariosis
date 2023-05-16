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
 * @since 10.2.3 Get template from last school year (rollover ID)
 *
 * @example GetTemplate( $_REQUEST['modname'], User( 'STAFF_ID' ) )
 *
 * @param string  $modname Specify program name (optional) defaults to current program.
 * @param integer $staff_id User ID (optional), default to logged in User.
 *
 * @return string Empty if no template found, else user template or last year's or default.
 */
function GetTemplate( $modname = '', $staff_id = 0 ) {

	if ( ! $modname )
	{
		$modname = $_REQUEST['modname'];
	}

	$rollover_id = 0;

	if ( ! $staff_id )
	{
		$staff_id = User( 'STAFF_ID' );

		$rollover_id = User( 'ROLLOVER_ID' );
	}
	else
	{
		$rollover_id = (int) DBGetOne( "SELECT ROLLOVER_ID
			FROM staff
			WHERE STAFF_ID='" . (int) $staff_id . "'" );
	}

	$staff_id_sql = '';

	if ( $rollover_id )
	{
		// @since 10.2.3 Get template from last school year (rollover ID)
		$staff_id_sql .= ",'" . $rollover_id . "'";
	}

	if ( $staff_id )
	{
		// Fix SQL error when no user in session.
		$staff_id_sql .= ",'" . $staff_id . "'";
	}

	$template = DBGetOne( "SELECT TEMPLATE
		FROM templates
		WHERE MODNAME='" . $modname . "'
		AND STAFF_ID IN(0" . $staff_id_sql . ")
		ORDER BY STAFF_ID DESC
		LIMIT 1" );

	return $template ? $template : '';
}


/**
 * Save Template
 *
 * @since 3.6
 * @since 5.0 Save Template even if no default template found.
 *
 * @example SaveTemplate( DBEscapeString( SanitizeHTML( $_POST['inputfreetext'] ) ) );
 *
 * @param string  $template Template text or HTML (use DBEscapeString() & SanitizeHTML() first!).
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
		FROM templates
		WHERE MODNAME='" . $modname . "'
		AND STAFF_ID IN(0,'" . $staff_id . "')", [], [ 'STAFF_ID' ] );

	/*if ( ! $is_template_update )
	{
		// Default template not found for modname.
		return false;
	}*/

	DBUpsert(
		'templates',
		[ 'TEMPLATE' => $template ],
		[ 'MODNAME' => $modname, 'STAFF_ID' => (int) $staff_id ],
		! isset( $is_template_update[ $staff_id ] ) ? 'insert' : 'update'
	);

	return true;
}

