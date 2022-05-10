<?php
/**
 * Merge Attendance Chart & Absence Summary
 *
 * @package RosarioSIS
 * @subpackage Attendance
 */

DrawHeader( ProgramTitle() );

$_REQUEST['report'] = issetVal( $_REQUEST['report'], '' );

$report_link = PreparePHP_SELF(
	[],
	[ 'report', 'attendance' ]
) . '&report=';

$tmp_allow_edit = false;

if ( ! AllowEdit() )
{
	// Temporary AllowEdit for non admin users for SelectIpnut display.
	$_ROSARIO['allow_edit'] = true;

	$tmp_allow_edit = true;
}

$report_select = SelectInput(
	$_REQUEST['report'],
	'report',
	'',
	[
		'' => ( User( 'PROFILE' ) === 'admin' || User( 'PROFILE' ) === 'teacher' ?
			_( 'Attendance Chart' ) :
			_( 'Daily Summary' ) ),
		'absence' => _( 'Absence Summary' ),
	],
	false,
	'onchange="' . AttrEscape( 'ajaxLink(' . json_encode( $report_link ) . ' + this.value);' ) . '" autocomplete="off"',
	false
);

if ( $tmp_allow_edit )
{
	// Remove temporary AllowEdit for non admin users for SelectIpnut display.
	$_ROSARIO['allow_edit'] = false;
}

DrawHeader( $report_select );

if ( $_REQUEST['report'] === 'absence' )
{
	require_once 'modules/Attendance/includes/StudentSummary.php';
}
else
{
	require_once 'modules/Attendance/includes/DailySummary.php';
}
