<?php
//FJ Portal Polls inspired by Portal Notes
require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';

DrawHeader( ProgramTitle() );

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

$profiles_RET = DBGet( "SELECT ID,TITLE FROM user_profiles ORDER BY ID" );

if ( $_REQUEST['modfunc'] === 'update'
	&& (  ( $_REQUEST['profiles']
		&& $_POST['profiles'] )
		|| ( $_REQUEST['values']
			&& $_POST['values'] ) )
	&& AllowEdit() )
{
	$polls_RET = DBGet( "SELECT ID
		FROM portal_polls
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	foreach ( (array) $polls_RET as $poll_id )
	{
		$poll_id = $poll_id['ID'];
		$_REQUEST['values'][$poll_id]['PUBLISHED_PROFILES'] = '';

		foreach ( [ 'admin', 'teacher', 'parent' ] as $profile_id )
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
				$sql = "UPDATE portal_polls SET ";
				$sql_question = "UPDATE portal_poll_questions SET ";

				$sql_questions = [];
				$id_questions = [];

				foreach ( (array) $columns as $column => $value )
				{
					if ( is_array( $value ) )
					{
						$id_questions[] = $column;
						$sql_question_cols = '';

						if ( isset( $value['OPTIONS'] )
							&& $value['OPTIONS'] )
						{
							// @since 6.0 Trim select Options.
							$value['OPTIONS'] = trim( $value['OPTIONS'] );
						}

						foreach ( (array) $value as $col => $val )
						{
							$sql_question_cols .= DBEscapeIdentifier( $col ) . "='" . $val . "',";
						}

						$sql_questions[] = $sql_question . $sql_question_cols;
					}
					else
					{
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . (int) $id . "'";
				DBQuery( $sql );

				$q = 0;

				foreach ( (array) $sql_questions as $sql_question )
				{
					$sql_question = mb_substr( $sql_question, 0, -1 ) . " WHERE ID='" . (int) $id_questions[$q] . "'";
					DBQuery( $sql_question );
					$q++;
				}
			}

			// New: check for Title.
			elseif ( $columns['TITLE'] )
			{
				$_REQUEST['values']['new']['PUBLISHED_PROFILES'] = '';

				foreach ( [ 'admin', 'teacher', 'parent' ] as $profile_id )
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

				$sql = "INSERT INTO portal_polls ";
				$fields = 'SCHOOL_ID,SYEAR,PUBLISHED_USER,';

				$values = "'" . UserSchool() . "','" . UserSyear() . "','" . User( 'STAFF_ID' ) . "',";

				$go = 0;
				$sql_question = "INSERT INTO portal_poll_questions ";
				$sql_questions = [];

				foreach ( (array) $columns as $column => $value )
				{
					if ( ! empty( $value ) || $value == '0' )
					{
						if ( mb_strpos( $column, 'new' ) !== false )
						{
							if ( ! $value['QUESTION'] )
							{
								continue;
							}

							$go_question = 0;
							$fields_question = 'PORTAL_POLL_ID,';

							// Substitution code so we can replace with actual Poll ID
							// when we retrieve it using DBLastInsertID(), see below.
							$values_question = "__PORTAL_POLL_ID__,";

							foreach ( (array) $value as $col => $val )
							{
								if ( $val )
								{
									$fields_question .= DBEscapeIdentifier( $col ) . ',';
									$values_question .= "'" . $val . "',";
									$go_question = true;
								}
							}

							if ( $go_question )
							{
								$sql_questions[] = $sql_question . '(' . mb_substr( $fields_question, 0, -1 ) .
									') values(' . mb_substr( $values_question, 0, -1 ) . ')';
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

					$portal_poll_id = DBLastInsertID();

					foreach ( (array) $sql_questions as $sql_question )
					{
						// Replace substitution code with actual Poll ID.
						$sql_question_with_poll_id = str_replace(
							'__PORTAL_POLL_ID__',
							"'" . $portal_poll_id . "'",
							$sql_question
						);

						DBQuery( $sql_question_with_poll_id );
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
	RedirectURL( [ 'modfunc', 'values', 'profiles' ] );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Poll' ) ) )
	{
		$delete_sql = "DELETE FROM portal_polls WHERE ID='" . (int) $_REQUEST['id'] . "';";
		$delete_sql .= "DELETE FROM portal_poll_questions WHERE PORTAL_POLL_ID='" . (int) $_REQUEST['id'] . "';";

		DBQuery( $delete_sql );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( [ 'modfunc', 'id' ] );
	}
}

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$questions_RET = DBGet( "SELECT ppq.ID,ppq.PORTAL_POLL_ID,ppq.OPTIONS,ppq.VOTES,ppq.QUESTION,ppq.TYPE
		FROM portal_poll_questions ppq,portal_polls pp
		WHERE pp.SCHOOL_ID='" . UserSchool() . "'
		AND pp.SYEAR='" . UserSyear() . "'
		AND pp.ID=ppq.PORTAL_POLL_ID
		ORDER BY ppq.ID", [ 'OPTIONS' => '_makeOptionsInput' ] );

	$polls_RET = DBGet(
		"SELECT pp.ID,pp.SORT_ORDER,pp.TITLE,'See_portal_poll_questions' AS OPTIONS,
			pp.VOTES_NUMBER,pp.START_DATE,pp.END_DATE,pp.PUBLISHED_PROFILES,pp.STUDENTS_TEACHER_ID,
			CASE WHEN pp.END_DATE IS NOT NULL AND pp.END_DATE<CURRENT_DATE THEN 'Y' ELSE NULL END AS EXPIRED
			FROM portal_polls pp
			WHERE pp.SCHOOL_ID='" . UserSchool() . "'
			AND pp.SYEAR='" . UserSyear() . "'
			ORDER BY EXPIRED DESC,pp.SORT_ORDER IS NULL,pp.SORT_ORDER,pp.CREATED_AT DESC",
		[
			'TITLE' => '_makeTextInput',
			'OPTIONS' => '_makeOptionsInputs',
			'VOTES_NUMBER' => '_makePollVotes',
			'SORT_ORDER' => '_makeTextInput',
			'START_DATE' => 'makePublishing',
		]
	);

	$columns = [
		'TITLE' => _( 'Title' ),
		'OPTIONS' => _( 'Poll' ),
		'VOTES_NUMBER' => _( 'Results' ),
		'SORT_ORDER' => _( 'Sort Order' ),
		'START_DATE' => _( 'Publishing Options' ),
	];
	//,'START_TIME' => 'Start Time','END_TIME' => 'End Time'

	$link['add']['html'] = [
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'OPTIONS' => _makeOptionsInputs( '', 'OPTIONS' ),
		'VOTES_NUMBER' => _makePollVotes( '', 'VOTES_NUMBER' ),
		'SHORT_NAME' => _makeTextInput( '', 'SHORT_NAME' ),
		'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		'START_DATE' => makePublishing( '', 'START_DATE' ),
	];

	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = [ 'id' => 'ID' ];

	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update' ) . '" method="POST">';
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

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	$extra = '';

	if ( $name === 'SORT_ORDER' )
	{
		$extra = ' type="number" min="-9999" max="9999"';
	}
	elseif ( $name !== 'TITLE' )
	{
		$extra = 'size=5 maxlength=10';
	}
	elseif ( $id !== 'new' )
	{
		$extra = 'required';
	}

	return TextInput(
		( $name == 'TITLE' && ! empty( $THIS_RET['EXPIRED'] ) ?
			[ $value, '<span style="color:red">' . $value . '</span>' ] :
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
	static $option_nb = 1;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
		$portal_poll_id = $THIS_RET['PORTAL_POLL_ID'];
	}
	else
	{
		$portal_poll_id = 'new';
		$id = 'new' . $option_nb;
	}

	$type_options = [ 'multiple_radio' => _( 'Select One from Options' ), 'multiple' => _( 'Select Multiple from Options' ) ];

	return '<tr' . ( $portal_poll_id == 'new' ? ' id="new-option-1"' : '' ) . '><td><div>' .
	TextInput(
		issetVal( $THIS_RET['QUESTION'], '' ),
		'values[' . $portal_poll_id . '][' . $id . '][QUESTION]',
		_( 'Title' ),
		'maxlength=255 size=20' . ( $portal_poll_id == 'new' ? '' : ' required' )
	) . '</div><div>' .
	TextAreaInput(
		$value,
		'values[' . $portal_poll_id . '][' . $id . '][' . $name . ']',
		_( 'Options' ) .
		( $portal_poll_id == 'new' ?
			// Do not use tooltip inside overflow, it sticks due to its absolute position.
			' - <span class="size-1">' . _( 'One per line' ) . '</span>' :
			''
		),
		'rows=3 cols=20',
		true,
		'text'
	) . '</div><div>' .
	SelectInput(
		issetVal( $THIS_RET['TYPE'] ),
		'values[' . $portal_poll_id . '][' . $id . '][TYPE]',
		_( 'Data Type' ),
		$type_options,
		false
	) . '</div></td></tr>';
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

	if ( ! empty( $THIS_RET['ID'] ) )
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
	$return = '<div id="divPollOptions' . $id . '" style="max-height: 350px; overflow-y: auto;" class="rt2colorBox">';

	if ( $id == 'new' )
	{
		ob_start();
		?>
		<script>
			var portalPollNewOption = function()
			{
				var table = document.getElementById('new-options-table'),
					nbOptions = (table.rows.length - 1),
					row = table.insertRow(nbOptions);

				// Fill the cells.
				function createCell(cell, tr, newId) {
					cell.innerHTML = tr.cells[0].innerHTML;
					reg = new RegExp('new' + (newId - 1),'g'); //g for global string
					cell.innerHTML = cell.innerHTML.replace(reg, 'new' + newId);
				}

				// Insert table cells to the new row.
				var tr = document.getElementById('new-option-' + nbOptions);

				row.setAttribute('id', 'new-option-' + (nbOptions + 1));

				createCell(row.insertCell(0), tr, nbOptions + 1);
			};
		</script>
		<?php
		$return .= ob_get_clean();

		$return .= '<table class="widefat" id="new-options-table">' .
			$value .
			'<tr><td class="align-right"><a href="#" onclick="portalPollNewOption();return false;">' .
			button( 'add' ) . ' ' . _( 'New Question' ) . '</a></tr></table>';
	}
	else
	{
		$return .= '<table class="widefat">' . $value . '</table>';
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

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$poll_id = $THIS_RET['ID'];
		$poll_questions_RET = DBGet( "SELECT QUESTION, VOTES, OPTIONS FROM portal_poll_questions WHERE PORTAL_POLL_ID='" . (int) $poll_id . "'" );

		$display_votes = DBGetOne( "SELECT DISPLAY_VOTES
			FROM portal_polls
			WHERE ID='" . (int) $poll_id . "'" );

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
