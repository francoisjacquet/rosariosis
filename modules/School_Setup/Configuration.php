<?php
require_once 'ProgramFunctions/Theme.fnc.php';

//FJ add School Configuration
//move the Modules config.inc.php to the database table
// 'config' if the value is needed in multiple modules
// 'program_config' if the value is needed in one module

DrawHeader( ProgramTitle() );

$configuration_link = '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '">' .
	( ! isset( $_REQUEST['tab'] ) ?
	'<b>' . _( 'Configuration' ) . '</b>' : _( 'Configuration' ) ) . '</a>';

$modules_link = '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&tab=modules">' .
	( isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] === 'modules' ?
	'<b>' . _( 'Modules' ) . '</b>' : _( 'Modules' ) ) . '</a>';

$plugins_link = '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins">' .
	( isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] === 'plugins' ?
	'<b>' . _( 'Plugins' ) . '</b>' : _( 'Plugins' ) ) . '</a>';

if ( AllowEdit() )
{
	DrawHeader( $configuration_link . ' | ' . $modules_link . ' | ' . $plugins_link );
}

if ( isset( $_REQUEST['tab'] )
	&& $_REQUEST['tab'] === 'modules' )
{
	require_once 'modules/School_Setup/includes/Modules.inc.php';
}
elseif ( isset( $_REQUEST['tab'] )
	&& $_REQUEST['tab'] === 'plugins' )
{
	require_once 'modules/School_Setup/includes/Plugins.inc.php';
}
else
{
	require_once 'ProgramFunctions/FileUpload.fnc.php';

	if ( $_REQUEST['modfunc'] === 'update' )
	{
		// FJ upload school logo.

		if ( ! empty( $_FILES['LOGO_FILE'] )
			&& AllowEdit() )
		{
			ImageUpload(
				'LOGO_FILE',
				array(),
				'assets/',
				array(),
				'.jpg',
				'school_logo_' . UserSchool()
			);
		}

		if ( ! empty( $_REQUEST['values'] )
			&& $_POST['values']
			&& AllowEdit() )
		{
			$updated = $numeric_error = false;

			foreach ( (array) $_REQUEST['values']['CONFIG'] as $column => $value )
			{
				$numeric_columns = array(
					'FAILED_LOGIN_LIMIT',
					'PASSWORD_STRENGTH',
				);

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

			foreach ( (array) $_REQUEST['values']['PROGRAM_CONFIG'] as $program => $columns )
			{
				foreach ( (array) $columns as $column => $value )
				{
					$numeric_columns = array(
						'ATTENDANCE_EDIT_DAYS_BEFORE',
						'ATTENDANCE_EDIT_DAYS_AFTER',
						'FOOD_SERVICE_BALANCE_WARNING',
						'FOOD_SERVICE_BALANCE_MINIMUM',
						'FOOD_SERVICE_BALANCE_TARGET',
					);

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

			$old_theme = Config( 'THEME' );

			// Theme changed? Update it live!
			ThemeLiveUpdate( Config( 'THEME' ), $old_theme );
		}

		// Unset modfunc & values & redirect URL.
		RedirectURL( array( 'modfunc', 'values' ) );
	}

	if ( ! $_REQUEST['modfunc'] )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST" enctype="multipart/form-data">';

		if ( AllowEdit() )
		{
			DrawHeader( '', SubmitButton() );
		}

		if ( ! empty( $note ) )
		{
			echo ErrorMessage( $note, 'note' );
		}

		if ( ! empty( $error ) )
		{
			echo ErrorMessage( $error, 'error' );
		}

		echo '<br />';
		PopTable( 'header', SchoolInfo( 'TITLE' ) );

		echo '<fieldset><legend>' . ParseMLField( Config( 'TITLE' ) ) . '</legend><table>';

		echo '<tr><td>' . MLTextInput( Config( 'TITLE' ), 'values[CONFIG][TITLE]', _( 'Program Title' ) ) . '</td></tr>';

		echo '<tr><td>' . TextInput( Config( 'NAME' ), 'values[CONFIG][NAME]', _( 'Program Name' ), 'required' ) . '</td></tr>';

		// FJ add Default Theme to Configuration.
		echo '<tr><td><table class="width-100p"><tr>';

		$themes = glob( 'assets/themes/*', GLOB_ONLYDIR );

		$count = 0;

		foreach ( (array) $themes as $theme )
		{
			$theme_name = str_replace( 'assets/themes/', '', $theme );

			echo '<td><label><input type="radio" name="values[CONFIG][THEME]" value="' . $theme_name . '"' .
				(  ( Config( 'THEME' ) === $theme_name ) ? ' checked' : '' ) . '> ' .
				$theme_name . '</label></td>';

			if ( ++$count % 3 == 0 )
			{
				echo '</tr><tr class="st">';
			}
		}

		echo '</tr></table></td></tr>';
		echo '<tr><td>';

		echo '<span class="legend-gray">' . _( 'Default Theme' ) . '</span> ';

		// FJ Add Force Default Theme.
		echo CheckboxInput(
			Config( 'THEME_FORCE' ),
			'values[CONFIG][THEME_FORCE]',
			_( 'Force' ),
			'',
			true
		);

		echo '</td></tr>';

		// FJ add Registration to Configuration.
		echo '<tr><td><fieldset><legend>' . _( 'Registration' ) . '</legend><table>';

		echo '<tr><td>' . CheckboxInput(
			Config( 'CREATE_USER_ACCOUNT' ),
			'values[CONFIG][CREATE_USER_ACCOUNT]',
			_( 'Create User Account' ) .
			'<div class="tooltip"><i>' .
			_( 'New users will be added with the No Access profile' ) .
			'</i></div>',
			'',
			false,
			button( 'check' ),
			button( 'x' )
		) . '</td></tr>';

		echo '<tr><td>' . CheckboxInput(
			Config( 'CREATE_STUDENT_ACCOUNT' ),
			'values[CONFIG][CREATE_STUDENT_ACCOUNT]',
			_( 'Create Student Account' ) .
			'<div class="tooltip"><i>' .
			_( 'New students will be added as Inactive students' ) .
			'</i></div>',
			'',
			false,
			button( 'check' ),
			button( 'x' )
		) . '</td></tr>';

		$students_email_field_RET = DBGet( "SELECT ID, TITLE
			FROM CUSTOM_FIELDS
			WHERE TYPE='text'
			AND CATEGORY_ID=1" );

		$students_email_field_options = array( 'USERNAME' => _( 'Username' ) );

		foreach ( (array) $students_email_field_RET as $field )
		{
			$students_email_field_options[str_replace( 'custom_', '', $field['ID'] )] = ParseMLField( $field['TITLE'] );
		}

		echo '<tr><td>' . SelectInput(
			Config( 'STUDENTS_EMAIL_FIELD' ),
			'values[CONFIG][STUDENTS_EMAIL_FIELD]',
			sprintf( _( 'Student email field' ), Config( 'NAME' ) ),
			$students_email_field_options,
			'N/A'
		) . '</td></tr>';

		echo '</table></fieldset>';

		// FJ add Security to Configuration.
		echo '<tr><td><fieldset><legend>' . _( 'Security' ) . '</legend><table>';

		// Failed login ban if >= X failed attempts within 10 minutes.
		echo '<tr><td>' . TextInput(
			Config( 'FAILED_LOGIN_LIMIT' ),
			'values[CONFIG][FAILED_LOGIN_LIMIT]',
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
			'values[CONFIG][PASSWORD_STRENGTH]',
			'',
			'type=number maxlength=1 min=0 max=4 style="width:196px;"',
			false
		);

		$password_strength_input_id = GetInputID( 'values[CONFIG][PASSWORD_STRENGTH]' );

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

		echo '</table></fieldset>';

		// Display Name.
		// @link https://www.w3.org/International/questions/qa-personal-names
		$display_name_options = array(
			"FIRST_NAME||' '||LAST_NAME" => _( 'First Name' ) . ' ' . _( 'Last Name' ),
			"FIRST_NAME||' '||LAST_NAME||coalesce(' '||NAME_SUFFIX,' ')" => _( 'First Name' ) . ' ' . _( 'Last Name' ) . ' ' . _( 'Suffix' ),
			"FIRST_NAME||coalesce(' '||MIDDLE_NAME||' ',' ')||LAST_NAME" => _( 'First Name' ) . ' ' . _( 'Middle Name' ) . ' ' . _( 'Last Name' ),
			"FIRST_NAME||', '||LAST_NAME||coalesce(' '||MIDDLE_NAME,' ')" => _( 'First Name' ) . ', ' . _( 'Last Name' ) . ' ' . _( 'Middle Name' ),
			"LAST_NAME||' '||FIRST_NAME" => _( 'Last Name' ) . ' ' . _( 'First Name' ),
			"LAST_NAME||', '||FIRST_NAME" => _( 'Last Name' ) . ', ' . _( 'First Name' ),
			"LAST_NAME||', '||FIRST_NAME||' '||COALESCE(MIDDLE_NAME,' ')" => _( 'Last Name' ) . ', ' . _( 'First Name' ) . ' ' . _( 'Middle Name' ),
		);

		echo '<tr><td>' . SelectInput(
			Config( 'DISPLAY_NAME' ),
			'values[CONFIG][DISPLAY_NAME]',
			_( 'Display Name' ),
			$display_name_options,
			false
		) . '</td></tr>';

		echo '</table></fieldset>';

		echo '<br /><fieldset><legend>' . _( 'School' ) . '</legend><table>';

		//FJ school year over one/two calendar years format
		echo '<tr><td>' . CheckboxInput( Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ), 'values[CONFIG][SCHOOL_SYEAR_OVER_2_YEARS]', _( 'School year over two calendar years' ), '', false, button( 'check' ), button( 'x' ) ) . '</td></tr>';

		// FJ upload school logo.
		echo '<tr><td>' . ( file_exists( 'assets/school_logo_' . UserSchool() . '.jpg' ) ?
			'<br /><img src="assets/school_logo_' . UserSchool() . '.jpg?cache_killer=' . rand() .
			'" style="max-width:225px; max-height:225px;" /><br />' : '' ) .
		FileInput(
			'LOGO_FILE',
			_( 'School logo' ) . ' (.jpg, .png, .gif)',
			'accept="image/*"'
		) . '</td></tr>';

		//FJ currency
		echo '<tr><td>' . TextInput( Config( 'CURRENCY' ), 'values[CONFIG][CURRENCY]', _( 'Currency Symbol' ), 'maxlength=3 size=3' ) . '</td></tr>';

		echo '</table></fieldset>';

		if ( $RosarioModules['Students'] )
		{
			echo '<br /><fieldset><legend>' . _( 'Students' ) . '</legend><table>';

			echo '<tr><td>' . CheckboxInput( Config( 'STUDENTS_USE_MAILING' ), 'values[CONFIG][STUDENTS_USE_MAILING]', _( 'Display Mailing Address' ), '', false, button( 'check' ), button( 'x' ) ) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'students', 'STUDENTS_USE_BUS' ),
				'values[PROGRAM_CONFIG][students][STUDENTS_USE_BUS]',
				_( 'Check Bus Pickup / Dropoff by default' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'students', 'STUDENTS_USE_CONTACT' ),
				'values[PROGRAM_CONFIG][students][STUDENTS_USE_CONTACT]',
				_( 'Enable Legacy Contact Information' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'students', 'STUDENTS_SEMESTER_COMMENTS' ),
				'values[PROGRAM_CONFIG][students][STUDENTS_SEMESTER_COMMENTS]',
				_( 'Use Semester Comments instead of Quarter Comments' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				Config( 'LIMIT_EXISTING_CONTACTS_ADDRESSES' ),
				'values[CONFIG][LIMIT_EXISTING_CONTACTS_ADDRESSES]',
				_( 'Limit Existing Contacts & Addresses to current school' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '</table></fieldset>';
		}

		if ( $RosarioModules['Grades'] )
		{
			echo '<br /><fieldset><legend>' . _( 'Grades' ) . '</legend><table>';

			$grades_options = array(
				'-1' => _( 'Use letter grades only' ),
				'0' => _( 'Use letter and percent grades' ),
				'1' => _( 'Use percent grades only' ),
			);

			echo '<tr><td>' . SelectInput(
				ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ),
				'values[PROGRAM_CONFIG][grades][GRADES_DOES_LETTER_PERCENT]',
				_( 'Grades' ),
				$grades_options,
				false
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_HIDE_NON_ATTENDANCE_COMMENT' ),
				'values[PROGRAM_CONFIG][grades][GRADES_HIDE_NON_ATTENDANCE_COMMENT]',
				_( 'Hide grade comment except for attendance period courses' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_TEACHER_ALLOW_EDIT' ),
				'values[PROGRAM_CONFIG][grades][GRADES_TEACHER_ALLOW_EDIT]',
				_( 'Allow Teachers to edit grades after grade posting period' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT' ),
				'values[PROGRAM_CONFIG][grades][GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT]',
				_( 'Allow Teachers to edit gradebook grades for past quarters' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_DO_STATS_STUDENTS_PARENTS' ),
				'values[PROGRAM_CONFIG][grades][GRADES_DO_STATS_STUDENTS_PARENTS]',
				_( 'Enable Anonymous Grade Statistics for Parents and Students' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '<tr><td>' . CheckboxInput(
				ProgramConfig( 'grades', 'GRADES_DO_STATS_ADMIN_TEACHERS' ),
				'values[PROGRAM_CONFIG][grades][GRADES_DO_STATS_ADMIN_TEACHERS]',
				_( 'Enable Anonymous Grade Statistics for Administrators and Teachers' ),
				'',
				false,
				button( 'check' ),
				button( 'x' )
			) . '</td></tr>';

			echo '</table></fieldset>';
		}

		if ( $RosarioModules['Attendance'] )
		{
			echo '<br /><fieldset><legend>' . _( 'Attendance' ) . '</legend><table>';

			echo '<tr><td>' . TextInput( Config( 'ATTENDANCE_FULL_DAY_MINUTES' ), 'values[CONFIG][ATTENDANCE_FULL_DAY_MINUTES]', _( 'Minutes in a Full School Day' ), 'maxlength=3 size=3 min=0' ) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				ProgramConfig( 'attendance', 'ATTENDANCE_EDIT_DAYS_BEFORE' ),
				'values[PROGRAM_CONFIG][attendance][ATTENDANCE_EDIT_DAYS_BEFORE]',
				_( 'Number of days before the school date teachers can edit attendance' ) .
				'<div class="tooltip"><i>' .
				_( 'Leave the field blank to always allow' ) .
				'</i></div>',
				'maxlength=2 size=2 min=0'
			) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				ProgramConfig( 'attendance', 'ATTENDANCE_EDIT_DAYS_AFTER' ),
				'values[PROGRAM_CONFIG][attendance][ATTENDANCE_EDIT_DAYS_AFTER]',
				_( 'Number of days after the school date teachers can edit attendance' ) .
				'<div class="tooltip"><i>' .
				_( 'Leave the field blank to always allow' ) .
				'</i></div>',
				'maxlength=2 size=2 min=0'
			) . '</td></tr>';

			echo '</table></fieldset>';
		}

		if ( $RosarioModules['Food_Service'] )
		{
			echo '<br /><fieldset><legend>' . _( 'Food Service' ) . '</legend><table>';

			echo '<tr><td>' . TextInput(
				ProgramConfig( 'food_service', 'FOOD_SERVICE_BALANCE_WARNING' ),
				'values[PROGRAM_CONFIG][food_service][FOOD_SERVICE_BALANCE_WARNING]',
				_( 'Food Service Balance minimum amount for warning' ),
				'maxlength=10 size=5 required'
			) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				ProgramConfig( 'food_service', 'FOOD_SERVICE_BALANCE_MINIMUM' ),
				'values[PROGRAM_CONFIG][food_service][FOOD_SERVICE_BALANCE_MINIMUM]',
				_( 'Food Service Balance minimum amount' ),
				'maxlength=10 size=5 required'
			) . '</td></tr>';

			echo '<tr><td>' . TextInput(
				ProgramConfig( 'food_service', 'FOOD_SERVICE_BALANCE_TARGET' ),
				'values[PROGRAM_CONFIG][food_service][FOOD_SERVICE_BALANCE_TARGET]',
				_( 'Food Service Balance target amount' ),
				'maxlength=10 size=5 required'
			) . '</td></tr>';

			echo '</table></fieldset>';
		}

		PopTable( 'footer' );

		if ( AllowEdit() )
		{
			echo '<br /><div class="center">' . SubmitButton() . '</div>';
		}

		echo '</form>';
	}
}

/**
 * Theme live update.
 * Configured theme has changed? Update it live!
 * Updates the stylesheet.css file to the new theme directory,
 * Using a Javascript snippet.
 *
 * @todo use it Preferences too!
 *
 * Local function
 *
 * @since  3.0
 *
 * @param  string  $new_theme New theme name / directory.
 * @param  string  $old_theme Old theme name / directory.
 * @param  boolean $default   Is default theme (Configuration.php) or Preferred theme (Preferences.php)?
 * @return boolean False if has not changed, else true.
 */
function _themeLiveUpdate( $new_theme, $old_theme, $default = true )
{
	if ( ! $new_theme
		|| ! $old_theme
		|| $new_theme === $old_theme )
	{
		return false;
	}

	if ( ! $default
		&& Config( 'THEME_FORCE' ) )
	{
		// Theme forced, we should not be able to change it anyway!
		return false;
	}

	// If not Forcing theme, update admin Preferred theme too.

	if ( $default
		&& ! Config( 'THEME_FORCE' )
		&& Preferences( 'THEME' ) !== $new_theme )
	{
		// TODO.
	}

	// Update stylesheet(s) href. ?>
	<script>
	$('link[href^="assets/themes"]').each(function(){
		$(this).attr('href', $(this).attr('href').replace(
			<?php echo json_encode( $old_theme ); ?>,
			<?php echo json_encode( $new_theme ); ?>
		) );
	});
	</script>
	<?php

	return true;
}
