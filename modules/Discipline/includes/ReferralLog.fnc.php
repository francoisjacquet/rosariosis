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
	$fields_RET = DBGet( "SELECT f.ID,u.TITLE,u.SELECT_OPTIONS,f.DATA_TYPE,u.SORT_ORDER
			FROM discipline_fields f,discipline_field_usage u
			WHERE u.DISCIPLINE_FIELD_ID=f.ID
			ORDER BY " . db_case( [ 'DATA_TYPE', "'textarea'", "'1'", "'0'" ] ) . ",SORT_ORDER",
		[],
		[ 'ID' ]
	);

	// Open fieldset
	$return = '<TR><TD colspan="2"><fieldset><legend>' . _( 'Include in Discipline Log' ) . '</legend>
		<TABLE class="width-100p cellspacing-0"><TR><TD>';

	$fields = [];

	$new = true;

	$value = 'Y';

	// Entry date field
	$fields[] = CheckboxInput( $value, 'elements[ENTRY_DATE]', _( 'Entry Date' ), '', $new );

	// Reporter field
	$fields[] = CheckboxInput( $value, 'elements[STAFF_ID]', _( 'Reporter' ), '', $new );

	// Custom Discipline fields
	foreach ( (array) $fields_RET as $id => $field )
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
 * @uses ReferralLogsGetExtra()
 * @uses ReferralLogsGetReferralHTML()
 *
 * @param  array  $extra GetStuList() extra
 *
 * @return array  Empty if no Students or no Referrals, else Referral Logs HTML array (key = student ID)
 */
function ReferralLogsGenerate( $extra )
{
	global $_ROSARIO;

	$referral_logs = [];

	$extra = ReferralLogsGetExtra( $extra );

	// Get Referrals
	$referrals_RET = GetStuList( $extra );

	if ( empty( $referrals_RET ) )
	{
		return [];
	}

	// Get eventual Incident Date timeframe
	$start_date = RequestedDate( 'discipline_entry_begin', '' );

	$end_date = RequestedDate( 'discipline_entry_end', '' );

	foreach ( (array) $referrals_RET as $student_id => $referrals )
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
		if ( $start_date
			|| $end_date )
		{
			if ( ! $end_date )
			{
				$end_date = DBDate();
			}
			elseif ( ! $start_date )
			{
				$start_date = GetMP( GetCurrentMP( 'FY', DBDate() ), 'START_DATE' );
			}

			DrawHeader( ProperDate( $start_date ) . ' - ' . ProperDate( $end_date ) );
		}
		else
		{
			DrawHeader( _( 'School Year' ) . ': ' .
				FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) );
		}

		echo '<BR />';

		foreach ( (array) $referrals as $referral )
		{
			echo ReferralLogsGetReferralHTML( $referral );
		}

		$referral_logs[ $student_id ] = ob_get_clean();
	}

	return $referral_logs;
}

/**
 * Get Referral HTML for Referral Logs
 *
 * @since 5.3
 *
 * @param array $referral Referral.
 *
 * @return string Referral HTML.
 */
function ReferralLogsGetReferralHTML( $referral )
{
	static $fields_RET = null;

	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	// Get custom Discipline fields
	if ( is_null( $fields_RET ) )
	{
		$fields_RET = DBGet( "SELECT f.ID,u.TITLE,u.SELECT_OPTIONS,f.DATA_TYPE,u.SORT_ORDER
			FROM discipline_fields f,discipline_field_usage u
			WHERE u.DISCIPLINE_FIELD_ID=f.ID
			ORDER BY " . db_case( [ 'DATA_TYPE', "'textarea'", "'1'", "'0'" ] ) . ",SORT_ORDER",
			[],
			[ 'ID' ]
		);
	}

	ob_start();

	// Entry Date
	if ( ! empty( $_REQUEST['elements']['ENTRY_DATE'] ) )
	{
		DrawHeader( '<b>' . _( 'Date' ) . ': </b>' . ProperDate( $referral['ENTRY_DATE'] ) );
	}

	// Reporter
	if ( ! empty( $_REQUEST['elements']['STAFF_ID'] ) )
	{
		DrawHeader( '<b>' . _( 'Reporter' ) .': </b>' . GetTeacher( $referral['STAFF_ID'] ) );
	}

	// Custom Discipline fields
	foreach ( (array) $_REQUEST['elements'] as $column => $yes )
	{
		// Zap not checked, Entry Date & Reporter
		if ( ! $yes
			|| $column === 'ENTRY_DATE'
			|| $column === 'STAFF_ID' )
		{
			continue;
		}

		$field_type = $fields_RET[mb_substr( $column, 9 )][1]['DATA_TYPE'];

		$value = $referral[ $column ];

		if ( $field_type === 'textarea' )
		{
			// TEXTEAREA fields.
			DrawHeader( MarkDownToHTML( $value ) );

			continue;
		}

		$title_txt = '<b>' . $fields_RET[mb_substr( $column, 9 )][1]['TITLE'] . ': </b> ';

		if ( $field_type === 'checkbox' )
		{
			// CHECKBOX fields.
			$value = button( ( $value === 'Y' ? 'check' : 'x' ) );
		}
		elseif ( $field_type === 'multiple_checkbox' )
		{
			// Multiple checkbox fields
			$value = str_replace( '||', ', ', mb_substr( (string) $value, 2, -2 ) );
		}
		elseif ( $field_type === 'numeric' )
		{
			$value = mb_strpos( $value, '.' ) === false ? $value : rtrim( rtrim( $value, '0' ), '.' );
		}

		DrawHeader( $title_txt . $value );
	}

	echo '<BR />';

	return ob_get_clean();
}

/**
 * Get $extra for Referral Logs SQL query
 *
 * @since 5.3
 *
 * @param array $extra Extra.
 *
 * @return array $extra Extra (SELECT, FROM, WHERE, group, ORDER) for Referral Logs SQL query.
 */
function ReferralLogsGetExtra( $extra )
{
	$extra['SELECT'] = issetVal( $extra['SELECT'], '' );
	$extra['FROM'] = issetVal( $extra['FROM'], '' );
	$extra['WHERE'] = issetVal( $extra['WHERE'], '' );

	foreach ( (array) $_REQUEST['elements'] as $column => $yes )
	{
		if ( $yes )
		{
			$extra['SELECT'] .= ',dr.' . $column;
		}
	}

	if ( mb_strpos( $extra['FROM'], 'discipline_referrals' ) === false  )
	{
		$extra['WHERE'] .= ' AND dr.STUDENT_ID=ssm.STUDENT_ID
			AND dr.SYEAR=ssm.SYEAR
			AND dr.SCHOOL_ID=ssm.SCHOOL_ID ';

		$extra['FROM'] .= ',discipline_referrals dr ';
	}

	$extra['group'] = [ 'STUDENT_ID' ];

	$extra['ORDER'] = ',dr.ENTRY_DATE';

	$extra['WHERE'] .= appendSQL( '', $extra );

	return $extra;
}
