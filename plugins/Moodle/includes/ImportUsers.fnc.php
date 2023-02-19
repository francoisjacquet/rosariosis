<?php
/**
 * Import Moodle Users functions
 *
 * @package Moodle plugin
 */

/**
 * List Moodle users
 *
 * @since 5.9
 *
 * @return array Empty if URL or Token not set or invalid, else users.
 */
function MoodleUsersList( $key, $value )
{
	require_once 'plugins/Moodle/client.php';

	// Check Moodle URL if set + token set.
	if ( ! MOODLE_URL
		|| ! MOODLE_TOKEN )
	{
		return [];
	}

	$serverurl = MOODLE_URL . '/webservice/xmlrpc/server.php?wstoken=' . MOODLE_TOKEN;

	if ( ! filter_var( $serverurl, FILTER_VALIDATE_URL ) )
	{
		return [];
	}

	// Check URL is responding with cURL.
	$functionname = 'core_user_get_users';

	// Dummy response function.
	function core_user_get_users_response( $response )
	{
		// We had a response, return true so moodle_xmlrpc_call will return true.
		return $response;
	}

	$criteria = [
		'key' => $key,
		'value' => $value,
	];

	$object = [ 'criteria' => $criteria ];

	$users = moodle_xmlrpc_call( $functionname, $object );

	return empty( $users['users'] ) ? [] : $users['users'];
}

/**
 * Filter Moodle Users
 * Users where confirmed=true, suspended=false, and id not exists in moodlexrosario table.
 *
 * @since 5.9
 *
 * @param  array $users Moodle Users.
 *
 * @return array        Filtered Moodle Users.
 */
function MoodleUsersFilter( $users )
{
	$filtered_users = [];

	$moodlexrosario_user_ids_RET = DBGet( "SELECT MOODLE_ID
		FROM moodlexrosario
		WHERE " . DBEscapeIdentifier( 'COLUMN' ) . "='student_id'
		OR " . DBEscapeIdentifier( 'COLUMN' ) . "='staff_id'", [], [ 'MOODLE_ID' ] );

	$moodlexrosario_user_ids = array_keys( $moodlexrosario_user_ids_RET );

	foreach ( $users as $user )
	{
		if ( empty( $user['confirmed'] )
			|| ! empty( $user['suspended'] )
			|| in_array( $user['id'], $moodlexrosario_user_ids ) )
		{
			// User is not confirmed, or suspended, or already in RosarioSIS.
			continue;
		}

		if ( $user['id'] == 1 )
		{
			// Guest User.
			continue;
		}

		$filtered_users[ $user['id'] ] = $user;
	}

	return $filtered_users;
}

/**
 * Make Moodle Users
 * Format Users array for ListOutput.
 *
 * @since 5.9
 *
 * @param  array $users Filtered Moodle Users.
 *
 * @return array        Formatted Moodle Users.
 */
function MoodleUsersMake( $users )
{
	$formatted_users = [ 0 => [] ];

	foreach ( $users as $user )
	{
		if ( ! isset( $user['firstname'] ) )
		{
			// First and last name require 'moodle/site:viewfullnames' capabitility.
			$names = explode( ' ', $user['fullname'] );

			$user['firstname'] = $names[0];

			$user['lastname'] = $names[1];
		}

		$formatted_users[] = [
			'CHECKBOX' => MakeChooseCheckbox( $user['id'] ),
			'PROFILE' => MoodleUsersMakeProfile( $user['id'] ),
			'FIRST_NAME' => MoodleUsersMakeName( $user['firstname'], $user['profileimageurl'] ),
			'LAST_NAME' => $user['lastname'],
			'EMAIL_ADDRESS' => issetVal( $user['email'] ),
			'USERNAME' => $user['username'],
			'ID' => $user['id'],
		];
	}

	unset( $formatted_users[0] );

	return $formatted_users;
}

/**
 * Moake Moodle User Profile: select target user profile in RosarioSIS.
 *
 * @since 5.9
 *
 * @param int $user_id Moodle user ID.
 *
 * @return string Profile select HTML.
 */
function MoodleUsersMakeProfile( $user_id )
{
	$profiles = [
		'student' => _( 'Student' ),
		'teacher' => _( 'Teacher' ),
		'parent' => _( 'Parent' ),
		'admin' => _( 'Administrator' ),
	];

	$profile_select = SelectInput(
		'student',
		'values[PROFILE][' . $user_id . ']',
		'',
		$profiles,
		false,
		'',
		false
	);

	return $profile_select;
}

/**
 * Make Moodle user name: add photo tip message.
 *
 * @since 5.9
 *
 * @param string $name      Moodle user name.
 * @param string $photo_url Photo URL.
 *
 * @return string Name + Photo tip message HTML.
 */
function MoodleUsersMakeName( $name, $photo_url )
{
	require_once 'ProgramFunctions/TipMessage.fnc.php';

	if ( ! $photo_url )
	{
		return $name;
	}

	return makeTipMessage(
		'<img src="' . URLEscape( $photo_url ) . '" style="max-height: 150px;" />',
		_( 'User Photo' ),
		$name
	);
}

/**
 * Student Enrollment Form HTML
 * For use on the Moodle Users import screen to set Student Enrollment options.
 *
 * @since 5.9
 *
 * @return string Form HTML.
 */
function MoodleUsersStudentEnrollmentForm()
{
	$html = '<fieldset><legend>' . _( 'Enrollment' ) . '</legend><table class="width-100p">';

	$gradelevels_RET = DBGet( "SELECT ID,TITLE
		FROM school_gradelevels
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

	$options = [];

	foreach ( (array) $gradelevels_RET as $gradelevel )
	{
		$options[ $gradelevel['ID'] ] = $gradelevel['TITLE'];
	}

	$html .= '<tr class="st"><td>' . SelectInput(
		'',
		'values[GRADE_ID]',
		_( 'Grade Level' ),
		$options,
		false
	) . '</td>';

	$calendars_RET = DBGet( "SELECT CALENDAR_ID,DEFAULT_CALENDAR,TITLE
		FROM attendance_calendars
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		ORDER BY DEFAULT_CALENDAR ASC" );

	$options = [];

	foreach ( (array) $calendars_RET as $calendar )
	{
		$options[ $calendar['CALENDAR_ID'] ] = $calendar['TITLE'];
	}

	$html .= '<td>' . SelectInput(
		'',
		'values[CALENDAR_ID]',
		_( 'Calendar' ),
		$options,
		false
	) . '</td>';

	$schools_RET = DBGet( "SELECT ID,TITLE
		FROM schools
		WHERE ID!='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	$options = [
		UserSchool() => _( 'Next grade at current school' ),
		'0' => _( 'Retain' ),
		'-1' => _( 'Do not enroll after this school year' ),
	];

	foreach ( (array) $schools_RET as $school )
	{
		$options[ $school['ID'] ] = $school['TITLE'];
	}

	$html .= '<td>' . SelectInput(
		'',
		'values[NEXT_SCHOOL]',
		_( 'Rolling / Retention Options' ),
		$options,
		false
	) . '</td></tr>';

	$enrollment_codes_RET = DBGet( "SELECT ID,TITLE AS TITLE
		FROM student_enrollment_codes
		WHERE SYEAR='" . UserSyear() . "'
		AND TYPE='Add'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

	$options = [];

	foreach ( (array) $enrollment_codes_RET as $enrollment_code )
	{
		$options[ $enrollment_code['ID'] ] = $enrollment_code['TITLE'];
	}

	$default = DBGetOne( "SELECT min(SCHOOL_DATE) AS START_DATE
		FROM attendance_calendar
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	if ( ! $default
		|| DBDate() > $default )
	{
		$default = DBDate();
	}

	$html .= '<tr><td colspan="3">' . DateInput( $default, 'values[START_DATE]', '', false ) . ' - ' .
		SelectInput(
			'',
			'values[ENROLLMENT_CODE]',
			_( 'Attendance Start Date this School Year' ),
			$options
		) .	'</td></tr>';

	$html .= '</table></fieldset>';

	return $html;
}

/**
 * Import Moodle user create Student
 *
 * @since 5.9
 * @since 9.3 Still use DBSeqNextID() for student ID, adapt for MySQL
 *
 * @param array $user Moodle user info.
 *
 * @return int Student ID or 0 if existing username.
 */
function MoodleUserImportStudent( $user )
{
	global $error,
		$DatabaseType;

	$username = $user['username'];

	$email_field_key = Config( 'STUDENTS_EMAIL_FIELD' );

	if ( $email_field_key !== 'USERNAME' )
	{
		$email_field_key = 'CUSTOM_' . (int) Config( 'STUDENTS_EMAIL_FIELD' );
	}
	elseif ( ! empty( $user['email'] ) )
	{
		$username = $user['email'];
	}

	// Check username uniqueness.
	$existing_username = DBGet( "SELECT 'exists'
		FROM staff
		WHERE USERNAME='" . $username . "'
		AND SYEAR='" . UserSyear() . "'
		UNION SELECT 'exists'
		FROM students
		WHERE USERNAME='" . $username . "'
		AND STUDENT_ID!='" . UserStudentID() . "'" );

	if ( $existing_username )
	{
		$error[] = $username . ': ' . _( 'A user with that username already exists. Choose a different username and try again.' );

		return 0;
	}

	do
	{
		// @since 9.3 Still use DBSeqNextID() for student ID, adapt for MySQL
		$student_id = DBSeqNextID( $DatabaseType === 'mysql' ? 'students' : 'students_student_id_seq' );
	}
	while ( DBGetOne( "SELECT STUDENT_ID
		FROM students
		WHERE STUDENT_ID='" . (int) $student_id . "'" ) );

	if ( ! isset( $user['firstname'] ) )
	{
		// First and last name requires 'moodle/site:viewfullnames' capabitility.
		$names = explode( ' ', $user['fullname'] );

		$user['firstname'] = $names[0];

		$user['lastname'] = $names[1];
	}

	$sql = "INSERT INTO students ";
	$fields = 'STUDENT_ID,LAST_NAME,FIRST_NAME,USERNAME';
	$values = "'" . $student_id . "','" . $user['firstname'] . "','" . $user['lastname'] . "','" . $username . "'";

	if ( $email_field_key !== 'USERNAME' )
	{
		$fields .= ',' . $email_field_key;
		$values .= ",'" . $user['email'] . "'";
	}

	if ( ! empty( $_REQUEST['values']['PASSWORD_SET_USE_USERNAME'] ) )
	{
		$fields .= ',PASSWORD';
		$values .= ",'" . encrypt_password( $username ) . "'";
	}

	$sql .= '(' . $fields . ') values(' . $values . ')';
	DBQuery( $sql );

	DBQuery( "INSERT INTO moodlexrosario (" . DBEscapeIdentifier( 'column' ) . ",rosario_id,moodle_id)
		VALUES('student_id','" . $student_id . "'," . $user['id'] . ")" );

	return $student_id;
}

/**
 * Enroll Student imported from Moodle
 * Use after MoodleUserImportStudent()
 *
 * @since 5.9
 *
 * @param int $student_id Student ID.
 */
function MoodleUserEnrollStudent( $student_id )
{
	$sql = "INSERT INTO student_enrollment ";

	$fields = 'SYEAR,SCHOOL_ID,STUDENT_ID,';

	$values .= "'" . UserSyear() . "','" . UserSchool() . "','" . $student_id . "',";

	$fields .= 'START_DATE,GRADE_ID,ENROLLMENT_CODE,NEXT_SCHOOL,CALENDAR_ID';

	$start_date = RequestedDate(
		$_REQUEST['year_values']['START_DATE'],
		$_REQUEST['month_values']['START_DATE'],
		$_REQUEST['day_values']['START_DATE']
	);

	$values .= "'" . $start_date . "','" .
		$_REQUEST['values']['GRADE_ID'] . "','" .
		$_REQUEST['values']['ENROLLMENT_CODE'] . "','" .
		$_REQUEST['values']['NEXT_SCHOOL'] . "','" .
		$_REQUEST['values']['CALENDAR_ID'] . "'";

	$sql .= '(' . $fields . ') values(' . $values . ');';
	DBQuery( $sql );
}

/**
 * Import Moodle user (admin, teacher or parent).
 *
 * @since 5.9
 *
 * @param array  $user    Moodle user info.
 * @param string $profile Profile: admin, teacher or parent.
 *
 * @return int StafF ID or 0 if existing username.
 */
function MoodleUserImportUser( $user, $profile )
{
	global $error;

	$username = $user['username'];

	// Check username uniqueness.
	$existing_username = DBGet( "SELECT 'exists'
		FROM staff
		WHERE USERNAME='" . $username . "'
		AND SYEAR='" . UserSyear() . "'
		AND STAFF_ID!='" . UserStaffID() . "'
		UNION SELECT 'exists'
		FROM students
		WHERE USERNAME='" . $username . "'" );

	if ( $existing_username )
	{
		$error[] = $username . ': ' . _( 'A user with that username already exists. Choose a different username and try again.' );

		return 0;
	}

	if ( ! isset( $user['firstname'] ) )
	{
		// First and last name requires 'moodle/site:viewfullnames' capabitility.
		$names = explode( ' ', $user['fullname'] );

		$user['firstname'] = $names[0];

		$user['lastname'] = $names[1];
	}

	if ( $profile == 'admin' )
	{
		$profile_id = '1';
	}
	elseif ( $profile == 'teacher' )
	{
		$profile_id = '2';
	}
	elseif ( $profile == 'parent' )
	{
		$profile_id = '3';
	}

	$sql = "INSERT INTO staff ";
	$fields = 'SYEAR,LAST_NAME,FIRST_NAME,USERNAME,PROFILE,PROFILE_ID';
	$values = "'" . UserSyear() . "','" . $user['firstname'] . "','" . $user['lastname'] . "','" .
		$username . "','" . $profile . "','" . $profile_id . "'";

	if ( ! empty( $user['email'] ) )
	{
		$fields .= ',EMAIL';
		$values .= ",'" . $user['email'] . "'";
	}

	if ( ! empty( $_REQUEST['values']['PASSWORD_SET_USE_USERNAME'] ) )
	{
		$fields .= ',PASSWORD';
		$values .= ",'" . encrypt_password( $username ) . "'";
	}

	$sql .= '(' . $fields . ') values(' . $values . ')';
	DBQuery( $sql );

	$staff_id = DBLastInsertID();

	DBQuery( "INSERT INTO moodlexrosario (" . DBEscapeIdentifier( 'column' ) . ",rosario_id,moodle_id)
		VALUES('staff_id','" . $staff_id . "'," . $user['id'] . ")" );

	return $staff_id;
}

/**
 * Confirm and Countdown JS before importing Moodle users.
 *
 * @since 5.9
 *
 * @param string $class_prefix Form and button CSS class prefix.
 */
function MoodleImportUsersFormConfirmCountdownJS( $class_prefix )
{
	?>
	<script>
	$(function(){
		$('.<?php echo $class_prefix; ?>-form').submit(function(){

			e.preventDefault();

			var alertTxt = <?php echo json_encode(
				_( 'Are you absolutely ready to import users? Make sure you have backed up your database!' )
			); ?>;

			// Alert.
			if ( ! window.confirm( alertTxt ) ) return false;

			var $buttons = $('.<?php echo $class_prefix; ?>-button'),
				buttonTxt = $buttons.val(),
				seconds = 5,
				stopButtonHTML = <?php echo json_encode( SubmitButton(
					_( 'Stop' ),
					'',
					'class="stop-button"'
				) ); ?>;

			$buttons.css('pointer-events', 'none').attr('disabled', true).val( buttonTxt + ' ... ' + seconds );

			var countdown = setInterval( function(){
				if ( seconds == 0 ) {
					clearInterval( countdown );
					$('.<?php echo $class_prefix; ?>-form').off('submit').submit();
					return;
				}

				$buttons.val( buttonTxt + ' ... ' + --seconds );
			}, 1000 );

			// Insert stop button.
			$( stopButtonHTML ).click( function(){
				clearInterval( countdown );
				$('.stop-button').remove();
				$buttons.css('pointer-events', '').attr('disabled', false).val( buttonTxt );
				return false;
			}).insertAfter( $buttons );
		});
	});
	</script>
	<?php
}
