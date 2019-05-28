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
			WHERE STAFF_ID='" . UserStaffID() . "'", array(), array( 'STUDENT_ID' ) );

		foreach ( (array) $_REQUEST['student'] as $student_id )
		{
			if ( ! $current_RET[$student_id] )
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
		RedirectURL( array( 'modfunc', 'student_id_remove' ) );
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

	if ( ! UserStaffID() )
	{
		// FJ add # Associated students.
		$extra['SELECT'] = ",(SELECT count(u.STUDENT_ID)
		FROM STUDENTS_JOIN_USERS u,STUDENT_ENROLLMENT ssm
		WHERE u.STAFF_ID=s.STAFF_ID
		AND ssm.STUDENT_ID=u.STUDENT_ID
		AND ssm.SYEAR='" . UserSyear() . "'
		AND ('" . DBDate() . "' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL)) AS ASSOCIATED";

		$extra['columns_after'] = array( 'ASSOCIATED' => '# ' . _( 'Associated' ) );

		$extra['profile'] = 'parent';

		Search( 'staff_id', $extra );
	}

	if ( UserStaffID() )
	{
		if ( $_REQUEST['search_modfunc'] === 'list' )
		{
			echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save" method="POST">';
			DrawHeader( '', SubmitButton( _( 'Add Selected Students' ) ) );
		}

		echo '<table class="center"><tr><td>';

		$current_RET = DBGet( "SELECT u.STUDENT_ID," . DisplayNameSQL( 's' ) . " AS FULL_NAME
			FROM STUDENTS_JOIN_USERS u,STUDENTS s
			WHERE s.STUDENT_ID=u.STUDENT_ID
			AND u.STAFF_ID='" . UserStaffID() . "'" );

		$link['remove'] = array(
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete',
			'variables' => array( 'student_id_remove' => 'STUDENT_ID' ),
		);

		$link['FULL_NAME'] = array(
			'link' => 'Modules.php?modname=Students/Student.php',
			'variables' => array( 'student_id' => 'STUDENT_ID' ),
		);

		ListOutput(
			$current_RET,
			array( 'FULL_NAME' => _( 'Students' ) ),
			'Student',
			'Students',
			$link,
			array(),
			array( 'search' => false )
		);

		echo '</td></tr><tr><td>';

		$extra['link'] = array( 'FULL_NAME' => false );
		$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
		$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
		$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) );
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
