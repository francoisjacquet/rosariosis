<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/TipMessage.fnc.php';
require_once 'modules/Discipline/includes/Referral.fnc.php';

DrawHeader( ProgramTitle() );

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

if ( ! empty( $_POST['values'] )
	&& AllowEdit() )
{
	$sql = "UPDATE DISCIPLINE_REFERRALS SET ";

	$go = 0;

	$categories_RET = DBGet( "SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS
		FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du
		WHERE du.SYEAR='" . UserSyear() . "'
		AND du.SCHOOL_ID='" . UserSchool() . "'
		AND du.DISCIPLINE_FIELD_ID=df.ID
		ORDER BY du.SORT_ORDER", array(), array( 'ID' ) );

	foreach ( (array) $_REQUEST['values'] as $column_name => $value )
	{
		if ( 1 ) //!empty($value) || $value=='0')
		{
			$column_data_type = $categories_RET[str_replace( 'CATEGORY_', '', $column_name )][1]['DATA_TYPE'];

			//FJ check numeric fields

			if ( $column_data_type === 'numeric'
				&& $value !== ''
				&& ! is_numeric( $value ) )
			{
				$error[] = _( 'Please enter valid Numeric data.' );
				continue;
			}

			// FJ textarea fields MarkDown sanitize.

			if ( $column_data_type === 'textarea' )
			{
				$value = SanitizeMarkDown( $_POST['values'][$column_name] );
			}

			if ( ! is_array( $value ) )
			{
				$sql .= "$column_name='" . str_replace( "&rsquo;", "''", $value ) . "',";
			}
			else
			{
				$sql .= $column_name . "='||";

				foreach ( (array) $value as $val )
				{
					if ( $val )
					{
						$sql .= str_replace( '&quot;', '"', $val ) . '||';
					}
				}

				$sql .= "',";
			}

			$go = true;
		}
	}

	$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $_REQUEST['referral_id'] . "'";

	if ( $go )
	{
		DBQuery( $sql );
	}

	// Unset values & redirect URL.
	RedirectURL( 'values' );
}

echo ErrorMessage( $error );

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Referral' ) ) )
	{
		DBQuery( "DELETE FROM DISCIPLINE_REFERRALS
			WHERE ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

$categories_RET = DBGet( "SELECT df.ID,du.TITLE
	FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du
	WHERE df.DATA_TYPE!='textarea'
	AND du.SYEAR='" . UserSyear() . "'
	AND du.SCHOOL_ID='" . UserSchool() . "'
	AND du.DISCIPLINE_FIELD_ID=df.ID
	ORDER BY du.SORT_ORDER" );

Widgets( 'reporter' );
Widgets( 'incident_date' );
Widgets( 'discipline_fields' );

$extra['SELECT'] = ',dr.*';

if ( mb_strpos( $extra['FROM'], 'DISCIPLINE_REFERRALS' ) === false )
{
	$extra['FROM'] .= ',DISCIPLINE_REFERRALS dr ';
	$extra['WHERE'] .= ' AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SYEAR=ssm.SYEAR AND dr.SCHOOL_ID=ssm.SCHOOL_ID ';
}

$extra['ORDER_BY'] = 'dr.ENTRY_DATE DESC,s.LAST_NAME,s.FIRST_NAME,s.MIDDLE_NAME';

$extra['columns_after'] = array( 'STAFF_ID' => _( 'Reporter' ), 'ENTRY_DATE' => _( 'Incident Date' ) );
$extra['functions'] = array( 'STAFF_ID' => 'GetTeacher', 'ENTRY_DATE' => 'ProperDate' );

foreach ( (array) $categories_RET as $category )
{
	$extra['columns_after']['CATEGORY_' . $category['ID']] = $category['TITLE'];
	$extra['functions']['CATEGORY_' . $category['ID']] = '_make';
}

$extra['new'] = true;

$extra['singular'] = _( 'Referral' );
$extra['plural'] = _( 'Referrals' );
$extra['link']['FULL_NAME']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'];
$extra['link']['FULL_NAME']['variables'] = array( 'referral_id' => 'ID' );
$extra['link']['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
$extra['link']['remove']['variables'] = array( 'id' => 'ID' );

// Parent: associated students.
$extra['ASSOCIATED'] = User( 'STAFF_ID' );

if ( ! $_REQUEST['modfunc']
	&& ! empty( $_REQUEST['referral_id'] ) )
{
	// FJ prevent referral ID hacking.

	if ( User( 'PROFILE' ) === 'parent' )
	{
		$where = " AND STUDENT_ID IN (SELECT STUDENT_ID
			FROM STUDENTS_JOIN_USERS
			WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "')";
	}
	elseif ( User( 'PROFILE' ) === 'student' )
	{
		$where = " AND STUDENT_ID='" . UserStudentID() . "'";
	}
	elseif ( User( 'PROFILE' ) === 'teacher' )
	{
		$where = " AND STUDENT_ID IN (SELECT STUDENT_ID FROM SCHEDULE
		WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
		AND '" . DBDate() . "'>=START_DATE
		AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL))";
	}
	elseif ( User( 'PROFILE' ) === 'admin' )
	{
		$where = " AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'";
	}

	$RET = DBGet( "SELECT * FROM DISCIPLINE_REFERRALS WHERE ID='" . $_REQUEST['referral_id'] . "'" . $where );

	if ( ! empty( $RET ) )
	{
		$RET = $RET[1];

		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&referral_id=' . $_REQUEST['referral_id'] . '" method="POST">';

		DrawHeader( '', SubmitButton() );

		echo '<br />';
		PopTable( 'header', _( 'Referral' ) );

		$categories_RET = DBGet( "SELECT df.ID,df.DATA_TYPE,du.TITLE,du.SELECT_OPTIONS
			FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du
			WHERE du.SYEAR='" . UserSyear() . "'
			AND du.SCHOOL_ID='" . UserSchool() . "'
			AND du.DISCIPLINE_FIELD_ID=df.ID
			ORDER BY du.SORT_ORDER" );

		echo '<table class="width-100p">';

		$student_full_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
			FROM STUDENTS
			WHERE STUDENT_ID='" . $RET['STUDENT_ID'] . "'" );

		echo '<tr><td>' . NoInput(
			MakeStudentPhotoTipMessage( $RET['STUDENT_ID'], $student_full_name ),
			_( 'Student' )
		) . '</td></tr>';

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

		echo '<tr><td>' . SelectInput(
			$RET['STAFF_ID'],
			'values[STAFF_ID]',
			_( 'Reporter' ),
			$users_options,
			false,
			'required',
			true
		) . '</td></tr>';

		echo '<tr><td>' .
		DateInput( $RET['ENTRY_DATE'], 'values[ENTRY_DATE]', _( 'Incident Date' ) ) .
			'</td></tr>';

		foreach ( (array) $categories_RET as $category )
		{
			echo '<tr><td>' . ReferralInput(
				$category,
				$RET['CATEGORY_' . $category['ID']],
				false
			) . '</td></tr>';
		}

		echo '</table>';

		echo PopTable( 'footer' );

		if ( AllowEdit() )
		{
			echo '<br /><div class="center">' . SubmitButton() . '</div>';
		}

		echo '</form>';
	}
	else
	{
		$error[] = _( 'No Students were found.' );

		$_REQUEST['referral_id'] = false;
	}
}

if ( empty( $_REQUEST['referral_id'] )
	&& ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	Search( 'student_id', $extra );
}

/**
 * @param $value
 * @param $column
 */
function _make( $value, $column )
{
	if ( mb_substr_count( $value, '-' ) === 2
		&& VerifyDate( $value ) )
	{
		$value = ProperDate( $value );
	}
	elseif ( is_numeric( $value ) )
	{
		$value = mb_strpos( $value, '.' ) === false ? $value : rtrim( rtrim( $value, '0' ), '.' );
	}
	elseif ( $value === 'Y' )
	{
		$value = button( 'check' );
	}

	return str_replace( '||', ', ', trim( $value, '|' ) );
}
