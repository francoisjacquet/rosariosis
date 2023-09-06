<?php
echo '<table class="general-info width-100p valign-top fixed-col"><tr class="st"><td rowspan="3">';

// IMAGE.

if ( AllowEdit()
	&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) ):
?>
	<a href="#" onclick="$('.user-photo-form,.user-photo').toggle(); return false;"><?php
	echo button( 'add', '', '', 'smaller' ) . '&nbsp;' . _( 'Student Photo' );
?></a><br />
	<div class="user-photo-form hide"><?php
	echo FileInput(
		'photo',
		_( 'Student Photo' ) . ' (.jpg, .png, .gif)',
		// Fix photo use mime types, not file extensions so mobile browsers allow camera
		'accept="image/jpeg, image/png, image/gif"'
	);
?></div>
<?php endif;

// @since 9.0 Fix Improper Access Control security issue: add random string to photo file name.
$picture_path = (array) glob( $StudentPicturesPath . '*/' . UserStudentID() . '.*jpg' );

$picture_path = end( $picture_path );

if ( $_REQUEST['student_id'] !== 'new' && $picture_path ):
?>
	<img src="<?php echo URLEscape( $picture_path ); ?>" class="user-photo" alt="<?php echo AttrEscape( _( 'Student Photo' ) ); ?>" />
<?php endif;
// END IMAGE.

echo '</td><td colspan="2">';

if ( AllowEdit() && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
{
	$div = false;

	$student_name_html = '<table class="cellspacing-0"><tr class="st"><td>' .
	TextInput(
		issetVal( $student['FIRST_NAME'], '' ),
		'students[FIRST_NAME]',
		_( 'First Name' ),
		'size=12 maxlength=50 required',
		$div
	) . '</td><td>' .
	TextInput(
		issetVal( $student['MIDDLE_NAME'], '' ),
		'students[MIDDLE_NAME]',
		_( 'Middle Name' ),
		'maxlength=50',
		$div
	) . '</td><td>' .
	TextInput(
		issetVal( $student['LAST_NAME'], '' ),
		'students[LAST_NAME]',
		_( 'Last Name' ),
		'size=12 maxlength=50 required',
		$div
	) . '</td><td>' .
	SelectInput(
		issetVal( $student['NAME_SUFFIX'], '' ),
		'students[NAME_SUFFIX]',
		_( 'Suffix' ),
		[
			'Jr' => _( 'Jr' ),
			'Sr' => _( 'Sr' ),
			'II' => _( 'II' ),
			'III' => _( 'III' ),
			'IV' => _( 'IV' ),
			'V' => _( 'V' ),
		],
		'',
		'',
		$div
	) . '</td></tr></table>';

	//FJ Moodle integrator

	if ( $_REQUEST['student_id'] === 'new'
		|| ! empty( $_REQUEST['moodle_create_student'] ) )
	{
		echo $student_name_html;
	}
	else
	{
		$id = 'student_name';

		echo InputDivOnclick(
			$id,
			$student_name_html,
			$student['FIRST_NAME'] . ' ' . $student['MIDDLE_NAME'] . ' ' .
			$student['LAST_NAME'] . ' ' . $student['NAME_SUFFIX'],
			FormatInputTitle( _( 'Name' ), $id )
		);
	}
}
elseif ( ! empty( $student ) )
{
	echo NoInput(
		trim( $student['FIRST_NAME'] . ' ' . $student['MIDDLE_NAME'] . ' ' .
			$student['LAST_NAME'] . ' ' . $student['NAME_SUFFIX'] ),
		_( 'Name' )
	);
}

echo '</td></tr><tr class="st"><td>';

if ( $_REQUEST['student_id'] == 'new' )
{
	echo TextInput( '', 'assign_student_id', sprintf( _( '%s ID' ), Config( 'NAME' ) ), 'maxlength=9 size=9' );
}
else
{
	echo NoInput( UserStudentID(), sprintf( _( '%s ID' ), Config( 'NAME' ) ) );
}

echo '</td><td>';

if ( ! empty( $student )
	&& array_key_exists( 'LAST_LOGIN', (array) $student ) )
{
	// Hide Last Login on Create Account and Add screens.
	echo NoInput( makeLogin( $student['LAST_LOGIN'] ), _( 'Last Login' ) );
}

echo '</td></tr><tr class="st"><td>';

// Moodle integrator.
// Username, password required.
$required = ! empty( $_REQUEST['moodle_create_student'] ) || basename( $_SERVER['PHP_SELF'] ) == 'index.php';

echo TextInput(
	issetVal( $student['USERNAME'], '' ),
	'students[USERNAME]',
	_( 'Username' ),
	( $required ? 'required ' : '' ) .
	( Config( 'STUDENTS_EMAIL_FIELD' ) === 'USERNAME' ?
		'maxlength=100 type="email" placeholder="' . AttrEscape( _( 'Email' ) ) . '" ' :
		'maxlength=100' ) .
	'autocomplete="off" size=22',
	empty( $_REQUEST['moodle_create_student'] )
);

echo '</td><td>';

echo PasswordInput(
	( empty( $student['PASSWORD'] )
		|| ! empty( $_REQUEST['moodle_create_student'] ) ? '' : str_repeat( '*', 8 ) ),
	'students[PASSWORD]',
	_( 'Password' ) .
	( ! empty( $_REQUEST['moodle_create_student'] )
		// @since 5.9 Automatic Moodle Student Account Creation.
		// Moodle creates user password.
		&& basename( $_SERVER['PHP_SELF'] ) !== 'index.php' ?
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
	empty( $_REQUEST['moodle_create_student'] )
);

echo '</td></tr></table>';

$_REQUEST['category_id'] = '1';
$separator = '<hr>';

include 'modules/Students/includes/Other_Info.inc.php';

if ( $_REQUEST['student_id'] !== 'new'
	&& ! empty( $student['SCHOOL_ID'] )
	&& $student['SCHOOL_ID'] != UserSchool() )
{
	$_ROSARIO['AllowEdit'][$_REQUEST['modname']] = $_ROSARIO['allow_edit'] = false;
}

if ( basename( $_SERVER['PHP_SELF'] ) !== 'index.php' )
{
	include 'modules/Students/includes/Enrollment.inc.php';
}
else
{
	// Create account.
	echo '<hr>';

	echo '<table class="create-account width-100p valign-top fixed-col"><tr class="st"><td>';

	$schools_RET = DBGet( "SELECT ID,TITLE
		FROM schools
		WHERE SYEAR='" . UserSyear() . "'
		ORDER BY ID" );

	$school_options = [];

	foreach ( (array) $schools_RET as $school )
	{
		$school_options[$school['ID']] = $school['TITLE'];
	}

	// @since 6.0 Reload page on School change, so we update UserSchool().
	$school_onchange_url = "'index.php?create_account=student&student_id=new&school_id='";

	// Add School select input.
	echo SelectInput(
		UserSchool(),
		'values[student_enrollment][new][SCHOOL_ID]',
		_( 'School' ),
		$school_options,
		false,
		'autocomplete="off" onchange="window.location.href=' . $school_onchange_url . ' + this.value;"',
		false
	);

	if ( Config( 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION' ) )
	{
		// @since 5.9 Automatic Student Account Activation.
		echo '</td><td>';

		// Grade Levels for ALL schools.
		$gradelevels_RET = DBGet( "SELECT ID,TITLE
			FROM school_gradelevels
			WHERE SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SCHOOL_ID,SORT_ORDER IS NULL,SORT_ORDER" );

		$gradelevel_options = [];

		foreach ( (array) $gradelevels_RET as $gradelevel )
		{
			$gradelevel_options[ $gradelevel['ID'] ] = $gradelevel['TITLE'];
		}

		// Add Grade Level select input.
		echo SelectInput(
			'',
			'values[student_enrollment][new][GRADE_ID]',
			_( 'Grade Level' ),
			$gradelevel_options,
			'N/A',
			'required'
		);
	}

	echo Config( 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION' ) ?
		'</td><td>' : '</td><td colspan="2">';

	// Add Captcha.
	echo CaptchaInput( 'captcha' . rand( 100, 9999 ), _( 'Captcha' ) );

	echo '</td></tr></table>';

	if ( $PopTable_opened )
	{
		echo '<div><table><tr><td>';

		PopTable( 'footer' );
	}
}
