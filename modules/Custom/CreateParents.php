<?php

require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';
require_once 'ProgramFunctions/SendEmail.fnc.php';

// This script will automatically create parent accounts and associate students based on an email address which is part of the student record.

DrawHeader( ProgramTitle() );

// Remove current student.

if ( UserStudentID() )
{
	unset( $_SESSION['student_id'] );

	// Unset student ID & redirect URL.
	RedirectURL( 'student_id' );
}

// The $email_column corresponds to a student field or an address field which is created for the email address.  The COLUMN_# is the column in the
// students table or the address table which holds the student contact email address.  You will need to create the column and inspect rosario database
// to determine the email column and assign it here.
// Making the email address an address field is useful when using 'ganged' address (address record is shared by multiple students by using the 'add
// existing address feature).
// The column name should start with 's.' if a student field or 'a.' if an address field.
//FJ Moodle Integrator: the "family" email field must be different from the student email field in the Moodle/config.inc.php
$email_column = ''; //example: 'a.CUSTOM_2'

//save $email_column var in SESSION

if ( isset( $_SESSION['email_column'] ) && empty( $email_column ) )
{
	$email_column = $_SESSION['email_column'];
}
elseif ( isset( $_POST['email_column'] ) )
{
	$email_column = $_SESSION['email_column'] = $_POST['email_column'];
}

if ( empty( $email_column ) )
{
	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

	//get Student / Address fields
	$student_columns = DBGet( "SELECT 's.CUSTOM_' || f.ID AS COLUMN, f.TITLE, c.TITLE AS CATEGORY
		FROM CUSTOM_FIELDS f, STUDENT_FIELD_CATEGORIES c
		WHERE f.TYPE='text'
		AND c.ID=f.CATEGORY_ID
		ORDER BY f.CATEGORY_ID, f.SORT_ORDER" );

	$address_columns = DBGet( "SELECT 'a.CUSTOM_' || f.ID AS COLUMN, f.TITLE, c.TITLE AS CATEGORY
		FROM ADDRESS_FIELDS f, ADDRESS_FIELD_CATEGORIES c
		WHERE f.TYPE='text'
		AND c.ID=f.CATEGORY_ID
		ORDER BY f.CATEGORY_ID, f.SORT_ORDER" );

	//display SELECT input
	$select_html = _( 'Select Parents email field' ) . ': <select id="email_column" name="email_column">';

	$select_html .= '<optgroup label="' . htmlspecialchars( _( 'Student Fields' ) ) . '">';

	foreach ( (array) $student_columns as $student_column )
	{
		$select_html .= '<option value="' . $student_column['COLUMN'] . '">' . ParseMLField( $student_column['CATEGORY'] ) . ' - ' . ParseMLField( $student_column['TITLE'] ) . '</option>';
	}

	$select_html .= '</optgroup><optgroup label="' . htmlspecialchars( _( 'Address Fields' ) ) . '">';

	foreach ( (array) $address_columns as $address_column )
	{
		$select_html .= '<option value="' . $address_column['COLUMN'] . '">' . ParseMLField( $address_column['CATEGORY'] ) . ' - ' . ParseMLField( $address_column['TITLE'] ) . '</option>';
	}

	$select_html .= '</optgroup></select>';

	DrawHeader( '', '', $select_html );

	echo '<br /><div class="center">' . SubmitButton( _( 'Select Parents email field' ) ) . '</div>';
	echo '</form>';
}

// A list of potential users is obtained from the student contacts with an address.  The student must have at least one such contact.  Students which
// have the same email will be associated to the same user and grouped together in the list and even though each will have contacts listed for selection
// only that of the first student selected in the group will be used in the creation of the account.

// Parent users are created with the following profile id. '3' is the default 'parent' profile.
$profile_id = '3';

// end of user configuration

if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit() )
{
	// If $test email is set then this script will only 'go through the motions' and email the results to the $test_email address instead of parents
	// no accounts are created and no associations are made.  Use this to verify the behavior and email operation before actual use.
	$test_email = isset( $_REQUEST['test_email'] ) ? $_REQUEST['test_email'] : null;

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

	// new for when parent account was created new
	// old for when parent account was existing
	$subject['new'] = _( 'New Parent Account' );
	$subject['old'] = _( 'Updated Parent Account' );

	// FJ add Template.
	$createparentstext = $_REQUEST['inputcreateparentstext_new'] .
		'__BLOCK2__' . $_REQUEST['inputcreateparentstext_old'];

	SaveTemplate( $createparentstext );

	$message['new'] = str_replace( "''", "'", $_REQUEST['inputcreateparentstext_new'] );
	$message['old'] = str_replace( "''", "'", $_REQUEST['inputcreateparentstext_old'] );

	if ( ! empty( $_REQUEST['student'] ) )
	{
		$st_list = "'" . implode( "','", $_REQUEST['student'] ) . "'";

		$extra['SELECT'] = ",lower(" . $email_column . ") AS EMAIL";
		$extra['SELECT'] .= ",(SELECT STAFF_ID FROM STAFF WHERE lower(EMAIL)=lower(" . $email_column . ") AND PROFILE='parent' AND SYEAR=ssm.SYEAR) AS STAFF_ID";
		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";
		$extra['group'] = array( 'EMAIL' );
		$extra['addr'] = true;
		$extra['STUDENTS_JOIN_ADDRESS'] = "AND sam.RESIDENCE='Y'";

		$RET = GetStuList( $extra );
		//echo '<pre>'; var_dump($RET); echo '</pre>';

		foreach ( (array) $RET as $email => $students )
		{
			unset( $id );

			$student_id = $students[1]['STUDENT_ID'];

			if ( ! $students[1]['STAFF_ID'] )
			{
				if ( ! empty( $_REQUEST['contact'][$student_id] ) )
				{
					// Username = email.
					$tmp_username = $username = mb_strtolower( trim( $students[1]['EMAIL'] ) );

					$username_exists_sql = "SELECT STAFF_ID
						FROM STAFF
						WHERE upper(USERNAME)=upper('" . $username . "')
						AND SYEAR='" . UserSyear() . "'";

					$i = 1;

					// If username already exists.

					while ( DBGet( $username_exists_sql ) )
					{
						$username = $tmp_username . $i++;
					}

					$user = DBGet( "SELECT FIRST_NAME,MIDDLE_NAME,LAST_NAME
						FROM PEOPLE
						WHERE PERSON_ID='" . $_REQUEST['contact'][$student_id] . "'" );

					$user = $user[1];

					//FJ change parent password generation
					//$password = $passwords[rand(0,count( $passwords )-1)];
					$password = $username . rand( 100, 999 );

					// FJ Moodle integrator / password.
					$password = UCFirst( $password ) . '*';

					if ( ! $test_email )
					{
						// Get Staff ID.
						$id = DBSeqNextID( 'STAFF_SEQ' );

						// FJ add password encryption.
						$password_encrypted = encrypt_password( $password );

						$sql = "INSERT INTO STAFF (STAFF_ID,SYEAR,PROFILE,PROFILE_ID,
							FIRST_NAME,MIDDLE_NAME,LAST_NAME,USERNAME,PASSWORD,EMAIL) values (
							'" . $id . "','" . UserSyear() . "','parent','" . $profile_id . "',
							'" . DBEscapeString( $user['FIRST_NAME'] ) . "',
							'" . DBEscapeString( $user['MIDDLE_NAME'] ) . "',
							'" . DBEscapeString( $user['LAST_NAME'] ) . "',
							'" . $username . "','" . $password_encrypted . "',
							'" . $students[1]['EMAIL'] . "')";

						DBQuery( $sql );

						// Hook.
						do_action( 'Custom/CreateParents.php|create_user' );

						$staff = DBGet( "SELECT " . DisplayNameSQL() . " AS NAME,
							USERNAME,PASSWORD
							FROM STAFF
							WHERE STAFF_ID='" . $id . "'" );
					}
					else
					{
						$id = true;
						$staff = array(
							1 => array(
								'NAME' => DisplayName(
									$user['FIRST_NAME'],
									$user['LAST_NAME'],
									$user['MIDDLE_NAME']
								),
								'USERNAME' => $username,
								'PASSWORD' => $password,
							),
						);
					}

					$account = 'new';
				}
			}
			else //if user already exists
			{
				$id = $students[1]['STAFF_ID'];

				$staff = DBGet( "SELECT " . DisplayNameSQL() . " AS NAME,
					USERNAME,PASSWORD
					FROM STAFF
					WHERE STAFF_ID='" . $id . "'" );

				$account = 'old';
			}

			if ( $id )
			{
				$staff = $staff[1];
				$student_list = '';

				foreach ( (array) $students as $student )
				{
					// Fix SQL error, check if student not already associated!
					$parent_associated_to_student_RET = DBGet( "SELECT 1
						FROM STUDENTS_JOIN_USERS
						WHERE STAFF_ID'" . $id . "'
						AND STUDENT_ID'" . $student['STUDENT_ID'] . "'" );

					if ( ! $test_email
						&& ! $parent_associated_to_student_RET )
					{
						// Join user to student.
						DBQuery( "INSERT INTO STUDENTS_JOIN_USERS (STAFF_ID,STUDENT_ID)
							VALUES ('" . $id . "','" . $student['STUDENT_ID'] . "')" );

						// Hook.
						do_action( 'Custom/CreateParents.php|user_assign_role' );
					}

					$student_list .= str_replace( '&nbsp;', ' ', $student['FULL_NAME'] ) . "\r";
				}

				$substitutions = array(
					'__PARENT_NAME__' => $staff['NAME'],
					'__ASSOCIATED_STUDENTS__' => $student_list,
					'__SCHOOL_ID__' => SchoolInfo( 'TITLE' ),
					'__USERNAME__' => $staff['USERNAME'],
					'__PASSWORD__' => $password,
				);

				$msg = SubstitutionsTextMake( $substitutions, $message[$account] );

				$to = empty( $test_email ) ? $students[1]['EMAIL'] : $test_email;

				$result = SendEmail( $to, $subject[$account], $msg, $reply_to );

				$RET[$email][1]['PARENT'] = $staff['NAME'];
				$RET[$email][1]['USERNAME'] = $staff['USERNAME'];
				$RET[$email][1]['PASSWORD'] = ( empty( $password ) ? '' : $password );

				if ( $result )
				{
					$RET[$email][1]['RESULT'] = _( 'Success' );
				}
				else
				{
					$RET[$email][1]['RESULT'] = _( 'Fail' );
				}
			}
			else
			{
				$RET[$email][1]['RESULT'] = _( 'Fail' );
			}
		}

		$columns = array( 'FULL_NAME' => _( 'Student' ), 'PARENT' => _( 'Parent' ), 'USERNAME' => _( 'Username' ), 'PASSWORD' => _( 'Password' ), 'EMAIL' => _( 'Email' ), 'RESULT' => _( 'Result' ) );
		ListOutput( $RET, $columns, 'Creation Result', 'Creation Results', false, array( 'EMAIL' ) );
	}
	else
	{
		$error[] = _( 'You must choose at least one student.' );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}

	//reset $email_column var
	unset( $_SESSION['email_column'], $email_column );
}

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] && ! empty( $email_column ) )
{
	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save" method="POST">';
		$extra['header_right'] = SubmitButton( _( 'Create Parent Accounts for Selected Students' ) );

		$extra['extra_header_left'] = '<table class="width-100p">';

		$template = GetTemplate();

		list( $template_new, $template_old ) = explode( '__BLOCK2__', $template );

		$extra['extra_header_left'] .= '<tr class="st"><td>' .
		'<textarea name="inputcreateparentstext_new" cols="100" rows="5">' .
		$template_new . '</textarea>' .
		FormatInputTitle(
			_( 'New Parent Account' ) . ' - ' . _( 'Email Text' ),
			'inputcreateparentstext_new'
		) . '</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td>' .
		'<textarea name="inputcreateparentstext_old" cols="100" rows="5">' .
		$template_old . '</textarea>' .
		FormatInputTitle(
			_( 'Updated Parent Account' ) . ' - ' . _( 'Email Text' ),
			'inputcreateparentstext_old'
		) . '</td></tr>';

		$substitutions = array(
			'__PARENT_NAME__' => _( 'Parent Name' ),
			'__ASSOCIATED_STUDENTS__' => _( 'Associated Students' ),
			'__SCHOOL_ID__' => _( 'School' ),
			'__USERNAME__' => _( 'Username' ),
			'__PASSWORD__' => _( 'Password' ),
		);

		$extra['extra_header_left'] .= '<tr class="st"><td class="valign-top">' .
			SubstitutionsInput( $substitutions ) .
		'<hr /></td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td>' . _( 'Test Mode' ) . ':<br />' .
		TextInput(
			'',
			'test_email',
			_( 'Email' ),
			'size="24"',
			false
		) . '</td></tr>';

		$extra['extra_header_left'] .= '</table>';
	}

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX,lower(" . $email_column . ") AS EMAIL,s.STUDENT_ID AS CONTACT";
	$extra['SELECT'] .= ",(SELECT STAFF_ID FROM STAFF WHERE lower(EMAIL)=lower(" . $email_column . ") AND PROFILE='parent' AND SYEAR=ssm.SYEAR) AS STAFF_ID";
	$extra['SELECT'] .= ",(SELECT 1
		FROM STUDENTS_JOIN_USERS sju,STAFF st
		WHERE sju.STUDENT_ID=s.STUDENT_ID
		AND st.STAFF_ID=sju.STAFF_ID
		AND st.SYEAR='" . UserSyear() . "'
		AND lower(st.EMAIL)=lower(" . $email_column . ")) AS HAS_ASSOCIATED_PARENTS";
	//$extra['WHERE'] = " AND " . $email_column . " IS NOT NULL";

	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['functions'] = array(
		'CHECKBOX' => '_makeChooseCheckbox',
		'CONTACT' => '_makeContactSelect',
		'EMAIL' => '_makeEmail',
	);
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( '', '', 'student' ) );
	$extra['columns_after'] = array( 'EMAIL' => _( 'Email' ), 'CONTACT' => _( 'Contact' ) );
	$extra['LO_group'] = $extra['group'] = array( 'EMAIL' );
	$extra['addr'] = true;
	$extra['SELECT'] .= ",a.ADDRESS_ID";
	$extra['STUDENTS_JOIN_ADDRESS'] .= " AND sam.RESIDENCE='Y'";

	$extra['search_title'] = _( 'Students having Contacts' );

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Create Parent Accounts for Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}

/**
 * Make Choose Checkbox
 *
 * Local function
 * DBGet() callback
 *
 * @param  string $value  STUDENT_ID value.
 * @param  string $column 'CHECKBOX'.
 *
 * @return string Checkbox or empty string if no Email or has no Parents
 */
function _makeChooseCheckbox( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['STAFF_ID'] ) )
	{
		$has_parents = DBGet( "SELECT 1
			FROM STUDENTS_JOIN_PEOPLE sjp,PEOPLE p
			WHERE p.PERSON_ID=sjp.PERSON_ID
			AND sjp.STUDENT_ID='" . $value . "'
			AND sjp.ADDRESS_ID='" . $THIS_RET['ADDRESS_ID'] . "'
			ORDER BY sjp.STUDENT_RELATION" );
	}
	else
	{
		$has_parents = true;
	}

	if ( filter_var( $THIS_RET['EMAIL'], FILTER_VALIDATE_EMAIL )
		&& $has_parents
		&& ! $THIS_RET['HAS_ASSOCIATED_PARENTS'] )
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
 * @param $column
 * @return mixed
 */
function _makeContactSelect( $value, $column )
{
	global $THIS_RET;

	if ( ! $THIS_RET['STAFF_ID'] )
	{
		$RET = DBGet( "SELECT sjp.PERSON_ID,sjp.STUDENT_RELATION,
			p.FIRST_NAME,p.LAST_NAME,p.MIDDLE_NAME
		FROM STUDENTS_JOIN_PEOPLE sjp,PEOPLE p
		WHERE p.PERSON_ID=sjp.PERSON_ID
		AND sjp.STUDENT_ID='" . $value . "'
		AND sjp.ADDRESS_ID='" . $THIS_RET['ADDRESS_ID'] . "'
		ORDER BY sjp.STUDENT_RELATION" );
	}
	else
	{
		$RET = DBGet( "SELECT '' AS PERSON_ID,STAFF_ID AS STUDENT_RELATION,
			FIRST_NAME,LAST_NAME,MIDDLE_NAME
			FROM STAFF WHERE
			STAFF_ID='" . $THIS_RET['STAFF_ID'] . "'" );
	}

	if ( ! empty( $RET ) )
	{
		$checked = ' checked';

		$i = 0;

		foreach ( (array) $RET as $contact )
		{
			$return .= ( $contact['PERSON_ID'] ? '<label><input type="radio" name="contact[' . $value . ']" value=' . $contact['PERSON_ID'] . $checked . ' /> ' : '&nbsp; ' );

			$return .= DisplayName(
				$contact['FIRST_NAME'],
				$contact['LAST_NAME'],
				$contact['MIDDLE_NAME']
			);

			$return .= ' (' . $contact['STUDENT_RELATION'] . ')';

			if ( $contact['PERSON_ID'] )
			{
				$return .= '</label>&nbsp; ';
			}

			$checked = '';
		}
	}

	return $return;
}


/**
 * Make Email address
 *
 * Local function
 * DBGet() callback
 *
 * @since 4.3
 *
 * @param  string $value  Contact email address.
 * @param  string $column 'EMAIL'.
 * @return string         Email address or red cross plus link to Student Info program.
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

	if ( AllowEdit( 'Students/Student.php' ) )
	{
		// Link to User Info program.
		$return .= ' <a href="Modules.php?modname=Students/Student.php&student_id=' . $THIS_RET['STUDENT_ID'] . '">' .
			_( 'Student Info' ) . '</a>';
	}

	return $return;
}
