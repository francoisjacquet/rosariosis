<?php

/**
 * Email a Referral and its content
 *
 * Email is from User if has his email set
 *
 * @param  int   $referral_id Referral ID.
 * @param  array $emails      array of emails.
 *
 * @return bool  true on success, false on failure
 */
function EmailReferral( $referral_id, $emails )
{
	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	// get Referral
	//FJ prevent referral ID hacking
	if ( User( 'PROFILE' ) === 'teacher' )
	{
		$where = " AND STUDENT_ID IN (SELECT STUDENT_ID FROM SCHEDULE
		WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
		AND '" . DBDate() . "'>=START_DATE
		AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL))";
	}
	elseif ( User( 'PROFILE' ) === 'admin' )
	{
		$where = " AND SYEAR='" . UserSyear() . "' AND SCHOOL_ID='" . UserSchool() . "'";
	}
	else
		return false;

	$referral_RET = DBGet( "SELECT *
		FROM DISCIPLINE_REFERRALS
		WHERE ID='" . $referral_id . "'" . $where );

	$categories_RET = DBGet( "SELECT f.ID,u.TITLE,u.SELECT_OPTIONS,f.DATA_TYPE,u.SORT_ORDER
		FROM DISCIPLINE_FIELDS f,DISCIPLINE_FIELD_USAGE u
		WHERE u.DISCIPLINE_FIELD_ID=f.ID
		AND u.SCHOOL_ID='" . UserSchool() . "'
		AND u.SYEAR='" . UserSyear() . "'
		ORDER BY " . db_case( array( 'DATA_TYPE', "'textarea'", "'1'", "'0'" ) ) . ",SORT_ORDER", array(), array( 'ID' ) );

	if ( ! empty( $referral_RET ) )
	{
		$referral = $referral_RET[1];

		$student_full_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
			FROM STUDENTS
			WHERE STUDENT_ID='" . $referral['STUDENT_ID'] . "'" );


		$student = _( 'Student' ) . ': ' . $student_full_name . ' (' . $referral['STUDENT_ID'] . ')';

		$date = _( 'Date' ) . ': ' . strip_tags( ProperDate( $referral['ENTRY_DATE'] ) );

		$reporter = _( 'Reporter' ) . ': ' . GetTeacher( $referral['STAFF_ID'] );

		$referral_fields = array();

		foreach ( (array) $referral as $column => $Y )
		{
			$category_id = mb_substr( $column, 9 );

			if ( ! isset( $categories_RET[ $category_id ] ) )
			{
				continue;
			}

			$data_type = $categories_RET[ $category_id ][1]['DATA_TYPE'];

			$title_txt = $categories_RET[ $category_id ][1]['TITLE'] . ': ';

			if ( $data_type !== 'textarea' )
			{

				if ( $data_type === 'checkbox' )
				{
					$referral_fields[] = $title_txt . ( $referral[ $column ] == 'Y' ? _( 'Yes' ) : _( 'No' ) );
				}
				elseif ( $data_type === 'multiple_checkbox' )
				{
					$referral_fields[] = $title_txt . str_replace( '||', ', ', mb_substr( $referral[ $column ], 2, -2 ) );
				}
				else
					$referral_fields[] = $title_txt . $referral[ $column ];
			}
			else
				$referral_fields[] = $title_txt . "\n" . MarkDownToHTML( $referral[ $column ] );
		}
	}
	else
		return false;

	// verify emails array and build TO
	$to_emails = array();

	foreach ( (array) $emails as $email )
	{
		if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) )
			$to_emails[] = $email;
	}

	if ( empty( $to_emails ) )
		return false;

	// email To
	$to = implode( ', ', $to_emails );

	// email From, if User has email set
	$from = null;

	if ( filter_var( User( 'EMAIL' ), FILTER_VALIDATE_EMAIL ) )
		$from = User( 'EMAIL' );

	// email Subject
	$subject = _( 'New discipline incident' ) . ' - ' .
		$student . ' - ' .
		$date;

	// email Message
	$message = $student . "\n";
	$message .= $reporter . "\n";
	$message .= $date . "\n\n";
	$message .= implode( "\n", $referral_fields );

	//var_dump($to, $subject,$message, $from);

	require_once 'ProgramFunctions/SendEmail.fnc.php';

	return SendEmail( $to, $subject,$message, $from );
}
