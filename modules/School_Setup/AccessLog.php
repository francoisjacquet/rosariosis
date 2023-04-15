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

require_once 'ProgramFunctions/UserAgent.fnc.php';

DrawHeader( ProgramTitle() );

// Set start date.
$start_date = RequestedDate( 'start', date( 'Y-m-d', time() - 60 * 60 * 24 ) );

// Set end date.
$end_date = RequestedDate( 'end', DBDate() );


if ( $_REQUEST['modfunc'] === 'delete' )
{
	// Prompt before deleting log.
	if ( DeletePrompt( _( 'Access Log' ) ) )
	{
		DBQuery( 'DELETE FROM access_log' );

		$note[] = _( 'Access Log cleared.' );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}
}

echo ErrorMessage( $note, 'note' );

if ( ! $_REQUEST['modfunc'] )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname']  ) . '" method="GET">';

	DrawHeader(
		_( 'From' ) . ' ' . DateInput( $start_date, 'start', '', false, false ) . ' - ' .
		_( 'To' ) . ' ' . DateInput( $end_date, 'end', '', false, false ) .
		Buttons( _( 'Go' ) )
	);

	echo '</form>';

	// Format DB data.
	$access_logs_functions = [
		'STATUS' => '_makeAccessLogStatus', // Translate status.
		'PROFILE' => '_makeAccessLogProfile', // Translate profile.
		'USERNAME' => '_makeAccessLogUsername', // Add link to user info.
		'CREATED_AT' => 'ProperDateTime', // Display localized & preferred Date & Time.
		'USER_AGENT' => '_makeAccessLogUserAgent', // Display Browser & OS.
	];

	$access_logs_RET = DBGet( "SELECT
		DISTINCT USERNAME,PROFILE,CREATED_AT,IP_ADDRESS,STATUS,USER_AGENT
		FROM access_log
		WHERE CREATED_AT >='" . $start_date . "'
		AND CREATED_AT <='" . $end_date . ' 23:59:59' . "'
		ORDER BY CREATED_AT DESC
		LIMIT 3000", $access_logs_functions );

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete' ) . '" method="POST">';

	DrawHeader( '', SubmitButton( _( 'Clear Log' ), '', '' ) );

	ListOutput(
		$access_logs_RET,
		[
			'CREATED_AT' => _( 'Date' ),
			'USERNAME' => _( 'Username' ),
			'PROFILE' => _( 'User Profile' ),
			'STATUS' => _( 'Status' ),
			'IP_ADDRESS' => _( 'IP Address' ),
			'USER_AGENT' => _( 'Browser' ),
		],
		'Login record',
		'Login records',
		[],
		[],
		[
			'count' => true,
			'save' => true,
			// @since 10.9 Add pagination for list > 1000 results
			'pagination' => true,
		]
	);

	echo '</form>';

	// When clicking on Username, go to Student or User Info. ?>
<script>
	$('.al-username').attr('href', function(){
		var url = 'Modules.php?modname=Users/User.php&search_modfunc=list&next_modname=Users/User.php&';

		if ( $(this).hasClass('student') ) {
			url = url.replace( /Users\/User\.php/g, 'Students/Student.php' ) + 'cust[USERNAME]=';
		} else {
			url += 'username=';
		}

		return url + this.firstChild.data;
	});
</script>
	<?php
}


/**
 * Make Status
 * Successful Login or Failed Login
 *
 * Local function
 * DBGet callback
 *
 * @since 3.0
 * @since 3.5 Banned status.
 *
 * @param  string $value   Field value.
 * @param  string $name    'STATUS'.
 *
 * @return string          Success or Banned or Fail.
 */
function _makeAccessLogStatus( $value, $column )
{
	if ( $value === 'B' )
	{
		return '<span style="color: red;">' . _( 'Banned' ) . '</span>';
	}

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
 * @since 3.0
 *
 * @param  string $value   Field value.
 * @param  string $name    'PROFILE'.
 *
 * @return string          Student, Administrator, Teacher, Parent, or No Access.
 */
function _makeAccessLogProfile( $value, $column )
{
	$profile_options = [
		'student' => _( 'Student' ),
		'admin' => _( 'Administrator' ),
		'teacher' => _( 'Teacher' ),
		'parent' => _( 'Parent' ),
		'none' => _( 'No Access' ),
	];

	if ( ! isset( $profile_options[ $value ] ) )
	{
		return '';
	}

	return $profile_options[ $value ];
}


/**
 * Make Username
 * Links to user info page.
 *
 * Local function
 * DBGet callback
 *
 * @since 3.0
 *
 * @param  string $value   Field value.
 * @param  string $name    'USERNAME'.
 *
 * @return string          USername linking to user info page.
 */
function _makeAccessLogUsername( $value, $column )
{
	global $THIS_RET;

	if ( ! $value )
	{
		return '';
	}

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $value;
	}

	return '<a class="al-username ' .
		( $THIS_RET['PROFILE'] === 'student' ? 'student' : '' ) .
		'" href="#">' . $value . '</a>';
}


/**
 * Make User Agent
 *
 * Local function
 * DBGet callback
 *
 * @since 3.0
 *
 * @link http://php.net/get-browser
 *
 * @param  string $value   Field value.
 * @param  string $name    'USER_AGENT'.
 *
 * @return string          Browser (OS).
 */
function _makeAccessLogUserAgent( $value, $column )
{
	if ( empty( $value ) )
	{
		return $value;
	}

	$os = GetUserAgentOS( $value );

	if ( $os )
	{
		$os = ' (' . $os . ')';
	}

	return GetUserAgentBrowser( $value ) . $os;
}
