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
	$fields[] = CheckboxInput( $value, 'log[ENTRY_DATE]', _( 'Entry Date' ), '', $new );

	// Reporter field
	$fields[] = CheckboxInput( $value, 'log[STAFF_ID]', _( 'Reporter' ), '', $new );

	// Custom Discipline fields
	foreach ( (array)$fields_RET as $id => $field )
	{
		// TEXTAREA fields are checked
		$value = ( $field[1]['DATA_TYPE'] === 'textarea' ? 'Y' : 'N' );

		$fields[] = CheckboxInput( $value, 'log[CATEGORY_' . $id . ']', $field[1]['TITLE'], '', $new );
	}

	$return .= implode( '</TD></TR><TR><TD>', $fields );

	// Close fieldset
	$return .= '</TD></TR></TABLE></fieldset></TD></TR>';

	return $return;
}


/**
 * Referral Log Generation
 * HTML Ready for PDF
 *
 * Uses Include in Discipline Log form, see ReferralLogIncludeForm()
 *
 * @example echo ReferralLogGenerate( $student_id );
 *
 * @global $_ROSARIO Unsets $_ROSARIO['DrawHeader']
 *
 * @param  string $student_id Student ID
 *
 * @return string Empty if no Student ID or no Referrals, else Referral Log HTML
 */
function ReferralLogGenerate( $student_id )
{
	global $_ROSARIO;

	static $fields_RET = null;

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

	if ( empty( $student_id ) )
	{
		return '';
	}

	$start_date = $end_date = '';

	// Get eventual Incident Date timeframe
	if ( isset( $_REQUEST['month_discipline_entry_begin'] )
		&& isset( $_REQUEST['day_discipline_entry_begin'] )
		&& isset( $_REQUEST['year_discipline_entry_begin'] ) )
	{
		$start_date = RequestedDate(
			$_REQUEST['day_discipline_entry_begin'],
			$_REQUEST['month_discipline_entry_begin'],
			$_REQUEST['year_discipline_entry_begin']
		);

		if ( isset( $_REQUEST['month_discipline_entry_end'] )
			&& isset( $_REQUEST['day_discipline_entry_end'] )
			&& isset( $_REQUEST['year_discipline_entry_end'] ) )
		{
			$end_date = RequestedDate(
				$_REQUEST['day_discipline_entry_end'],
				$_REQUEST['month_discipline_entry_end'],
				$_REQUEST['year_discipline_entry_end']
			);
		}
	}

	foreach ( (array)$_REQUEST['log'] as $column => $Y )
	{
		$extra['SELECT'] .= ',r.' . $column;
	}

	$extra['FROM'] .= ',DISCIPLINE_REFERRALS r ';

	// Limit to Current Student ID
	$extra['WHERE'] .= " AND ssm.STUDENT_ID='" . $student_id . "'";

	$extra['WHERE'] .= " AND r.STUDENT_ID=ssm.STUDENT_ID AND r.SYEAR=ssm.SYEAR ";

	if ( mb_strpos( $extra['FROM'], 'DISCIPLINE_REFERRALS dr' ) !== false )
		$extra['WHERE'] .= ' AND r.ID=dr.ID';

	$extra['group'] = array( 'STUDENT_ID' );

	$extra['ORDER'] = ',r.ENTRY_DATE';

	$extra['WHERE'] .= appendSQL( '', $extra );

	// Get Referrals
	$referrals_RET = GetStuList( $extra );

	$referrals = $referrals_RET[$student_id];

	if ( empty( $referrals ) )
	{
		return '';
	}

	// Start output buffer
	ob_start();

	unset( $_ROSARIO['DrawHeader'] );

	DrawHeader( _( 'Discipline Log' ) );

	// Student Info
	DrawHeader( $referrals[1]['FULL_NAME'], $referrals[1]['STUDENT_ID'] );

	// School Info
	DrawHeader( SchoolInfo( 'TITLE' ) );

	// Incident Date timeframe
	if ( $start_date
		&& $end_date )
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
		if ( isset( $_REQUEST['log']['ENTRY_DATE'] ) )
		{
			DrawHeader( '<b>' . _( 'Date' ) . ': </b>' . ProperDate( $referral['ENTRY_DATE'] ) );
		}

		// Reporter
		if ( isset( $_REQUEST['log']['STAFF_ID'] ) )
		{
			DrawHeader( '<b>' . _( 'Reporter' ) .': </b>' . GetTeacher( $referral['STAFF_ID'] ) );
		}

		// Custom Discipline fields
		foreach ( (array)$_REQUEST['log'] as $column => $Y )
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

	// Return output buffer
	return ob_get_clean();
}