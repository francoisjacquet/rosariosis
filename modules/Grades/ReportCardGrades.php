<?php
//echo '<pre>'; var_dump($_REQUEST); echo '</pre>';

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit()
		&& $_REQUEST['tab_id'] )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			// FJ fix SQL bug invalid numeric data.

			if (  ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
				&& ( empty( $columns['BREAK_OFF'] ) || is_numeric( $columns['BREAK_OFF'] ) )
				&& ( empty( $columns['GPA_VALUE'] ) || is_numeric( $columns['GPA_VALUE'] ) )
				&& ( empty( $columns['UNWEIGHTED_GP'] ) || is_numeric( $columns['UNWEIGHTED_GP'] ) )
				&& ( empty( $columns['GP_SCALE'] ) || is_numeric( $columns['GP_SCALE'] ) )
				&& ( empty( $columns['GP_PASSING_VALUE'] ) || is_numeric( $columns['GP_PASSING_VALUE'] ) )
				&& ( empty( $columns['HR_GPA_VALUE'] ) || is_numeric( $columns['HR_GPA_VALUE'] ) )
				&& ( empty( $columns['HHR_GPA_VALUE'] ) || is_numeric( $columns['HHR_GPA_VALUE'] ) )
				&& ( empty( $columns['HRS_GPA_VALUE'] ) || is_numeric( $columns['HRS_GPA_VALUE'] ) ) )
			{
				if ( $id !== 'new' )
				{
					if ( $_REQUEST['tab_id'] !== 'new' )
					{
						$sql = "UPDATE REPORT_CARD_GRADES SET ";
					}
					else
					{
						$sql = "UPDATE REPORT_CARD_GRADE_SCALES SET ";
					}

					foreach ( (array) $columns as $column => $value )
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					if ( $_REQUEST['tab_id'] !== 'new' )
					{
						$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";
					}
					else
					{
						$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";
					}

					DBQuery( $sql );
				}

				// New: check for Title
				elseif ( $columns['TITLE'] )
				{
					if ( $_REQUEST['tab_id'] !== 'new' )
					{
						$sql = 'INSERT INTO REPORT_CARD_GRADES ';
						$fields = 'ID,SCHOOL_ID,SYEAR,GRADE_SCALE_ID,';
						$values = db_seq_nextval( 'REPORT_CARD_GRADES_SEQ' ) . ',\'' . UserSchool() . '\',\'' . UserSyear() . '\',\'' . $_REQUEST['tab_id'] . '\',';
					}
					else
					{
						$sql = 'INSERT INTO REPORT_CARD_GRADE_SCALES ';
						$fields = 'ID,SCHOOL_ID,SYEAR,';
						$values = db_seq_nextval( 'REPORT_CARD_GRADE_SCALES_SEQ' ) . ',\'' . UserSchool() . '\',\'' . UserSyear() . '\',';
					}

					$go = false;

					foreach ( (array) $columns as $column => $value )
					{
						if ( ! empty( $value ) || $value == '0' )
						{
							$fields .= DBEscapeIdentifier( $column ) . ',';
							$values .= "'" . $value . "',";
							$go = true;
						}
					}

					$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

					if ( $go )
					{
						DBQuery( $sql );
					}
				}
			}
			else
			{
				$error[] = _( 'Please enter valid Numeric data.' );
			}
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		if ( DeletePrompt( _( 'Report Card Grade' ) ) )
		{
			DBQuery( "DELETE FROM REPORT_CARD_GRADES
				WHERE ID='" . $_REQUEST['id'] . "'" );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( array( 'modfunc', 'id' ) );
		}
	}
	elseif ( DeletePrompt( _( 'Report Card Grading Scale' ) ) )
	{
		$delete_sql = "DELETE FROM REPORT_CARD_GRADES
			WHERE GRADE_SCALE_ID='" . $_REQUEST['id'] . "';";

		$delete_sql .= "DELETE FROM REPORT_CARD_GRADE_SCALES
			WHERE ID='" . $_REQUEST['id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

//FJ fix SQL bug invalid numeric data
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	if ( User( 'PROFILE' ) === 'admin' )
	{
		$grade_scales_RET = DBGet( "SELECT ID,TITLE
			FROM REPORT_CARD_GRADE_SCALES
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY SORT_ORDER", array(), array( 'ID' ) );

		if ( $_REQUEST['tab_id'] == ''
			|| $_REQUEST['tab_id'] !== 'new'
			&& empty( $grade_scales_RET[$_REQUEST['tab_id']] ) )
		{
			if ( ! empty( $grade_scales_RET ) )
			{
				$_REQUEST['tab_id'] = key( $grade_scales_RET ) . '';
			}
			else
			{
				$_REQUEST['tab_id'] = 'new';
			}
		}
	}
	else
	{
		$course_period_RET = DBGet( 'SELECT GRADE_SCALE_ID,DOES_BREAKOFF,TEACHER_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID=\'' . UserCoursePeriod() . '\'' );

		if ( ! $course_period_RET[1]['GRADE_SCALE_ID'] )
		{
			ErrorMessage( array( _( 'This course is not graded.' ) ), 'fatal' );
		}

		$grade_scales_RET = DBGet( 'SELECT ID,TITLE FROM REPORT_CARD_GRADE_SCALES WHERE ID=\'' . $course_period_RET[1]['GRADE_SCALE_ID'] . '\'', array(), array( 'ID' ) );

		if ( $course_period_RET[1]['DOES_BREAKOFF'] == 'Y' )
		{
			$teacher_id = $course_period_RET[1]['TEACHER_ID'];

			$gradebook_config = ProgramUserConfig( 'Gradebook', $teacher_id );
		}

		$_REQUEST['tab_id'] = key( $grade_scales_RET ) . '';
	}

	$tabs = array();
	$grade_scale_select = array();

	foreach ( (array) $grade_scales_RET as $id => $grade_scale )
	{
		$tabs[] = array( 'title' => $grade_scale[1]['TITLE'], 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $id );
		$grade_scale_select[$id] = $grade_scale[1]['TITLE'];
	}

	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		$sql = 'SELECT * FROM REPORT_CARD_GRADES WHERE GRADE_SCALE_ID=\'' . $_REQUEST['tab_id'] . '\' AND SYEAR=\'' . UserSyear() . '\' AND SCHOOL_ID=\'' . UserSchool() . '\' ORDER BY BREAK_OFF IS NOT NULL DESC,BREAK_OFF DESC, SORT_ORDER';

		$functions = array(
			'TITLE' => '_makeTextInput',
			'BREAK_OFF' => '_makeGradesInput',
			'SORT_ORDER' => '_makeTextInput',
			'GPA_VALUE' => '_makeGradesInput',
			'UNWEIGHTED_GP' => '_makeGradesInput',
			'COMMENT' => '_makeTextInput',
		);

		$LO_columns = array(
			'TITLE' => _( 'Title' ),
			'BREAK_OFF' => _( 'Breakoff' ),
			'GPA_VALUE' => _( 'GPA Value' ),
			'UNWEIGHTED_GP' => _( 'Unweighted GP Value' ),
			'SORT_ORDER' => _( 'Order' ),
			'COMMENT' => _( 'Comment' ),
		);

		if ( User( 'PROFILE' ) === 'admin' && AllowEdit() )
		{
			$functions += array( 'GRADE_SCALE_ID' => '_makeGradesInput' );
			$LO_columns += array( 'GRADE_SCALE_ID' => _( 'Grade Scale' ) );
		}

		$link['add']['html'] = array(
			'TITLE' => _makeTextInput( '', 'TITLE' ),
			'BREAK_OFF' => _makeGradesInput( '', 'BREAK_OFF' ),
			'GPA_VALUE' => _makeGradesInput( '', 'GPA_VALUE' ),
			'UNWEIGHTED_GP' => _makeGradesInput( '', 'UNWEIGHTED_GP' ),
			'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
			'COMMENT' => _makeTextInput( '', 'COMMENT' ),
		);

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=' . $_REQUEST['tab_id'];
		$link['remove']['variables'] = array( 'id' => 'ID' );
		$link['add']['html']['remove'] = button( 'add' );

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$tabs[] = array(
				'title' => button( 'add', '', '', 'smaller' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
			);
		}
	}
	else
	{
		$sql = "SELECT * FROM REPORT_CARD_GRADE_SCALES
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY SORT_ORDER,ID";

		$functions = array(
			'TITLE' => '_makeTextInput',
			'GP_SCALE' => '_makeGradesInput',
			'GP_PASSING_VALUE' => '_makeGradesInput',
			'COMMENT' => '_makeTextInput',
			'HHR_GPA_VALUE' => '_makeGradesInput',
			'HR_GPA_VALUE' => '_makeGradesInput',
			'HRS_GPA_VALUE' => '_makeGradesInput',
			'SORT_ORDER' => '_makeTextInput',
		);

		$LO_columns = array(
			'TITLE' => _( 'Grade Scale' ),
			'GP_SCALE' => _( 'Scale Value' ),
			'GP_PASSING_VALUE' => _( 'Minimum Passing Grade' ),
			'COMMENT' => _( 'Comment' ),
			'HHR_GPA_VALUE' => _( 'High Honor Roll GPA Min' ),
			'HR_GPA_VALUE' => _( 'Honor Roll GPA Min' ),
			'HRS_GPA_VALUE' => _( 'Honor Roll by Subject GPA Min' ),
			'SORT_ORDER' => _( 'Sort Order' ),
		);

		$link['add']['html'] = array(
			'TITLE' => _makeTextInput( '', 'TITLE' ),
			'GP_SCALE' => _makeGradesInput( '', 'GP_SCALE' ),
			'GP_PASSING_VALUE' => _makeGradesInput( '', 'GP_PASSING_VALUE' ),
			'COMMENT' => _makeTextInput( '', 'COMMENT' ),
			'HHR_GPA_VALUE' => _makeGradesInput( '', 'HHR_GPA_VALUE' ),
			'HR_GPA_VALUE' => _makeGradesInput( '', 'HR_GPA_VALUE' ),
			'HRS_GPA_VALUE' => _makeGradesInput( '', 'HRS_GPA_VALUE' ),
			'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		);

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=new';
		$link['remove']['variables'] = array( 'id' => 'ID' );
		$link['add']['html']['remove'] = button( 'add' );

		$tabs[] = array(
			'title' => button( 'add', '', '', 'smaller' ),
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new',
		);
	}

	$LO_ret = DBGet( $sql, $functions );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&tab_id=' . $_REQUEST['tab_id'] . '" method="POST">';
	DrawHeader( '', SubmitButton() );
	echo '<br />';

	$LO_options = array( 'search' => false,
		'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $_REQUEST['tab_id'] ) );

	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		ListOutput( $LO_ret, $LO_columns, 'Grade', 'Grades', $link, array(), $LO_options );
	}
	else
	{
		ListOutput( $LO_ret, $LO_columns, 'Grade Scale', 'Grade Scales', $link, array(), $LO_options );
	}

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}

/**
 * @param $value
 * @param $name
 */
function _makeGradesInput( $value, $name )
{
	global $THIS_RET,
	$grade_scale_select,
	$teacher_id,
		$gradebook_config;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name === 'GRADE_SCALE_ID' )
	{
		return SelectInput(
			$value,
			'values[' . $id . '][' . $name . ']',
			'',
			$grade_scale_select,
			false
		);
	}

	if ( $name === 'COMMENT' )
	{
		$extra = 'size=15 maxlength=100';
	}
	elseif ( $name === 'BREAK_OFF'
		&& $teacher_id
		&& isset( $gradebook_config[UserCoursePeriod() . '-' . $THIS_RET['ID']] )
		&& $gradebook_config[UserCoursePeriod() . '-' . $THIS_RET['ID']] != '' )
	{
		// Breakoff configured by Teacher.
		return '<span style="color:blue">' .
			$gradebook_config[UserCoursePeriod() . '-' . $THIS_RET['ID']] . '%</span>';
	}
	else
	{
		$extra = 'size=4 maxlength=5';

		if ( $value )
		{
			$value = number_format( (float) $value, 2, '.', '' );
		}
	}

	if ( $name === 'BREAK_OFF'
		&& $value !== '' )
	{
		// Append "%" to displayed Breakoff value.
		$value = array( $value, $value . '%' );
	}

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$extra
	);
}

/**
 * @param $value
 * @param $name
 */
function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name === 'TITLE' )
	{
		$extra = 'size=5 maxlength=100';

		if ( $id !== 'new' )
		{
			$extra .= ' required';
		}
	}
	elseif ( $name === 'COMMENT' )
	{
		$extra = 'size=15 maxlength=1000';
	}
	else
	{
		$extra = 'size=4 maxlength=5';
	}

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
		$extra
	);
}
