<?php
/**
 * School Periods select input function.
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * School Periods select input for current Course Period (Teacher).
 * Sets $_REQUEST[ $name ]
 *
 * @uses UserCoursePeriod()
 *
 * @example SchoolPeriodsSelectInput( issetVal( $_REQUEST['school_period'] ), 'school_period', '', 'autocomplete="off" onchange="ajaxLink(this.form.action + \'&school_period=\' + this.value);"' );
 *
 * @since 7.0 Fix Numbered days display
 *
 * @param string $value Input value.
 * @param string $name  Input name.
 * @param string $title Input title or label (optional).
 * @param string $extra Extra HTML attributes (optional).
 *
 * @return School Periods select input HTML.
 */
function SchoolPeriodsSelectInput( $value, $name, $title, $extra = '' )
{
	$school_periods_RET = DBGet( "SELECT cpsp.PERIOD_ID,cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID,
		sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cpsp.DAYS,cp.SHORT_NAME AS CP_SHORT_NAME
		FROM course_periods cp,school_periods sp,course_period_school_periods cpsp
		WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
		AND cpsp.PERIOD_ID=sp.PERIOD_ID
		AND cp.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
		ORDER BY sp.SORT_ORDER IS NULL,sp.SORT_ORDER" );

	$period_selected = false;

	$input = '<label><select name="' . AttrEscape( $name ) . '" id="' . GetInputID( $name ) . '" ' . $extra . '>';

	foreach ( (array) $school_periods_RET as $school_period )
	{
		$selected = '';

		if ( $value === $school_period['PERIOD_ID'] )
		{
			$period_selected = true;

			$selected = ' selected';
		}

		// FJ days display to locale.
		$days_convert = [
			'U' => _( 'Sunday' ),
			'M' => _( 'Monday' ),
			'T' => _( 'Tuesday' ),
			'W' => _( 'Wednesday' ),
			'H' => _( 'Thursday' ),
			'F' => _( 'Friday' ),
			'S' => _( 'Saturday' ),
		];

		// FJ days numbered.
		if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
		{
			$days_convert = [
				'U' => '7',
				'M' => '1',
				'T' => '2',
				'W' => '3',
				'H' => '4',
				'F' => '5',
				'S' => '6',
			];
		}

		$period_days = '';

		$days_strlen = mb_strlen( $school_period['DAYS'] );

		for ( $i = 0; $i < $days_strlen; $i++ )
		{
			$period_days .= mb_substr( $days_convert[ $school_period['DAYS'][ $i ] ], 0, 3 ) . '.';
		}

		$period_days_text = '';

		$nb_days = mb_strlen( $school_period['DAYS'] );

		if ( ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null
				&& $nb_days < SchoolInfo( 'NUMBER_DAYS_ROTATION' ) )
			|| ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) === null
				&& $nb_days < 5 ) )
		{
			$period_days_text = ' - ' .
				( $nb_days < 2 ? _( 'Day' ) : _( 'Days' ) ) .
				' ' . $period_days;
		}

		$input .= '<option value="' . AttrEscape( $school_period['PERIOD_ID'] ) . '"' . $selected . '>' .
			$school_period['TITLE'] . $period_days_text . '</option>';
	}

	if ( ! $school_periods_RET )
	{
		// Error if no school periods.
		$input .= '<option value="">' . _( 'No periods found' ) . '</option>';
	}
	elseif ( ! $period_selected )
	{
		RedirectURL( $name );

		// Set school period to first one in the list.
		$_REQUEST[ $name ] = $school_periods_RET[1]['PERIOD_ID'];
	}

	return $input . '</select>' . ( $title ? FormatInputTitle( $title ) : '' ) . '</label>';
}
