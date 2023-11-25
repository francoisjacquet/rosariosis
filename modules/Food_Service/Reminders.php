<?php

if ( empty( $_SESSION['FSA_type'] ) )
{
	$_SESSION['FSA_type'] = 'student';
}

if ( ! empty( $_REQUEST['type'] ) )
{
	$_SESSION['FSA_type'] = $_REQUEST['type'];
}
else
{
	$_REQUEST['type'] = $_SESSION['FSA_type'];
}

if ( $_REQUEST['modfunc'] != 'save' )
{
	$header = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&type=student' ) . '">' .
		( $_REQUEST['type'] === 'student' ?
		'<b>' . _( 'Students' ) . '</b>' : _( 'Students' ) ) . '</a>';

	$header .= ' | <a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&type=staff' ) . '">' .
		( $_REQUEST['type'] === 'staff' ?
		'<b>' . _( 'Users' ) . '</b>' : _( 'Users' ) ) . '</a>';

	DrawHeader(  ( $_REQUEST['type'] == 'staff' ? _( 'User' ) : _( 'Student' ) ) . ' &minus; ' . ProgramTitle() );
	User( 'PROFILE' ) === 'student' ? '' : DrawHeader( $header );
}

require_once 'modules/Food_Service/' . ( $_REQUEST['type'] == 'staff' ? 'Users' : 'Students' ) . '/Reminders.php';

/**
 * Make Choose Checkbox
 *
 * Local function
 * DBGet() callback
 *
 * @uses MakeChooseCheckbox
 *
 * @param  string $value  STUDENT_ID or STAFF_ID value.
 * @param  string $column 'CHECKBOX'.
 *
 * @return string Checkbox or empty string if balance above Warning / Minimum amounts and Positive
 */
function _makeChooseCheckbox( $value, $column )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['WARNING'] )
		|| ! empty( $THIS_RET['NEGATIVE'] )
		|| ! empty( $THIS_RET['MINIMUM'] ) )
	{
		return MakeChooseCheckbox( $value, $column );
	}
	else
	{
		return '';
	}
}

/**
 * @param $value
 */
function x( $value )
{
	if ( $value )
	{
		return button( 'x' );
	}
	else
	{
		return '&nbsp;';
	}
}

/**
 * @param $value
 * @return mixed
 */
function red( $value )
{
	if ( $value < 0 )
	{
		return '<span style="color:red">' . $value . '</span>';
	}
	else
	{
		return $value;
	}
}


/**
 * Food Service Reminder Output
 *
 * @since 4.3
 * Before 4.3, a reminder() function was used and duplicated in both Students/ & Users/ programs.
 *
 * @param  array $user         User (staff or student) info.
 * @param  float $target       Target amount.
 * @param  array $last_deposit Last deposit DATE & AMOUNT.
 * @param  float $payment      Payment amount.
 * @param  string $note        Note to user.
 * @param  array  $xstudents   Other students on this account (optional).
 */
function FoodServiceReminderOutput( $user, $target, $last_deposit, $payment, $note, $xstudents = [] )
{
	echo '<h2 class="center">' .
		( ! empty( $_REQUEST['year_end'] ) ? _( 'Year End' ) . ' ' : '' ) .
		_( 'Lunch Payment Reminder' ) . '</h2>';

	if ( empty( $user['SCHOOL_TITLE'] ) )
	{
		$user['SCHOOL_TITLE'] = SchoolInfo( 'TITLE' );
	}

	echo '<h3 class="center">' . $user['SCHOOL_TITLE'] . '</h3>';

	echo '<table class="width-100p fixed-col">';
	echo '<tr><td>';

	echo NoInput(
		$user['FULL_NAME'],
		( empty( $user['STUDENT_ID'] ) ? $user['STAFF_ID'] : $user['STUDENT_ID'] )
	);

	if ( count( $xstudents ) )
	{
		echo '<br />' . _( 'Other students on this account' ) . ':';

		foreach ( (array) $xstudents as $xstudent )
		{
			echo '<br />&nbsp;&nbsp;' . $xstudent['FULL_NAME'];
		}
	}

	echo '</td><td>';

	if ( ! empty( $user['GRADE_ID'] ) )
	{
		echo NoInput( $user['GRADE_ID'], _( 'Grade Level' ) );
	}
	echo '</td><td>';

	if ( ! empty( $user['TEACHER'] ) )
	{
		echo NoInput( $user['TEACHER'], _( 'Teacher' ) );
	}
	echo '</td></tr>';


	echo '<tr><td>';
	echo NoInput( ProperDate( DBDate() ), _( 'Date' ) );

	echo '</td><td>';
	echo NoInput(
		( $last_deposit ? $last_deposit['DATE'] : _( 'None' ) ),
		_( 'Date of Last Deposit' )
	);

	echo '</td><td>';
	echo NoInput(
		( $last_deposit ? Currency( $last_deposit['AMOUNT'] ) : _( 'None' ) ),
		_( 'Amount of Last Deposit' )
	);
	echo '</td></tr>';


	echo '<tr><td>';
	echo NoInput(
		( $user['BALANCE'] < 0 ? '<b>' . Currency( $user['BALANCE'] ) . '</b>' : Currency( $user['BALANCE'] ) ),
		_( 'Balance' )
	);

	echo '</td><td>';
	echo '<b>' . NoInput(
		Currency( $payment ),
		( ! empty( $_REQUEST['year_end'] ) ? _( 'Requested Payment' ) : _( 'Mimimum Payment' ) )
	) . ' </b>';

	echo '</td><td>';
	if ( ! empty( $user['ACCOUNT_ID'] ) )
	{
		echo NoInput( $user['ACCOUNT_ID'], _( 'Account ID' ) );
	}
	elseif ( ! empty( $user['PROFILE'] ) )
	{
		$profiles = [
			'admin' => _( 'Administrator' ),
			'teacher' => _( 'Teacher' ),
			'parent' => _( 'Parent' ),
			'none' => _( 'No Access' ),
		];

		echo NoInput( $profiles[ $user['PROFILE'] ], _( 'Profile' ) );
	}
	echo '</td></tr></table>';

	if ( ! empty( $user['FIRST_NAME'] ) )
	{
		$note = str_replace( '%N', $user['FIRST_NAME'], $note );
	}

	// $note = str_replace( '%F', $user['FIRST_NAME'], $note );

	$note = str_replace(
		[ '%g', '%h' ],
		[ 'he/she', 'his/her' ],
		$note
	);

	$note = str_replace( '%P', Currency( $payment ), $note );
	$note = str_replace( '%T', Currency( $target ), $note );

	echo '<p>' . $note . '</p>';
}
