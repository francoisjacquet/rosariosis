<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit()
	&& UserStudentID() )
{
	if ( isset( $_REQUEST['staff'] )
		&& is_array( $_REQUEST['staff'] ) )
	{
		$current_RET = DBGet( "SELECT STAFF_ID
			FROM students_join_users
			WHERE STUDENT_ID='" . UserStudentID() . "'", [], [ 'STAFF_ID' ] );

		foreach ( (array) $_REQUEST['staff'] as $staff_id )
		{
			if ( empty( $current_RET[$staff_id] ) )
			{
				DBInsert(
					'students_join_users',
					[ 'STAFF_ID' => (int) $staff_id, 'STUDENT_ID' => UserStudentID() ]
				);

				//hook
				do_action( 'Students/AddUsers.php|user_assign_role' );
			}
		}

		$note[] = _( 'The selected user\'s profile now includes access to the selected students.' );
	}
	else
	{
		$error[] = _( 'You must choose at least one user' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit()
	&& UserStudentID() )
{
	if ( DeletePrompt( _( 'student from that user' ), _( 'remove access to' ) )
		&& ! empty( $_REQUEST['staff_id_remove'] ) )
	{
		DBQuery( "DELETE FROM students_join_users
			WHERE STAFF_ID='" . (int) $_REQUEST['staff_id_remove'] . "'
			AND STUDENT_ID='" . UserStudentID() . "'" );

		// Hook.
		do_action( 'Students/AddUsers.php|user_unassign_role' );

		// Unset modfunc & staff ID remove & redirect URL.
		RedirectURL( [ 'modfunc', 'staff_id_remove' ] );
	}
}

echo ErrorMessage( $note, 'note' );

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$extra['SELECT'] = ",(SELECT count(u.STAFF_ID) FROM students_join_users u,staff st WHERE u.STUDENT_ID=s.STUDENT_ID AND st.STAFF_ID=u.STAFF_ID AND st.SYEAR=ssm.SYEAR) AS ASSOCIATED";
	$extra['columns_after'] = [ 'ASSOCIATED' => '# ' . _( 'Associated' ) ];

	if ( ! UserStudentID() )
	{
		Search( 'student_id', $extra );
	}

	if ( UserStudentID() )
	{
		if ( $_REQUEST['search_modfunc'] === 'list' )
		{
			echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';

			DrawHeader( '', SubmitButton( _( 'Add Selected Parents' ) ) );
		}

		echo '<table class="center"><tr><td>';

		$current_RET = DBGet( "SELECT u.STAFF_ID,
			" . DisplayNameSQL( 's' ) . " AS FULL_NAME,s.LAST_LOGIN
			FROM students_join_users u,staff s
			WHERE s.STAFF_ID=u.STAFF_ID
			AND u.STUDENT_ID='" . UserStudentID() . "'
			AND s.SYEAR='" . UserSyear() . "'", [ 'LAST_LOGIN' => 'makeLogin' ] );

		$link['remove'] = [
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete',
			'variables' => [ 'staff_id_remove' => 'STAFF_ID' ],
		];

		$link['FULL_NAME'] = [
			'link' => 'Modules.php?modname=Users/User.php',
			'variables' => [ 'staff_id' => 'STAFF_ID' ],
		];

		ListOutput(
			$current_RET,
			[ 'FULL_NAME' => _( 'Parents' ), 'LAST_LOGIN' => _( 'Last Login' ) ],
			'Associated Parent',
			'Associated Parents',
			$link,
			[],
			[ 'search' => false ]
		);

		echo '</td></tr><tr><td>';

		if ( AllowEdit() )
		{
			unset( $extra );

			$current_parent_ids = [];

			foreach ( $current_RET as $current_parent )
			{
				$current_parent_ids[] = $current_parent['STAFF_ID'];
			}

			if ( $current_parent_ids )
			{
				// @since 10.9 Exclude already associated parents from Search()
				$extra['WHERE'] = " AND s.STAFF_ID NOT IN(" . implode( ',', $current_parent_ids ) . ")";
			}

			$extra['link'] = [ 'FULL_NAME' => false ];
			$extra['SELECT'] = ",NULL AS CHECKBOX";
			$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
			$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( '', 'STAFF_ID', 'staff' ) ];
			$extra['new'] = true;
			$extra['options']['search'] = false;
			$extra['profile'] = 'parent';

			Search( 'staff_id', $extra );
		}

		echo '</td></tr></table>';

		if ( $_REQUEST['search_modfunc'] === 'list' )
		{
			echo '<br /><div class="center">' . SubmitButton( _( 'Add Selected Parents' ) ) . '</div></form>';
		}
	}
}
