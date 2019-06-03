<?php
/**
 * Remove Access for selected Students
 * and Associated Parents.
 * Grant Access back.
 *
 * @since 4.8
 *
 * @package RosarioSIS
 */

// Remove or grant access for students.
$accessfunc = empty( $_REQUEST['accessfunc'] ) ? '' : 'grant';

if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit() )
{
	if ( ! empty( $_REQUEST['st_arr'] ) )
	{
		$username_prefix_add = ProgramConfig(
			'custom',
			'REMOVE_ACCESS_USERNAME_PREFIX_ADD',
			$_REQUEST['username_prefix_add']
		);

		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$update_sql = "UPDATE STUDENTS
			SET USERNAME='" . $username_prefix_add . "'||USERNAME
			WHERE STUDENT_ID IN (" . $st_list . ")";

		if ( $accessfunc === 'grant' )
		{
			$update_sql = "UPDATE STUDENTS
				SET USERNAME=REPLACE(USERNAME,'" . $username_prefix_add . "','')
				WHERE STUDENT_ID IN (" . $st_list . ")";
		}

		DBQuery( $update_sql );

		if ( ! empty( $_REQUEST['parents_no_access'] ) )
		{
			$profile_from = $accessfunc === 'grant' ? 'none' : 'parent';

			$profile_to = $accessfunc === 'grant' ? 'parent' : 'none';

			DBQuery( "UPDATE STAFF
				SET PROFILE='" . $profile_to . "'
				WHERE PROFILE='" . $profile_from . "'
				AND STAFF_ID IN(SELECT STAFF_ID
					FROM STUDENTS_JOIN_USERS
					WHERE STUDENT_ID IN (" . $st_list . "))
				AND SYEAR='" . UserSyear() . "'" );
		}
	}
	else
	{
		$error[] = _( 'You must choose at least one student.' );
	}

	RedirectURL( array( 'modfunc', 'st_arr' ) );
}

if ( ! $_REQUEST['modfunc'] )
{
	if ( $accessfunc === 'grant' )
	{
		DrawHeader( '<span class="module-icon Students"></span> ' . _( 'Grant Access' ) );

		$access_header = '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '"><b>' .
			_( 'Remove Access' ) . '</b></a>';

		$button_label = _( 'Grant Access for Selected Students' );

		$checkbox_parents_label = _( 'Grant Access for Associated Parents' );
	}
	else
	{
		DrawHeader( ProgramTitle() );

		$access_header = '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&accessfunc=grant"><b>' .
			_( 'Grant Access' ) . '</b></a>';

		$button_label = _( 'Remove Access for Selected Students' );

		$checkbox_parents_label = _( 'Remove Access for Associated Parents' );
	}

	DrawHeader( $access_header );

	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=save&include_inactive=' . $_REQUEST['include_inactive'] .
			'&_search_all_schools=' . $_REQUEST['_search_all_schools'] .
			'&accessfunc=' . $accessfunc . '" method="POST">';

		$extra['header_right'] = SubmitButton( $button_label );

		$username_prefix_add = ProgramConfig( 'custom', 'REMOVE_ACCESS_USERNAME_PREFIX_ADD' );

		if ( $accessfunc !== 'grant' )
		{
			$extra['extra_header_left'] = TextInput(
				$username_prefix_add,
				'username_prefix_add',
				_( 'Add prefix to username' ),
				'required',
				false
			) . '<br /><br />';
		}

		$extra['extra_header_left'] .= CheckboxInput(
			( $accessfunc === 'grant' ? 'Y' : '' ),
			'parents_no_access',
			$checkbox_parents_label,
			'autocomplete="off"',
			true
		);
	}

	// Students having Username, Password set.
	$extra['WHERE'] = " AND s.USERNAME IS NOT NULL
		AND s.PASSWORD IS NOT NULL";

	if ( $accessfunc === 'grant' )
	{
		if ( ! $username_prefix_add )
		{
			$extra['WHERE'] .= " AND s.USERNAME LIKE 'no_username_prefix_set...%'";
		}
		else
		{
			// Student already being blocked.
			$extra['WHERE'] .= " AND s.USERNAME LIKE '" . DBEscapeString( $username_prefix_add ) . "%'";
		}
	}
	elseif ( $username_prefix_add )
	{
		// Student not already being blocked.
		$extra['WHERE'] .= " AND s.USERNAME NOT LIKE '" . DBEscapeString( $username_prefix_add ) . "%'";
	}

	$extra['columns_after'] = array( 'USERNAME' => _( 'Username' ) );

	$extra['SELECT'] .= ",s.STUDENT_ID AS CHECKBOX,s.USERNAME";
	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) );
	$extra['new'] = true;

	$extra['action'] = '&accessfunc=' . $accessfunc;

	$extra['search_title'] = $accessfunc === 'grant' ?
		_( 'Students without Access' ) :
		_( 'Students with Access' );

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' .

		SubmitButton( $button_label ) . '</div></form>';
	}
}
