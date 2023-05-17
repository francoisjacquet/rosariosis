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

$_REQUEST['profile_id'] = issetVal( $_REQUEST['profile_id'] );

// Sanitize requested Profile ID.

if ( isset( $_REQUEST['profile_id'] )
	&& $_REQUEST['profile_id'] !== (string) (int) $_REQUEST['profile_id'] )
{
	$_REQUEST['profile_id'] = false;
}

if ( $_REQUEST['profile_id'] !== false )
{
	$exceptions_RET = DBGet( "SELECT PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT
		FROM profile_exceptions
		WHERE PROFILE_ID='" . (int) $_REQUEST['profile_id'] . "'", [], [ 'MODNAME' ] );

	$xprofile = DBGetOne( "SELECT PROFILE
		FROM user_profiles
		WHERE ID='" . (int) $_REQUEST['profile_id'] . "'" );

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
			FROM user_profiles
			WHERE ID='" . (int) $_REQUEST['profile_id'] . "'" );
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
			$delete_sql = "DELETE FROM user_profiles
				WHERE ID='" . (int) $_REQUEST['profile_id'] . "';";

			$delete_sql .= "DELETE FROM staff_exceptions
				WHERE USER_ID IN (SELECT STAFF_ID
					FROM staff
					WHERE PROFILE_ID='" . (int) $_REQUEST['profile_id'] . "');";

			$delete_sql .= "DELETE FROM profile_exceptions
				WHERE PROFILE_ID='" . (int) $_REQUEST['profile_id'] . "';";

			DBQuery( $delete_sql );

			DBQuery( "INSERT INTO staff_exceptions (USER_ID,MODNAME,CAN_USE,CAN_EDIT)
				SELECT s.STAFF_ID,e.MODNAME,e.CAN_USE,e.CAN_EDIT
				FROM staff s,profile_exceptions e
				WHERE s.PROFILE_ID='" . (int) $_REQUEST['profile_id'] . "'
				AND s.PROFILE_ID=e.PROFILE_ID" );

			// Unset modfunc & profile ID & redirect URL.
			RedirectURL( [ 'modfunc', 'profile_id' ] );
		}
	}
	else // bad or already deleted profile ID
	{
		// Unset modfunc & profile ID & redirect URL.
		RedirectURL( [ 'modfunc', 'profile_id' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'update'
	&& empty( $_REQUEST['new_profile_title'] )
	&& AllowEdit() )
{
	$tmp_menu = $menu;

	$categories_RET = DBGet( "SELECT ID,TITLE FROM student_field_categories" );

	foreach ( (array) $categories_RET as $category )
	{
		$file = 'Students/Student.php&category_id=' . $category['ID'];
		$tmp_menu['Students'][$xprofile][$file] = ' &nbsp; &nbsp; &rsaquo; ' . $category['TITLE'];
	}

	$categories_RET = DBGet( "SELECT ID,TITLE FROM staff_field_categories" );

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

	if ( $xprofile === 'admin' )
	{
		// @since 8.5 Admin Student Payments Delete restriction.
		$file = 'Student_Billing/StudentPayments.php&modfunc=remove';
		$tmp_menu['Student_Billing'][$xprofile][$file] = '&nbsp;&nbsp;&rsaquo; ' . _( 'Delete' );
	}

	if ( isset( $_POST['can_use'] ) )
	{
		foreach ( (array) $tmp_menu as $modcat => $profiles )
		{
			$values = isset( $profiles[$xprofile] ) ? $profiles[$xprofile] : [];

			foreach ( (array) $values as $modname => $title )
			{
				if ( ! is_numeric( $modname )
					&& $modname !== 'default'
					&& $modname !== 'title' )
				{
					$can_edit = issetVal( $_REQUEST['can_edit'][str_replace( '.', '_', $modname )] );

					$can_use = issetVal( $_REQUEST['can_use'][str_replace( '.', '_', $modname )] );

					if ( empty( $exceptions_RET[$modname] )
						&& ( $can_edit || $can_use ) )
					{
						DBInsert(
							'profile_exceptions',
							[ 'PROFILE_ID' => (int) $_REQUEST['profile_id'], 'MODNAME' => $modname ]
						);
					}
					elseif ( ! empty( $exceptions_RET[$modname] )
						&& ! $can_use
						&& ! $can_edit )
					{
						DBQuery( "DELETE FROM profile_exceptions
							WHERE PROFILE_ID='" . (int) $_REQUEST['profile_id'] . "'
							AND MODNAME='" . $modname . "'" );
					}

					if ( $can_edit || $can_use )
					{
						DBUpdate(
							'profile_exceptions',
							[ 'CAN_EDIT' => $can_edit, 'CAN_USE' => $can_use ],
							[ 'PROFILE_ID' => (int) $_REQUEST['profile_id'], 'MODNAME' => $modname ]
						);
					}
				}
			}
		}
	}

	$exceptions_RET = DBGet( "SELECT MODNAME,CAN_USE,CAN_EDIT
		FROM profile_exceptions
		WHERE PROFILE_ID='" . (int) $_REQUEST['profile_id'] . "'", [], [ 'MODNAME' ] );

	unset( $tmp_menu );

	// Unset modfunc & can edit & can use & redirect URL.
	RedirectURL( [ 'modfunc', 'can_edit', 'can_use' ] );

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
	$exceptions_RET = [];

	$xprofile = $_REQUEST['new_profile_type'];

	if ( ! in_array( $xprofile, [ 'admin', 'teacher', 'parent' ] ) )
	{
		// Sanitize requested profile type.
		$xprofile = 'parent';
	}

	$id = DBInsert(
		'user_profiles',
		[ 'TITLE' => $_REQUEST['new_profile_title'], 'PROFILE' => $xprofile ],
		'id'
	);

	$_REQUEST['profile_id'] = $id;

	// Unset modfunc & new profile type & new profile title & redirect URL.
	RedirectURL( [ 'modfunc', 'new_profile_title', 'new_profile_type' ] );
}

if ( $_REQUEST['modfunc'] != 'delete' )
{
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&profile_id=' . $_REQUEST['profile_id']  ) . '" method="POST">';
	DrawHeader( _( 'Select the programs that users of this profile can use and which programs those users can use to save information.' ), SubmitButton() );
	echo '<br />';
	echo '<table><tr class="st"><td class="valign-top">';

	echo '<table class="widefat">';

	//$profiles_RET = DBGet( "SELECT ID,TITLE,PROFILE FROM user_profiles" );
	$profiles_RET = DBGet( "SELECT ID,TITLE,PROFILE FROM user_profiles ORDER BY ID",
		[],
		[ 'PROFILE', 'ID' ]
	);

	echo '<tr><th colspan="3">' . _( 'Profiles' ) . '</th></tr>';

	foreach ( [ 'admin', 'teacher', 'parent', 'student' ] as $profiles )
	{
		foreach ( (array) $profiles_RET[$profiles] as $id => $profile )
		{
			if ( $_REQUEST['profile_id'] != '' && $id == $_REQUEST['profile_id'] )
			{
				echo '<tr id="selected_tr" class="highlight">';
			}
			else
			{
				echo '<tr class="highlight-hover">';
			}

			echo '<td>' . ( AllowEdit() && $id > 3 ?
				button(
					'remove',
					'',
					URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=delete&profile_id=' . $id ) ) :
				'&nbsp;'
			) . '</td>';

			echo '<td><a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&profile_id=' . $id ) . '">' .
				// HTML add arrow to indicate sub-profile.
				( $id > 3 ? '&#10551; ' : '' ) . _( $profile[1]['TITLE'] ) . ' &nbsp; </a>';
			echo '</td>';

			echo '<td><div class="arrow right"></div></td>';
			echo '</tr>';
		}
	}

	if ( AllowEdit() )
	{
		$new_profile_form =  '<input type="text" name="new_profile_title" id="new_profile_title" size="15" maxlength="100" />' .
		FormatInputTitle( _( 'Title' ), 'new_profile_title' ) .
		'<br /><select name="new_profile_type" id="new_profile_type">
			<option value="admin">' . _( 'Administrator' ) . '</option>' .
		'<option value="teacher">' . _( 'Teacher' ) . '</option>' .
		'<option value="parent">' . _( 'Parent' ) . '</select>' .
		FormatInputTitle( _( 'Type' ), 'new_profile_type' ) .
		'</div>';

		echo '<script>new_profile_html = ' . json_encode( $new_profile_form ) . '</script>';

		echo '<tr class="highlight-hover"><td>' .
		button( 'add', '', '"#!" onclick="addHTML(new_profile_html, \'new_profile_div\', true);"' ) .
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

		foreach ( (array) $menu as $modcat => $profiles )
		{
			$values = isset( $profiles[$xprofile] ) ? $profiles[$xprofile] : [];

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

			echo '<h3 class="dashboard-module-title"><span class="module-icon ' . $modcat . '"';

			if ( ! in_array( $modcat, $RosarioCoreModules ) )
			{
				// Modcat is addon module, set custom module icon.
				echo ' style="background-image: url(modules/' . $modcat . '/icon.png);"';
			}

			echo '></span> ' . $module_title . '</h3>';

			echo '<table class="widefat fixed-col"><tr><th class="align-right"><label>' . _( 'Can Use' ) . ' ' .
				( AllowEdit() ?
				'<input type="checkbox" name="' . AttrEscape( 'can_use_' . $modcat ) .
				'" onclick="' . AttrEscape( 'checkAll(this.form,this.form.can_use_' . $modcat .
					'.checked,"can_use[' . $modcat . '");' ) . '">' :
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
					'<input type="checkbox" name="' . AttrEscape( 'can_edit_' . $modcat ) .
					'" onclick="' . AttrEscape( 'checkAll(this.form,this.form.can_edit_' . $modcat .
						'.checked,"can_edit[' . $modcat . '");' ) . '">' :
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
					$can_use = issetVal( $exceptions_RET[$file][1]['CAN_USE'] );
					$can_edit = issetVal( $exceptions_RET[$file][1]['CAN_EDIT'] );

					$user_profiles_admin_restriction = $xprofile === 'admin'
						&& $_REQUEST['profile_id'] === User( 'PROFILE_ID' )
						&& $file === 'Users/Profiles.php'
						&& AllowEdit();

					if ( $user_profiles_admin_restriction )
					{
						// @since 10.0 Prevent admin from removing own access to User Profiles program.
						$_ROSARIO['allow_edit'] = false;

						// POST disabled checkbox: retain checked values using hidden fields.
						echo '<input type="hidden" name="can_use[' .
							str_replace( '.', '_', $file ) . ']" value="Y" />';

						echo '<input type="hidden" name="can_edit[' .
							str_replace( '.', '_', $file ) . ']" value="Y" />';
					}

					echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
					str_replace( '.', '_', $file ) . ']" value="Y"' .
						( $can_use === 'Y' ? ' checked' : '' ) .
						( AllowEdit() ? '' : ' disabled' ) . ' /></td>';

					if ( $xprofile === 'admin'
						|| ( $xprofile !== 'teacher'
							&& $file === 'Scheduling/Requests.php' ) )
					{
						echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
						str_replace( '.', '_', $file ) . ']" value="Y"' .
							( $can_edit === 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' disabled' ) . ' /></td>';
					}
					else
					{
						echo '<td>&nbsp;</td>';
					}

					if ( $user_profiles_admin_restriction )
					{
						// @since 10.0 Prevent admin from removing own access to User Profiles program.
						$_ROSARIO['allow_edit'] = true;
					}

					echo '<td>' . $title . '</td></tr>';

					if ( $modcat === 'Students'
						&& $file === 'Students/Student.php' )
					{
						$categories_RET = DBGet( "SELECT ID,TITLE
							FROM student_field_categories
							ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

						foreach ( (array) $categories_RET as $category )
						{
							$file = 'Students/Student.php&category_id=' . $category['ID'];
							$title = '&nbsp;&nbsp;&rsaquo; ' . ParseMLField( $category['TITLE'] );

							$can_use = issetVal( $exceptions_RET[$file][1]['CAN_USE'] );
							$can_edit = issetVal( $exceptions_RET[$file][1]['CAN_EDIT'] );

							//echo '<tr><td>&nbsp;</td><td>&nbsp;</td>';
							echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
							str_replace( '.', '_', $file ) . ']" value="Y"' .
							( $can_use == 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' DISABLED' ) . ' /></td>';

							echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
							str_replace( '.', '_', $file ) . ']" value="Y"' .
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
							FROM staff_field_categories
							WHERE " . $categories_profiles_where .
							" ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

						foreach ( (array) $categories_RET as $category )
						{
							$file = 'Users/User.php&category_id=' . $category['ID'];
							$title = '&nbsp;&nbsp;&rsaquo; ' . ParseMLField( $category['TITLE'] );

							$can_use = issetVal( $exceptions_RET[$file][1]['CAN_USE'] );
							$can_edit = issetVal( $exceptions_RET[$file][1]['CAN_EDIT'] );

							echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
							str_replace( '.', '_', $file ) . ']" value="Y"' .
							( $can_use == 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' DISABLED' ) . '></td>';

							echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
							str_replace( '.', '_', $file ) . ']" value="Y"' .
							( $can_edit == 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' DISABLED' ) . ' /></td>';

							echo '<td>' . $title . '</td></tr>';

							if ( $xprofile === 'admin'
								&& $category['ID'] === '1' )
							{
								// Admin User Profile restriction.
								$file = 'Users/User.php&category_id=1&user_profile';
								$title = ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&rsaquo; ' . _( 'User Profile' );

								$can_use = issetVal( $exceptions_RET[$file][1]['CAN_USE'] );
								$can_edit = issetVal( $exceptions_RET[$file][1]['CAN_EDIT'] );

								echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
								str_replace( '.', '_', $file ) . ']" value="Y"' .
								( $can_use == 'Y' ? ' checked' : '' ) .
								( AllowEdit() ? '' : ' DISABLED' ) . '></td>';

								echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
								str_replace( '.', '_', $file ) . ']" value="Y"' .
								( $can_edit == 'Y' ? ' checked' : '' ) .
								( AllowEdit() ? '' : ' DISABLED' ) . ' /></td>';

								echo '<td>' . $title . '</td></tr>';

								// Admin Schools restriction.
								$file = 'Users/User.php&category_id=1&schools';
								$title = ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&rsaquo; ' . _( 'Schools' );
								$can_use = issetVal( $exceptions_RET[$file][1]['CAN_USE'] );
								$can_edit = issetVal( $exceptions_RET[$file][1]['CAN_EDIT'] );

								echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
								str_replace( '.', '_', $file ) . ']" value="Y"' .
								( $can_use == 'Y' ? ' checked' : '' ) .
								( AllowEdit() ? '' : ' DISABLED' ) . '></td>';

								echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
								str_replace( '.', '_', $file ) . ']" value="Y"' .
								( $can_edit == 'Y' ? ' checked' : '' ) .
								( AllowEdit() ? '' : ' DISABLED' ) . ' /></td>';

								echo '<td>' . $title . '</td></tr>';
							}
						}
					}
					elseif ( $modcat === 'Student_Billing'
						&& $file === 'Student_Billing/StudentPayments.php' )
					{
						if ( $xprofile === 'admin' )
						{
							// @since 8.5 Admin Student Payments Delete restriction.
							$file = 'Student_Billing/StudentPayments.php&modfunc=remove';
							$title = '&nbsp;&nbsp;&rsaquo; ' . _( 'Delete' );

							$can_use = issetVal( $exceptions_RET[$file][1]['CAN_USE'] );
							$can_edit = issetVal( $exceptions_RET[$file][1]['CAN_EDIT'] );

							echo '<tr><td class="align-right"><input type="checkbox" name="can_use[' .
							str_replace( '.', '_', $file ) . ']" value="Y"' .
							( $can_use == 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' DISABLED' ) . '></td>';

							echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
							str_replace( '.', '_', $file ) . ']" value="Y"' .
							( $can_edit == 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' DISABLED' ) . ' /></td>';

							echo '<td>' . $title . '</td></tr>';
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
