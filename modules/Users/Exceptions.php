<?php

DrawHeader( ProgramTitle() );

// bugfix recreate $menu on page reload

if ( ! isset( $menu ) )
{
	// Include Menu.php for each active module.

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

if ( UserStaffID() )
{
	$profile = DBGet( "SELECT PROFILE_ID,PROFILE
		FROM staff
		WHERE STAFF_ID='" . UserStaffID() . "'" );

	if ( $profile[1]['PROFILE_ID'] || $profile[1]['PROFILE'] == 'none' )
	{
		unset( $_SESSION['staff_id'] );
	}
}

StaffWidgets( 'permissions_N' );
Search( 'staff_id', $extra );

$user_id = UserStaffID();

$xprofile = DBGetOne( "SELECT PROFILE
	FROM staff
	WHERE STAFF_ID='" . (int) $user_id . "'" );

$exceptions_RET = DBGet( "SELECT MODNAME,CAN_USE,CAN_EDIT
	FROM staff_exceptions
	WHERE USER_ID='" . (int) $user_id . "'", [], [ 'MODNAME' ] );

if ( $_REQUEST['modfunc'] === 'update'
	&& AllowEdit()
	&& UserStaffID() )
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
						'staff_exceptions',
						[ 'USER_ID' => (int) $user_id, 'MODNAME' => $modname ]
					);
				}
				elseif ( ! empty( $exceptions_RET[$modname] )
					&& ! $can_use
					&& ! $can_edit )
				{
					DBQuery( "DELETE FROM staff_exceptions
						WHERE USER_ID='" . (int) $user_id . "'
						AND MODNAME='" . $modname . "'" );
				}

				if ( $can_edit || $can_use )
				{
					DBUpdate(
						'staff_exceptions',
						[ 'CAN_EDIT' => $can_edit, 'CAN_USE' => $can_use ],
						[ 'USER_ID' => (int) $user_id, 'MODNAME' => $modname ]
					);
				}
			}
		}
	}

	$exceptions_RET = DBGet( "SELECT MODNAME,CAN_USE,CAN_EDIT
		FROM staff_exceptions
		WHERE USER_ID='" . (int) $user_id . "'", [], [ 'MODNAME' ] );

	unset( $tmp_menu );

	// Unset modfunc & can edit & can use & redirect URL.
	RedirectURL( [ 'modfunc', 'can_edit', 'can_use' ] );
}

if ( UserStaffID()
	&& ! $_REQUEST['modfunc'] )
{
	$staff_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS FULL_NAME,PROFILE,PROFILE_ID
		FROM staff
		WHERE STAFF_ID='" . UserStaffID() . "'" );

	if ( ! $staff_RET[1]['PROFILE_ID'] )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&staff_id=' . UserStaffID() ) . '" method="POST">';
		DrawHeader( _( 'Select the programs with which this user can use and save information.' ), SubmitButton() );
		echo '<br />';
		PopTable( 'header', _( 'Permissions' ) );

		echo '';

		foreach ( (array) $menu as $modcat => $profiles )
		{
			$values = issetVal( $profiles[$staff_RET[1]['PROFILE']] );

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
					'.checked,"can_use[' . $modcat . '");' ) . '" /> ' :
				'' ) .
				'</label></th>';

			if ( $xprofile === 'admin'
				|| $modcat === 'Students'
				|| ( $xprofile !== 'teacher'
					&& $modcat === 'Scheduling' )
				|| $modcat === 'Users' )
			{
				echo '<th class="align-right"><label>' . _( 'Can Edit' ) . ' ' .
					( AllowEdit() ?
					'<input type="checkbox" name="' . AttrEscape( 'can_edit_' . $modcat ) .
					'" onclick="' . AttrEscape( 'checkAll(this.form,this.form.can_edit_' . $modcat .
						'.checked,"can_edit[' . $modcat . '");' ) . '" /> ' :
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

					echo '<td class="align-right"><input type="checkbox" name="can_use[' .
					str_replace( '.', '_', $file ) . ']" value="Y"' .
						( $can_use === 'Y' ? ' checked' : '' ) .
						( AllowEdit() ? '' : ' disabled' ) . '></td>';

					if ( $xprofile === 'admin'
						|| ( $xprofile !== 'teacher'
							&& $file === 'Scheduling/Requests.php' ) )
					{
						echo '<td class="align-right"><input type="checkbox" name="can_edit[' .
						str_replace( '.', '_', $file ) . ']" value="Y"' .
							( $can_edit === 'Y' ? ' checked' : '' ) .
							( AllowEdit() ? '' : ' disabled' ) . '></td>';
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
							FROM student_field_categories
							ORDER BY SORT_ORDER IS NULL,SORT_ORDER,TITLE" );

						foreach ( (array) $categories_RET as $category )
						{
							$file = 'Students/Student.php&category_id=' . $category['ID'];
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
							( AllowEdit() ? '' : ' DISABLED' ) . '></td>';

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
							( AllowEdit() ? '' : ' DISABLED' ) . '></td>';

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
					echo '<tr><td colspan="3">' . $title . '</td></tr>';
				}
			}

			echo '</table>';
		}

		PopTable( 'footer' );
		echo '<br /><div class="center">' . SubmitButton() . '</div>';

		echo '</div>';
		echo '</td></tr></table>';
		echo '</form>';
		echo '<div id="new_id_content" style="position:absolute;visibility:hidden;">' . _( 'Title' ) . ' <input type="text" name="new_profile_title" /><br />';
		echo _( 'Type' ) . ' <select name="new_profile_type"><option value="admin">' . _( 'Administrator' ) . '<option value="teacher">' . _( 'Teacher' ) . '<option value="parent">' . _( 'Parent' ) . '</select></div>';
	}
	else
	{
		$profile_title = DBGetOne( "SELECT TITLE
		FROM user_profiles
		WHERE ID='" . (int) $staff_RET[1]['PROFILE_ID'] . "'" );

		echo '<br />';

		$warning[] = sprintf(
			_( '%s is assigned to the profile %s.' ),
			$staff_RET[1]['FULL_NAME'],
			'"' . _( $profile_title ) . '"'
		);

		$warning[] = sprintf( _( 'To assign permissions to this user, either change the permissions for this profile using the %s setup or change this user to a user with custom permissions by using %s.' ), ( AllowUse( 'Users/Profiles.php' ) ? '<a href="Modules.php?modname=Users/Profiles.php">' : '' ) . _( 'Profiles' ) . ( AllowUse( 'Users/Profiles.php' ) ? '</a>' : '' ), ( AllowUse( 'Users/User.php' ) ? '<a href="Modules.php?modname=Users/User.php">' : '' ) . _( 'General Info' ) . ( AllowUse( 'Users/User.php' ) ? '</a>' : '' ) );

		echo ErrorMessage( $warning, 'warning' );
	}
}
