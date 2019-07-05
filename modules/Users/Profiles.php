<?php

DrawHeader( ProgramTitle() );

// bugfix recreate $menu on page reload

if ( ! isset( $menu ) )
{
	// include Menu.php for each active module

	foreach ( (array) $RosarioModules as $module => $active )
	{
		if ( $active )
		{
			if ( ROSARIO_DEBUG )
			{
				include 'modules/' . $module . '/Menu.php';
			}
			else
			{
				@include 'modules/' . $module . '/Menu.php';
			}
		}
	}
}

// Sanitize requested Profile ID.

if ( isset( $_REQUEST['profile_id'] )
	&& $_REQUEST['profile_id'] !== (string) (int) $_REQUEST['profile_id'] )
{
	$_REQUEST['profile_id'] = false;
}

if ( $_REQUEST['profile_id'] !== false )
{
	$exceptions_RET = DBGet( "SELECT PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT
		FROM PROFILE_EXCEPTIONS
		WHERE PROFILE_ID='" . $_REQUEST['profile_id'] . "'", array(), array( 'MODNAME' ) );

	$profile_RET = DBGet( "SELECT PROFILE
		FROM USER_PROFILES
		WHERE ID='" . $_REQUEST['profile_id'] . "'" );

	$xprofile = $profile_RET[1]['PROFILE'];

	if ( $xprofile === 'student' )
	{
		$xprofile = 'parent';
		//FJ enable password change for students
		//unset($menu['Users']);
		unset( $menu['Users']['parent']['Users/User.php'] );
	}
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( isset( $_REQUEST['profile_id'] )
		&& (int) $_REQUEST['profile_id'] > 3 )
	{
		$_REQUEST['profile_id'] = (int) $_REQUEST['profile_id'];

		$profile_RET = DBGet( "SELECT TITLE
			FROM USER_PROFILES
			WHERE ID='" . $_REQUEST['profile_id'] . "'" );
	}
	else // bad profile ID
	{
		$profile_RET = null;
	}

	if ( ! empty( $profile_RET ) )
	{
		$go = Prompt(
			_( 'Confirm Delete' ),
			sprintf( _( 'Are you sure you want to delete the user profile <i>%s</i>?' ), $profile_RET[1]['TITLE'] ),
			sprintf( _( 'Users of that profile will retain their permissions as a custom set which can be modified on a per-user basis through %s.' ), _( 'User Permissions' ) )
		);

		if ( $go )
		{
			$delete_sql = "DELETE FROM USER_PROFILES
				WHERE ID='" . $_REQUEST['profile_id'] . "';";

			$delete_sql .= "DELETE FROM STAFF_EXCEPTIONS
				WHERE USER_ID IN (SELECT STAFF_ID
					FROM STAFF
					WHERE PROFILE_ID='" . $_REQUEST['profile_id'] . "');";

			$delete_sql .= "DELETE FROM PROFILE_EXCEPTIONS
				WHERE PROFILE_ID='" . $_REQUEST['profile_id'] . "';";

			DBQuery( $delete_sql );

			DBQuery( "INSERT INTO STAFF_EXCEPTIONS (USER_ID,MODNAME,CAN_USE,CAN_EDIT)
				SELECT s.STAFF_ID,e.MODNAME,e.CAN_USE,e.CAN_EDIT
				FROM STAFF s,PROFILE_EXCEPTIONS e
				WHERE s.PROFILE_ID='" . $_REQUEST['profile_id'] . "'
				AND s.PROFILE_ID=e.PROFILE_ID" );

			// Unset modfunc & profile ID & redirect URL.
			RedirectURL( array( 'modfunc', 'profile_id' ) );
		}
	}
	else // bad or already deleted profile ID
	{
		// Unset modfunc & profile ID & redirect URL.
		RedirectURL( array( 'modfunc', 'profile_id' ) );
	}
}

if ( $_REQUEST['modfunc'] === 'update'
	&& ! $_REQUEST['new_profile_title']
	&& AllowEdit() )
{
	$tmp_menu = $menu;

	$categories_RET = DBGet( "SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES" );

	foreach ( (array) $categories_RET as $category )
	{
		$file = 'Students/Student.php&category_id=' . $category['ID'];
		$tmp_menu['Students'][$xprofile][$file] = ' &nbsp; &nbsp; &rsaquo; ' . $category['TITLE'];
	}

	$categories_RET = DBGet( "SELECT ID,TITLE FROM STAFF_FIELD_CATEGORIES" );

	foreach ( (array) $categories_RET as $category )
	{
		$file = 'Users/User.php&category_id=' . $category['ID'];
		$tmp_menu['Users'][$xprofile][$file] = ' &nbsp; &nbsp; &rsaquo; ' . $category['TITLE'];

		if ( $xprofile === 'admin'
			&& $category['ID'] === '1' )
		{
			// Admin User Profile restriction.
			$file = 'Users/User.php&category_id=1&user_profile';
			$tmp_menu['Users'][$xprofile][$file] = ' &nbsp; &nbsp;  &nbsp; &nbsp; &rsaquo; ' . _( 'User Profile' );

			// Admin Schools restriction.
			$file = 'Users/User.php&category_id=1&schools';
			$tmp_menu['Users'][$xprofile][$file] = ' &nbsp; &nbsp;  &nbsp; &nbsp; &rsaquo; ' . _( 'Schools' );
		}
	}

	if ( isset( $_POST['can_use'] ) )
	{
		foreach ( (array) $tmp_menu as $modcat => $profiles )
		{
			$values = $profiles[$xprofile];

			foreach ( (array) $values as $modname => $title )
			{
				if ( ! is_numeric( $modname )
					&& $modname !== 'default'
					&& $modname !== 'title' )
				{
					if ( empty( $exceptions_RET[$modname] ) && ( $_REQUEST['can_edit'][str_replace( '.', '_', $modname )] || $_REQUEST['can_use'][str_replace( '.', '_', $modname )] ) )
					{
						DBQuery( "INSERT INTO PROFILE_EXCEPTIONS (PROFILE_ID,MODNAME) values('" . $_REQUEST['profile_id'] . "','" . $modname . "')" );
					}
					elseif ( ! empty( $exceptions_RET[$modname] ) && ! $_REQUEST['can_edit'][str_replace( '.', '_', $modname )] && ! $_REQUEST['can_use'][str_replace( '.', '_', $modname )] )
					{
						DBQuery( "DELETE FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . $_REQUEST['profile_id'] . "' AND MODNAME='" . $modname . "'" );
					}

					if ( ! empty( $_REQUEST['can_edit'][str_replace( '.', '_', $modname )] )
						|| ! empty( $_REQUEST['can_use'][str_replace( '.', '_', $modname )] ) )
					{
						$update = "UPDATE PROFILE_EXCEPTIONS SET ";

						if ( ! empty( $_REQUEST['can_edit'][str_replace( '.', '_', $modname )] ) )
						{
							$update .= "CAN_EDIT='Y',";
						}
						else
						{
							$update .= "CAN_EDIT=NULL,";
						}

						if ( ! empty( $_REQUEST['can_use'][str_replace( '.', '_', $modname )] ) )
						{
							$update .= "CAN_USE='Y'";
						}
						else
						{
							$update .= "CAN_USE=NULL";
						}

						$update .= " WHERE PROFILE_ID='" . $_REQUEST['profile_id'] . "' AND MODNAME='" . $modname . "'";

						DBQuery( $update );
					}
				}
			}
		}
	}

	$exceptions_RET = DBGet( "SELECT MODNAME,CAN_USE,CAN_EDIT
		FROM PROFILE_EXCEPTIONS
		WHERE PROFILE_ID='" . $_REQUEST['profile_id'] . "'", array(), array( 'MODNAME' ) );

	unset( $tmp_menu );

	// Unset modfunc & can edit & can use & redirect URL.
	RedirectURL( array( 'modfunc', 'can_edit', 'can_use' ) );

	// If Admin Profile updated, reload menu.

	if ( $_REQUEST['profile_id'] === '1' )
	{
		?>
		<script>
			ajaxLink( 'Side.php' );
		</script>
		<?php
}
}

if ( $_REQUEST['modfunc']
	&& ! empty( $_REQUEST['new_profile_title'] )
	&& AllowEdit() )
{
	$id = DBSeqNextID( 'USER_PROFILES_SEQ' );
	$exceptions_RET = array();

	$xprofile = $_REQUEST['new_profile_type'];

	if ( ! in_array( $xprofile, array( 'admin', 'teacher', 'parent' ) ) )
	{
		// Sanitize requested profile type.
		$xprofile = 'parent';
	}

	DBQuery( "INSERT INTO USER_PROFILES (ID,TITLE,PROFILE)
		values('" . $id . "','" . $_REQUEST['new_profile_title'] . "','" . $xprofile . "')" );

	$_REQUEST['profile_id'] = $id;

	// Unset modfunc & new profile type & new profile title & redirect URL.
	RedirectURL( array( 'modfunc', 'new_profile_title', 'new_profile_type' ) );
}

if ( $_REQUEST['modfunc'] != 'delete' )
{
	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&profile_id=' . $_REQUEST['profile_id'] . '" method="POST">';
	DrawHeader( _( 'Select the programs that users of this profile can use and which programs those users can use to save information.' ), SubmitButton() );
	echo '<br />';
	echo '<table><tr class="st"><td class="valign-top">';

	echo '<table class="widefat">';

	//$profiles_RET = DBGet( "SELECT ID,TITLE,PROFILE FROM USER_PROFILES" );
	$profiles_RET = DBGet( "SELECT ID,TITLE,PROFILE FROM USER_PROFILES ORDER BY ID",
		array(),
		array( 'PROFILE', 'ID' )
	);

	echo '<tr><th colspan="3">' . _( 'Profiles' ) . '</th></tr>';

	foreach ( array( 'admin', 'teacher', 'parent', 'student' ) as $profiles )
	{
		foreach ( (array) $profiles_RET[$profiles] as $id => $profile )
		{
			if ( $_REQUEST['profile_id'] != '' && $id == $_REQUEST['profile_id'] )
			{
				echo '<tr id="selected_tr" class="highlight"><td>' .
				( AllowEdit() && $id > 3 ?
					button( 'remove', '', '"Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete&profile_id=' . $id . '"' ) :
					'&nbsp;'
				) . '</td><td>';
			}
			else
			{
				echo '<tr class="highlight-hover"><td>' .
				( AllowEdit() && $id > 3 ?
					button( 'remove', '', '"Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete&profile_id=' . $id . '"' ) :
					'&nbsp;'
				) . '</td><td>';
			}

			echo '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&profile_id=' . $id . '">' .
				_( $profile[1]['TITLE'] ) . ' &nbsp; </a>';
			echo '</td>';

			echo '<td><div class="arrow right"></div></td>';
			echo '</tr>';
		}
	}

	if ( AllowEdit() )
	{
		$new_profile_form = _( 'Title' ) . ' <input type="text" name="new_profile_title" size="15" /><br />' .
		_( 'Type' ) . ' <select name="new_profile_type">
			<option value="admin">' . _( 'Administrator' ) . '</option>' .
		'<option value="teacher">' . _( 'Teacher' ) . '</option>' .
		'<option value="parent">' . _( 'Parent' ) . '</select></div>';

		echo '<script>new_profile_html = ' . json_encode( $new_profile_form ) . '</script>';

		echo '<tr class="highlight-hover"><td>' .
		button( 'add', '', '"#" onclick="addHTML(new_profile_html, \'new_profile_div\', true); return false;"' ) .
			'</td><td colspan="2">';

		echo '<a href="#" onclick="addHTML(new_profile_html, \'new_profile_div\', true); return false;">' .
		_( 'Add a User Profile' ) . '</a><br /><div id="new_profile_div"></div></td></tr>';
	}

	echo '</table><br />';
	echo '</td><td></td><td>';

	echo '<div id="main_div">';

	if ( $_REQUEST['profile_id'] != '' )
	{
		PopTable( 'header', _( 'Permissions' ) );

		echo '';

		foreach ( (array) $menu as $modcat => $profiles )
		{
			$values = $profiles[$xprofile];

			if ( empty( $values ) )
			{
				// Do not display empty module (no programs allowed).
				continue;
			}

			if ( isset( $values['title'] ) )
			{
				$module_title = $values['title'];
			}
			elseif ( ! in_array( $modcat, $RosarioCoreModules ) )
			{
				$module_title = dgettext( $modcat, str_replace( '_', ' ', $modcat ) );
			}
			else
			{
				$module_title = _( str_replace( '_', ' ', $modcat ) );
			}

			echo '<h3 style="margin-bottom: 7px;"><span class="module-icon ' . $modcat . '"></span> ' . $module_title . '</h3>';

			echo '<table class="widefat fixed-col"><tr><th class="align-right"><label>' . _( 'Can Use' ) . ' ' .
				( AllowEdit() ?
				'<input type="checkbox" name="can_use_' . $modcat .
				'" onclick="checkAll(this.form,this.form.can_use_' . $modcat .
				'.checked,\'can_use[' . $modcat . '\');">' :
				'' ) .
				'</label></th>';

			if ( $xprofile === 'admin'
				|| $modcat === 'Students'
				|| ( $xprofile !== 'teacher'
					&& $modcat === 'Scheduling' )
				|| ( $_REQUEST['profile_id'] !== '0' // Student.
					 && $modcat === 'Users' ) )
			{
				echo '<th class="align-right"><label>' . _( 'Can Edit' ) . ' ' .
					( AllowEdit() ?
					'<input type="checkbox" name="can_edit_' . $modcat .
					'" onclick="checkAll(this.form,this.form.can_edit_' . $modcat .
					'.checked,\'can_edit[' . $modcat . '\');">' :
					'' ) .
					'</label></th>';
			}
			else
			{
				echo '<th>&nbsp;</th>';
			}

			echo '<th>&nbsp;</th></tr>';

			foreach ( (array) $values as $file => $title )
			{
				if ( ! is_numeric( $file )
					&& $file !== 'default'
					&& $file !== 'title' )
				{
					$can_use = ( empty( $exceptions_RET[$file] ) ? '' : $exceptions_RET[$file][1]['CAN_USE'] );
					$can_edit = ( empty( $exceptions_RET[$file] ) ? '' : $exceptions_RET[$file][1]['CAN_EDIT'] );

					echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
					str_replace( '.', '_', $file ) . ']" value="true"' .
						( $can_use === 'Y' ? ' checked' : '' ) .
						( AllowEdit() ? '' : ' disabled' ) . '></td>';

					if ( $xprofile === 'admin'
						|| ( $xprofile !== 'teacher'
							&& $file === 'Scheduling/Requests.php' ) )
					{
						echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
						str_replace( '.', '_', $file ) . ']" value="true"' .
							( $can_edit === 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' disabled' ) . ' /></td>';
					}
					else
					{
						echo '<td>&nbsp;</td>';
					}

					echo '<td>' . $title . '</td></tr>';

					if ( $modcat === 'Students'
						&& $file === 'Students/Student.php' )
					{
						$categories_RET = DBGet( "SELECT ID,TITLE
							FROM STUDENT_FIELD_CATEGORIES
							ORDER BY SORT_ORDER,TITLE" );

						foreach ( (array) $categories_RET as $category )
						{
							$file = 'Students/Student.php&category_id=' . $category['ID'];
							$title = '&nbsp;&nbsp;&rsaquo; ' . ParseMLField( $category['TITLE'] );

							$can_use = ( empty( $exceptions_RET[$file] ) ? '' : $exceptions_RET[$file][1]['CAN_USE'] );
							$can_edit = ( empty( $exceptions_RET[$file] ) ? '' : $exceptions_RET[$file][1]['CAN_EDIT'] );

							//echo '<tr><td>&nbsp;</td><td>&nbsp;</td>';
							echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
							str_replace( '.', '_', $file ) . ']" value="true"' .
							( $can_use == 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' DISABLED' ) . ' /></td>';

							echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
							str_replace( '.', '_', $file ) . ']" value="true"' .
							( $can_edit == 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' DISABLED' ) . ' /></td>';

							echo '<td>' . $title . '</td></tr>';
						}
					}
					elseif ( $modcat === 'Users'
						&& $file === 'Users/User.php' )
					{
						$categories_profiles_where = DBEscapeIdentifier( $xprofile ) . "='Y'";

						if ( $xprofile === 'admin' )
						{
							// Admins can access all profiles, hence their tabs too.
							$categories_profiles_where .= " OR TEACHER='Y' OR PARENT='Y' OR NONE='Y'";
						}
						elseif ( $xprofile === 'teacher' )
						{
							// Teachers can access themselves and parents, hence their tabs too.
							$categories_profiles_where .= " OR PARENT='Y'";
						}

						$categories_RET = DBGet( "SELECT ID,TITLE
							FROM STAFF_FIELD_CATEGORIES
							WHERE " . $categories_profiles_where .
							" ORDER BY SORT_ORDER,TITLE" );

						foreach ( (array) $categories_RET as $category )
						{
							$file = 'Users/User.php&category_id=' . $category['ID'];
							$title = '&nbsp;&nbsp;&rsaquo; ' . ParseMLField( $category['TITLE'] );

							$can_use = ( empty( $exceptions_RET[$file] ) ? '' : $exceptions_RET[$file][1]['CAN_USE'] );
							$can_edit = ( empty( $exceptions_RET[$file] ) ? '' : $exceptions_RET[$file][1]['CAN_EDIT'] );

							echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
							str_replace( '.', '_', $file ) . ']" value="true"' .
							( $can_use == 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' DISABLED' ) . '></td>';

							echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
							str_replace( '.', '_', $file ) . ']" value="true"' .
							( $can_edit == 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' DISABLED' ) . ' /></td>';

							echo '<td>' . $title . '</td></tr>';

							if ( $xprofile === 'admin'
								&& $category['ID'] === '1' )
							{
								// Admin User Profile restriction.
								$file = 'Users/User.php&category_id=1&user_profile';
								$title = ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&rsaquo; ' . _( 'User Profile' );

								$can_use = ( empty( $exceptions_RET[$file] ) ? '' : $exceptions_RET[$file][1]['CAN_USE'] );
								$can_edit = ( empty( $exceptions_RET[$file] ) ? '' : $exceptions_RET[$file][1]['CAN_EDIT'] );

								echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
								str_replace( '.', '_', $file ) . ']" value="true"' .
								( $can_use == 'Y' ? ' checked' : '' ) .
								( AllowEdit() ? '' : ' DISABLED' ) . '></td>';

								echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
								str_replace( '.', '_', $file ) . ']" value="true"' .
								( $can_edit == 'Y' ? ' checked' : '' ) .
								( AllowEdit() ? '' : ' DISABLED' ) . ' /></td>';

								echo '<td>' . $title . '</td></tr>';

								// Admin Schools restriction.
								$file = 'Users/User.php&category_id=1&schools';
								$title = ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&rsaquo; ' . _( 'Schools' );
								$can_use = ( empty( $exceptions_RET[$file] ) ? '' : $exceptions_RET[$file][1]['CAN_USE'] );
								$can_edit = ( empty( $exceptions_RET[$file] ) ? '' : $exceptions_RET[$file][1]['CAN_EDIT'] );

								echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
								str_replace( '.', '_', $file ) . ']" value="true"' .
								( $can_use == 'Y' ? ' checked' : '' ) .
								( AllowEdit() ? '' : ' DISABLED' ) . '></td>';

								echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
								str_replace( '.', '_', $file ) . ']" value="true"' .
								( $can_edit == 'Y' ? ' checked' : '' ) .
								( AllowEdit() ? '' : ' DISABLED' ) . ' /></td>';

								echo '<td>' . $title . '</td></tr>';
							}
						}
					}
				}
				elseif ( $file !== 'default'
					&& $file !== 'title' )
				{
					echo '<tr><td colspan="2">' . $title . '<td></tr>';
				}
			}

			echo '</table>';
		}

		PopTable( 'footer' );

		echo '<br /><div class="center">' . SubmitButton() . '</div>';
	}

	echo '</div>';
	echo '</td></tr></table>';
	echo '</form>';
}
