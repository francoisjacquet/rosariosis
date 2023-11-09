<?php

require_once 'modules/Grades/includes/ClassRank.inc.php';

DrawHeader( ProgramTitle() );

Search( 'student_id' );

if ( UserStudentID() )
{
	$student_id = UserStudentID();

	$mp_id = issetVal( $_REQUEST['mp_id'] );

	$tab_id = ! empty( $_REQUEST['tab_id'] ) ? $_REQUEST['tab_id'] : 'grades';

	// FJ fix bug no delete MP.

	if ( $_REQUEST['modfunc'] === 'update'
		&& ! empty( $_REQUEST['removemp'] )
		&& $_REQUEST['new_sms']
		&& DeletePrompt( _( 'Marking Period' ) ) )
	{
		//DBQuery("DELETE FROM student_mp_stats WHERE student_id = $student_id and marking_period_id = $mp_id");
		DBQuery( "DELETE FROM student_mp_stats
			WHERE STUDENT_ID='" . (int) $student_id . "'
			AND MARKING_PERIOD_ID='" . (int) $_REQUEST['new_sms'] . "'" );

		unset( $mp_id );

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}

	if ( $_REQUEST['modfunc'] === 'update'
		&& empty( $_REQUEST['removemp'] ) )
	{
		if ( ! empty( $_REQUEST['new_sms'] ) )
		{
			// FJ fix SQL bug when marking period already exist.
			$sms_RET = DBGet( "SELECT 1
				FROM student_mp_stats
				WHERE STUDENT_ID='" . (int) $student_id . "'
				AND MARKING_PERIOD_ID='" . (int) $_REQUEST['new_sms'] . "'" );

			if ( empty( $sms_RET ) )
			{
				DBInsert(
					'student_mp_stats',
					[
						'STUDENT_ID' => (int) $student_id,
						'MARKING_PERIOD_ID' => (int) $_REQUEST['new_sms'],
					]
				);
			}

			$mp_id = $_REQUEST['new_sms'];
		}

		if ( ! empty( $_REQUEST['SMS_GRADE_LEVEL'] )
			&& $mp_id )
		{
			DBUpdate(
				'student_mp_stats',
				[ 'GRADE_LEVEL_SHORT' => $_REQUEST['SMS_GRADE_LEVEL'] ],
				[
					'STUDENT_ID' => (int) $student_id,
					'MARKING_PERIOD_ID' => (int) $mp_id,
				]
			);
		}

		$_REQUEST['values'] = issetVal( $_REQUEST['values'] );

		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			// FJ fix SQL bug when text data entered, data verification.

			if (  ( empty( $columns['GRADE_PERCENT'] ) || is_numeric( $columns['GRADE_PERCENT'] ) ) && ( empty( $columns['GP_SCALE'] ) || is_numeric( $columns['GP_SCALE'] ) ) && ( empty( $columns['UNWEIGHTED_GP'] ) || is_numeric( $columns['UNWEIGHTED_GP'] ) ) && ( empty( $columns['WEIGHTED_GP'] ) || is_numeric( $columns['WEIGHTED_GP'] ) ) && ( empty( $columns['CREDIT_EARNED'] ) || is_numeric( $columns['CREDIT_EARNED'] ) ) && ( empty( $columns['CREDIT_ATTEMPTED'] ) || is_numeric( $columns['CREDIT_ATTEMPTED'] ) ) )
			{
				if ( $id !== 'new' )
				{
					$go = DBUpdate(
						'student_report_card_grades',
						$columns,
						[ 'ID' => (int) $id ]
					);
				}

				// New: check for Title.
				elseif ( $columns['COURSE_TITLE'] )
				{
					// Fix bug SQL SYEAR=NULL.
					$syear = DBGetOne( "SELECT SYEAR
						FROM marking_periods
						WHERE MARKING_PERIOD_ID='" . (int) $mp_id . "'" );

					$insert_columns = [
						'SCHOOL_ID' => UserSchool(),
						'STUDENT_ID' => (int) $student_id,
						'MARKING_PERIOD_ID' => (int) $mp_id,
						'SYEAR' => $syear,
					];

					if ( empty( $columns['GP_SCALE'] ) )
					{
						$columns['GP_SCALE'] = SchoolInfo( 'REPORTING_GP_SCALE' );
					}

					if ( empty( $columns['CREDIT_ATTEMPTED'] ) )
					{
						$columns['CREDIT_ATTEMPTED'] = 1;
					}

					if ( empty( $columns['CREDIT_EARNED'] ) )
					{
						if ( $columns['UNWEIGHTED_GP'] > 0
							|| $columns['WEIGHTED_GP'] > 0 )
						{
							$columns['CREDIT_EARNED'] = 1;
						}
						else
						{
							$columns['CREDIT_EARNED'] = 0;
						}
					}

					if ( ! isset( $columns['CLASS_RANK'] ) )
					{
						$columns['CLASS_RANK'] = 'Y';
					}

					$go = DBInsert(
						'student_report_card_grades',
						$insert_columns + $columns
					);
				}

				if ( $go )
				{
					// @since 4.7 Automatic Class Rank calculation.
					ClassRankCalculateAddMP( $mp_id );
				}
			}
			else
			{
				$error[] = _( 'Please enter valid Numeric data.' );
			}
		}

		// Unset modfunc & redirect URL.
		RedirectURL( 'modfunc' );
	}

	if ( $_REQUEST['modfunc'] === 'remove' )
	{
		if ( DeletePrompt( _( 'Student Grade' ) ) )
		{
			DBQuery( "DELETE FROM student_report_card_grades
				WHERE ID='" . (int) $_REQUEST['id'] . "'" );

			if ( $mp_id )
			{
				// @since 4.7 Automatic Class Rank calculation.
				ClassRankCalculateAddMP( $mp_id );
			}

			// Unset modfunc & ID & redirect URL.
			RedirectURL( [ 'modfunc', 'id' ] );
		}
	}

	// FJ fix SQL bug when text data entered, data verification
	echo ErrorMessage( $error );

	if ( ! $_REQUEST['modfunc'] )
	{
		$student_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
			FROM students
			WHERE STUDENT_ID='" . (int) $student_id . "'" );

		$student = $student_RET[1];

		$displayname = $student['FULL_NAME'];

		$g_sql = "SELECT mp.syear, mp.marking_period_id as mp_id, mp.title as mp_name, mp.post_end_date as posted, sms.grade_level_short as grade_level,
		CASE WHEN sms.gp_credits > 0 THEN (sms.sum_weighted_factors/sms.gp_credits)*s.reporting_gp_scale ELSE 0 END as weighted_gpa,
		sms.cum_weighted_factor*s.reporting_gp_scale as weighted_cum,
		CASE WHEN sms.gp_credits > 0 THEN (sms.sum_unweighted_factors/sms.gp_credits)*s.reporting_gp_scale ELSE 0 END as unweighted_gpa,
		sms.cum_unweighted_factor*s.reporting_gp_scale as unweighted_cum,
		CASE WHEN sms.cr_credits > 0 THEN (sms.cr_weighted_factors/cr_credits)*s.reporting_gp_scale ELSE 0 END as cr_weighted,
		CASE WHEN sms.cr_credits > 0 THEN (sms.cr_unweighted_factors/cr_credits)*s.reporting_gp_scale ELSE 0 END as cr_unweighted
		FROM marking_periods mp, student_mp_stats sms, schools s
		WHERE sms.marking_period_id=mp.marking_period_id
		AND	s.id=mp.school_id
		AND sms.student_id='" . $student_id . "'
		AND mp.school_id='" . UserSchool() . "'
		ORDER BY posted";

		$g_RET = DBGet( $g_sql );

		$last_posted = null;
		$g_mp = []; // Grade marking_periods.
		$grecs = []; // Grade records.

		if ( $g_RET )
		{
			foreach ( (array) $g_RET as $g_rec )
			{
				if ( $mp_id == null || $mp_id == $g_rec['MP_ID'] )
				{
					$mp_id = $g_rec['MP_ID'];
				}

				$g_mp[$g_rec['MP_ID']] = [
					'schoolyear' => FormatSyear( $g_rec['SYEAR'], Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ),
					'mp_name' => $g_rec['MP_NAME'],
					'grade_level' => $g_rec['GRADE_LEVEL'],
					'weighted_cum' => $g_rec['WEIGHTED_CUM'],
					'unweighted_cum' => $g_rec['UNWEIGHTED_CUM'],
					'weighted_gpa' => $g_rec['WEIGHTED_GPA'],
					'unweighted_gpa' => $g_rec['UNWEIGHTED_GPA'],
					'cr_weighted' => $g_rec['CR_WEIGHTED'],
					'cr_unweighted' => $g_rec['CR_UNWEIGHTED'],
					'gpa' => issetVal( $g_rec['GPA'] ),
				];
			}
		}
		else
		{
			$mp_id = "0";
		}

		$mp_select = '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&tab_id=' . $tab_id  ) . '" method="POST">';

		$mp_select .= '<select name="mp_id" onchange="ajaxPostForm(this.form,true);">';

		foreach ( $g_mp as $id => $mp_array )
		{
			$mp_select .= '<option value="' . AttrEscape( $id ) . '"' . ( $id == $mp_id ? ' selected' : '' ) . '>' .
			$mp_array['schoolyear'] . ' ' . $mp_array['mp_name'] . ', ' .
			_( 'Grade Level' ) . ' ' . $mp_array['grade_level'] .
				'</option>';
		}

		$mp_select .= '<option value="0" ' . ( $mp_id == '0' ? ' selected' : '' ) . '>' .
		_( 'Add another marking period' ) .
			'</option>';

		$mp_select .= '</select></form>';

		DrawHeader( $mp_select );

		// FORM for updates/new records.
		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=update&tab_id=' . $tab_id . '&mp_id=' . $mp_id  ) . '" method="POST">';

		DrawHeader( '', SubmitButton() );
		echo '<br />';

		if ( $mp_id != '0' )
		{
			echo PopTable( 'header', $displayname );

			echo '<fieldset><legend>' . _( 'Marking Period Statistics' ) . '</legend>';

			echo '<table class="cellpadding-5"><tr><td>' . _( 'GPA' ) . '</td><td>' .
				NoInput(
					(float) number_format( issetVal( $g_mp[$mp_id]['weighted_gpa'], 0 ), 2, '.', '' ),
					_( 'Weighted' )
				) . '</td><td>' .
				NoInput(
					(float) number_format( issetVal( $g_mp[$mp_id]['unweighted_gpa'], 0 ), 2, '.', '' ),
					_( 'Unweighted' )
				) . '</td></tr>';

			echo '<tr><td>' . _( 'Class Rank GPA' ) . '</td><td>' .
				NoInput(
					(float) number_format( issetVal( $g_mp[$mp_id]['cr_weighted'], 0 ), 2, '.', '' ),
					_( 'Weighted' )
				) . '</td><td>' .
				NoInput(
					(float) number_format( issetVal( $g_mp[$mp_id]['cr_unweighted'], 0 ), 2, '.', '' ),
					_( 'Unweighted' )
				) . '</td></tr></table>';

			echo '</fieldset>';

			echo PopTable( 'footer' ) . '<br />';
		}

		$sms_grade_level = TextInput(
			issetVal( $g_mp[$mp_id]['grade_level'] ),
			"SMS_GRADE_LEVEL",
			_( 'Grade Level' ),
			'size=3 maxlength=3'
		);

		if ( $mp_id == '0' )
		{
			$syear = UserSyear();

			$mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,SYEAR,TITLE,POST_END_DATE
				FROM marking_periods
				WHERE SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR BETWEEN '" . sprintf( '%d', $syear - 5 ) . "' AND '" . $syear . "'
				ORDER BY POST_END_DATE DESC" );

			if ( $mp_RET )
			{
				$mp_options = [];

				foreach ( $mp_RET as $id => $mp )
				{
					$mp_options[$mp['MARKING_PERIOD_ID']] = FormatSyear(
						$mp['SYEAR'],
						Config( 'SCHOOL_SYEAR_OVER_2_YEARS' )
					) . ', ' . $mp['TITLE'];
				}

				echo PopTable( 'header', _( 'Add another marking period' ) );

				echo '<table class="cellpadding-5"><tr class="st"><td>';
				echo SelectInput(
					null,
					'new_sms',
					_( 'New Marking Period' ),
					$mp_options,
					false,
					null
				);
				echo '</td><td>';
				echo $sms_grade_level;
				echo '</td></tr></table>';

				echo PopTable( 'footer' );
			}
		}
		else
		{
			echo $sms_grade_level;

			$tab_url = 'Modules.php?modname=' . $_REQUEST['modname'] . '&mp_id=' . $mp_id . '&tab_id=';

			$tabs = [];

			$tabs[] = [
				'title' => 'Grades',
				'link' => $tab_url . 'grades',
			];

			$tabs[] = [
				'title' => 'Credits',
				'link' => $tab_url . 'credit',
			];

			$LO_options = [
				'count' => false,
				'download' => false,
				'search' => false,
				'header' => WrapTabs(
					$tabs,
					$tab_url . $tab_id
				),
			];

			$LO_columns = [
				'COURSE_TITLE' => _( 'Course' ),
			];

			// MP has Course Periods?
			$mp_has_course_periods = DBGet( "SELECT COUNT(COURSE_PERIOD_ID)
				FROM course_periods
				WHERE MARKING_PERIOD_ID='" . (int) $mp_id . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			if ( $mp_has_course_periods )
			{
				// Add Course Periods select input.
				$LO_columns['COURSE_PERIOD_ID'] = _( 'Course Period' );
			}

			// Build forms based on tab selected.

			if ( $tab_id == 'grades' )
			{
				$functions = [
					'COURSE_TITLE' => '_makeTextInput',
					'COURSE_PERIOD_ID' => '_makeSelectInput',
					'GRADE_PERCENT' => '_makeTextInput',
					'GRADE_LETTER' => '_makeTextInput',
					'WEIGHTED_GP' => '_makeTextInput',
					'UNWEIGHTED_GP' => '_makeTextInput',
					'GP_SCALE' => '_makeTextInput',
					'COMMENT' => '_makeTextInput',
				];

				$LO_columns += [
					'GRADE_PERCENT' => _( 'Percentage' ),
					'GRADE_LETTER' => _( 'Grade' ),
					'WEIGHTED_GP' => _( 'Grade Points' ),
					'UNWEIGHTED_GP' => _( 'Unweighted Grade Points' ),
					'GP_SCALE' => _( 'Grade Scale' ),
					'COMMENT' => _( 'Comments' ),
				];

				$link['add']['html'] = [
					'COURSE_TITLE' => _makeTextInput( '', 'COURSE_TITLE' ),
					'COURSE_PERIOD_ID' => _makeSelectInput( '', 'COURSE_PERIOD_ID' ),
					'GRADE_PERCENT' => _makeTextInput( '', 'GRADE_PERCENT' ),
					'GRADE_LETTER' => _makeTextInput( '', 'GRADE_LETTER' ),
					'WEIGHTED_GP' => _makeTextInput( '', 'WEIGHTED_GP' ),
					'UNWEIGHTED_GP' => _makeTextInput( '', 'UNWEIGHTED_GP' ),
					'GP_SCALE' => _makeTextInput( SchoolInfo( 'REPORTING_GP_SCALE' ), 'GP_SCALE' ),
					'COMMENT' => _makeTextInput( '', 'COMMENT' ),
				];
			}
			else
			{
				$functions = [
					'COURSE_TITLE' => '_makeTextInput',
					'COURSE_PERIOD_ID' => '_makeSelectInput',
					'CREDIT_ATTEMPTED' => '_makeTextInput',
					'CREDIT_EARNED' => '_makeTextInput',
					'CREDIT_CATEGORY' => '_makeTextInput',
					'CLASS_RANK' => '_makeCheckboxInput',
				];

				$LO_columns += [
					'CREDIT_ATTEMPTED' => _( 'Credit Attempted' ),
					'CREDIT_EARNED' => _( 'Credit Earned' ),
					'CREDIT_CATEGORY' => _( 'Credit Category' ),
					'CLASS_RANK' => _( 'Affects Class Rank' ),
				];

				$link['add']['html'] = [
					'COURSE_TITLE' => _makeTextInput( '', 'COURSE_TITLE' ),
					'COURSE_PERIOD_ID' => _makeSelectInput( '', 'COURSE_PERIOD_ID' ),
					'CREDIT_ATTEMPTED' => _makeTextInput( '', 'CREDIT_ATTEMPTED' ),
					'CREDIT_EARNED' => _makeTextInput( '', 'CREDIT_EARNED' ),
					'CREDIT_CATEGORY' => _makeTextInput( '', 'CREDIT_CATEGORY' ),
					'CLASS_RANK' => _makeCheckboxInput( '', 'CLASS_RANK' ),
				];
			}

			$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
				'&modfunc=remove&mp_id=' . $mp_id;

			$link['remove']['variables'] = [ 'id' => 'ID' ];

			$link['add']['html']['remove'] = button( 'add' );

			// FJ SQL error fix: operator does not exist: character varying = integer, add explicit type casts.
			// $sql = 'SELECT * FROM student_report_card_grades WHERE STUDENT_ID = '.$student_id.' AND MARKING_PERIOD_ID = '.$mp_id.' ORDER BY ID';

			$student_grades_RET = DBGet( "SELECT ID,COURSE_TITLE,COURSE_PERIOD_ID,
				CREDIT_ATTEMPTED,CREDIT_EARNED,CREDIT_CATEGORY,CLASS_RANK,
				GRADE_PERCENT,GRADE_LETTER,WEIGHTED_GP,UNWEIGHTED_GP,GP_SCALE,COMMENT
				FROM student_report_card_grades
				WHERE STUDENT_ID='" . (int) $student_id . "'
				AND MARKING_PERIOD_ID='" . (int) $mp_id . "'
				ORDER BY ID", $functions );

			ListOutput( $student_grades_RET, $LO_columns, '.', '.', $link, [], $LO_options );
		}

		echo '<br /><div class="center">';

		if ( $mp_id == "0" )
		{
			echo SubmitButton( _( 'Remove Marking Period' ), 'removemp', '' );
		}

		echo SubmitButton() . '</div>';
		echo '</form>';
	}
}

/**
 * @param $value
 * @param $name
 */
function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name === 'COURSE_TITLE' )
	{
		$extra = 'size=13 maxlength=50';

		if ( $id !== 'new' )
		{
			$extra .= ' required';
		}
	}
	elseif ( $name === 'COMMENT' )
	{
		$extra = 'size=20 maxlength=500';
	}
	elseif ( $name === 'GRADE_PERCENT' )
	{
		$extra = 'size=4 maxlength=6';
	}
	elseif ( $name === 'GRADE_LETTER'
		|| $name === 'WEIGHTED_GP'
		|| $name === 'UNWEIGHTED_GP' )
	{
		$extra = 'size=3 maxlength=5';
	}
	//elseif ( $name=='GP_VALUE')
	//    $extra = 'size=5 maxlength=5';
	//elseif ( $name=='UNWEIGHTED_GP_VALUE')
	else
	{
		$extra = 'size=4 maxlength=10';
	}

	if ( ( $name === 'GP_SCALE'
			|| $name === 'CREDIT_ATTEMPTED'
			|| $name === 'CREDIT_EARNED' )
		&& $value )
	{
		// Remove trailing 0.
		$value = (float) $value;
	}

	$return = TextInput(
		$value,
		"values[" . $id . "][" . $name . "]",
		'',
		$extra,
		( $id !== 'new' )
	);

	if ( $name === 'COMMENT'
		&& mb_strlen( (string) $value ) > 60
		&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		// Comments length > 60 chars, responsive table ColorBox.
		$return = '<div id="divStudentGradesComment' . $id . '" class="rt2colorBox">' .
			$return . '</div>';
	}

	return $return;
}

/**
 * Make Select Input
 * Used to select Course Periods,
 * only for new Grades and when Course Period not found.
 *
 * Local function. DBGet() callback.
 *
 * @since 3.5.1
 *
 * @param  string $value Course Period ID.
 * @param  string $name  'COURSE_PERIOD_ID' column.
 * @return string Select input or Course Period title.
 */
function _makeSelectInput( $value, $name )
{
	global $THIS_RET,
		$mp_id;

	/**
	 * @var mixed
	 */
	static $options = null;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( is_null( $options ) )
	{
		$options = [];

		// Get MP Course Periods.
		$mp_course_periods_RET = DBGet( "SELECT COURSE_PERIOD_ID,TITLE
			FROM course_periods
			WHERE SYEAR=(SELECT SYEAR
				FROM school_marking_periods
				WHERE MARKING_PERIOD_ID='" . (int) $mp_id . "')
			AND SCHOOL_ID='" . UserSchool() . "'
			AND GRADE_SCALE_ID IS NOT NULL
			ORDER BY COURSE_ID,TITLE" );

		foreach ( (array) $mp_course_periods_RET as $mp_course_period )
		{
			$options[$mp_course_period['COURSE_PERIOD_ID']] = $mp_course_period['TITLE'];
		}
	}

	if ( $id !== 'new'
		&& isset( $options[$value] ) )
	{
		// Return Course Period title, no Select input.
		return $options[$value];
	}

	// Select input only for new Grades or when Course Period not found.
	$extra = 'style="max-width: 150px;"';

	return SelectInput(
		$value,
		"values[" . $id . "][" . $name . "]",
		'',
		$options,
		'N/A',
		$extra,
		( $id !== 'new' )
	);
}

/**
 * @param $value
 * @param $name
 */
function _makeCheckboxInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	return CheckboxInput( $value, "values[" . $id . "][" . $name . "]", '', '', ( $id === 'new' ) );
}
