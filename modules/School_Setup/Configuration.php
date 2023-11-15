<?php
require_once 'ProgramFunctions/Theme.fnc.php';
require_once 'ProgramFunctions/FileUpload.fnc.php';

//FJ add School Configuration
// move the Modules config.inc.php to the database table
// 'config' if the value is needed in multiple modules
// 'program_config' if the value is needed in one module

DrawHeader( ProgramTitle() );

if ( empty( $_REQUEST['tab'] ) )
{
	$_REQUEST['tab'] = 'school';
}


$configuration_link = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] ) . '">' .
	( $_REQUEST['tab'] !== 'modules' && $_REQUEST['tab'] !== 'plugins' ?
	'<b>' . _( 'Configuration' ) . '</b>' : _( 'Configuration' ) ) . '</a>';

$multiple_schools_admin_has_1_school = SchoolInfo( 'SCHOOLS_NB' ) > 1
	&& DBGetOne( "SELECT SCHOOLS
		FROM staff
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND SYEAR='" . UserSyear() . "'
		AND SCHOOLS='," . UserSchool() . ",';" );

$modules_link = ' | <a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=modules' ) . '">' .
	( $_REQUEST['tab'] === 'modules' ?
	'<b>' . _( 'Modules' ) . '</b>' : _( 'Modules' ) ) . '</a>';

$plugins_link = ' | <a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins' ) . '">' .
	( $_REQUEST['tab'] === 'plugins' ?
	'<b>' . _( 'Plugins' ) . '</b>' : _( 'Plugins' ) ) . '</a>';

// Hide Modules & Plugins tabs if Administrator of 1 school only.
if ( AllowEdit() && ! $multiple_schools_admin_has_1_school )
{
	DrawHeader( $configuration_link . $modules_link . $plugins_link );
}

if ( $_REQUEST['tab'] === 'modules' )
{
	require_once 'modules/School_Setup/includes/Modules.inc.php';
}
elseif ( $_REQUEST['tab'] === 'plugins' )
{
	require_once 'modules/School_Setup/includes/Plugins.inc.php';
}
else
{
	if ( $_REQUEST['modfunc'] === 'update' )
	{
		if ( ! empty( $_FILES['LOGO_FILE'] )
			&& AllowEdit() )
		{
			// Upload school logo.
			ImageUpload(
				'LOGO_FILE',
				[],
				'assets/',
				[],
				'.jpg',
				'school_logo_' . UserSchool()
			);
		}

		if ( ! empty( $_REQUEST['values'] )
			&& $_POST['values']
			&& AllowEdit() )
		{
			$updated = $numeric_error = false;

			$old_theme = Config( 'THEME' );

			$_REQUEST['values']['config'] = issetVal( $_REQUEST['values']['config'] );

			if ( ! empty( $_REQUEST['values']['CONFIG'] ) )
			{
				// Compatibility with add-ons version < 10.0, gather CONFIG (uppercase table name) values too.
				$_REQUEST['values']['config'] = array_merge(
					(array) $_REQUEST['values']['config'],
					$_REQUEST['values']['CONFIG']
				);
			}

			foreach ( (array) $_REQUEST['values']['config'] as $column => $value )
			{
				$numeric_columns = [
					'FAILED_LOGIN_LIMIT',
					'PASSWORD_STRENGTH',
				];

				if ( in_array( $column, $numeric_columns )
					&& $value != ''
					&& ! is_numeric( $value ) )
				{
					$numeric_error = true;

					continue;
				}

				Config( $column, $value );

				$updated = true;
			}

			$_REQUEST['values']['program_config'] = issetVal( $_REQUEST['values']['program_config'] );

			if ( ! empty( $_REQUEST['values']['PROGRAM_CONFIG'] ) )
			{
				// Compatibility with add-ons version < 10.0, gather PROGRAM_CONFIG (uppercase table name) values too.
				$_REQUEST['values']['program_config'] = array_merge(
					(array) $_REQUEST['values']['program_config'],
					$_REQUEST['values']['PROGRAM_CONFIG']
				);
			}

			foreach ( (array) $_REQUEST['values']['program_config'] as $program => $columns )
			{
				foreach ( (array) $columns as $column => $value )
				{
					$numeric_columns = [
						'ATTENDANCE_FULL_DAY_MINUTES',
						'ATTENDANCE_EDIT_DAYS_BEFORE',
						'ATTENDANCE_EDIT_DAYS_AFTER',
						'FOOD_SERVICE_BALANCE_WARNING',
						'FOOD_SERVICE_BALANCE_MINIMUM',
						'FOOD_SERVICE_BALANCE_TARGET',
					];

					if ( in_array( $column, $numeric_columns )
						&& $value != ''
						&& ! is_numeric( $value ) )
					{
						$numeric_error = true;

						continue;
					}

					ProgramConfig( $program, $column, $value );

					$updated = true;
				}
			}

			if ( $updated )
			{
				$note[] = button( 'check' ) . '&nbsp;' .
				_( 'The school configuration has been modified.' );
			}

			if ( $numeric_error )
			{
				$error[] = _( 'Please enter valid Numeric data.' );
			}

			// Theme changed? Update it live!
			ThemeLiveUpdate( Config( 'THEME' ), $old_theme );
		}

		// Unset modfunc & values & redirect URL.
		RedirectURL( [ 'modfunc', 'values' ] );
	}

	if ( ! $_REQUEST['modfunc'] )
	{
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=' . $_REQUEST['tab'] . '&modfunc=update' ) . '" method="POST" enctype="multipart/form-data">';

		if ( AllowEdit() )
		{
			DrawHeader( '', SubmitButton() );
		}

		echo ErrorMessage( $note, 'note' );

		echo ErrorMessage( $error, 'error' );

		echo '<br />';

		$tabs = [];

		if ( ! $multiple_schools_admin_has_1_school )
		{
			// Hide System config options if Administrator of 1 school only.
			$tabs[] = [
				'title' => Config( 'NAME' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=system',
			];
		}

		$tabs[] = [
			'title' => _( 'School' ),
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=school',
		];

		if ( $RosarioModules['Students'] )
		{
			$tabs[] = [
				'title' => _( 'Students' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=students',
			];
		}

		if ( $RosarioModules['Grades'] )
		{
			$tabs[] = [
				'title' => _( 'Grades' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=grades',
			];
		}

		if ( $RosarioModules['Attendance'] )
		{
			$tabs[] = [
				'title' => _( 'Attendance' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=attendance',
			];
		}

		if ( $RosarioModules['Food_Service'] )
		{
			$tabs[] = [
				'title' => _( 'Food Service' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=food_service',
			];
		}

		/**
		 * Configuration School Tabs action Hook.
		 * Plugins or modules can add their own tabs.
		 *
		 * @since 6.2
		 */
		do_action( 'School_Setup/Configuration.php|school_tabs', [ &$tabs ] );

		$_ROSARIO['selected_tab'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=' . $_REQUEST['tab'];

		PopTable( 'header', $tabs );

		if ( $_REQUEST['tab'] === 'system'
			&& ! $multiple_schools_admin_has_1_school )
		{
			echo '<table class="cellpadding-5 width-100p"><tr><td>' .
				TextInput( Config( 'NAME' ), 'values[config][NAME]', _( 'Program Name' ), 'required maxlength=20' ) .
			'</td></tr>';

			echo '<tr><td>' .
				MLTextInput( Config( 'TITLE' ), 'values[config][TITLE]', _( 'Program Title' ) ) .
			'</td></tr>';

			// FJ add Default Theme to Configuration.
			echo '<tr><td><table class="width-100p"><tr>';

			$themes = glob( 'assets/themes/*', GLOB_ONLYDIR );

			$theme_options = [];

			foreach ( (array) $themes as $theme )
			{
				$theme_name = str_replace( 'assets/themes/', '', $theme );

				$theme_options[ $theme_name ] = $theme_name;
			}

			echo '<tr><td>' . RadioInput(
				Config( 'THEME' ),
				'values[config][THEME]',
				'',
				$theme_options,
				false
			);

			echo FormatInputTitle(
				_( 'Default Theme' ),
				'',
				false,
				( AllowEdit() ? '' : '<br />' )
			) . ' ';

			// Add Force Default Theme.
			echo CheckboxInput(
				Config( 'THEME_FORCE' ),
				'values[config][THEME_FORCE]',
				_( 'Force' ),
				'',
				true
			) . '</td></tr></table></td></tr>';

			echo '<tr><td><fieldset><legend>' . _( 'Public Registration' ) . '</legend><table>';

			echo '<tr><td colspan="2">' . CheckboxInput(
				Config( 'CREATE_USER_ACCOUNT' ),
				'values[config][CREATE_USER_ACCOUNT]',
				_( 'Create User Account' ) .
				'<div class="tooltip"><i>' .
				_( 'New users will be added with the No Access profile' ) .
				'</i></div>',
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			$create_student_account_tooltip = '';

			if ( ! Config( 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION' ) ) // Do NOT use empty() here!
			{
				$create_student_account_tooltip = '<div class="tooltip"><i>' .
					_( 'New students will be added as Inactive students' ) . '</i></div>';
			}

			echo '<tr><td colspan="2">' . CheckboxInput(
				Config( 'CREATE_STUDENT_ACCOUNT' ),
				'values[config][CREATE_STUDENT_ACCOUNT]',
				_( 'Create Student Account' ) . $create_student_account_tooltip,
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			// @since 5.9 Automatic Student Account Activation.
			// HTML add arrow to indicate sub-option.
			echo '<tr><td class="valign-top">&#10551; </td><td>' . CheckboxInput(
				Config( 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION' ),
				'values[config][CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION]',
				_( 'Automatic Student Account Activation' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			$school_options = [];

			$schools_RET = DBGet( "SELECT ID,TITLE
				FROM schools
				WHERE SYEAR='" . UserSyear() . "'
				ORDER BY ID" );

			foreach ( $schools_RET as $school )
			{
				$school_options[ $school['ID'] ] = $school['TITLE'];
			}

			// @since 6.3 Create Student Account Default School.
			// HTML add arrow to indicate sub-option.
			echo '<tr><td class="valign-top">&#10551; </td><td>' . SelectInput(
				Config( 'CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL' ),
				'values[config][CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL]',
				_( 'Default School' ),
				$school_options
			) . '</td></tr>';

			$students_email_field_RET = DBGet( "SELECT ID,TITLE
				FROM custom_fields
				WHERE TYPE='text'
				AND CATEGORY_ID=1" );

			$students_email_field_options = [ 'USERNAME' => _( 'Username' ) ];

			foreach ( (array) $students_email_field_RET as $field )
			{
				$students_email_field_options[ $field['ID'] ] = ParseMLField( $field['TITLE'] );
			}

			echo '<tr><td colspan="2">' . SelectInput(
				Config( 'STUDENTS_EMAIL_FIELD' ),
				'values[config][STUDENTS_EMAIL_FIELD]',
				_( 'Student email field' ),
				$students_email_field_options,
				'N/A'
			) . '</td></tr>';

			echo '</table></fieldset></td></tr>';

			// FJ add Security to Configuration.
			echo '<tr><td><fieldset><legend>' . _( 'Security' ) . '</legend><table>';

			// Failed login ban if >= X failed attempts within 10 minutes.
			echo '<tr><td>' . TextInput(
				Config( 'FAILED_LOGIN_LIMIT' ),
				'values[config][FAILED_LOGIN_LIMIT]',
				_( 'Failed Login Attempts Limit' ) .
				'<div class="tooltip"><i>' .
				_( 'Leave the field blank to always allow' ) .
				'</i></div>',
				'type=number maxlength=2 size=2 min=2 max=99'
			) . '</td></tr>';

			// Password Strength.
			// @since 4.4.
			echo '<tr><td>' . TextInput(
				Config( 'PASSWORD_STRENGTH' ),
				'values[config][PASSWORD_STRENGTH]',
				'',
				'type=number maxlength=1 min=0 max=4 style="width:196px;"',
				false
			);

			$password_strength_input_id = GetInputID( 'values[config][PASSWORD_STRENGTH]' );

			// Password strength bars, hang tight.
			?>
			<div class="password-strength-bars" style="width:200px;">
				<span class="score0"></span>
				<span class="score1"></span>
				<span class="score2"></span>
				<span class="score3"></span>
				<span class="score4"></span>
			</div>
			<script>
				var passwordStrengthBarsScore = function(input) {
					var $input = ( typeof input === 'string' ? $( '#' + input ) : $( this ) ),
						score = $input.val();

					$input.nextAll('.password-strength-bars').children('span').each(function(i, el) {
						$(el).css('visibility', ( i <= score ? 'visible' : 'hidden' ) );
					});
				};

				var passwordStrengthInputId = <?php echo json_encode( $password_strength_input_id ); ?>;

				passwordStrengthBarsScore(passwordStrengthInputId);
				$('#' + passwordStrengthInputId ).change(passwordStrengthBarsScore);
			</script>
			<?php

			echo FormatInputTitle(
				_( 'Password Strength' ) .
				'<div class="tooltip"><i>' .
				_( 'Minimum password strength required.' ) . ' ' .
				_( 'Set to 0 to disable.' ) .
				'</i></div>',
				$password_strength_input_id,
				false,
				''
			) .	'</td></tr>';

			// @since 5.3 Force password change on first login.
			echo '<tr><td>' . CheckboxInput(
				Config( 'FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN' ),
				'values[config][FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN]',
				_( 'Force Password Change on First Login' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '</table></fieldset></td></tr>';

			/**
			 * Display Name.
			 *
			 * @since 9.3 SQL use CONCAT() instead of pipes || for MySQL compatibility
			 * @link https://www.w3.org/International/questions/qa-personal-names
			 */
			$display_name_options = [
				"CONCAT(FIRST_NAME,' ',LAST_NAME)" => _( 'First Name' ) . ' ' . _( 'Last Name' ),
				"CONCAT(FIRST_NAME,' ',LAST_NAME,coalesce(NULLIF(CONCAT(' ',NAME_SUFFIX),' '),''))" => _( 'First Name' ) . ' ' . _( 'Last Name' ) . ' ' . _( 'Suffix' ),
				"CONCAT(FIRST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME,' '),'  '),' '),LAST_NAME)" => _( 'First Name' ) . ' ' . _( 'Middle Name' ) . ' ' . _( 'Last Name' ),
				"CONCAT(FIRST_NAME,', ',LAST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME),' '),''))" => _( 'First Name' ) . ', ' . _( 'Last Name' ) . ' ' . _( 'Middle Name' ),
				"CONCAT(LAST_NAME,' ',FIRST_NAME)" => _( 'Last Name' ) . ' ' . _( 'First Name' ),
				"CONCAT(LAST_NAME,', ',FIRST_NAME)" => _( 'Last Name' ) . ', ' . _( 'First Name' ),
				"CONCAT(LAST_NAME,', ',FIRST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME),' '),''))" => _( 'Last Name' ) . ', ' . _( 'First Name' ) . ' ' . _( 'Middle Name' ),
				"CONCAT(LAST_NAME,coalesce(NULLIF(CONCAT(' ',MIDDLE_NAME,' '),'  '),' '),FIRST_NAME)" => _( 'Last Name' ) . ' ' . _( 'Middle Name' ) . ' ' . _( 'First Name' ),
			];

			echo '<tr><td>' . SelectInput(
				Config( 'DISPLAY_NAME' ),
				'values[config][DISPLAY_NAME]',
				_( 'Display Name' ),
				$display_name_options,
				false
			) . '</td></tr></table>';
		}

		if ( $_REQUEST['tab'] === 'school' )
		{
			// School year over one/two calendar years format.
			echo '<table class="cellpadding-5 width-100p"><tr><td>' . CheckboxInput(
				Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ),
				'values[config][SCHOOL_SYEAR_OVER_2_YEARS]',
				_( 'School year over two calendar years' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			// Upload school logo.
			echo '<tr><td>' . ( file_exists( 'assets/school_logo_' . UserSchool() . '.jpg' ) ?
				'<br /><img src="assets/school_logo_' . UserSchool() . '.jpg?cache_killer=' . rand() .
				'" style="max-width:225px; max-height:225px;" /><br />' : '' ) .
			FileInput(
				'LOGO_FILE',
				_( 'School logo' ) . ' (.jpg, .png, .gif)',
				'accept="image/*"'
			) . '</td></tr>';

			// Currency.
			echo '<tr><td><table><tr class="st"><td>' . TextInput(
				Config( 'CURRENCY' ),
				'values[config][CURRENCY]',
				_( 'Currency Symbol' ),
				'maxlength=8 size=3'
			) . '</td>';

			// @since 9.1 Add decimal & thousands separator configuration.
			// @link https://en.wikipedia.org/wiki/Decimal_separator
			$thousands_separator_options = [
				',' => ',',
				'.' => '.',
				'&amp;nbsp;' => '&nbsp;',
			];

			$thousands_separator_value = Config( 'THOUSANDS_SEPARATOR' );

			if ( $thousands_separator_value === '&nbsp;' )
			{
				$thousands_separator_value = '&amp;nbsp;';
			}

			echo '<td>' . SelectInput(
				$thousands_separator_value,
				'values[config][THOUSANDS_SEPARATOR]',
				_( 'Thousands separator' ),
				$thousands_separator_options,
				false
			) . '</td>';

			$decimal_separator_options = [
				'.' => '.',
				',' => ',',
			];

			echo '<td>' . SelectInput(
				Config( 'DECIMAL_SEPARATOR' ),
				'values[config][DECIMAL_SEPARATOR]',
				_( 'Decimal separator' ),
				$decimal_separator_options,
				false
			) . '</td>';

			echo '<td>' . Currency( 1250.00 ) . '</td></tr></table></td></tr>';

			// Add "Find a Student" fieldset.
			echo '<tr><td><fieldset><legend>' . _( 'Find a Student' ) . '</legend><table>';

			// @since 7.4 Add Course Widget configuration option: Popup window or Pull-Down.
			$course_widget_options = [
				'' => _( 'Popup window' ),
				'select' => _( 'Pull-Down' ),
			];

			echo '<tr><td>' . SelectInput(
				Config( 'COURSE_WIDGET_METHOD' ),
				'values[config][COURSE_WIDGET_METHOD]',
				_( 'Course Widget' ),
				$course_widget_options,
				false
			) . '</td></tr>';

			echo '</table></fieldset></td></tr>';

			echo '</table>';
		}

		if ( $_REQUEST['tab'] === 'students' )
		{
			$addresses_contacts_cat_title = DBGetOne( "SELECT TITLE
				FROM student_field_categories
				WHERE ID='3'" );

			echo '<table class="cellpadding-5 width-100p"><tr><td><fieldset><legend>' .
				ParseMLField( $addresses_contacts_cat_title ) . '</legend><table>';

			echo '<tr><td>' . CheckboxInput( Config( 'STUDENTS_USE_MAILING' ), 'values[config][STUDENTS_USE_MAILING]', _( 'Display Mailing Address' ), '', false, button( 'check' ), button( 'x' ) ) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'students', 'STUDENTS_USE_BUS' ),
				'values[program_config][students][STUDENTS_USE_BUS]',
				_( 'Check Bus Pickup / Dropoff by default' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'students', 'STUDENTS_USE_CONTACT' ),
				'values[program_config][students][STUDENTS_USE_CONTACT]',
				_( 'Enable Legacy Contact Information' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			if ( ! $multiple_schools_admin_has_1_school )
			{
				// Hide All schools config options if Administrator of 1 school only.
				echo '<tr><td>' . CheckboxInput(
					Config( 'LIMIT_EXISTING_CONTACTS_ADDRESSES' ),
					'values[config][LIMIT_EXISTING_CONTACTS_ADDRESSES]',
					_( 'Limit Existing Contacts & Addresses to current school' ),
					'',
					false,
					button( 'check' ),
					button( 'x' )
				) . '</td></tr>';
			}

			echo '</table></fieldset></td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'students', 'STUDENTS_SEMESTER_COMMENTS' ),
				'values[program_config][students][STUDENTS_SEMESTER_COMMENTS]',
				_( 'Use Semester Comments instead of Quarter Comments' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '</table>';
		}

		if ( $_REQUEST['tab'] === 'grades' )
		{
			$grades_options = [
				'-1' => _( 'Use letter grades only' ),
				'0' => _( 'Use letter and percent grades' ),
				'1' => _( 'Use percent grades only' ),
			];

			echo '<table class="cellpadding-5 width-100p"><tr><td>' . SelectInput(
				ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ),
				'values[program_config][grades][GRADES_DOES_LETTER_PERCENT]',
				_( 'Grades' ),
				$grades_options,
				false
			) . '</td></tr>';

			echo '<tr><td><fieldset><legend>' .
				_( 'Input Final Grades' ) . '</legend><table>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_HIDE_NON_ATTENDANCE_COMMENT' ),
				'values[program_config][grades][GRADES_HIDE_NON_ATTENDANCE_COMMENT]',
				_( 'Hide grade comment except for attendance period courses' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_TEACHER_ALLOW_EDIT' ),
				'values[program_config][grades][GRADES_TEACHER_ALLOW_EDIT]',
				_( 'Allow Teachers to edit grades after grade posting period' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '</table></fieldset></td></tr>';

			echo '<tr><td><fieldset><legend>' .
				_( 'Gradebook' ) . ' - ' . _( 'Grades' ) . '</legend><table>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT' ),
				'values[program_config][grades][GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT]',
				_( 'Allow Teachers to edit gradebook grades for past quarters' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '</table></fieldset></td></tr>';

			echo '<tr><td><fieldset><legend>' .
				_( 'Student Grades' ) . '</legend><table>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_DO_STATS_STUDENTS_PARENTS' ),
				'values[program_config][grades][GRADES_DO_STATS_STUDENTS_PARENTS]',
				_( 'Enable Anonymous Grade Statistics for Parents and Students' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_DO_STATS_ADMIN_TEACHERS' ),
				'values[program_config][grades][GRADES_DO_STATS_ADMIN_TEACHERS]',
				_( 'Enable Anonymous Grade Statistics for Administrators and Teachers' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '</table></fieldset></td></tr></table>';
		}

		if ( $_REQUEST['tab'] === 'attendance' )
		{
			// @since 11.2 Dynamic Daily Attendance calculation based on total course period minutes
			$tooltip = '<div class="tooltip"><i>' .
				_( 'Set to 0 for dynamic Daily Attendance calculation based on total course period minutes.' ) .
				'</i></div>';

			echo '<table class="cellpadding-5"><tr><td>' . TextInput(
				Config( 'ATTENDANCE_FULL_DAY_MINUTES' ),
				'values[config][ATTENDANCE_FULL_DAY_MINUTES]',
				_( 'Minutes in a Full School Day' ) . $tooltip,
				' type="number" min="0" max="999"'
			) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				ProgramConfig( 'attendance', 'ATTENDANCE_EDIT_DAYS_BEFORE' ),
				'values[program_config][attendance][ATTENDANCE_EDIT_DAYS_BEFORE]',
				_( 'Number of days before the school date teachers can edit attendance' ) .
				'<div class="tooltip"><i>' .
				_( 'Leave the field blank to always allow' ) .
				'</i></div>',
				' type="number" min="1" max="99"'
			) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				ProgramConfig( 'attendance', 'ATTENDANCE_EDIT_DAYS_AFTER' ),
				'values[program_config][attendance][ATTENDANCE_EDIT_DAYS_AFTER]',
				_( 'Number of days after the school date teachers can edit attendance' ) .
				'<div class="tooltip"><i>' .
				_( 'Leave the field blank to always allow' ) .
				'</i></div>',
				' type="number" min="1" max="99"'
			) . '</td></tr></table>';
		}

		if ( $_REQUEST['tab'] === 'food_service' )
		{
			echo '<table class="cellpadding-5"><tr><td>' . TextInput(
				ProgramConfig( 'food_service', 'FOOD_SERVICE_BALANCE_WARNING' ),
				'values[program_config][food_service][FOOD_SERVICE_BALANCE_WARNING]',
				_( 'Food Service Balance minimum amount for warning' ),
				' type="number" step="0.01" max="999999999999" min="-999999999999" required'
			) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				ProgramConfig( 'food_service', 'FOOD_SERVICE_BALANCE_MINIMUM' ),
				'values[program_config][food_service][FOOD_SERVICE_BALANCE_MINIMUM]',
				_( 'Food Service Balance minimum amount' ),
				' type="number" step="0.01" max="999999999999" min="-999999999999" required'
			) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				ProgramConfig( 'food_service', 'FOOD_SERVICE_BALANCE_TARGET' ),
				'values[program_config][food_service][FOOD_SERVICE_BALANCE_TARGET]',
				_( 'Food Service Balance target amount' ),
				' type="number" step="0.01" max="999999999999" min="-999999999999" required'
			) . '</td></tr></table>';
		}

		/**
		 * Configuration School Table action Hook.
		 * Plugins or modules can add their own Config options to the table (per school).
		 *
		 * @since 5.8
		 */
		do_action( 'School_Setup/Configuration.php|school_table' );

		PopTable( 'footer' );

		if ( AllowEdit() )
		{
			echo '<br /><div class="center">' . SubmitButton() . '</div>';
		}

		echo '</form>';
	}
}
