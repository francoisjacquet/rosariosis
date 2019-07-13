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
	'accept="image/*"'
);
?></div>
<?php endif;

if ( $_REQUEST['staff_id'] !== 'new' && ( $file = @fopen( $picture_path = $UserPicturesPath . UserSyear() . '/' . UserStaffID() . '.jpg', 'r' ) ) || ( $file = @fopen( $picture_path = $UserPicturesPath . ( UserSyear() - 1 ) . '/' . UserStaffID() . '.jpg', 'r' ) ) ):
	fclose( $file );
	?>
		<img src="<?php echo $picture_path . ( ! empty( $new_photo_file ) ? '?cacheKiller=' . rand() : '' ); ?>" class="user-photo" alt="<?php echo htmlspecialchars( _( 'User Photo' ), ENT_QUOTES ); ?>" />
	<?php endif;
// END IMAGE

echo '</td><td colspan="2">';

$titles_array = array(
	'Mr' => _( 'Mr' ),
	'Mrs' => _( 'Mrs' ),
	'Ms' => _( 'Ms' ),
	'Miss' => _( 'Miss' ),
	'Dr' => _( 'Dr' ),
);

$suffixes_array = array(
	'Jr' => _( 'Jr' ),
	'Sr' => _( 'Sr' ),
	'II' => _( 'II' ),
	'III' => _( 'III' ),
	'IV' => _( 'IV' ),
	'V' => _( 'V' ),
);

if ( AllowEdit() && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
{
	$div = false;

	$user_name_html = '<table><tr class="st"><td>' .
	SelectInput(
		$staff['TITLE'],
		'staff[TITLE]',
		_( 'Title' ),
		$titles_array,
		'',
		'',
		$div
	) . '</td><td>' .
	TextInput(
		$staff['FIRST_NAME'],
		'staff[FIRST_NAME]',
		_( 'First Name' ),
		'maxlength=50 required',
		$div
	) . '</td><td>' .
	TextInput(
		$staff['MIDDLE_NAME'],
		'staff[MIDDLE_NAME]',
		_( 'Middle Name' ),
		'maxlength=50',
		$div
	) . '</td><td>' .
	TextInput(
		$staff['LAST_NAME'],
		'staff[LAST_NAME]',
		_( 'Last Name' ),
		'maxlength=50 required',
		$div
	) . '</td><td>' .
	SelectInput(
		$staff['NAME_SUFFIX'],
		'staff[NAME_SUFFIX]',
		_( 'Suffix' ),
		$suffixes_array,
		'',
		'',
		$div
	) . '</td></tr></table>';

	if ( $_REQUEST['staff_id'] === 'new'
		|| $_REQUEST['moodle_create_user'] )
	{
		echo $user_name_html;
	}
	else
	{
		$id = 'user_name';

		echo InputDivOnclick(
			$id,
			$user_name_html,
			$titles_array[$staff['TITLE']] . ' ' . $staff['FIRST_NAME'] . ' ' .
			$staff['MIDDLE_NAME'] . ' ' . $staff['LAST_NAME'] . ' ' . $staff['NAME_SUFFIX'],
			FormatInputTitle( _( 'Name' ), $id )
		);
	}
}
else
{
	echo NoInput(
		trim( $titles_array[$staff['TITLE']] . ' ' . $staff['FIRST_NAME'] . ' ' .
			$staff['MIDDLE_NAME'] . ' ' . $staff['LAST_NAME'] . ' ' . $staff['NAME_SUFFIX'] ),
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

$required = $_REQUEST['moodle_create_user'] || $old_user_in_moodle || basename( $_SERVER['PHP_SELF'] ) == 'index.php';

echo TextInput(
	$staff['USERNAME'],
	'staff[USERNAME]',
	_( 'Username' ),
	'size=12 maxlength=100 ' . ( $required ? 'required' : '' ),
	( $_REQUEST['moodle_create_user'] ? false : true )
);

echo '</td><td>';

echo PasswordInput(
	( ! $staff['PASSWORD']
		|| $_REQUEST['moodle_create_user'] ? '' : str_repeat( '*', 8 ) ),
	'staff[PASSWORD]',
	_( 'Password' ) .
	( $_REQUEST['moodle_create_user']
		|| $old_user_in_moodle ?
		'<div class="tooltip"><i>' .
		_( 'The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character' ) .
		'</i></div>' :
		''
	),
	'maxlength="42" tabindex="2" strength' . ( $required ? ' required' : '' ),
	( $_REQUEST['moodle_create_user'] ? false : true )
);

echo '</td></tr><tr class="st"><td colspan="2">';

echo NoInput( makeLogin( $staff['LAST_LOGIN'] ), _( 'Last Login' ) );

echo '</td></tr></table><hr />';

echo '<table class="width-100p valign-top fixed-col">';

if ( basename( $_SERVER['PHP_SELF'] ) != 'index.php' )
{
	echo '<tr class="st"><td>';

	$profile_options = array(
		'admin' => _( 'Administrator' ),
		'teacher' => _( 'Teacher' ),
		'parent' => _( 'Parent' ),
		'none' => _( 'No Access' ),
	);

	$admin_user_profile_restriction = false;

	// Admin User Profile restriction.

	if ( User( 'PROFILE' ) !== 'admin'
		|| User( 'PROFILE' ) === 'admin'
		&& AllowEdit()
		&& ! AllowEdit( 'Users/User.php&category_id=1&user_profile' ) )
	{
		if ( $_REQUEST['staff_id'] !== 'new' )
		{
			// Temporarily deactivate AllowEdit.
			$_ROSARIO['allow_edit'] = false;
		}
		else
		{
			// Remove Administrator from profile options.
			$profile_options = array(
				'teacher' => _( 'Teacher' ),
				'parent' => _( 'Parent' ),
				'none' => _( 'No Access' ),
			);
		}

		$admin_user_profile_restriction = true;
	}

	echo SelectInput(
		$staff['PROFILE'],
		'staff[PROFILE]',
		_( 'User Profile' ),
		$profile_options,
		false,
		'required',
		$_REQUEST['moodle_create_user'] ? false : true
	);

	echo '</td><td>';

	$permissions_options = array();

	if ( $_REQUEST['staff_id'] !== 'new' )
	{
		$permissions_RET = DBGet( "SELECT ID,TITLE
			FROM USER_PROFILES
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
		$staff['PROFILE_ID'],
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

	// Admin User Profile restriction.

	if ( $admin_user_profile_restriction
		&& $_REQUEST['staff_id'] !== 'new' )
	{
		// Reactivate AllowEdit.
		$_ROSARIO['allow_edit'] = true;
	}

	echo '</td><td>';

	//FJ remove Schools for Parents

	if ( $staff['PROFILE'] !== 'parent' )
	{
		$schools_RET = DBGet( "SELECT ID,TITLE
			FROM SCHOOLS
			WHERE SYEAR='" . UserSyear() . "'
			ORDER BY TITLE" );

		unset( $options );

		if ( $schools_RET )
		{
			$admin_schools_restriction = false;

			// Admin Schools restriction.
			if ( User( 'PROFILE' ) !== 'admin'
				|| User( 'PROFILE' ) === 'admin'
				&& AllowEdit()
				&& ! AllowEdit( 'Users/User.php&category_id=1&schools' ) )
			{
				// Temporarily deactivate AllowEdit.
				$_ROSARIO['allow_edit'] = false;

				$admin_schools_restriction = true;
			}

			$i = 0;

			$schools_html = '<table class="cellspacing-0 width-100p"><tr class="st">';

			$school_titles = array();

			foreach ( (array) $schools_RET as $school )
			{
				if ( $i % 2 === 0 )
				{
					$schools_html .= '</tr><tr class="st">';
				}

				$value = mb_strpos( $staff['SCHOOLS'], ',' . $school['ID'] . ',' ) !== false ? 'Y' : '';

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

			if ( $_REQUEST['staff_id'] !== 'new'
				&& AllowEdit() )
			{
				echo InputDivOnclick(
					$id,
					$schools_html . str_replace( '<br />', '', $title ),
					$school_titles ? implode( ', ', $school_titles ) : _( 'All Schools' ),
					$title
				);
			}
			elseif ( AllowEdit() )
			{
				echo $schools_html . str_replace( '<br />', '', $title );
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

	echo '</td></tr>';
}

echo '<tr class="st"><td>';
// FJ Moodle integrator: email required
//echo TextInput($staff['EMAIL'],'staff[EMAIL]',_('Email Address'),'size=12 maxlength=100');
echo TextInput(
	$staff['EMAIL'],
	'staff[EMAIL]',
	_( 'Email Address' ),
	'type="email" pattern="[^ @]*@[^ @]*" size=12 maxlength=100' .
	( $_REQUEST['moodle_create_user'] || $old_user_in_moodle ? ' required' : '' ),
	( $_REQUEST['moodle_create_user'] ? false : true )
);

// FJ create account.

if ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
{
	echo '</td><td>';
}
else
{
	echo '</td><td colspan="2">';
}

echo TextInput(
	$staff['PHONE'],
	'staff[PHONE]',
	_( 'Phone Number' ),
	'size=12 maxlength=100'
);

// FJ create account.

if ( basename( $_SERVER['PHP_SELF'] ) === 'index.php' )
{
	echo '</td><td>';

	// Add Captcha.
	echo CaptchaInput( 'captcha' . rand( 100, 9999 ), _( 'Captcha' ) );
}

echo '</td></tr></table>';

$_REQUEST['category_id'] = '1';
$separator = '<hr />';

require_once 'modules/Users/includes/Other_Info.inc.php';
