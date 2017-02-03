<?php
/**
 * Access Log program
 *
 * @since 3.0
 *
 * Original module:
 * @copyright @dpredster
 * @link https://github.com/dpredster/Access_Log/ (Original extra module, now deprecated)
 *
 * @package RosarioSIS
 * @subpackage modules
 */

DrawHeader( ProgramTitle() );

// Set start date as the 1st of the month and end date as current day.
$start_date = date( 'Y-m' ) . '-01';
$end_date = DBDate();

// Requested start date.
if ( isset( $_REQUEST['day_start'] )
	&& isset( $_REQUEST['month_start'] )
	&& isset( $_REQUEST['year_start'] ) )
{
	$start_date = RequestedDate(
		$_REQUEST['year_start'],
		$_REQUEST['month_start'],
		$_REQUEST['day_start']
	);
}

// Requested end date.
if ( isset( $_REQUEST['day_end'] )
	&& isset( $_REQUEST['month_end'] )
	&& isset( $_REQUEST['year_end'] ) )
{
	$end_date = RequestedDate(
		$_REQUEST['year_end'],
		$_REQUEST['month_end'],
		$_REQUEST['day_end']
	);
}

if ( ! $_REQUEST['modfunc'] )
{
	echo '<FORM name="log" id="log" action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="GET">';

	DrawHeader(
		_( 'From' ) . ' ' . PrepareDate( $start_date, '_start' ) . ' - ' .
		_( 'To' ) . ' ' . PrepareDate( $end_date, '_end' ) .
		Buttons( _( 'Go' ) )
	);

	echo '</form>';

	// Format DB data.
	$alllogs_functions = array(
		'STATUS' => '_makeAccessLogStatus', // Translate status.
		'PROFILE' => '_makeAccessLogProfile', // Translate profile.
		'LOGIN_TIME' => 'ProperDateTime', // Display localized & preferred Date & Time.
	);

	$alllogs_RET = DBGet( DBQuery( "SELECT
		DISTINCT USERNAME,PROFILE,LOGIN_TIME,IP_ADDRESS,STATUS
		FROM ACCESS_LOG
		WHERE LOGIN_TIME >='" . $start_date . "'
		AND LOGIN_TIME <='" . $end_date . ' 23:59:59' . "'
		ORDER BY LOGIN_TIME DESC" ), $alllogs_functions );

	echo '<form name="del" id="del" action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Clear Log' ), 'del' ) );

	ListOutput(
		$alllogs_RET,
		array(
			'LOGIN_TIME' => _( 'Date' ),
			'USERNAME' => _( 'Username' ),
			'PROFILE' => _( 'User Profile' ),
			'STATUS' => _( 'Status' ),
			'IP_ADDRESS' => _( 'IP Address' ),
		),
		'Login record',
		'Login records',
		array(),
		array(),
		array( 'count' => true, 'save' => true )
	);

	echo '<div class="center">' . SubmitButton( _( 'Clear Log' ), 'del' ) . '</div>';

	echo '</form>';
}

if ( $_REQUEST['modfunc'] == 'delete' )
{
	// Prompt before deleting log.
	if ( DeletePrompt( _( 'Access Log' ) ) )
	{
		DBQuery( 'DELETE FROM ACCESS_LOG' );

		$note[] = _( 'Access Log cleared.' );

		echo ErrorMessage( $note, 'note' );
	}
}


/**
 * Make Status
 * Successful Login or Failed Login
 *
 * Local function
 * DBGet callback
 *
 * @since 1.2
 *
 * @param  string $value   Field value.
 * @param  string $name    'STATUS'.
 *
 * @return string          Success or Fail.
 */
function _makeAccessLogStatus( $value, $column )
{
	if ( $value
		&& $value !== 'Failed Login' ) // Compatibility with version 1.1.
	{
		return _( 'Success' );
	}

	return _( 'Fail' );
}


/**
 * Make Profile
 * Only for successful logins.
 *
 * Local function
 * DBGet callback
 *
 * @since 1.2
 *
 * @param  string $value   Field value.
 * @param  string $name    'PROFILE'.
 *
 * @return string          Student, Administrator, Teacher, Parent, or No Access.
 */
function _makeAccessLogProfile( $value, $column )
{
	$profile_options = array(
		'student' => _( 'Student' ),
		'admin' => _( 'Administrator' ),
		'teacher' => _( 'Teacher' ),
		'parent' => _( 'Parent' ),
		'none' => _( 'No Access' ),
	);

	if ( ! isset( $profile_options[ $value ] ) )
	{
		return '';
	}

	return $profile_options[ $value ];
}
