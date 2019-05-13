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
	'accept="image/*"'
);
?></div>
<?php endif;

if ( $_REQUEST['student_id'] !== 'new' && ( $file = @fopen( $picture_path = $StudentPicturesPath . UserSyear() . '/' . UserStudentID() . '.jpg', 'r' ) ) || ( $file = @fopen( $picture_path = $StudentPicturesPath . ( UserSyear() - 1 ) . '/' . UserStudentID() . '.jpg', 'r' ) ) ):
	fclose( $file );
	?>
			<img src="<?php echo $picture_path . ( ! empty( $new_photo_file ) ? '?cacheKiller=' . rand() : '' ); ?>" class="user-photo" alt="<?php echo htmlspecialchars( _( 'Student Photo' ), ENT_QUOTES ); ?>" />
		<?php endif;
// END IMAGE.

echo '</td><td colspan="2">';

if ( AllowEdit() && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
{
	$div = false;

	$student_name_html = '<table><tr class="st"><td>' .
	TextInput(
		$student['FIRST_NAME'],
		'students[FIRST_NAME]',
		_( 'First Name' ),
		'size=12 maxlength=50 required',
		$div
	) . '</td><td>' .
	TextInput(
		$student['MIDDLE_NAME'],
		'students[MIDDLE_NAME]',
		_( 'Middle Name' ),
		'maxlength=50',
		$div
	) . '</td><td>' .
	TextInput(
		$student['LAST_NAME'],
		'students[LAST_NAME]',
		_( 'Last Name' ),
		'size=12 maxlength=50 required',
		$div
	) . '</td><td>' .
	SelectInput(
		$student['NAME_SUFFIX'],
		'students[NAME_SUFFIX]',
		_( 'Suffix' ),
		array(
			'Jr' => _( 'Jr' ),
			'Sr' => _( 'Sr' ),
			'II' => _( 'II' ),
			'III' => _( 'III' ),
			'IV' => _( 'IV' ),
			'V' => _( 'V' ),
		),
		'',
		'',
		$div
	) . '</td></tr></table>';

	//FJ Moodle integrator

	if ( $_REQUEST['student_id'] === 'new'
		|| $_REQUEST['moodle_create_student'] )
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
else
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
	echo TextInput( '', 'assign_student_id', sprintf( _( '%s ID' ), Config( 'NAME' ) ), 'maxlength=10 size=10' );
}
else
{
	echo NoInput( UserStudentID(), sprintf( _( '%s ID' ), Config( 'NAME' ) ) );
}

echo '</td><td>';

echo NoInput( makeLogin( $student['LAST_LOGIN'] ), _( 'Last Login' ) );

echo '</td></tr><tr class="st"><td>';
//FJ Moodle integrator
//username, password required

$required = $_REQUEST['moodle_create_student'] || $old_student_in_moodle || basename( $_SERVER['PHP_SELF'] ) == 'index.php';

echo TextInput(
	$student['USERNAME'],
	'students[USERNAME]',
	_( 'Username' ),
	( $required ? 'required ' : '' ) .
	( Config( 'STUDENTS_EMAIL_FIELD' ) === 'USERNAME' ?
		'type="email" pattern="[^ @]*@[^ @]*" placeholder="' . _( 'Email' ) . '"' :
		'' ),
	! $_REQUEST['moodle_create_student']
);

echo '</td><td>';

echo PasswordInput(
	( ! $student['PASSWORD']
		|| $_REQUEST['moodle_create_student'] ? '' : str_repeat( '*', 8 ) ),
	'students[PASSWORD]',
	_( 'Password' ) .
	( $_REQUEST['moodle_create_student']
		|| $old_student_in_moodle ?
		'<div class="tooltip"><i>' .
		_( 'The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character' ) .
		'</i></div>' :
		''
	),
	'maxlength="42" strength' . ( $required ? ' required' : '' ),
	( $_REQUEST['moodle_create_student'] ? false : true )
);

echo '</td></tr></table>';

$_REQUEST['category_id'] = '1';
$separator = '<hr />';

include 'modules/Students/includes/Other_Info.inc.php';

if ( $_REQUEST['student_id'] !== 'new'
	&& $student['SCHOOL_ID'] != UserSchool()
	&& $student['SCHOOL_ID'] )
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
	echo '<hr />';

	echo '<table class="create-account width-100p valign-top fixed-col"><tr class="st"><td>';

	$schools_RET = DBGet( "SELECT ID, TITLE
		FROM SCHOOLS
		WHERE SYEAR='" . UserSyear() . "'
		ORDER BY ID" );

	$school_options = array();

	foreach ( (array) $schools_RET as $school )
	{
		$school_options[$school['ID']] = $school['TITLE'];
	}

	// Add School select input.
	echo SelectInput(
		'',
		'values[STUDENT_ENROLLMENT][new][SCHOOL_ID]',
		_( 'School' ),
		$school_options,
		false
	);

	echo '</td><td colspan="2">';

	// Add Captcha.
	echo CaptchaInput( 'captcha' . rand( 100, 9999 ), _( 'Captcha' ) );

	echo '</td></tr></table>';

	if ( $PopTable_opened )
	{
		echo '<table><tr><td>';

		PopTable( 'footer' );
	}
}
