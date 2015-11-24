<?php

/**
 * Referral Log functions
 */

/**
 * Get Include in Discipline Log form
 *
 * To be uses in Search form
 *
 * @example $extra['second_col'] .= ReferralLogIncludeForm();
 *
 * @return string Include in Discipline Log form
 */
function ReferralLogIncludeForm()
{
	static $return = null;

	if ( !is_null( $return ) )
		return $return;

	// Get custom Discipline fields
	$fields_RET = DBGet(
		DBQuery( "SELECT f.ID,u.TITLE,u.SELECT_OPTIONS,f.DATA_TYPE,u.SORT_ORDER 
			FROM DISCIPLINE_FIELDS f,DISCIPLINE_FIELD_USAGE u 
			WHERE u.DISCIPLINE_FIELD_ID=f.ID
			ORDER BY " . db_case( array( 'DATA_TYPE', "'textarea'", "'1'", "'0'" ) ) . ",SORT_ORDER" ),
		array(),
		array( 'ID' )
	);

	// Open fieldset
	$return = '<TR><TD><fieldset><legend>' . _( 'Include in Discipline Log' ) . '</legend>
		<TABLE class="width-100p cellspacing-0"><TR><TD>';

	$fields = array();

	$new = true;

	$value = 'Y';

	// Entry date field
	$fields[] = CheckboxInput( $value, 'elements[ENTRY_DATE]', _( 'Entry Date' ), '', $new );

	// Reporter field
	$fields[] = CheckboxInput( $value, 'elements[STAFF_ID]', _( 'Reporter' ), '', $new );

	// Custom Discipline fields
	foreach ( (array)$fields_RET as $id => $field )
	{
		// TEXTAREA fields are checked
		$value = ( $field[1]['DATA_TYPE'] === 'textarea' ? 'Y' : 'N' );

		$fields[] = CheckboxInput( $value, 'elements[CATEGORY_' . $id . ']', $field[1]['TITLE'], '', $new );
	}

	$return .= implode( '</TD></TR><TR><TD>', $fields );

	// Close fieldset
	$return .= '</TD></TR></TABLE></fieldset></TD></TR>';

	return $return;
}


/**
 * Referral Logs Generation
 * HTML Ready for PDF
 *
 * Uses Include in Discipline Log form, see ReferralLogIncludeForm()
 *
 * @example $referral_logs = ReferralLogsGenerate( $extra );
 *
 * @param  array  $extra GetStuList() extra
 *
 * @return array  Empty if no Students or no Referrals, else Referral Logs HTML array (key = student ID)
 */
function ReferralLogsGenerate( $extra )
{
	global $_ROSARIO;

	static $fields_RET = null;

	$referral_logs = array();

	// Get custom Discipline fields
	if ( is_null( $fields_RET ) )
	{
		$fields_RET = DBGet(
			DBQuery( "SELECT f.ID,u.TITLE,u.SELECT_OPTIONS,f.DATA_TYPE,u.SORT_ORDER 
				FROM DISCIPLINE_FIELDS f,DISCIPLINE_FIELD_USAGE u 
				WHERE u.DISCIPLINE_FIELD_ID=f.ID
				ORDER BY " . db_case( array( 'DATA_TYPE', "'textarea'", "'1'", "'0'" ) ) . ",SORT_ORDER" ),
			array(),
			array( 'ID' )
		);
	}

	// Get eventual Incident Date timeframe
	if ( $_REQUEST['month_discipline_entry_begin']
		&& $_REQUEST['day_discipline_entry_begin']
		&& $_REQUEST['year_discipline_entry_begin'] )
	{
		$start_date = $_REQUEST['day_discipline_entry_begin'] . '-' .
			$_REQUEST['month_discipline_entry_begin'] . '-' .
			$_REQUEST['year_discipline_entry_begin'];

		if ( !VerifyDate( $start_date ) )
			unset( $start_date );

		$end_date = $_REQUEST['day_discipline_entry_end'] . '-' .
			$_REQUEST['month_discipline_entry_end'] . '-' .
			$_REQUEST['year_discipline_entry_end'];

		if ( !VerifyDate( $end_date ) )
			unset( $end_date );
	}

	foreach ( (array)$_REQUEST['elements'] as $column => $Y )
	{
		$extra['SELECT'] .= ',r.' . $column;
	}

	$extra['FROM'] .= ',DISCIPLINE_REFERRALS r ';

	$extra['WHERE'] .= " AND r.STUDENT_ID=ssm.STUDENT_ID AND r.SYEAR=ssm.SYEAR ";

	if ( mb_strpos( $extra['FROM'], 'DISCIPLINE_REFERRALS dr' ) !== false )
		$extra['WHERE'] .= ' AND r.ID=dr.ID';

	$extra['group'] = array( 'STUDENT_ID' );

	$extra['ORDER'] = ',r.ENTRY_DATE';

	$extra['WHERE'] .= appendSQL( '', $extra );

	// Get Referrals
	$referrals_RET = GetStuList( $extra );

	if ( empty( $referrals_RET ) )
	{
		return array();
	}

	foreach ( (array)$referrals_RET as $student_id => $referrals )
	{
		// Begin output buffer
		ob_start();

		unset( $_ROSARIO['DrawHeader'] );

		DrawHeader( _( 'Discipline Log' ) );

		// Student Info
		DrawHeader( $referrals[1]['FULL_NAME'], $referrals[1]['STUDENT_ID'] );

		// School Info
		DrawHeader( SchoolInfo( 'TITLE' ) );

		// Incident Date timeframe
		if ( isset( $start_date )
			&& isset( $end_date ) )
		{
			DrawHeader( ProperDate( $start_date) . ' - ' . ProperDate( $end_date ) );
		}
		else
		{
			//FJ school year over one/two calendar years format
			DrawHeader( _( 'School Year' ) . ': ' .
				FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) );
		}

		echo '<BR />';

		foreach ( (array)$referrals as $referral )
		{
			// Entry Date
			if ( isset( $_REQUEST['elements']['ENTRY_DATE'] ) )
			{
				DrawHeader( '<b>' . _( 'Date' ) . ': </b>' . ProperDate( $referral['ENTRY_DATE'] ) );
			}

			// Reporter
			if ( isset( $_REQUEST['elements']['STAFF_ID'] ) )
			{
				DrawHeader( '<b>' . _( 'Reporter' ) .': </b>' . GetTeacher( $referral['STAFF_ID'] ) );
			}

			// Custom Discipline fields
			foreach ( (array)$_REQUEST['elements'] as $column => $Y )
			{
				// Zap Entry Date & Reporter
				if ( $column === 'ENTRY_DATE'
					|| $column === 'STAFF_ID' )
				{
					continue;
				}

				$field_type = $fields_RET[mb_substr( $column, 9 )][1]['DATA_TYPE'];

				if ( $field_type !== 'textarea' )
				{
					$title_txt = '<b>' . $fields_RET[mb_substr( $column, 9 )][1]['TITLE'] . ': </b> ';

					// CHECKBOX fields
					if ( $field_type === 'checkbox' )
					{
						DrawHeader( $title_txt .
							( $referral[$column] === 'Y' ?
								button( 'check', '', '', 'bigger' ) :
								button( 'x', '', '', 'bigger' ) ) );
					}
					// Multiple checkbox fields
					elseif ( $field_type === 'multiple_checkbox' )
					{
						DrawHeader( $title_txt .
							str_replace( '||', ', ', mb_substr( $referral[$column], 2, -2 ) ) );
					}
					// Others
					else
						DrawHeader( $title_txt . $referral[$column] );
				}
				// TEXTEAREA fields
				else
					DrawHeader( nl2br( $referral[$column] ) );
			}

			echo '<BR />';
		}

		// Get output buffer
		$referral_logs[$student_id] = ob_get_clean();
	}

	return $referral_logs;
}