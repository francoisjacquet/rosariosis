<?php

require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/SendEmail.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';

// This was a quick hack to email parents who were assigned accounts but had never logged in
// Warning: the passwords associated to the accounts will be reset

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'save' )
{
	// If $test email is set then this script will only 'go through the motions'
	// and email the results to the $test_email address instead of parents.
	$test_email = issetVal( $_REQUEST['test_email'] );

	// Set the from and cc emails here - the emails can be comma separated list of emails.
	$reply_to = '';

	if ( filter_var( User( 'EMAIL' ), FILTER_VALIDATE_EMAIL ) )
	{
		$reply_to = User( 'NAME' ) . ' <' . User( 'EMAIL' ) . '>';
	}
	elseif ( ! filter_var( $test_email, FILTER_VALIDATE_EMAIL ) )
	{
		$error[] = _( 'You must set the <b>test mode email</b> or have a user email address to use this script.' );

		ErrorMessage( $error, 'fatal' );
	}

	$subject = _( 'New Parent Account' );

	if ( isset( $_REQUEST['inputnotifyparentstext'] ) )
	{
		SaveTemplate( $_REQUEST['inputnotifyparentstext'] );
	}

	$message = GetTemplate();

	if ( ! empty( $_REQUEST['staff'] ) )
	{
		$st_list = "'" . implode( "','", $_REQUEST['staff'] ) . "'";

		$extra['SELECT'] = "," . DisplayNameSQL( 's' ) . " AS NAME,s.USERNAME,s.PASSWORD,s.EMAIL";
		$extra['WHERE'] = " AND s.STAFF_ID IN (" . $st_list . ")";

		$RET = GetStaffList( $extra );
		//echo '<pre>'; var_dump($RET); echo '</pre>';

		$LO_result = [ 0 => [] ];

		$i = 0;

		foreach ( (array) $RET as $staff )
		{
			$staff_id = $staff['STAFF_ID'];

			// Use big random number for parent password generation.
			$password = $staff['USERNAME'] . rand( 1, 9999999999 );

			$password_encrypted = encrypt_password( $password );
			DBQuery( "UPDATE staff SET PASSWORD='" . $password_encrypted . "' WHERE STAFF_ID='" . (int) $staff_id . "'" );

			$students_RET = DBGet( "SELECT " . DisplayNameSQL( 's' ) . " AS FULL_NAME
			FROM students s,student_enrollment sse,students_join_users sju
			WHERE sju.STAFF_ID='" . (int) $staff_id . "'
			AND s.STUDENT_ID=sju.STUDENT_ID
			AND sse.STUDENT_ID=sju.STUDENT_ID
			AND sse.SYEAR='" . UserSyear() . "'
			AND sse.END_DATE IS NULL" );
			//echo '<pre>'; var_dump($students_RET); echo '</pre>';

			$student_list = '';

			foreach ( (array) $students_RET as $student )
			{
				$student_list .= $student['FULL_NAME'] . "\r";
			}

			$substitutions = [
				'__PARENT_NAME__' => $staff['NAME'],
				'__ASSOCIATED_STUDENTS__' => $student_list,
				'__SCHOOL_ID__' => SchoolInfo( 'TITLE' ),
				'__USERNAME__' => $staff['USERNAME'],
				'__PASSWORD__' => $password,
			];

			$msg = SubstitutionsTextMake( $substitutions, $message );

			$to = empty( $test_email ) ? $staff['EMAIL'] : $test_email;

			$result = SendEmail( $to, $subject, $msg, $reply_to );

			$LO_result[] = [
				'PARENT' => $staff['FULL_NAME'],
				'USERNAME' => $staff['USERNAME'],
				'EMAIL' => $to,
				'RESULT' => $result ? _( 'Success' ) : _( 'Fail' ),
			];

			$i++;
		}

		unset( $LO_result[0] );

		$columns = [
			'PARENT' => _( 'Parent' ),
			'USERNAME' => _( 'Username' ),
			'EMAIL' => _( 'Email' ),
			'RESULT' => _( 'Result' ),
		];

		ListOutput(
			$LO_result,
			$columns,
			'Notification Result',
			'Notification Results',
			false,
			[],
			[ 'save' => false, 'search' => false, 'sort' => false ]
		);

		// Unset staff, inputnotifyparentstext & redirect URL.
		RedirectURL( [ 'staff', 'inputnotifyparentstext' ] );
	}
	else
	{
		$error[] = _( 'You must choose at least one user' );

		// Unset modfunc, staff, inputnotifyparentstext & redirect URL.
		RedirectURL( [ 'modfunc', 'staff', 'inputnotifyparentstext' ] );
	}
}

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] || $_REQUEST['search_modfunc'] === 'list' )
{
	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';
		$extra['header_right'] = SubmitButton( _( 'Notify Selected Parents' ) );

		$extra['extra_header_left'] = '<table class="width-100p">';

		$template = GetTemplate();

		$extra['extra_header_left'] .= '<tr class="st"><td>' .
		'<textarea name="inputnotifyparentstext" id="inputnotifyparentstext" cols="97" rows="5">'
		. $template . '</textarea>' .
		FormatInputTitle(
			_( 'New Parent Account' ) . ' - ' . _( 'Email Text' ),
			'inputnotifyparentstext'
		) . '<br /><br /></td></tr>';

		$substitutions = [
			'__PARENT_NAME__' => _( 'Parent Name' ),
			'__ASSOCIATED_STUDENTS__' => _( 'Associated Students' ),
			'__SCHOOL_ID__' => _( 'School' ),
			'__USERNAME__' => _( 'Username' ),
			'__PASSWORD__' => _( 'Password' ),
		];

		$extra['extra_header_left'] .= '<tr class="st"><td class="valign-top">' .
			SubstitutionsInput( $substitutions ) .
		'<hr></td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td>' . _( 'Test Mode' ) . ':<br />' .
		TextInput(
			'',
			'test_email',
			_( 'Email' ),
			'maxlength=255 type="email" placeholder="' . AttrEscape( _( 'Email' ) ) . '" size="24"',
			false
		) . '</td></tr>';

		$extra['extra_header_left'] .= '</table>';
	}

	$extra['SELECT'] = ",s.STAFF_ID AS CHECKBOX,s.USERNAME,s.EMAIL";

	$extra['SELECT'] .= ",(SELECT COUNT(st.STUDENT_ID) FROM students st,student_enrollment sse,students_join_users sju WHERE sju.STAFF_ID=s.STAFF_ID AND st.STUDENT_ID=sju.STUDENT_ID AND sse.STUDENT_ID=sju.STUDENT_ID AND sse.SYEAR='" . UserSyear() . "' AND sse.END_DATE IS NULL) AS ASSOCIATED";

	$extra['WHERE'] = " AND s.LAST_LOGIN IS NULL";

	$extra['functions'] = [
		'CHECKBOX' => '_makeChooseCheckbox',
		'ASSOCIATED' => '_makeAssociated',
		'EMAIL' => '_makeEmail',
	];

	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( '', '', 'staff' ) ];

	$extra['columns_after'] = [
		'ASSOCIATED' => _( 'Associated Students' ),
		'USERNAME' => _( 'Username' ),
		'EMAIL' => _( 'Email' ),
	];

	$extra['link'] = [ 'FULL_NAME' => false ];

	$extra['profile'] = 'parent';

	$extra['search_title'] = _( 'Find Parents who never logged in' );

	$extra['new'] = true;

	Search( 'staff_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Notify Selected Parents' ) ) . '</div>';
		echo '</form>';
	}
}

/**
 * Make Choose Checkbox
 *
 * Local function
 * DBGet() callback
 *
 * @uses MakChooseCheckbox
 *
 * @param  string $value  STAFF_ID value.
 * @param  string $column 'CHECKBOX'.
 *
 * @return string Checkbox or empty string if no Email or has no Children
 */
function _makeChooseCheckbox( $value, $column )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['USERNAME'] )
		&& filter_var( $THIS_RET['EMAIL'], FILTER_VALIDATE_EMAIL )
		&& $THIS_RET['ASSOCIATED'] > 0 )
	{
		return MakeChooseCheckbox( $value, $column );
	}
	else
	{
		return '';
	}
}


/**
 * Make Associated Students
 *
 * Local function
 * DBGet() callback
 *
 * @since 4.3
 *
 * @param  string $value  Number of Associated students.
 * @param  string $column 'ASSOCIATED'.
 * @return string         Number or 0 in red plus link to Associate Students with Parents program.
 */
function _makeAssociated( $value, $column )
{
	global $THIS_RET;

	if ( $value > 0
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $value;
	}

	$link = '';

	if ( AllowEdit( 'Users/AddStudents.php' ) )
	{
		// Link to Associate Students with Parents program.
		$link = ' <a href="' . URLEscape( 'Modules.php?modname=Users/AddStudents.php&staff_id=' . $THIS_RET['STAFF_ID'] ) . '">' .
			_( 'Associate Students with Parents' ) . '</a>';
	}

	return '<span style="color:red">' . $value . '</span>' . $link;
}


/**
 * Make Email address
 *
 * Local function
 * DBGet() callback
 *
 * @since 4.3
 *
 * @param  string $value  Parent email address.
 * @param  string $column 'EMAIL'.
 * @return string         Email address or red cross plus link to User Info program.
 */
function _makeEmail( $value, $column )
{
	global $THIS_RET;

	if ( filter_var( $value, FILTER_VALIDATE_EMAIL )
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return $value;
	}

	if ( ! $value )
	{
		$return = button( 'x' );
	}
	else
	{
		$return = '<span style="color:red">' . $value . '</span>';
	}

	if ( AllowEdit( 'Users/User.php' ) )
	{
		// Link to User Info program.
		$return .= ' <a href="' . URLEscape( 'Modules.php?modname=Users/User.php&staff_id=' . $THIS_RET['STAFF_ID'] ) . '">' .
			_( 'User Info' ) . '</a>';
	}

	return $return;
}
