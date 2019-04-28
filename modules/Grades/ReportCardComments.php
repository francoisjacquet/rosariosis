<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update' )
{
	if ( ! empty( $_REQUEST['values'] )
		&& ! empty( $_POST['values'] )
		&& AllowEdit()
		&& $_REQUEST['tab_id'] != '' )
	{
		if ( $_REQUEST['tab_id'] !== 'new' || ! empty( $_REQUEST['course_id'] ) )
		{
			if ( $_REQUEST['tab_id'] === 'new' )
			{
				$table = 'REPORT_CARD_COMMENT_CATEGORIES';
			}
			else
			{
				$table = 'REPORT_CARD_COMMENTS';
			}

			foreach ( (array) $_REQUEST['values'] as $id => $columns )
			{
				//FJ fix SQL bug invalid sort order

				if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
				{
					if ( $id !== 'new' )
					{
						$sql = "UPDATE " . DBEscapeIdentifier( $table ) . " SET ";

						foreach ( (array) $columns as $column => $value )
						{
							$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
						}

						$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";
						DBQuery( $sql );
					}

					// New: check for Title
					elseif ( $columns['TITLE'] )
					{
						$sql = "INSERT INTO $table ";

						$fields = "ID,SCHOOL_ID,SYEAR,COURSE_ID," . ( $_REQUEST['tab_id'] == 'new' ? '' : "CATEGORY_ID," );

						$values = db_seq_nextval( $table . '_SEQ' ) . ",'" . UserSchool() . "','" . UserSyear() . "'," . ( $_REQUEST['tab_id'] == 'new' ? "'" . $_REQUEST['course_id'] . "'" : ( $_REQUEST['tab_id'] == '-1' ? "NULL,NULL" : ( $_REQUEST['tab_id'] == '0' ? "'0',NULL" : "'" . $_REQUEST['course_id'] . "','" . $_REQUEST['tab_id'] . "'" ) ) ) . ",";

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
					$error[] = _( 'Please enter a valid Sort Order.' );
				}
			}
		}
		else
		{
			$error[] = _( 'There are no courses setup yet.' );
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( $_REQUEST['tab_id'] == 'new' )
	{
		if ( DeletePrompt( _( 'Report Card Comment Category' ) ) )
		{
			$delete_sql = "DELETE FROM REPORT_CARD_COMMENTS
				WHERE CATEGORY_ID='" . $_REQUEST['id'] . "';";

			$delete_sql .= "DELETE FROM REPORT_CARD_COMMENT_CATEGORIES
				WHERE ID='" . $_REQUEST['id'] . "';";

			DBQuery( $delete_sql );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( array( 'modfunc', 'id' ) );
		}
	}
	elseif ( $_REQUEST['tab_id'] == '-1' )
	{
		if ( DeletePrompt( _( 'Report Card Comment' ) ) )
		{
			DBQuery( "DELETE FROM REPORT_CARD_COMMENTS
				WHERE ID='" . $_REQUEST['id'] . "'" );

			// Unset modfunc & ID & redirect URL.
			RedirectURL( array( 'modfunc', 'id' ) );
		}
	}
	elseif ( DeletePrompt( _( 'Report Card Comment' ) ) )
	{
		DBQuery( "DELETE FROM REPORT_CARD_COMMENTS
			WHERE ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	if ( User( 'PROFILE' ) === 'admin' )
	{
		$subjects_RET = DBGet( "SELECT SUBJECT_ID,TITLE
			FROM COURSE_SUBJECTS
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND (SELECT count(1)
				FROM COURSE_PERIODS
				WHERE SUBJECT_ID=COURSE_SUBJECTS.SUBJECT_ID
				AND GRADE_SCALE_ID IS NOT NULL)>0
			ORDER BY SORT_ORDER,TITLE", array(), array( 'SUBJECT_ID' ) );

		if ( ! $_REQUEST['subject_id'] || ! $subjects_RET[$_REQUEST['subject_id']] )
		{
			$_REQUEST['subject_id'] = key( $subjects_RET ) . '';
		}

		$courses_RET = DBGet( "SELECT COURSE_ID,TITLE
			FROM COURSES
			WHERE SUBJECT_ID='" . $_REQUEST['subject_id'] . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			AND (SELECT count(1)
				FROM COURSE_PERIODS
				WHERE COURSE_ID=COURSES.COURSE_ID
				AND GRADE_SCALE_ID IS NOT NULL)>0
			ORDER BY TITLE", array(), array( 'COURSE_ID' ) );

		if ( ! $_REQUEST['course_id'] || ! $courses_RET[$_REQUEST['course_id']] )
		{
			$_REQUEST['course_id'] = key( $courses_RET ) . '';
		}

		$subject_onchange_URL = "'Modules.php?modname=" . $_REQUEST['modname'] .
			"&subject_id='";

		$subject_select = '<select name="subject_id" onchange="ajaxLink(' . $subject_onchange_URL . ' + this.options[selectedIndex].value);">';

		//FJ Add No Courses were found error

		if ( empty( $subjects_RET ) )
		{
			$subject_select .= '<option value="">' . sprintf( _( 'No %s were found.' ), _( 'Courses' ) ) . '</option>';
		}
		else
		{
			foreach ( (array) $subjects_RET as $id => $subject )
			{
				$subject_select .= '<option value="' . $id . '"' . ( $_REQUEST['subject_id'] == $id ? ' selected' : '' ) . '>' . $subject[1]['TITLE'] . '</option>';
			}
		}

		$subject_select .= '</select>';

		$course_onchange_URL = "'Modules.php?modname=" . $_REQUEST['modname'] .
			'&subject_id=' . $_REQUEST['subject_id'] .
			"&course_id='";

		$course_select .= '<select name="course_id" onchange="ajaxLink(' . $course_onchange_URL . ' + this.options[selectedIndex].value);">';

		//FJ Add No Courses were found error

		if ( empty( $courses_RET ) )
		{
			$course_select .= '<option value="">' . sprintf( _( 'No %s were found.' ), _( 'Courses' ) ) . '</option>';
		}
		else
		{
			foreach ( (array) $courses_RET as $id => $course )
			{
				$course_select .= '<option value="' . $id . '"' . ( $_REQUEST['course_id'] == $id ? ' selected' : '' ) . '>' . $course[1]['TITLE'] . '</option>';
			}
		}

		$course_select .= '</select>';
	}
	else
	{
		$course_period_RET = DBGet( "SELECT GRADE_SCALE_ID,DOES_BREAKOFF,TEACHER_ID,COURSE_ID
			FROM COURSE_PERIODS
			WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" );

		if ( ! $course_period_RET[1]['GRADE_SCALE_ID'] )
		{
			ErrorMessage( array( _( 'This course is not graded.' ) ), 'fatal' );
		}

		$subjects_RET = DBGet( "SELECT TITLE
			FROM COURSE_SUBJECTS
			WHERE SUBJECT_ID='" . $course_period_RET[1]['SUBJECT_ID'] . "'" );

		//FJ add subject areas
		$courses_RET = DBGet( "SELECT TITLE,SUBJECT_ID,
			(SELECT TITLE FROM COURSE_SUBJECTS WHERE SUBJECT_ID=COURSES.SUBJECT_ID) AS SUBJECT
			FROM COURSES
			WHERE COURSE_ID='" . $course_period_RET[1]['COURSE_ID'] . "'" );

		$_REQUEST['subject_id'] = $courses_RET[1]['SUBJECT_ID'];
		$_REQUEST['course_id'] = $course_period_RET[1]['COURSE_ID'];

		$subject_select = $courses_RET[1]['SUBJECT'];
		$course_select = $courses_RET[1]['TITLE'];
	}

	$categories_RET = DBGet( "SELECT rc.ID,rc.TITLE,rc.COLOR,1,rc.SORT_ORDER,
	(SELECT count(1) FROM REPORT_CARD_COMMENTS WHERE COURSE_ID=rc.COURSE_ID AND CATEGORY_ID=rc.ID) AS COUNT
	FROM REPORT_CARD_COMMENT_CATEGORIES rc
	WHERE rc.COURSE_ID='" . $_REQUEST['course_id'] . "'
	UNION
	SELECT 0,'" . _( 'All Courses' ) . "',NULL,2,NULL,(SELECT count(1) FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='" . UserSchool() . "' AND COURSE_ID='0' AND SYEAR='" . UserSyear() . "')
	UNION
	SELECT -1,'" . _( 'General' ) . "',NULL,3,NULL,(SELECT count(1) FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='" . UserSchool() . "' AND COURSE_ID IS NULL AND SYEAR='" . UserSyear() . "')
	ORDER BY 4,SORT_ORDER", array(), array( 'ID' ) );

	if ( $_REQUEST['tab_id'] == '' || $_REQUEST['tab_id'] !== 'new' && ! $categories_RET[$_REQUEST['tab_id']] )
	//$_REQUEST['tab_id'] = key($categories_RET).'';
	{
		$_REQUEST['tab_id'] = '-1';
	}
	//FJ default to -1 (General)

	$tabs = array();

	foreach ( (array) $categories_RET as $id => $category )
	{
		if ( $category[1]['COUNT'] || AllowEdit() )
		{
			$tabs[] = array(
				'title' => $category[1]['TITLE'],
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' .
				$_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'] . '&tab_id=' . $id
			) +
			( $category[1]['COLOR'] ? array( 'color' => $category[1]['COLOR'] ) : array() );

			if ( $id > 0 )
			{
				$category_select[$id] = $category[1]['TITLE'];
			}
		}
	}

	if ( $_REQUEST['tab_id'] == 'new' )
	{
		$sql = "SELECT *
			FROM REPORT_CARD_COMMENT_CATEGORIES
			WHERE COURSE_ID='" . $_REQUEST['course_id'] . "'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER";

		$functions = array(
			'TITLE' => '_makeCommentsInput',
			'SORT_ORDER' => '_makeCommentsInput',
			'COLOR' => '_makeColorInput',
		);

		$LO_columns = array(
			'TITLE' => _( 'Comment Category' ),
			'SORT_ORDER' => _( 'Sort Order' ),
			'COLOR' => _( 'Color' ),
		);

		$link['add']['html'] = array(
			'TITLE' => _makeCommentsInput( '', 'TITLE' ),
			'SORT_ORDER' => _makeCommentsInput( '', 'SORT_ORDER' ),
			'COLOR' => _makeColorInput( '', 'COLOR' ),
		);

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&course_id=' . $_REQUEST['course_id'] . '&tab_id=new';

		$link['remove']['variables'] = array( 'id' => 'ID' );
		$link['add']['html']['remove'] = button( 'add' );

		$tabs[] = array(
			'title' => button( 'add', '', '', 'smaller' ),
			'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' .
				$_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'] . '&tab_id=new',
		);
	}
	elseif ( $_REQUEST['tab_id'] == '-1' )
	{
		$sql = "SELECT *
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND COURSE_ID IS NULL
		ORDER BY SORT_ORDER";

		$functions = array( 'TITLE' => '_makeCommentsInput', 'SORT_ORDER' => '_makeCommentsInput' );
		$LO_columns = array( 'TITLE' => _( 'Comment' ), 'SORT_ORDER' => _( 'Sort Order' ) );

		$link['add']['html'] = array(
			'TITLE' => _makeCommentsInput( '', 'TITLE' ),
			'SORT_ORDER' => _makeCommentsInput( '', 'SORT_ORDER' ),
		);

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&subject_id=' . $_REQUEST['subject_id'] .
			'&course_id=' . $_REQUEST['course_id'] . '&tab_id=-1';

		$link['remove']['variables'] = array( 'id' => 'ID' );
		$link['add']['html']['remove'] = button( 'add' );

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$tabs[] = array(
				'title' => button( 'add', '', '', 'smaller' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' .
					$_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'] . '&tab_id=new',
			);
		}
	}
	else
	{
		$codes_RET = DBGet( "SELECT ID,TITLE
			FROM REPORT_CARD_COMMENT_CODE_SCALES
			WHERE SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER,TITLE" );

		$code_select = array( '' => _( 'N/A' ) );

		foreach ( (array) $codes_RET as $code )
		{
			$code_select[$code['ID']] = $code['TITLE'];
		}

		$functions = array(
			'TITLE' => '_makeCommentsInput',
			'SCALE_ID' => '_makeCommentsInput',
			'SORT_ORDER' => '_makeCommentsInput',
		);

		$LO_columns = array(
			'TITLE' => _( 'Comment' ),
			'SCALE_ID' => _( 'Code Scale' ),
			'SORT_ORDER' => _( 'Sort Order' ),
		);

		if ( $_REQUEST['tab_id'] == '0' )
		{
			// need to be more specific since course_id=0 is not unique
			$sql = "SELECT *
			FROM REPORT_CARD_COMMENTS
			WHERE COURSE_ID='0'
			AND SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER,TITLE";
		}
		else
		{
			$sql = "SELECT *
			FROM REPORT_CARD_COMMENTS
			WHERE CATEGORY_ID='" . $_REQUEST['tab_id'] . "'
			ORDER BY SORT_ORDER,TITLE";

			if ( User( 'PROFILE' ) === 'admin' && AllowEdit() )
			{
				$functions += array( 'CATEGORY_ID' => '_makeCommentsInput' );
				$LO_columns += array( 'CATEGORY_ID' => _( 'Category' ) );
			}
		}

		$link['add']['html'] = array(
			'TITLE' => _makeCommentsInput( '', 'TITLE' ),
			'SCALE_ID' => _makeCommentsInput( '', 'SCALE_ID' ),
			'SORT_ORDER' => _makeCommentsInput( '', 'SORT_ORDER' ),
		);

		$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&subject_id=' . $_REQUEST['subject_id'] . '&course_id=' .
			$_REQUEST['course_id'] . '&tab_id=' . $_REQUEST['tab_id'];

		$link['remove']['variables'] = array( 'id' => 'ID' );
		$link['add']['html']['remove'] = button( 'add' );

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$tabs[] = array(
				'title' => button( 'add', '', '', 'smaller' ),
				'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' .
					$_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'] . '&tab_id=new',
			);
		}
	}

	$LO_ret = DBGet( $sql, $functions );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update&course_id=' .
		$_REQUEST['course_id'] . '&tab_id=' . $_REQUEST['tab_id'] . '" method="POST">';

	DrawHeader( $subject_select . ' : ' . $course_select, SubmitButton() );
	echo '<br />';

	//FJ fix SQL bug invalid sort order
	echo ErrorMessage( $error );

	$LO_options = array(
		'save' => false,
		'search' => false,
		'header_color' => $categories_RET[$_REQUEST['tab_id']][1]['COLOR'],
		'header' => WrapTabs( $tabs, 'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' .
			$_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'] . '&tab_id=' . $_REQUEST['tab_id'] ),
	);

	//ListOutput($LO_ret,$LO_columns,$singular,$plural,$link,array(),$LO_options);

	if ( $_REQUEST['tab_id'] == 'new' )
	{
		ListOutput( $LO_ret, $LO_columns, 'Category', 'Categories', $link, array(), $LO_options );
	}
	else
	{
		ListOutput( $LO_ret, $LO_columns, 'Comment', 'Comments', $link, array(), $LO_options );
	}

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}

/**
 * Make Comments input
 * Select, and Text inputs
 *
 * Local function
 * DBGet() callback
 *
 * @param string $value  Column value.
 * @param string $column Column name.
 *
 * @return Input HTML.
 */
function _makeCommentsInput( $value, $name )
{
	global $THIS_RET,
	$category_select,
		$code_select;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name === 'CATEGORY_ID' )
	{
		return SelectInput(
			$value,
			'values[' . $id . '][' . $name . ']',
			'',
			$category_select,
			false
		);
	}
	elseif ( $name === 'SCALE_ID' )
	{
		return SelectInput(
			$value,
			'values[' . $id . '][' . $name . ']',
			'',
			$code_select,
			false
		);
	}

	$extra = '';

	if ( $name === 'SORT_ORDER' )
	{
		$extra .= ' size=3 maxlength=5';
	}

	if ( $name === 'TITLE' )
	{
		if ( $id !== 'new' )
		{
			$extra .= ' required';
		}
		else
		{
			$extra .= ' size=20';
		}
	}

	return TextInput(
		$value,
		'values[' . $id . '][' . $name . ']',
		'',
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

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	return ColorInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		'hidden',
		'data-position="bottom right"'
	);
}
