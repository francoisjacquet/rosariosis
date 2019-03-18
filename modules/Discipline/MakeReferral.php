<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/TipMessage.fnc.php';

DrawHeader( ProgramTitle() );

// set start date

if ( isset( $_REQUEST['day_start'] )
	&& isset( $_REQUEST['month_start'] )
	&& isset( $_REQUEST['year_start'] ) )
{
	$start_date = RequestedDate(
		$_REQUEST['year_start'],
		$_REQUEST['month_start'],
		$_REQUEST['day_start']
	);
}

if ( empty( $start_date ) )
{
	$start_date = date( 'Y-m' ) . '-01';
}

// set end date

if ( isset( $_REQUEST['day_end'] )
	&& isset( $_REQUEST['month_end'] )
	&& isset( $_REQUEST['year_end'] ) )
{
	$end_date = RequestedDate(
		$_REQUEST['year_end'],
		$_REQUEST['month_end'],
		$_REQUEST['day_end']
	);
}

if ( empty( $end_date ) )
{
	$end_date = DBDate();
}

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( count( $_REQUEST['st_arr'] ) )
	{
		$email_sent = false;

		foreach ( $_REQUEST['st_arr'] as $student_id )
		{
			$sql = "INSERT INTO DISCIPLINE_REFERRALS ";

			$referral_id = DBSeqNextID( 'DISCIPLINE_REFERRALS_SEQ' );

			$fields = "ID,SYEAR,SCHOOL_ID,STUDENT_ID,";
			$values = $referral_id . ",'" . UserSyear() . "','" . UserSchool() . "','" . $student_id . "',";

			if ( User( 'PROFILE' ) === 'teacher' )
			{
				// Limit relator to Teacher.
				$_REQUEST['values']['STAFF_ID'] = $_POST['values']['STAFF_ID'] = User( 'STAFF_ID' );
			}

			$go = 0;

			$categories_RET = DBGet( "SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du WHERE du.SYEAR='" . UserSyear() . "' AND du.SCHOOL_ID='" . UserSchool() . "' AND du.DISCIPLINE_FIELD_ID=df.ID ORDER BY du.SORT_ORDER", array(), array( 'ID' ) );

			foreach ( (array) $_REQUEST['values'] as $column => $value )
			{
				if ( ! empty( $value ) || $value == '0' )
				{
					$column_data_type = $categories_RET[str_replace( 'CATEGORY_', '', $column )][1]['DATA_TYPE'];

					//FJ check numeric fields

					if ( $column_data_type === 'numeric'
						&& ! is_numeric( $value ) )
					{
						$error[] = _( 'Please enter valid Numeric data.' );
						$go = 0;
						break;
					}

					// FJ textarea fields MarkDown sanitize.

					if ( $column_data_type === 'textarea' )
					{
						$value = SanitizeMarkDown( $_POST['values'][$column] );
					}

					$fields .= DBEscapeIdentifier( $column ) . ',';

					if ( ! is_array( $value ) )
					{
						$values .= "'" . str_replace( '&quot;', '"', $value ) . "',";
					}
					else
					{
						$values .= "'||";

						foreach ( (array) $value as $val )
						{
							if ( $val )
							{
								$values .= str_replace( '&quot;', '"', $val ) . '||';
							}
						}

						$values .= "',";
					}

					$go = true;
				}
			}

			// Insert Referral date (fixed to today, != entry date).
			$fields .= 'REFERRAL_DATE,';

			$values .= "'" . DBDate() . "',";

			$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

			if ( $go )
			{
				DBQuery( $sql );

				// FJ email Discipline Referral feature.

				if ( isset( $_REQUEST['emails'] ) )
				{
					require_once 'modules/Discipline/includes/EmailReferral.fnc.php';

					if ( EmailReferral( $referral_id, $_REQUEST['emails'] ) )
					{
						$email_sent = true;
					}
					elseif ( ROSARIO_DEBUG )
					{
						echo 'Referral not emailed: ' . var_dump( $referral_id );
					}
				}
			}
		}

		if ( $go )
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
	RedirectURL( 'modfunc', 'values', 'st_arr' );
}

echo ErrorMessage( $error );

echo ErrorMessage( $note, 'note' );

if ( ! $_REQUEST['modfunc'] )
{
	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		//FJ teachers need AllowEdit (to edit the input fields)
		$_ROSARIO['allow_edit'] = true;

		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&include_inactive=' . $_REQUEST['include_inactive'] . '" method="POST">';

		DrawHeader( '', SubmitButton( _( 'Add Referral for Selected Students' ) ) );

		echo '<br />';

		PopTable( 'header', ProgramTitle() );

		$categories_RET = DBGet( "SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS
			FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du
			WHERE du.SYEAR='" . UserSyear() . "'
			AND du.SCHOOL_ID='" . UserSchool() . "'
			AND du.DISCIPLINE_FIELD_ID=df.ID
			ORDER BY du.SORT_ORDER" );

		echo '<table class="width-100p"><tr><td>';

		$users_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME,
			EMAIL,PROFILE
			FROM STAFF
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOLS LIKE '%," . UserSchool() . ",%'
			AND PROFILE IN ('admin','teacher')
			ORDER BY FULL_NAME" );

		$users_options = array();

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

		echo ChosenSelectInput(
			$value,
			'emails[]',
			_( 'Administrators' ),
			$emailadmin_options,
			$allow_na,
			$extra,
			$div
		);

		echo '</td><td>';

		echo ChosenSelectInput(
			$value,
			'emails[]',
			_( 'Teachers' ),
			$emailteacher_options,
			$allow_na,
			$extra,
			$div
		);

		echo '</td></tr></table></td></tr>';

		foreach ( (array) $categories_RET as $category )
		{
			echo '<tr><td>';

			switch ( $category['DATA_TYPE'] )
			{
				case 'text':
					echo TextInput(
						'',
						'values[CATEGORY_' . $category['ID'] . ']',
						$category['TITLE'],
						'maxlength=255'
					);

					break;

				case 'numeric':
					echo TextInput(
						'',
						'values[CATEGORY_' . $category['ID'] . ']',
						$category['TITLE'],
						'type="number" min="-999999999999999999" max="999999999999999999"'
					);

					break;

				case 'textarea':
					echo TextAreaInput(
						'',
						'values[CATEGORY_' . $category['ID'] . ']',
						$category['TITLE'],
						'maxlength=5000 rows=4 cols=30'
					);

					break;

				case 'checkbox':
					echo CheckboxInput(
						'',
						'values[CATEGORY_' . $category['ID'] . ']',
						$category['TITLE'],
						'',
						true
					);

					break;

				case 'date':
					echo DateInput(
						DBDate(),
						'_values[CATEGORY_' . $category['ID'] . ']',
						$category['TITLE']
					);

					break;

				case 'multiple_checkbox':
					$options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $category['SELECT_OPTIONS'] ) );

					echo '<table class="cellpadding-5"><tr class="st">';

					$i = 0;

					foreach ( (array) $options as $option )
					{
						if ( $i++ % 3 == 0 )
						{
							echo '</tr><tr class="st">';
						}

						echo '<td><label>
							<input type="checkbox" name="values[CATEGORY_' . $category['ID'] . '][]"
								value="' . htmlspecialchars( $option, ENT_QUOTES ) . '" />&nbsp;' .
							( $option != '' ? $option : '-' ) .
							'</label></td>';
					}

					echo '</tr></table>';

					echo FormatInputTitle( $category['TITLE'], '', false, '' );

					break;

				case 'multiple_radio':
					$options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $category['SELECT_OPTIONS'] ) );

					echo '<table class="cellpadding-5"><tr class="st">';

					$i = 0;

					foreach ( (array) $options as $option )
					{
						if ( $i++ % 3 == 0 )
						{
							echo '</tr><tr class="st">';
						}

						echo '<td><label>
							<input type="radio" name="values[CATEGORY_' . $category['ID'] . ']"
								value="' . htmlspecialchars( $option, ENT_QUOTES ) . '">&nbsp;' .
							( $option != '' ? $option : '-' ) .
							'</label></td>';
					}

					echo '</tr></table>';

					echo FormatInputTitle( $category['TITLE'], '', false, '' );

					break;

				case 'select':
					$options = array();

					$category['SELECT_OPTIONS'] = str_replace( "\n", "\r", str_replace( "\r\n", "\r", $category['SELECT_OPTIONS'] ) );

					$select_options = explode( "\r", $category['SELECT_OPTIONS'] );

					foreach ( (array) $select_options as $option )
					{
						$options[$option] = $option;
					}

					echo SelectInput(
						'',
						'values[CATEGORY_' . $category['ID'] . ']',
						$category['TITLE'],
						$options,
						'N/A'
					);

					break;
			}

			echo '</td></tr>';
		}

		echo '</table>';

		PopTable( 'footer' );

		echo '<br />';
	}

	$extra = array();

	$extra['link'] = array( 'FULL_NAME' => false );

	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";

	$extra['functions'] = array(
		'CHECKBOX' => 'MakeChooseCheckbox',
		'FULL_NAME' => 'makePhotoTipMessage',
	);

	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( '', '', 'st_arr' ) );

	$extra['new'] = true;

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Add Referral for Selected Students' ) ) . '</div>';

		echo '</form>';
	}
}
