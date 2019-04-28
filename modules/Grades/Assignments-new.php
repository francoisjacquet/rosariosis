<?php

DrawHeader( ProgramTitle() );

$_ROSARIO['allow_edit'] = ( $_REQUEST['allow_edit'] === 'Y' );

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_POST['values'] ) )
	{
		foreach ( (array) $_REQUEST['values'] as $id => $columns )
		{
			//FJ added SQL constraint TITLE & POINTS are not null

			if (  ( ! isset( $columns['TITLE'] ) || ! empty( $columns['TITLE'] ) ) && ( ! isset( $columns['POINTS'] ) || ! empty( $columns['POINTS'] ) ) )
			{
				//FJ fix SQL bug invalid numeric data
				//FJ default points

				if (  ( empty( $columns['POINTS'] ) || ( is_numeric( $columns['POINTS'] ) && intval( $columns['POINTS'] ) >= 0 ) ) && ( empty( $columns['DEFAULT_POINTS'] ) || $columns['DEFAULT_POINTS'] == '*' || ( is_numeric( $columns['DEFAULT_POINTS'] ) && intval( $columns['DEFAULT_POINTS'] ) >= 0 ) ) )
				{
					if ( $id !== 'new' )
					{
						if ( $_REQUEST['tab_id'] !== 'new' )
						{
							$sql = "UPDATE GRADEBOOK_ASSIGNMENTS SET ";
							//if ( ! $columns['COURSE_ID'])
							//	$columns['COURSE_ID'] = 'N';
						}
						else
						{
							$sql = "UPDATE GRADEBOOK_ASSIGNMENT_TYPES SET ";
						}

						foreach ( (array) $columns as $column => $value )
						{
							if ( $column == 'POINTS' )
							{
								$value += 0;
							}
							elseif ( $column == 'FINAL_GRADE_PERCENT' && $value != '' )
							{
								$value /= 100;
							}
							elseif ( $column == 'COURSE_ID' )
							{
								if ( $value == 'Y' )
								{
									$column = 'COURSE_PERIOD_ID';
									$value = '';
									$sql .= "COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'),";
								}
								else
								{
									$value = '';
									$sql .= "COURSE_PERIOD_ID='" . UserCoursePeriod() . "',";
								}
							}

							//FJ default points
							elseif ( $column == 'DEFAULT_POINTS' && $value == '*' )
							{
								$value = '-1';
							}

							$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
						}

						if ( $_REQUEST['tab_id'] !== 'new' )
						{
							$sql = mb_substr( $sql, 0, -1 ) . " WHERE ASSIGNMENT_ID='" . $id . "'";
						}
						else
						{
							$sql = mb_substr( $sql, 0, -1 ) . " WHERE ASSIGNMENT_TYPE_ID='" . $id . "'";
						}

						DBQuery( $sql );
					}
					else
					{
						if ( $_REQUEST['tab_id'] !== 'new' )
						{
							$sql = 'INSERT INTO GRADEBOOK_ASSIGNMENTS ';
							$fields = "ASSIGNMENT_ID,STAFF_ID,MARKING_PERIOD_ID,";
							$values = db_seq_nextval( 'GRADEBOOK_ASSIGNMENTS_SEQ' ) . ",'" . User( 'STAFF_ID' ) . "','" . UserMP() . "',";

							if ( ! empty( $_REQUEST['tab_id'] ) )
							{
								$fields .= "ASSIGNMENT_TYPE_ID,";
								$values .= "'" . $_REQUEST['tab_id'] . "',";
							}

							if ( ! $columns['COURSE_ID'] )
							{
								$columns['COURSE_ID'] = 'N';
							}
						}
						else
						{
							$sql = 'INSERT INTO GRADEBOOK_ASSIGNMENT_TYPES ';
							$fields = 'ASSIGNMENT_TYPE_ID,STAFF_ID,COURSE_ID,';
							$values = db_seq_nextval( 'GRADEBOOK_ASSIGNMENT_TYPES_SEQ' ) . ",'" . User( 'STAFF_ID' ) . "',(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'),";
						}

						$go = false;

						foreach ( (array) $columns as $column => $value )
						{
							if ( $column == 'POINTS' && $value != '' )
							{
								$value = ( $value + 0 ) . '';
							}
							elseif ( $column == 'FINAL_GRADE_PERCENT' && $value != '' )
							{
								$value = ( $value / 100 ) . '';
							}
							elseif ( $column == 'COURSE_ID' )
							{
								if ( $value == 'Y' )
								{
									$column = 'COURSE_PERIOD_ID';
									$value = '';
									$fields .= "COURSE_ID,";
									$values .= "(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'),";
								}
								else
								{
									$value = '';
									$fields .= 'COURSE_PERIOD_ID,';
									$values .= "'" . UserCoursePeriod() . "',";
								}
							}

							//FJ default points
							elseif ( $column == 'DEFAULT_POINTS' && $value == '*' )
							{
								$value = '-1';
							}

							if ( $value != '' )
							{
								$fields .= DBEscapeIdentifier( $column ) . ',';
								$values .= "'" . $value . "',";

								if ( $column != 'ASSIGNMENT_TYPE_ID' && $column != 'ASSIGNED_DATE' && $column != 'DUE_DATE' && $column != 'DEFAULT_POINTS' && $column != 'DESCRIPTION' )
								{
									$go = true;
								}
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
					echo ErrorMessage( array( _( 'Please enter valid Numeric data.' ) ) );
				}
			}
			else
			{
				echo ErrorMessage( array( _( 'Please fill in the required fields' ) ) );
			}
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove' )
{
	if ( DeletePrompt( $_REQUEST['tab_id'] !== 'new' ? _( 'Assignment' ) : _( 'Assignment Type' ) ) )
	{
		if ( $_REQUEST['tab_id'] !== 'new' )
		{
			$delete_sql = "DELETE FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='" . $_REQUEST['id'] . "';";
			$delete_sql .= "DELETE FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_ID='" . $_REQUEST['id'] . "';";
		}
		else
		{
			$assignments_RET = DBGet( "SELECT ASSIGNMENT_ID FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_TYPE_ID='" . $_REQUEST['id'] . "'" );

			$delete_sql = '';

			if ( ! empty( $assignments_RET ) )
			{
				foreach ( (array) $assignments_RET as $assignment_id )
				{
					$delete_sql .= "DELETE FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='" . $assignment_id['ASSIGNMENT_ID'] . "';";
				}
			}

			$delete_sql .= "DELETE FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_TYPE_ID='" . $_REQUEST['id'] . "';";
			$delete_sql .= "DELETE FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE ASSIGNMENT_TYPE_ID='" . $_REQUEST['id'] . "';";
		}

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	$types_RET = DBGet( "SELECT ASSIGNMENT_TYPE_ID,TITLE,SORT_ORDER,COLOR FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "' AND COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "') ORDER BY SORT_ORDER,TITLE", array(), array( 'ASSIGNMENT_TYPE_ID' ) );

	if ( ! empty( $_REQUEST['tab_id'] ) )
	{
		if ( $_REQUEST['tab_id'] !== 'new' && ! $types_RET[$_REQUEST['tab_id']] )
		{
			if ( ! empty( $types_RET ) )
			{
				$_REQUEST['tab_id'] = key( $types_RET ) . '';
			}
			else
			{
				$_REQUEST['tab_id'] = 'new';
			}
		}
	}
	elseif ( empty( $types_RET ) )
	{
		$_REQUEST['tab_id'] = 'new';
	}

	if ( ! empty( $types_RET ) )
	{
		$tabs = array( array( 'title' => _( 'All' ), 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=&allow_edit=' . $_REQUEST['allow_edit'] ) );
	}

	foreach ( (array) $types_RET as $id => $type )
	{
		$tabs[] = array( 'title' => $type[1]['TITLE'], 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $id . '&allow_edit=' . $_REQUEST['allow_edit'], 'color' => $type[1]['COLOR'] );
		$type_options[$id] = ! $_REQUEST['tab_id'] && $type[1]['COLOR'] ? array( $type[1]['TITLE'], '<span style="color:' . $type[1]['COLOR'] . '">' . $type[1]['TITLE'] . '</span>' ) : $type[1]['TITLE'];
	}

	if ( $_REQUEST['tab_id'] !== 'new' )
	{
		//FJ default points
		$sql = "SELECT ASSIGNMENT_ID,TITLE,ASSIGNED_DATE,DUE_DATE,POINTS,COURSE_ID,DESCRIPTION,ASSIGNMENT_TYPE_ID,DEFAULT_POINTS," .
		db_case( array( '(DUE_DATE<ASSIGNED_DATE)', 'TRUE', "'Y'", 'NULL' ) ) . " AS DATE_ERROR," .
		db_case( array( '(ASSIGNED_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=\'' . UserMP() . '\'))', 'TRUE', "'Y'", 'NULL' ) ) . " AS ASSIGNED_ERROR," .
		db_case( array( 'DUE_DATE>(SELECT END_DATE+1 FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=\'' . UserMP() . '\')', 'TRUE', "'Y'", 'NULL' ) ) . " AS DUE_ERROR " .
		"FROM GRADEBOOK_ASSIGNMENTS " .
		"WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "' AND (COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "') OR COURSE_PERIOD_ID='" . UserCoursePeriod() . "')" . ( $_REQUEST['tab_id'] ? " AND ASSIGNMENT_TYPE_ID='" . $_REQUEST['tab_id'] . "'" : '' ) .
		" AND MARKING_PERIOD_ID='" . UserMP() . "' ORDER BY " . Preferences( 'ASSIGNMENT_SORTING', 'Gradebook' ) . " DESC,ASSIGNMENT_ID DESC,TITLE";
		$functions = array( 'TITLE' => '_makeAssnInput', 'POINTS' => '_makeAssnInput', 'ASSIGNED_DATE' => '_makeAssnInput', 'DUE_DATE' => '_makeAssnInput', 'COURSE_ID' => '_makeAssnInput', 'DESCRIPTION' => '_makeAssnInput', 'DEFAULT_POINTS' => '_makeAssnInput' );

		if ( $_REQUEST['allow_edit'] == 'Y' || ! $_REQUEST['tab_id'] )
		{
			$functions['ASSIGNMENT_TYPE_ID'] = '_makeAssnInput';
		}

		$LO_ret = DBGet( $sql, $functions );

		$LO_columns = array(
			'TITLE' => _( 'Title' ),
			'POINTS' => _( 'Points' ),
			'DEFAULT_POINTS' => _( 'Default Points' ) .
			'<div class="tooltip"><i>' . _( 'Enter an asterisk (*) to excuse student' ) . '</i></div>',
			'ASSIGNED_DATE' => _( 'Assigned Date' ),
			'DUE_DATE' => _( 'Due Date' ),
			'COURSE_ID' => _( 'All' ),
			'DESCRIPTION' => _( 'Description' ),
		);

		if ( $_REQUEST['allow_edit'] == 'Y' || ! $_REQUEST['tab_id'] )
		{
			$LO_columns += array( 'ASSIGNMENT_TYPE_ID' => _( 'Type' ) );
		}

		$link['add']['html'] = array( 'TITLE' => _makeAssnInput( '', 'TITLE' ), 'POINTS' => _makeAssnInput( '', 'POINTS' ), 'DEFAULT_POINTS' => _makeAssnInput( '', 'DEFAULT_POINTS' ), 'ASSIGNED_DATE' => _makeAssnInput( '', 'ASSIGNED_DATE' ), 'DUE_DATE' => _makeAssnInput( '', 'DUE_DATE' ), 'COURSE_ID' => _makeAssnInput( '', 'COURSE_ID' ), 'DESCRIPTION' => _makeAssnInput( '', 'DESCRIPTION' ) );

		if ( empty( $_REQUEST['tab_id'] ) )
		{
			$link['add']['html'] += array( 'ASSIGNMENT_TYPE_ID' => _makeAssnInput( '', 'ASSIGNMENT_TYPE_ID' ) );
		}

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=' . $_REQUEST['tab_id'] . '&allow_edit=' . $_REQUEST['allow_edit'];
		$link['remove']['variables'] = array( 'id' => 'ASSIGNMENT_ID' );
		$link['add']['html']['remove'] = button( 'add' );
		$link['add']['first'] = 1; // number before add link moves to top

		$tabs[] = array( 'title' => button( 'add', '', '', 'smaller' ), 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new&allow_edit=' . $_REQUEST['allow_edit'] );

		$subject = 'Assignments';
	}
	else
	{
		$sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE,FINAL_GRADE_PERCENT,SORT_ORDER,COLOR FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "' AND COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "') ORDER BY SORT_ORDER,TITLE";
		$functions = array( 'TITLE' => '_makeTypeInput', 'SORT_ORDER' => '_makeTypeInput', 'COLOR' => '_makeColorInput' );

		if ( Preferences( 'WEIGHT', 'Gradebook' ) == 'Y' )
		{
			$functions['FINAL_GRADE_PERCENT'] = '_makeTypeInput';
		}

		$LO_ret = DBGet( $sql, $functions );

		$LO_columns = array( 'TITLE' => _( 'Type' ) );

		if ( Preferences( 'WEIGHT', 'Gradebook' ) == 'Y' )
		{
			$LO_columns += array( 'FINAL_GRADE_PERCENT' => _( 'Percent' ) );
		}

		$LO_columns += array(
			'SORT_ORDER' => _( 'Sort Order' ),
			'COLOR' => _( 'Color' ),
		);

		$link['add']['html'] = array(
			'TITLE' => _makeTypeInput( '', 'TITLE' ),
			'SORT_ORDER' => _makeTypeInput( '', 'SORT_ORDER' ),
			'COLOR' => _makeColorInput( '', 'COLOR' ),
		);

		if ( Preferences( 'WEIGHT', 'Gradebook' ) == 'Y' )
		{
			$link['add']['html']['FINAL_GRADE_PERCENT'] = _makeTypeInput( '', 'FINAL_GRADE_PERCENT' );
		}

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&tab_id=new&allow_edit=' . $_REQUEST['allow_edit'];
		$link['remove']['variables'] = array( 'id' => 'ASSIGNMENT_TYPE_ID' );
		$link['add']['html']['remove'] = button( 'add' );

		$tabs[] = array( 'title' => button( 'add', '', '', 'smaller' ), 'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=new&allow_edit=' . $_REQUEST['allow_edit'] );

		$subject = 'Assignmemt Types';
	}

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&tab_id=' . $_REQUEST['tab_id'] . '" method="POST">';

	DrawHeader( CheckBoxOnclick( 'allow_edit', _( 'Edit' ) ), SubmitButton() );

	echo '<br />';

	$LO_options = array( 'save' => false, 'search' => false, 'header_color' => $types_RET[$_REQUEST['tab_id']][1]['COLOR'],
		'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab_id=' . $_REQUEST['tab_id'] . '&allow_edit=' . $_REQUEST['allow_edit'] ) );

	if ( $subject == 'Assignments' )
	{
		ListOutput( $LO_ret, $LO_columns, 'Assignment', 'Assignments', $link, array(), $LO_options );
	}
	else
	{
		ListOutput( $LO_ret, $LO_columns, 'Assignment Type', 'Assignment Types', $link, array(), $LO_options );
	}

	echo '<div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}

/**
 * @param $value
 * @param $name
 */
function _makeAssnInput( $value, $name )
{
	global $THIS_RET, $type_options;

	if ( $THIS_RET['ASSIGNMENT_ID'] )
	{
		$id = $THIS_RET['ASSIGNMENT_ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name == 'TITLE' )
	{
		/*if ( $id!== 'new' && ! $value)
		$title = '<span style="color:red">'._('Title').'</span>';*/
		$extra = 'size=20 maxlength=100';
	}
	elseif ( $name == 'POINTS' )
	{
		/*if ( $id!== 'new' && $value=='')
		$title = '<span style="color:red">'._('Points').'</span>';*/
		$extra = 'size=5 maxlength=5';
	}

	//FJ default points
	elseif ( $name == 'DEFAULT_POINTS' )
	{
		if ( $value == '-1' )
		{
			$value = '*';
		}

		$extra = 'size=5 maxlength=5';
	}
	elseif ( $name == 'ASSIGNED_DATE' )
	{
		return DateInput(
			$id == 'new' && Preferences( 'DEFAULT_ASSIGNED', 'Gradebook' ) == 'Y' ? DBDate() : $value,
			'values[' . $id . '][ASSIGNED_DATE]',
			( $THIS_RET['ASSIGNED_ERROR'] == 'Y' ?
				'<span class="legend-red">' . _( 'Assigned date is after end of quarter!' ) . '</span>' :
				( $THIS_RET['DATE_ERROR'] == 'Y' ? '<span class="legend-red">' . _( 'Assigned date is after due date!' ) . '</span>' : '' )
			),
			$id !== 'new'
		);
	}
	elseif ( $name == 'DUE_DATE' )
	{
		return DateInput(
			$id == 'new' && Preferences( 'DEFAULT_DUE', 'Gradebook' ) == 'Y' ? DBDate() : $value,
			'values[' . $id . '][DUE_DATE]',
			( $THIS_RET['DUE_ERROR'] == 'Y' ?
				'<span class="legend-red">' . _( 'Due date is after end of quarter!' ) . '</span>' :
				( $THIS_RET['DATE_ERROR'] == 'Y' ? '<span class="legend-red">' . _( 'Due date is before assigned date!' ) . '</span>' : '' )
			),
			$id !== 'new'
		);
	}
	elseif ( $name == 'COURSE_ID' )
	{
		return CheckboxInput(
			$value,
			'values[' . $id . '][COURSE_ID]',
			'',
			'',
			$id == 'new'
		);
	}
	elseif ( $name == 'DESCRIPTION' )
	{
		$extra = 'size=20 maxlength=1000';
	}
	elseif ( $name == 'ASSIGNMENT_TYPE_ID' )
	{
		return SelectInput(
			$value,
			'values[' . $id . '][ASSIGNMENT_TYPE_ID]',
			'',
			$type_options,
			false
		);
	}

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		$title,
		$extra
	);
}

/**
 * @param $value
 * @param $name
 */
function _makeTypeInput( $value, $name )
{
	global $THIS_RET, $total_percent;

	if ( $THIS_RET['ASSIGNMENT_TYPE_ID'] )
	{
		$id = $THIS_RET['ASSIGNMENT_TYPE_ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name == 'TITLE' )
	{
		$extra = 'size=20 maxlength=100';
	}
	elseif ( $name == 'FINAL_GRADE_PERCENT' )
	{
		if ( $id == 'new' )
		{
			$title = ( $total_percent != 1 ? '<span style="color:red">' : '' ) . _( 'Total' ) . ' = ' . ( $total_percent * 100 ) . '%' . ( $total_percent != 1 ? '</span>' : '' );
		}
		else
		{
			$total_percent += $value;
			$value = array( $value * 100, ( $value * 100 ) . '%' );
		}

		$extra = 'size=5 maxlength=10';
	}
	elseif ( $name == 'SORT_ORDER' )
	{
		$extra = 'size=5 maxlength=10';
	}

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		$title,
		$extra
	);
}

/**
 * Make Color Input
 * Local function
 * DBGet callback
 *
 * @uses ColorInput()
 *
 * @global $THIS_RET
 *
 * @param  string $value  Value
 * @param  string $column 'COLOR'
 * @return string Color Input
 */
function _makeColorInput( $value, $column )
{
	global $THIS_RET;

	if ( $THIS_RET['ASSIGNMENT_TYPE_ID'] )
	{
		$id = $THIS_RET['ASSIGNMENT_TYPE_ID'];
	}
	else
	{
		$id = 'new';
	}

	return ColorInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		'hidden'
	);
}
