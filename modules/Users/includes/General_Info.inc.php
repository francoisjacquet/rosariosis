<?php
echo '<table class="general-info width-100p valign-top fixed-col"><tr class="st"><td rowspan="4">';

// IMAGE.
if ( AllowEdit()
	&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) ):
?>
	<a href="#" onclick="$('.user-photo-form,.user-photo').toggle(); return false;"><?php
	echo button( 'add', '', '', 'smaller' ) . '&nbsp;' . _( 'User Photo' );
?></a><br />
	<div class="user-photo-form hide"><?php
	echo FileInput(
		'photo',
		_( 'User Photo' ) . ' (.jpg, .png, .gif)',
		// Fix photo use mime types, not file extensions so mobile browsers allow camera
		'accept="image/jpeg, image/png, image/gif"'
	);
?></div>
<?php endif;

// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
$picture_path = (array) glob( $UserPicturesPath . UserSyear() . '/' . UserStaffID() . '.*jpg' );

$picture_path = end( $picture_path );

if ( ! $picture_path
	&& ! empty( $staff['ROLLOVER_ID'] ) )
{
	// Use Last Year's if Missing.
	// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
	$picture_path = (array) glob( $UserPicturesPath . ( UserSyear() - 1 ) . '/' . $staff['ROLLOVER_ID'] . '.*jpg' );

	$picture_path = end( $picture_path );
}

if ( $_REQUEST['staff_id'] !== 'new' && $picture_path ):
?>
	<img src="<?php echo URLEscape( $picture_path ); ?>" class="user-photo" alt="<?php echo AttrEscape( _( 'User Photo' ) ); ?>" />
<?php endif;
// END IMAGE

echo '</td><td colspan="2">';

$titles_array = [
	'Mr' => _( 'Mr' ),
	'Mrs' => _( 'Mrs' ),
	'Ms' => _( 'Ms' ),
	'Miss' => _( 'Miss' ),
	'Dr' => _( 'Dr' ),
];

$suffixes_array = [
	'Jr' => _( 'Jr' ),
	'Sr' => _( 'Sr' ),
	'II' => _( 'II' ),
	'III' => _( 'III' ),
	'IV' => _( 'IV' ),
	'V' => _( 'V' ),
];

$staff_title = isset( $staff['TITLE'] ) && isset( $titles_array[ $staff['TITLE'] ] ) ?
	$titles_array[ $staff['TITLE'] ] : '';

$staff_suffix = isset( $staff['NAME_SUFFIX'] ) && isset( $suffixes_array[ $staff['NAME_SUFFIX'] ] ) ?
	$suffixes_array[ $staff['NAME_SUFFIX'] ] : '';

if ( AllowEdit() && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
{
	$div = false;

	$user_name_html = '<table class="cellspacing-0"><tr class="st"><td>' .
	SelectInput(
		issetVal( $staff['TITLE'], '' ),
		'staff[TITLE]',
		_( 'Title' ),
		$titles_array,
		'',
		'',
		$div
	) . '</td><td>' .
	TextInput(
		issetVal( $staff['FIRST_NAME'], '' ),
		'staff[FIRST_NAME]',
		_( 'First Name' ),
		'size=12 maxlength=50 required',
		$div
	) . '</td><td>' .
	TextInput(
		issetVal( $staff['MIDDLE_NAME'], '' ),
		'staff[MIDDLE_NAME]',
		_( 'Middle Name' ),
		'maxlength=50',
		$div
	) . '</td><td>' .
	TextInput(
		issetVal( $staff['LAST_NAME'], '' ),
		'staff[LAST_NAME]',
		_( 'Last Name' ),
		'size=12 maxlength=50 required',
		$div
	) . '</td><td>' .
	SelectInput(
		issetVal( $staff['NAME_SUFFIX'], '' ),
		'staff[NAME_SUFFIX]',
		_( 'Suffix' ),
		$suffixes_array,
		'',
		'',
		$div
	) . '</td></tr></table>';

	if ( $_REQUEST['staff_id'] === 'new'
		|| ! empty( $_REQUEST['moodle_create_user'] ) )
	{
		echo $user_name_html;
	}
	else
	{
		$id = 'user_name';

		echo InputDivOnclick(
			$id,
			$user_name_html,
			$staff_title . ' ' . $staff['FIRST_NAME'] . ' ' .
			$staff['MIDDLE_NAME'] . ' ' . $staff['LAST_NAME'] . ' ' . $staff_suffix,
			FormatInputTitle( _( 'Name' ), $id )
		);
	}
}
elseif ( ! empty( $staff ) )
{
	echo NoInput(
		trim( $staff_title . ' ' . $staff['FIRST_NAME'] . ' ' .
			$staff['MIDDLE_NAME'] . ' ' . $staff['LAST_NAME'] . ' ' . $staff_suffix ),
		_( 'Name' )
	);
}

echo '</td></tr>';

if ( ! isset( $_REQUEST['staff_id'] )
	|| $_REQUEST['staff_id'] !== 'new' )
{
	echo '<tr class="st"><td>';

	echo NoInput( $staff['STAFF_ID'], sprintf( _( '%s ID' ), Config( 'NAME' ) ) );

	echo '</td><td>';

	echo NoInput( $staff['ROLLOVER_ID'], sprintf( _( 'Last Year %s ID' ), Config( 'NAME' ) ) );

	echo '</td></tr>';
}

echo '<tr class="st"><td>';

//FJ Moodle integrator
//username, password required

$required = ! empty( $_REQUEST['moodle_create_user'] ) || ! empty( $old_user_in_moodle ) || basename( $_SERVER['PHP_SELF'] ) == 'index.php';

echo TextInput(
	issetVal( $staff['USERNAME'], '' ),
	'staff[USERNAME]',
	_( 'Username' ),
	'size=22 maxlength=100 autocomplete="off" ' . ( $required ? 'required' : '' ),
	empty( $_REQUEST['moodle_create_user'] )
);

echo '</td><td>';

echo PasswordInput(
	( empty( $staff['PASSWORD'] ) || ! empty( $_REQUEST['moodle_create_user'] ) ? '' : str_repeat( '*', 8 ) ),
	'staff[PASSWORD]',
	_( 'Password' ) .
	( ! empty( $_REQUEST['moodle_create_user'] ) ?
		'<div class="tooltip"><i>' .
		_( 'The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character' ) .
		// @since 5.9 Moodle creates user password if left empty.
		'. ' ._( 'Moodle will create a password and send an email to user if left empty.' ) .
		'</i></div>' :
		''
	),
	'maxlength="42" strength' .
	// @since 5.9 Moodle creates user password if left empty + Do not update Moodle user password.
	( basename( $_SERVER['PHP_SELF'] ) == 'index.php' ? ' required' : '' ),
	empty( $_REQUEST['moodle_create_user'] )
);

echo '</td></tr><tr class="st"><td colspan="2">';

if ( array_key_exists( 'LAST_LOGIN', $staff ) )
{
	// Hide Last Login on Create Account and Add screens.
	echo NoInput( makeLogin( issetVal( $staff['LAST_LOGIN'], '' ) ), _( 'Last Login' ) );
}

echo '</td></tr></table>';

if ( basename( $_SERVER['PHP_SELF'] ) != 'index.php' )
{
	echo '<hr><table class="width-100p valign-top fixed-col">';

	echo '<tr class="st"><td>';

	$profile_options = [
		'parent' => _( 'Parent' ),
		'teacher' => _( 'Teacher' ),
		'admin' => _( 'Administrator' ),
		'none' => _( 'No Access' ),
	];

	$admin_user_profile_restriction = User( 'PROFILE' ) === 'admin'
		&& AllowEdit()
		&& ! AllowEdit( 'Users/User.php&category_id=1&user_profile' );

	// User Profile restrictions.
	if ( $admin_user_profile_restriction )
	{
		if ( $_REQUEST['staff_id'] !== 'new' )
		{
			// Temporarily deactivate AllowEdit.
			$_ROSARIO['allow_edit'] = false;
		}
		else
		{
			// Remove Administrator from profile options.
			$profile_options = [
				'parent' => _( 'Parent' ),
				'teacher' => _( 'Teacher' ),
				'none' => _( 'No Access' ),
			];
		}
	}

	$non_admin_user_profile_restriction = User( 'PROFILE' ) !== 'admin' && AllowEdit();

	if ( $non_admin_user_profile_restriction )
	{
		// Temporarily deactivate AllowEdit.
		$_ROSARIO['allow_edit'] = false;
	}

	echo SelectInput(
		issetVal( $staff['PROFILE'], '' ),
		'staff[PROFILE]',
		_( 'User Profile' ),
		$profile_options,
		false,
		'required',
		empty( $_REQUEST['moodle_create_user'] )
	);

	echo '</td><td>';

	if ( $staff['PROFILE'] !== 'none' )
	{
		// Permissions (not for "No Access" profile).
		$permissions_options = [];

		if ( $_REQUEST['staff_id'] !== 'new' )
		{
			$permissions_RET = DBGet( "SELECT ID,TITLE
				FROM user_profiles
				WHERE PROFILE='" . $staff['PROFILE'] . "'
				ORDER BY ID" );

			foreach ( (array) $permissions_RET as $permission )
			{
				$permissions_options[$permission['ID']] = _( $permission['TITLE'] );
			}

			$na = _( 'Custom' );
		}
		else
		{
			$na = _( 'Default' );
		}

		echo SelectInput(
			issetVal( $staff['PROFILE_ID'], '' ),
			'staff[PROFILE_ID]',
			_( 'Permissions' ),
			$permissions_options,
			$na
		);

		if ( User( 'PROFILE' ) === 'admin'
			&& AllowEdit( 'Users/Exceptions.php' )
			&& ! $staff['PROFILE_ID']
			&& UserStaffID() )
		{
			// Add link to User Permissions.
			echo '<div><a href="Modules.php?modname=Users/Exceptions.php">' .
			_( 'User Permissions' ) . '</a></div>';
		}
	}

	// User Profile restrictions.

	if ( $_REQUEST['staff_id'] !== 'new'
		&& ( $admin_user_profile_restriction
			|| $non_admin_user_profile_restriction ) )
	{
		// Reactivate AllowEdit.
		$_ROSARIO['allow_edit'] = true;
	}

	echo '</td><td>';

	//FJ remove Schools for Parents

	if ( $staff['PROFILE'] !== 'parent' )
	{
		$schools_RET = DBGet( "SELECT ID,TITLE
			FROM schools
			WHERE SYEAR='" . UserSyear() . "'
			ORDER BY TITLE" );

		unset( $options );

		if ( $schools_RET )
		{
			$admin_schools_restriction = ( User( 'PROFILE' ) !== 'admin' && AllowEdit() )
				|| ( User( 'PROFILE' ) === 'admin' && AllowEdit()
					&& ! AllowEdit( 'Users/User.php&category_id=1&schools' ) );

			// Admin Schools restriction.
			if ( $admin_schools_restriction )
			{
				// Temporarily deactivate AllowEdit.
				$_ROSARIO['allow_edit'] = false;
			}

			$i = 0;

			$schools_html = '<table class="cellspacing-0 width-100p"><tr class="st">';

			$school_titles = [];

			foreach ( (array) $schools_RET as $school )
			{
				if ( $i % 2 === 0 )
				{
					$schools_html .= '</tr><tr class="st">';
				}

				$value = isset( $staff['SCHOOLS'] )
					&& mb_strpos( $staff['SCHOOLS'], ',' . $school['ID'] . ',' ) !== false ? 'Y' : '';

				$schools_html .= '<td>' . CheckboxInput(
					$value,
					'staff[SCHOOLS][' . $school['ID'] . ']',
					$school['TITLE'],
					'',
					true,
					button( 'check' ),
					button( 'x' )
				) . '&nbsp;</td>';

				if ( $value )
				{
					$school_titles[] = $school['TITLE'];
				}

				$i++;
			}

			$schools_html .= '</tr></table>';

			$id = 'schools';

			$title = FormatInputTitle( _( 'Schools' ), $id );
			$title_nobr = FormatInputTitle( _( 'Schools' ), $id, false, '' );

			if ( $_REQUEST['staff_id'] !== 'new'
				&& AllowEdit() )
			{
				echo InputDivOnclick(
					$id,
					$schools_html . $title_nobr,
					$school_titles ? implode( ', ', $school_titles ) : _( 'All Schools' ),
					$title
				);
			}
			elseif ( AllowEdit() )
			{
				echo $schools_html . $title_nobr;
			}

			// Admin Schools restriction.
			elseif ( $_REQUEST['staff_id'] === 'new'
				&& $admin_schools_restriction )
			{
				// Assign new user to current school only.
				echo SchoolInfo( 'TITLE' ) . $title;
			}
			else
			{
				echo ( $school_titles ? implode( ', ', $school_titles ) : _( 'All Schools' ) ) .
					$title;
			}

			// Admin Schools restriction.
			if ( $admin_schools_restriction )
			{
				// Reactivate AllowEdit.
				$_ROSARIO['allow_edit'] = true;
			}
		}

		//echo SelectInput($staff['SCHOOL_ID'],'staff[SCHOOL_ID]','School',$options,'All Schools');
	}

	echo '</td></tr></table>';
}

$_REQUEST['category_id'] = '1';
$separator = '<hr>';

require_once 'modules/Users/includes/Other_Info.inc.php';

// FJ create account.
if ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
{
	echo '<hr>';

	echo '<table class="create-account width-100p valign-top fixed-col"><tr class="st"><td colspan="3">';

	// Add Captcha.
	echo CaptchaInput( 'captcha' . rand( 100, 9999 ), _( 'Captcha' ) );

	echo '</td></tr></table>';
}
