<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit()
	&& UserStaffID() )
{
	if ( isset( $_REQUEST['student'] )
		&& is_array( $_REQUEST['student'] ) )
	{
		$current_RET = DBGet( "SELECT STUDENT_ID
			FROM STUDENTS_JOIN_USERS
			WHERE STAFF_ID='" . UserStaffID() . "'", [], [ 'STUDENT_ID' ] );

		foreach ( (array) $_REQUEST['student'] as $student_id )
		{
			if ( empty( $current_RET[$student_id] ) )
			{
				DBQuery( "INSERT INTO STUDENTS_JOIN_USERS (STUDENT_ID,STAFF_ID)
					VALUES('" . $student_id . "','" . UserStaffID() . "')" );

				//hook
				do_action( 'Users/AddStudents.php|user_assign_role' );
			}
		}

		$note[] = _( 'The selected user\'s profile now includes access to the selected students.' );
	}
	else
	{
		$error[] = _( 'You must choose at least one student.' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit()
	&& UserStaffID() )
{
	if ( DeletePrompt( _( 'student from that user' ), _( 'remove access to' ) )
		&& ! empty( $_REQUEST['student_id_remove'] ) )
	{
		DBQuery( "DELETE FROM STUDENTS_JOIN_USERS
			WHERE STUDENT_ID='" . $_REQUEST['student_id_remove'] . "'
			AND STAFF_ID='" . UserStaffID() . "'" );

		// Hook.
		do_action( 'Users/AddStudents.php|user_unassign_role' );

		// Unset modfunc & student ID remove & redirect URL.
		RedirectURL( [ 'modfunc', 'student_id_remove' ] );
	}
}

echo ErrorMessage( $note, 'note' );

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	if ( UserStaffID() )
	{
		$profile = DBGetOne( "SELECT PROFILE
			FROM STAFF
			WHERE STAFF_ID='" . UserStaffID() . "'" );

		if ( $profile !== 'parent' )
		{
			unset( $_SESSION['staff_id'] );
		}
	}

	if ( ! UserStaffID()
		|| ! empty( $_REQUEST['profile'] ) ) // Fix reset UserStaffID() when pressing back button.
	{
		// FJ add # Associated students.
		$extra['SELECT'] = ",(SELECT count(u.STUDENT_ID)
		FROM STUDENTS_JOIN_USERS u,STUDENT_ENROLLMENT ssm
		WHERE u.STAFF_ID=s.STAFF_ID
		AND ssm.STUDENT_ID=u.STUDENT_ID
		AND ssm.SYEAR='" . UserSyear() . "'
		AND ('" . DBDate() . "' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL)) AS ASSOCIATED";

		$extra['columns_after'] = [ 'ASSOCIATED' => '# ' . _( 'Associated' ) ];

		$extra['profile'] = 'parent';

		$extra['new'] = true;

		Search( 'staff_id', $extra );
	}

	if ( UserStaffID() )
	{
		if ( $_REQUEST['search_modfunc'] === 'list' )
		{
			echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';
			DrawHeader( '', SubmitButton( _( 'Add Selected Students' ) ) );
		}

		echo '<table class="center"><tr><td>';

		// SQL fix only display enrolled students.
		$current_RET = DBGet( "SELECT u.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME,ssm.SCHOOL_ID
			FROM STUDENTS_JOIN_USERS u,STUDENTS s,STUDENT_ENROLLMENT ssm
			WHERE s.STUDENT_ID=u.STUDENT_ID
			AND u.STAFF_ID='" . UserStaffID() . "'
			AND ssm.STUDENT_ID=u.STUDENT_ID
			AND ssm.SYEAR='" . UserSyear() . "'
			AND ('" . DBDate() . "' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL)", [ 'FULL_NAME' => '_makeStudentInfoLink' ] );

		$link['remove'] = [
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete',
			'variables' => [ 'student_id_remove' => 'STUDENT_ID' ],
		];

		ListOutput(
			$current_RET,
			[ 'FULL_NAME' => _( 'Students' ) ],
			'Student',
			'Students',
			$link,
			[],
			[ 'search' => false ]
		);

		echo '</td></tr><tr><td>';

		$extra['link'] = [ 'FULL_NAME' => false ];
		$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
		$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
		$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) ];
		$extra['new'] = true;
		$extra['options']['search'] = false;

		if ( AllowEdit() )
		{
			Search( 'student_id', $extra );
		}

		echo '</td></tr></table>';

		if ( $_REQUEST['search_modfunc'] === 'list' )
		{
			echo '<br /><div class="center">' . SubmitButton( _( 'Add Selected Students' ) ) . '</div></form>';
		}
	}
}

/**
 * Make Student Info link
 *
 * @since 7.2
 *
 * Local function
 * DBGet() callback
 *
 * @param  string $value  Student Full Name.
 * @param  string $column Column.
 *
 * @return string         Link to Student Info program.
 */
function _makeStudentInfoLink( $value, $column = 'FULL_NAME' )
{
	global $THIS_RET;

	$modname = 'Students/Student.php';

	if ( ! AllowUse( $modname )
		|| ! $THIS_RET['STUDENT_ID'] )
	{
		return $value;
	}

	$link = 'Modules.php?modname=' . $modname . '&student_id=' . $THIS_RET['STUDENT_ID'];

	if ( $THIS_RET['SCHOOL_ID'] !== UserSchool() )
	{
		$link .= '&school_id=' . $THIS_RET['SCHOOL_ID'];
	}

	return '<a href="' . $link . '">' . $value . '</a>';
}
