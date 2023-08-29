<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/TipMessage.fnc.php';
require_once 'modules/Discipline/includes/Referral.fnc.php';

$_REQUEST['include_inactive'] = issetVal( $_REQUEST['include_inactive'], '' );

DrawHeader( ProgramTitle() );

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['st_arr'] ) )
	{
		$categories_RET = DBGet( "SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS
			FROM discipline_fields df,discipline_field_usage du
			WHERE du.SYEAR='" . UserSyear() . "'
			AND du.SCHOOL_ID='" . UserSchool() . "'
			AND du.DISCIPLINE_FIELD_ID=df.ID
			ORDER BY du.SORT_ORDER IS NULL,du.SORT_ORDER", [], [ 'ID' ] );

		$insert_columns = [];

		if ( User( 'PROFILE' ) === 'teacher' )
		{
			// Limit relator to Teacher.
			$_REQUEST['values']['STAFF_ID'] = $_POST['values']['STAFF_ID'] = User( 'STAFF_ID' );
		}

		foreach ( (array) $_REQUEST['values'] as $column => $value )
		{
			$column_data_type = isset( $categories_RET[str_replace( 'CATEGORY_', '', $column )][1]['DATA_TYPE'] ) ?
				$categories_RET[str_replace( 'CATEGORY_', '', $column )][1]['DATA_TYPE'] :
				'';

			if ( $column_data_type === 'numeric'
				&& $value !== ''
				&& ! is_numeric( $value ) )
			{
				// Check numeric fields.
				$error[] = _( 'Please enter valid Numeric data.' );

				continue;
			}

			if ( $column_data_type === 'textarea' )
			{
				// Textarea fields MarkDown sanitize.
				$value = DBEscapeString( SanitizeMarkDown( $_POST['values'][$column] ) );
			}

			if ( is_array( $value ) )
			{
				$value_f = '||';

				foreach ( (array) $value as $val )
				{
					if ( $val !== '' )
					{
						$value_f .= $val . '||';
					}
				}

				$value = trim( $value_f, '|' ) === '' ? '' : $value_f;
			}

			$insert_columns[ $column ] = $value;
		}

		$email_sent = false;

		foreach ( $_REQUEST['st_arr'] as $student_id )
		{
			$referral_id = DBInsert(
				'discipline_referrals',
				[
					'SYEAR' => UserSyear(),
					'SCHOOL_ID' => UserSchool(),
					'STUDENT_ID' => (int) $student_id,
					'REFERRAL_DATE' => DBDate(),
				] + $insert_columns,
				'id'
			);

			if ( $referral_id
				&& ( isset( $_REQUEST['admin_emails'] )
					|| isset( $_REQUEST['teacher_emails'] ) ) )
			{
				// Email Discipline Referral feature.
				require_once 'modules/Discipline/includes/EmailReferral.fnc.php';

				$emails = array_merge(
					issetVal( $_REQUEST['admin_emails'], [] ),
					issetVal( $_REQUEST['teacher_emails'], [] )
				);

				if ( EmailReferral( $referral_id, $emails ) )
				{
					$email_sent = true;
				}
				elseif ( ROSARIO_DEBUG )
				{
					echo 'Referral not emailed: ' . var_dump( $referral_id );
				}
			}
		}

		if ( $referral_id )
		{
			$note[] = _( 'That discipline incident has been referred to an administrator.' );

			if ( $email_sent )
			{
				$note[] = _( 'That discipline incident has been emailed.' );
			}
		}
	}
	else
	{
		$error[] = _( 'You must choose at least one student.' );
	}

	// Unset modfunc, values & st_arr & redirect URL.
	RedirectURL( [ 'modfunc', 'values', 'st_arr' ] );
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

if ( ! $_REQUEST['modfunc'] )
{
	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		//FJ teachers need AllowEdit (to edit the input fields)
		$_ROSARIO['allow_edit'] = true;

		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=save&include_inactive=' . $_REQUEST['include_inactive']  ) . '" method="POST">';

		DrawHeader( '', SubmitButton( _( 'Add Referral for Selected Students' ) ) );

		echo '<br />';

		PopTable( 'header', ProgramTitle() );

		echo '<table class="width-100p"><tr><td>';

		$users_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME,
			EMAIL,PROFILE
			FROM staff
			WHERE SYEAR='" . UserSyear() . "'
			AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)
			AND PROFILE IN ('admin','teacher')
			ORDER BY FULL_NAME" );

		$users_options = [];

		foreach ( (array) $users_RET as $user )
		{
			$users_options[$user['STAFF_ID']] = $user['FULL_NAME'];
		}

		if ( User( 'PROFILE' ) === 'teacher' )
		{
			// Limit reporter to Teacher.
			echo NoInput( $users_options[User( 'STAFF_ID' )], _( 'Reporter' ) );
		}
		else
		{
			echo SelectInput(
				User( 'STAFF_ID' ),
				'values[STAFF_ID]',
				_( 'Reporter' ),
				$users_options,
				false,
				'required',
				false
			);
		}

		echo '</td></tr>';

		echo '<tr><td>' .
		DateInput( DBDate(), 'values[ENTRY_DATE]', _( 'Incident Date' ), false, false ) .
			'</td></tr>';

		// FJ email Discipline Referral feature
		// email Referral to: Administrators and/or Teachers
		// get Administrators & Teachers with valid emails:
		$emailadmin_options = $emailteacher_options = [];

		foreach ( (array) $users_RET as $user )
		{
			if ( filter_var( $user['EMAIL'], FILTER_VALIDATE_EMAIL ) )
			{
				if ( $user['PROFILE'] === 'admin' )
				{
					$emailadmin_options[$user['EMAIL']] = $user['FULL_NAME'];
				}
				elseif ( $user['PROFILE'] === 'teacher' )
				{
					$emailteacher_options[$user['EMAIL']] = $user['FULL_NAME'];
				}
			}
		}

		echo '<tr><td>' . _( 'Email Referral to' ) . ':<br />';

		$value = $allow_na = $div = false;

		// Chosen Multiple select inputs.
		$extra = 'multiple';

		echo '<table><tr class="st"><td>';

		echo Select2Input(
			$value,
			'admin_emails[]',
			_( 'Administrators' ),
			$emailadmin_options,
			$allow_na,
			$extra,
			$div
		);

		echo '</td><td>';

		echo Select2Input(
			$value,
			'teacher_emails[]',
			_( 'Teachers' ),
			$emailteacher_options,
			$allow_na,
			$extra,
			$div
		);

		echo '</td></tr></table></td></tr>';

		$categories_RET = DBGet( "SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS
			FROM discipline_fields df,discipline_field_usage du
			WHERE du.SYEAR='" . UserSyear() . "'
			AND du.SCHOOL_ID='" . UserSchool() . "'
			AND du.DISCIPLINE_FIELD_ID=df.ID
			ORDER BY du.SORT_ORDER IS NULL,du.SORT_ORDER" );

		foreach ( (array) $categories_RET as $category )
		{
			echo '<tr><td><hr>' . ReferralInput( $category ) . '</td></tr>';
		}

		echo '</table>';

		PopTable( 'footer' );

		echo '<br />';
	}

	$extra = [];

	$extra['link'] = [ 'FULL_NAME' => false ];

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";

	$extra['functions'] = [
		'CHECKBOX' => 'MakeChooseCheckbox',
		'FULL_NAME' => 'makePhotoTipMessage',
	];

	$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( '', '', 'st_arr' ) ];

	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Add Referral for Selected Students' ) ) . '</div>';

		echo '</form>';
	}
}
