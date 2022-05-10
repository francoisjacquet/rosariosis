<?php
/**
 * Merge Schedule Report & Master Schedule Report
 *
 * @package RosarioSIS
 * @subpackage Scheduling
 */

DrawHeader( ProgramTitle() );

$_REQUEST['subject_id'] = issetVal( $_REQUEST['subject_id'], '' );
$_REQUEST['course_id'] = issetVal( $_REQUEST['course_id'], '' );
$_REQUEST['course_period_id'] = issetVal( $_REQUEST['course_period_id'], '' );
$_REQUEST['include_child_mps'] = issetVal( $_REQUEST['include_child_mps'], '' );

$_REQUEST['report'] = issetVal( $_REQUEST['report'], '' );

$report_link = PreparePHP_SELF(
	[],
	[ 'report', 'modfunc', 'subject_id', 'course_id', 'course_period_id', 'include_child_mps' ]
) . '&report=';

$report_select = SelectInput(
	$_REQUEST['report'],
	'report',
	'',
	[
		'' => _( 'Schedule Report' ),
		'master' => _( 'Master Schedule Report' ),
	],
	false,
	'onchange="' . AttrEscape( 'ajaxLink(' . json_encode( $report_link ) . ' + this.value);' ) . '" autocomplete="off"',
	false
);

DrawHeader( $report_select );

if ( $_REQUEST['report'] === 'master' )
{
	require_once 'modules/Scheduling/includes/MasterScheduleReport.php';
}
else
{
	require_once 'modules/Scheduling/includes/ScheduleReport.php';
}
