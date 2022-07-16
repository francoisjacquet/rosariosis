<?php

/**
 * Email a Referral and its content
 * Email is from User if has email set
 *
 * @uses EmailReferralGetReferralSafe()
 * @uses EmailReferralFormatFields()
 *
 * @param  int   $referral_id Referral ID.
 * @param  array $emails      array of emails.
 *
 * @return bool  true on success, false on failure
 */
function EmailReferral( $referral_id, $emails )
{
	require_once 'ProgramFunctions/SendEmail.fnc.php';

	// Verify emails array and build TO.
	$to_emails = [];

	foreach ( (array) $emails as $email )
	{
		if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) )
		{
			$to_emails[] = $email;
		}
	}

	$referral = EmailReferralGetReferralSafe( $referral_id );

	if ( empty( $to_emails ) || empty( $referral ) )
	{
		return false;
	}

	$referral_fields = EmailReferralFormatFields( $referral );

	// Email To.
	$to = implode( ', ', $to_emails );

	// Email From, if User has email set.
	$from = null;

	if ( filter_var( User( 'EMAIL' ), FILTER_VALIDATE_EMAIL ) )
	{
		$from = User( 'EMAIL' );
	}

	$student_full_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
		FROM students
		WHERE STUDENT_ID='" . (int) $referral['STUDENT_ID'] . "'" );

	$student = _( 'Student' ) . ': ' . $student_full_name . ' (' . $referral['STUDENT_ID'] . ')';

	$date = _( 'Date' ) . ': ' . strip_tags( ProperDate( $referral['ENTRY_DATE'] ) );

	$reporter = _( 'Reporter' ) . ': ' . GetTeacher( $referral['STAFF_ID'] );

	// Email Subject.
	$subject = _( 'New discipline incident' ) . ' - ' . $student_full_name . ' - ' . strip_tags( ProperDate( $referral['ENTRY_DATE'] ) );

	// Email Message.
	$message = $student . "\n" . $reporter . "\n" . $date . "\n\n" . implode( "\n", $referral_fields );

	//var_dump($to, $subject,$message, $from);

	return SendEmail( $to, $subject, $message, $from );
}

/**
 * Get Referral. Safe for Email sending.
 * Returns early if user is not Teacher or Admin.
 *
 * @since 5.3
 *
 * @param int $referral_id Referral ID.
 *
 * @return array Referral, with all columns.
 */
function EmailReferralGetReferralSafe( $referral_id )
{
	if ( User( 'PROFILE' ) !== 'teacher'
		&& User( 'PROFILE' ) !== 'admin' )
	{
		return [];
	}

	if ( User( 'PROFILE' ) === 'teacher' )
	{
		$where = " AND STUDENT_ID IN (SELECT STUDENT_ID FROM schedule
		WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
		AND '" . DBDate() . "'>=START_DATE
		AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL)
		AND MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . "))";
	}
	elseif ( User( 'PROFILE' ) === 'admin' )
	{
		$where = " AND SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "'";
	}

	$referral_RET = DBGet( "SELECT *
		FROM discipline_referrals
		WHERE ID='" . (int) $referral_id . "'" . $where );

	return empty( $referral_RET[1] ) ? [] : $referral_RET[1];
}


/**
 * Get Referral Fields Formatted for Email
 *
 * @since 5.3
 *
 * @param array $referral Referral, with all columns.
 *
 * @return array Referral fields, Formatted for Email.
 */
function EmailReferralFormatFields( $referral )
{
	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	$categories_RET = DBGet( "SELECT f.ID,u.TITLE,u.SELECT_OPTIONS,f.DATA_TYPE,u.SORT_ORDER
		FROM discipline_fields f,discipline_field_usage u
		WHERE u.DISCIPLINE_FIELD_ID=f.ID
		AND u.SCHOOL_ID='" . UserSchool() . "'
		AND u.SYEAR='" . UserSyear() . "'
		ORDER BY " . db_case( [ 'DATA_TYPE', "'textarea'", "'1'", "'0'" ] ) . ",SORT_ORDER", [], [ 'ID' ] );

	$referral_fields = [];

	foreach ( (array) $referral as $column => $Y )
	{
		$category_id = mb_substr( $column, 9 );

		if ( ! isset( $categories_RET[ $category_id ] ) )
		{
			continue;
		}

		$data_type = $categories_RET[ $category_id ][1]['DATA_TYPE'];

		$title_txt = $categories_RET[ $category_id ][1]['TITLE'] . ': ';

		if ( $data_type === 'textarea' )
		{
			$referral_fields[] = $title_txt . "\n" . MarkDownToHTML( $referral[ $column ] );

			continue;
		}

		if ( $data_type === 'checkbox' )
		{
			$referral_fields[] = $title_txt . ( $referral[ $column ] == 'Y' ? _( 'Yes' ) : _( 'No' ) );

			continue;
		}

		if ( $data_type === 'multiple_checkbox' )
		{
			$referral_fields[] = $title_txt . str_replace( '||', ', ', mb_substr( (string) $referral[ $column ], 2, -2 ) );

			continue;
		}

		$referral_fields[] = $title_txt . $referral[ $column ];
	}

	return $referral_fields;
}
