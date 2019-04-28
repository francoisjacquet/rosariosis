<?php
//FJ Portal Polls inspired by Portal Notes
require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';

DrawHeader( ProgramTitle() );

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

$profiles_RET = DBGet( "SELECT ID,TITLE FROM USER_PROFILES ORDER BY ID" );

if ( $_REQUEST['modfunc'] === 'update'
	&& (  ( $_REQUEST['profiles']
		&& $_POST['profiles'] )
		|| ( $_REQUEST['values']
			&& $_POST['values'] ) )
	&& AllowEdit() )
{
	$polls_RET = DBGet( "SELECT ID FROM PORTAL_POLLS WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "'" );

	foreach ( (array) $polls_RET as $poll_id )
	{
		$poll_id = $poll_id['ID'];
		$_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'] = '';

		foreach ( array( 'admin', 'teacher', 'parent' ) as $profile_id )
		{
			if ( ! empty( $_REQUEST['profiles'][$poll_id][$profile_id] ) )
			{
				$_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'] .= ',' . $profile_id;
			}
		}

		if ( ! empty( $_REQUEST['profiles'][$poll_id] ) )
		{
			foreach ( (array) $profiles_RET as $profile )
			{
				$profile_id = $profile['ID'];

				if ( ! empty( $_REQUEST['profiles'][$poll_id][$profile_id] ) )
				{
					$_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'] .= ',' . $profile_id;
				}
			}
		}

		if ( ! empty( $_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'] ) )
		{
			$_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'] .= ',';
		}
	}
}

if ( $_REQUEST['modfunc'] === 'update'
	&& $_REQUEST['values']
	&& $_POST['values']
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		// FJ fix SQL bug invalid sort order.

		if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
		{
			if ( $id !== 'new' )
			{
				$sql = "UPDATE PORTAL_POLLS SET ";
				$sql_question = "UPDATE PORTAL_POLL_QUESTIONS SET ";

				$sql_questions = array();
				$id_questions = array();

				foreach ( (array) $columns as $column => $value )
				{
					if ( is_array( $value ) )
					{
						$id_questions[] = $column;
						$sql_question_cols = '';

						foreach ( (array) $value as $col => $val )
						{
							$sql_question_cols .= $col . "='" . $val . "',";
						}

						$sql_questions[] = $sql_question . $sql_question_cols;
					}
					else
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";
				DBQuery( $sql );

				$q = 0;

				foreach ( (array) $sql_questions as $sql_question )
				{
					$sql_question = mb_substr( $sql_question, 0, -1 ) . " WHERE ID='" . $id_questions[$q] . "'";
					DBQuery( $sql_question );
					$q++;
				}
			}

			// New: check for Title.
			elseif ( $columns['TITLE'] )
			{
				foreach ( array( 'admin', 'teacher', 'parent' ) as $profile_id )
				{
					if ( isset( $_REQUEST['profiles']['new'][$profile_id] )
						&& $_REQUEST['profiles']['new'][$profile_id] )
					{
						$_REQUEST['values']['new']['PUBLISHED_PROFILES'] .= $profile_id . ',';
					}
				}

				foreach ( (array) $profiles_RET as $profile )
				{
					$profile_id = $profile['ID'];

					if ( isset( $_REQUEST['profiles']['new'][$profile_id] )
						&& $_REQUEST['profiles']['new'][$profile_id] )
					{
						$_REQUEST['values']['new']['PUBLISHED_PROFILES'] .= $profile_id . ',';
					}
				}

				$columns['PUBLISHED_PROFILES'] = $_REQUEST['values']['new']['PUBLISHED_PROFILES'] ?
				',' . $_REQUEST['values']['new']['PUBLISHED_PROFILES'] :
				'';

				$sql = "INSERT INTO PORTAL_POLLS ";
				$sql_question = "INSERT INTO PORTAL_POLL_QUESTIONS ";
				$fields = 'ID,SCHOOL_ID,SYEAR,PUBLISHED_DATE,PUBLISHED_USER,';

				$portal_poll_id = DBSeqNextID( 'PORTAL_POLLS_SEQ' );
				//$values = db_seq_nextval('PORTAL_POLLS_SEQ').",'".UserSchool()."','".UserSyear()."',CURRENT_TIMESTAMP,'".User('STAFF_ID')."',";
				$values = $portal_poll_id . ",'" . UserSchool() . "','" . UserSyear() . "',CURRENT_TIMESTAMP,'" . User( 'STAFF_ID' ) . "',";

				$go = 0;
				$sql_questions = array();

				foreach ( (array) $columns as $column => $value )
				{
					if ( ! empty( $value ) || $value == '0' )
					{
						if ( mb_strpos( $column, 'new' ) !== false )
						{
							$go_question = 0;
							$fields_question = 'ID,PORTAL_POLL_ID,';

							$portal_poll_question_id = DBSeqNextID( 'PORTAL_POLL_QUESTIONS_SEQ' );
							$values_question = $portal_poll_question_id . "," . $portal_poll_id . ",";

							foreach ( (array) $value as $col => $val )
							{
								if ( $val )
								{
									$fields_question .= $col . ',';
									$values_question .= "'" . $val . "',";
									$go_question = true;
								}
							}

							if ( $go_question )
							{
								$sql_questions[] = $sql_question . '(' . mb_substr( $fields_question, 0, -1 ) . ') values(' . mb_substr( $values_question, 0, -1 ) . ')';
							}
						}
						else
						{
							$fields .= DBEscapeIdentifier( $column ) . ',';
							$values .= "'" . $value . "',";
							$go = true;
						}
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				if ( $go )
				{
					DBQuery( $sql );

					foreach ( (array) $sql_questions as $sql_question )
					{
						DBQuery( $sql_question );
					}
				}
			}
		}
		else
		{
			$error[] = _( 'Please enter a valid Sort Order.' );
		}
	}

	// Unset modfunc & values & profiles & redirect URL.
	RedirectURL( array( 'modfunc', 'values', 'profiles' ) );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Poll' ) ) )
	{
		$delete_sql = "DELETE FROM PORTAL_POLLS WHERE ID='" . $_REQUEST['id'] . "';";
		$delete_sql .= "DELETE FROM PORTAL_POLL_QUESTIONS WHERE PORTAL_POLL_ID='" . $_REQUEST['id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$sql_questions = "SELECT ppq.ID,ppq.PORTAL_POLL_ID,ppq.OPTIONS,ppq.VOTES,ppq.QUESTION,ppq.TYPE FROM PORTAL_POLL_QUESTIONS ppq, PORTAL_POLLS pp WHERE pp.SCHOOL_ID='" . UserSchool() . "' AND pp.SYEAR='" . UserSyear() . "' AND pp.ID=ppq.PORTAL_POLL_ID ORDER BY ppq.ID";
	$QI_questions = DBQuery( $sql_questions );
	$questions_RET = DBGet( $QI_questions, array( 'OPTIONS' => '_makeOptionsInput' ) );

	$sql = "SELECT pp.ID,pp.SORT_ORDER,pp.TITLE,'See_PORTAL_POLL_QUESTIONS' AS OPTIONS,pp.VOTES_NUMBER,pp.START_DATE,pp.END_DATE,pp.PUBLISHED_PROFILES,pp.STUDENTS_TEACHER_ID,
	CASE WHEN pp.END_DATE IS NOT NULL AND pp.END_DATE<CURRENT_DATE THEN 'Y' ELSE NULL END AS EXPIRED
	FROM PORTAL_POLLS pp
	WHERE pp.SCHOOL_ID='" . UserSchool() . "'
	AND pp.SYEAR='" . UserSyear() . "'
	ORDER BY EXPIRED DESC,pp.SORT_ORDER,pp.PUBLISHED_DATE DESC";

	$QI = DBQuery( $sql );
	$polls_RET = DBGet( $QI, array( 'TITLE' => '_makeTextInput', 'OPTIONS' => '_makeOptionsInputs', 'VOTES_NUMBER' => '_makePollVotes', 'SORT_ORDER' => '_makeTextInput', 'START_DATE' => 'makePublishing' ) );

	$columns = array( 'TITLE' => _( 'Title' ), 'OPTIONS' => _( 'Poll' ), 'VOTES_NUMBER' => _( 'Results' ), 'SORT_ORDER' => _( 'Sort Order' ), 'START_DATE' => _( 'Publishing Options' ) );
	//,'START_TIME' => 'Start Time','END_TIME' => 'End Time'
	$link['add']['html'] = array( 'TITLE' => _makeTextInput( '', 'TITLE' ), 'OPTIONS' => _makeOptionsInputs( '', 'OPTIONS' ), 'VOTES_NUMBER' => _makePollVotes( '', 'VOTES_NUMBER' ), 'SHORT_NAME' => _makeTextInput( '', 'SHORT_NAME' ), 'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ), 'START_DATE' => makePublishing( '', 'START_DATE' ) );
	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = array( 'id' => 'ID' );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';
	DrawHeader( '', SubmitButton() );

	ListOutput( $polls_RET, $columns, 'Poll', 'Polls', $link );

	echo '<div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
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

	if ( $name !== 'TITLE' )
	{
		$extra = 'size=5 maxlength=10';
	}
	elseif ( $id !== 'new' )
	{
		$extra = 'required';
	}

	return TextInput(
		( $name == 'TITLE' && $THIS_RET['EXPIRED'] ?
			array( $value, '<span style="color:red">' . $value . '</span>' ) :
			$value ),
		'values[' . $id . '][' . $name . ']',
		'',
		$extra
	);
}

/**
 * @param $value
 * @param $name
 */
function _makeOptionsInput( $value, $name )
{
	global $THIS_RET, $portal_poll_id;
	static $OptionNb = 0;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
		$portal_poll_id = $THIS_RET['PORTAL_POLL_ID'];
	}
	else
	{
		$portal_poll_id = 'new';
		$id = 'new' . $OptionNb;
	}

	if ( $portal_poll_id == $old_portal_poll_id )
	{
		$OptionNb++;
	}

	$old_portal_poll_id = $portal_poll_id;

	$type_options = array( 'multiple_radio' => _( 'Select One from Options' ), 'multiple' => _( 'Select Multiple from Options' ) );

	return '<tr' . ( $portal_poll_id == 'new' ? ' id="newOption_0"' : '' ) . '><td>' .
	TextInput(
		$THIS_RET['QUESTION'],
		'values[' . $portal_poll_id . '][' . $id . '][QUESTION]',
		'',
		'maxlength=255 size=20'
	) . '</td><td>' .
	TextAreaInput(
		$value,
		'values[' . $portal_poll_id . '][' . $id . '][' . $name . ']',
		'',
		'rows=3 cols=20',
		true,
		'text'
	) . ( $portal_poll_id == 'new' ? '<br />' . _( '* one per line' ) : '' ) . '</td><td>' .
	SelectInput(
		$THIS_RET['TYPE'],
		'values[' . $portal_poll_id . '][' . $id . '][TYPE]',
		'',
		$type_options,
		false
	) . '</td></tr>';
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function _makeOptionsInputs( $value, $name )
{
	global $THIS_RET, $questions_RET;

	$value = '';

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];

		foreach ( $questions_RET as $question )
		{
			if ( $question['PORTAL_POLL_ID'] == $id )
			{
				$value .= $question['OPTIONS'];
			}
		}
	}
	else
	{
		$id = 'new';
		$value = _makeOptionsInput( '', 'OPTIONS' );
	}

	//FJ responsive rt td too large
	$return .= '<div id="divPollOptions' . $id . '" style="max-height: 350px; overflow-y: auto;" class="rt2colorBox">';

	if ( $id == 'new' )
	{
		$return .= '<script>
			function newOption()
			{
				var table = document.getElementById(\'newOptionsTable\');
				var nbOptions = (table.rows.length - 3);
				row = table.insertRow(2+nbOptions);
				// insert table cells to the new row
				var tr = document.getElementById(\'newOption_\'+nbOptions);
				row.setAttribute(\'id\', \'newOption_\'+(nbOptions+1));
				for (i = 0; i < 3; i++) {
					createCell(row.insertCell(i), tr, i, nbOptions+1);
				}
			}
			// fill the cells
			function createCell(cell, tr, i, newId) {
				cell.innerHTML = tr.cells[i].innerHTML;
				reg = new RegExp(\'new\' + (newId-1),\'g\'); //g for global string
				cell.innerHTML = cell.innerHTML.replace(reg, \'new\'+newId);
			}
		</script>';
		$return .= '<table class="widefat" id="newOptionsTable"><tr><td><b>' . _( 'Question' ) . '</b></td><td><b>' . _( 'Options' ) . '</b></td><td><b>' . _( 'Data Type' ) . '</b></td></tr>' . $value . '<tr><td colspan="3" class="align-right"><a href="#" onclick="newOption();return false;">' . button( 'add' ) . ' ' . _( 'New Question' ) . '</a></tr></table>';
	}
	else
	{
		$return .= '<table class="widefat"><tr><td><b>' . _( 'Question' ) . '</b></td><td><b>' . _( 'Options' ) . '</b></td><td><b>' . _( 'Data Type' ) . '</b></td></tr>' . $value . '</table>';
	}

	$return .= '</div>';

	return $return;
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function _makePollVotes( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['ID'] )
	{
		$poll_id = $THIS_RET['ID'];
		$poll_questions_RET = DBGet( "SELECT QUESTION, VOTES, OPTIONS FROM PORTAL_POLL_QUESTIONS WHERE PORTAL_POLL_ID='" . $poll_id . "'" );

		$display_votes = DBGetOne( "SELECT DISPLAY_VOTES
			FROM PORTAL_POLLS
			WHERE ID='" . $poll_id . "'" );

		$checkbox = CheckboxInput(
			$display_votes,
			'values[' . $poll_id . '][DISPLAY_VOTES]',
			_( 'Results Display' )
		);

		if ( empty( $value ) )
		{
			return $checkbox;
		}

		return '<div>' . $checkbox . '</div><div style="float:left;">' .
		PortalPollsVotesDisplay( $poll_id, true, $poll_questions_RET, $value ) .
			'</div>';
	}

	return CheckboxInput( '', "values[new][DISPLAY_VOTES]", _( 'Results Display' ), '', true );
}
