<?php
/**
 * Portal Polls and Portal Notes functions.
 *
 * @todo Format code!
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

// Portal Notes attached files Path
// You can override the Path definition in the config.inc.php file

if ( ! isset( $PortalNotesFilesPath ) )
{
	$PortalNotesFilesPath = 'assets/PortalNotesFiles/';
}

//FJ Portal Polls functions

function PortalPollsVote( $poll_id, $votes_array )
{
	// Get poll:
	$poll_RET = DBGet( "SELECT EXCLUDED_USERS, VOTES_NUMBER, DISPLAY_VOTES
		FROM PORTAL_POLLS
		WHERE ID='" . $poll_id . "'" );

	$poll_questions_RET = DBGet( "SELECT ID, QUESTION, OPTIONS, VOTES
		FROM PORTAL_POLL_QUESTIONS
		WHERE PORTAL_POLL_ID='" . $poll_id . "'
		ORDER BY ID" );

	if ( ! $poll_RET || ! $poll_questions_RET )
	{
		// Should never be displayed, so do not translate.
		return ErrorMessage( array( 'Poll does not exist' ) );
	}

	// Add user to excluded users list, format = '|[profile_id]:[user_id]'.
	$excluded_user = GetPortalPollUser();

	if ( ! $excluded_user
		|| mb_strpos( $poll_RET[1]['EXCLUDED_USERS'] . '|', $excluded_user . '|' ) !== false )
	{
		// Should never be displayed, so do not translate.
		return ErrorMessage( array( 'User excluded from this poll' ) );
	}

	$excluded_users = $poll_RET[1]['EXCLUDED_USERS'] . $excluded_user;

	$poll_questions_updated = PortalPollsSaveVotes( $poll_questions_RET, $votes_array );

	// Submit query.
	DBQuery( "UPDATE PORTAL_POLLS
		SET EXCLUDED_USERS='" . $excluded_users . "',
		VOTES_NUMBER=(SELECT CASE WHEN VOTES_NUMBER ISNULL THEN 1 ELSE VOTES_NUMBER+1 END
			FROM PORTAL_POLLS
			WHERE ID='" . $poll_id . "')
		WHERE ID='" . $poll_id . "'" );

	return PortalPollsVotesDisplay(
		$poll_id,
		$poll_RET[1]['DISPLAY_VOTES'],
		$poll_questions_updated,
		( empty( $poll_RET[1]['VOTES_NUMBER'] ) ? 1 : $poll_RET[1]['VOTES_NUMBER'] + 1 ),
		true
	);
}

/**
 * function called by PortalPollsVote()
 * save Votes to PORTAL_POLL_QUESTIONS table
 *
 * @return $poll_questions_RET array updated with Votes
 */
function PortalPollsSaveVotes( $poll_questions_RET, $votes_array )
{
	// Add votes.
	$voted_array = array();

	foreach ( (array) $poll_questions_RET as $key => $question )
	{
		if ( ! empty( $question['VOTES'] ) )
		{
			$voted_array[$question['ID']] = explode( '||', $question['VOTES'] );

			if ( is_array( $votes_array[$question['ID']] ) ) // Multiple.
			{
				foreach ( (array) $votes_array[$question['ID']] as $checked_box )
				{
					$voted_array[$question['ID']][$checked_box]++;
				}
			}
			else // Multiple_radio.
			{
				$voted_array[$question['ID']][$votes_array[$question['ID']]]++;
			}
		}
		else // First vote.
		{
			$voted_array[$question['ID']] = array();

			$options_array = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $question['OPTIONS'] ) );

			if ( is_array( $votes_array[$question['ID']] ) ) // Multiple.
			{
				foreach ( (array) $options_array as $option_nb => $option_label )
				{
					$voted_array[$question['ID']][$option_nb] = 0;
				}

				foreach ( (array) $votes_array[$question['ID']] as $checked_box )
				{
					$voted_array[$question['ID']][$checked_box]++;
				}
			}
			else // Multiple_radio.
			{
				foreach ( (array) $options_array as $option_nb => $option_label )
				{
					$voted_array[$question['ID']][$option_nb] = ( $votes_array[$question['ID']] == $option_nb ? 1 : 0 );
				}
			}
		}

		$voted_array[$question['ID']] = implode( '||', $voted_array[$question['ID']] );

		// Submit query.
		DBQuery( "UPDATE PORTAL_POLL_QUESTIONS
			SET VOTES='" . $voted_array[$question['ID']] . "'
			WHERE ID='" . $question['ID'] . "'" );

		// Update the $poll_questions_RET array with Votes.
		$poll_questions_RET[$key]['VOTES'] = $voted_array[$question['ID']];
	}

	return $poll_questions_RET;
}

/**
 * @param $value
 * @param $name
 */
function PortalPollsDisplay( $value, $name )
{
	global $THIS_RET;

	$poll_id = $THIS_RET['ID'];

	// Get poll:
	$poll_RET = DBGet( "SELECT EXCLUDED_USERS,VOTES_NUMBER,DISPLAY_VOTES
		FROM PORTAL_POLLS
		WHERE ID='" . $poll_id . "'" );

	require_once 'ProgramFunctions/Linkify.fnc.php';

	$poll_questions_RET = DBGet( "SELECT ID,QUESTION,OPTIONS,TYPE,VOTES
		FROM PORTAL_POLL_QUESTIONS
		WHERE PORTAL_POLL_ID='" . $poll_id . "'
		ORDER BY ID", array( 'OPTIONS' => 'Linkify' ) );

	if ( ! $poll_RET || ! $poll_questions_RET )
	{
		// Should never be displayed, so do not translate.
		return ErrorMessage( array( 'Poll does not exist' ) );
	}

	$excluded_user = GetPortalPollUser();

	if ( ! $excluded_user )
	{
		// Should never be displayed, so do not translate.
		return ErrorMessage( array( 'User not logged in' ) );
	}

	// Check if user is in excluded users list (format = '|[profile_id]:[user_id]').

	if ( mb_strpos( $poll_RET[1]['EXCLUDED_USERS'] . '|', $excluded_user . '|' ) !== false )
	{
		// User already voted, display votes.
		return PortalPollsVotesDisplay(
			$poll_id,
			$poll_RET[1]['DISPLAY_VOTES'],
			$poll_questions_RET,
			$poll_RET[1]['VOTES_NUMBER']
		);
	}

	return PortalPollForm(
		$poll_id,
		$poll_questions_RET
	);
}

/**
 * Get poll user, using the excluded users format:
 * |[profile_id]:[user_id]
 *
 * @since 3.4
 *
 * @return string User, empty if no user ID.
 */
function GetPortalPollUser()
{
	$profile_id = User( 'PROFILE_ID' );

	if ( $profile_id !== '0' ) // FJ call right Student/Staff ID.
	{
		$user_id = $_SESSION['STAFF_ID'];
	}
	else
	{
		$user_id = $_SESSION['STUDENT_ID'];
	}

	if ( ! $user_id )
	{
		return '';
	}

	return '|' . $profile_id . ':' . $user_id;
}

/**
 * Function called by PortalPollsDisplay()
 * generates the Portal Poll's HTML form
 *
 * @param  integer $poll_id            Poll ID.
 * @param  array   $poll_questions_RET Poll questions.
 * @return string  Poll HTML form.
 */
function PortalPollForm( $poll_id, $poll_questions_RET )
{
	$poll_form = '';

	//FJ responsive rt td too large

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$poll_form .= '<div id="divPortalPoll' . $poll_id . '" class="divPortalPoll rt2colorBox">';
	}

	$poll_form .= '<form method="POST" id="formPortalPoll' . $poll_id . '"
		action="ProgramFunctions/PortalPollsNotes.fnc.php"
		target="divPortalPoll' . $poll_id . '">
	<table class="width-100p widefat">';

	foreach ( (array) $poll_questions_RET as $question )
	{
		$poll_form .= '<tr><td class="valign-top"><b>' . $question['QUESTION'] . '</b></td>
		<td><table class="width-100p cellspacing-0">';

		$options_array = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $question['OPTIONS'] ) );

		$checked = true;

		foreach ( $options_array as $option_nb => $option_label )
		{
			if ( $question['TYPE'] == 'multiple_radio' )
			{
				$poll_form .= '<tr><td>
					<label>
					<input type="radio" name="votes[' . $poll_id . '][' . $question['ID'] . ']" value="' .
					$option_nb . '" ' . ( $checked ? 'checked' : '' ) . ' />&nbsp;' .
					$option_label .
					'</label>
					</td></tr>' . "\n";
			}
			else // Multiple.
			{
				$poll_form .= '<tr><td>
					<label>
					<input type="checkbox" name="votes[' . $poll_id . '][' . $question['ID'] . '][]" value="' .
					$option_nb . '" />&nbsp;' . $option_label .
					'</label>
					</td></tr>' . "\n";
			}

			$checked = false;
		}

		$poll_form .= '</table></td></tr>';
	}

	$poll_form .= '</td></tr></table><p>' . Buttons( _( 'Submit' ) ) . '</p></form>';

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$poll_form .= '</div>';
	}

	return $poll_form;
}

/**
 * @param $poll_id
 * @param $display_votes
 * @param $poll_questions_RET
 * @param $votes_number
 * @param $js_included_is_voting
 * @return mixed
 */
function PortalPollsVotesDisplay( $poll_id, $display_votes, $poll_questions_RET, $votes_number, $js_included_is_voting = false )
{
	if ( ! $display_votes )
	{
		$note[] = button( 'check', '', '', 'bigger' ) .
		'&nbsp;' . _( 'Poll completed' );

		return ErrorMessage( $note, 'note' );
	}

	$votes_display = '';

	// FJ responsive rt td too large.

	if ( ! $js_included_is_voting )
	{
		$votes_display .= '<div id="divPortalPoll' . $poll_id . '" class="divPortalPoll rt2colorBox">' . "\n";
	}

	foreach ( (array) $poll_questions_RET as $question )
	{
		$total_votes = 0;

		// Question.
		$votes_display .= '<p><b>' . $question['QUESTION'] . '</b></p>
			<table class="widefat col1-align-right">' . "\n";

		// Votes.
		$votes_array = explode( '||', $question['VOTES'] );

		foreach ( (array) $votes_array as $votes )
		{
			$total_votes += $votes;
		}

		// Options.
		$options_array = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $question['OPTIONS'] ) );

		$options_array_count = count( $options_array );

		for ( $i = 0; $i < $options_array_count; $i++ )
		{
			$percent = round(  ( $votes_array[$i] / $total_votes ) * 100 );

			$votes_display .= '<tr>
				<td>' . $options_array[$i] . '</td>
				<td><div class="bar" style="width:' . $percent . 'px;">' . $percent . '</div></td>
				<td><b> ' . $percent . '%</b></td>
			</tr>' . "\n";
		}

		$votes_display .= '</table>' . "\n";
	}

	$votes_display .= '<p>' . _( 'Total Participants' ) . ': ' . $votes_number . '</p>';

	if ( ! $js_included_is_voting )
	{
		$votes_display .= '</div>';
	}

	return $votes_display;
}

// AJAX vote call:

if ( isset( $_POST['votes'] )
	&& is_array( $_POST['votes'] ) )
{
	if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] )
		|| $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest' )
	{
		die( 'Error: no AJAX' );
	}

	chdir( '../' );

	require_once 'Warehouse.php';

	foreach ( (array) $_POST['votes'] as $poll_id => $votes_array )
	{
		if ( ! empty( $votes_array ) )
		{
			echo PortalPollsVote( $poll_id, $votes_array );
			break;
		}
	}

	exit();
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function makePublishing( $value, $name )
{
	global $THIS_RET;
	static $profiles = null;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	//FJ responsive rt td too large
	$return = '<div id="divPublishing' . $id . '" class="rt2colorBox">' . "\n";

	//FJ remove LO_field
	$return .= '<table class="widefat"><tr><td><b>' . _( 'Visible Between' ) . ':</b><br />';
	$return .= DateInput( $value, 'values[' . $id . '][' . $name . ']' ) . ' ' . _( 'to' ) . ' ';
	$return .= DateInput( $THIS_RET['END_DATE'], 'values[' . $id . '][END_DATE]' ) . '</td></tr>';

	$return .= '<tr><td style="padding:0;">';

	if ( is_null( $profiles ) )
	{
		$profiles_RET = DBGet( "SELECT ID,TITLE FROM USER_PROFILES ORDER BY ID" );

		//add Profiles with Custom permissions to profiles list
		$profiles = array_merge( array(
			array( 'ID' => 'admin', 'TITLE' => _( 'Administrator w/Custom' ) ),
			array( 'ID' => 'teacher', 'TITLE' => _( 'Teacher w/Custom' ) ),
			array( 'ID' => 'parent', 'TITLE' => _( 'Parent w/Custom' ) ),
		), $profiles_RET );
	}

	$return .= makePublishingVisibleTo( $profiles, $THIS_RET, $id );

	$return .= '</td></tr></table>';

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		$return .= '</div>';
	}

	return $return;
}

/**
 * function called by makePublishing()
 * generates the "Visible To" part of the Publishing options
 *
 * @return $visibleTo HTML form
 */
function makePublishingVisibleTo( $profiles, $THIS_RET, $id )
{
	$visibleTo = '<table class="width-100p cellspacing-0">
	<tr>
		<td colspan="2"><b>' . _( 'Visible To' ) . ':</b></td>
	</tr>
	<tr class="st">';

	// FJ Portal Polls add students teacher.
	$teachers_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
	FROM STAFF
	WHERE (SCHOOLS IS NULL OR STRPOS(SCHOOLS,'," . UserSchool() . ",')>0)
	AND SYEAR='" . UserSyear() . "'
	AND PROFILE='teacher'
	ORDER BY LAST_NAME,FIRST_NAME" );

	$teachers = array();

	foreach ( (array) $teachers_RET as $teacher )
	{
		$teachers[$teacher['STAFF_ID']] = $teacher['FULL_NAME'];
	}

	$i = 0;

	foreach ( (array) $profiles as $profile )
	{
		$i++;
		$checked = mb_strpos( $THIS_RET['PUBLISHED_PROFILES'], ',' . $profile['ID'] . ',' ) !== false;

		$visibleTo .= '<td>' . CheckboxInput( $checked, 'profiles[' . $id . '][' . $profile['ID'] . ']', _( $profile['TITLE'] ), '', true );

		//FJ Portal Polls add students teacher

		if ( $profile['ID'] === '0' && $_REQUEST['modname'] == 'School_Setup/PortalPolls.php' ) //student & verify this is not a Portal Note!
		{
			$visibleTo .= ': ' . SelectInput(
				$THIS_RET['STUDENTS_TEACHER_ID'],
				'values[' . $id . '][STUDENTS_TEACHER_ID]',
				_( 'Limit to Teacher' ),
				$teachers,
				'N/A',
				'',
				true
			);
		}

		$visibleTo .= '</td>';

		if ( $i % 2 == 0 && $i != count( $profiles ) )
		{
			$visibleTo .= '</tr><tr class="st">';
		}
	}

	for ( ; $i % 2 != 0; $i++ )
	{
		$visibleTo .= '<td>&nbsp;</td>';
	}

	$visibleTo .= '</tr>';

	if ( $_REQUEST['modname'] == 'School_Setup/PortalNotes.php' )
	{
		//hook
		$args = $id;
		do_action( 'School_Setup/PortalNotes.php|portal_note_field', $args );
	}

	$visibleTo .= '</table>';

	return $visibleTo;
}

//FJ file attached to portal notes
/**
 * @param $value
 * @param $name
 * @return mixed
 */
function makeFileAttached( $value, $name )
{
	global $THIS_RET, $PortalNotesFilesPath;
	static $filesAttachedCount = 0;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];

		if ( empty( $value ) )
		{
			$return = '&nbsp;';
		}
		else
		{
			$filesAttachedCount++;

			//FJ colorbox
			$view_online = '<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/visualize.png" class="button bigger" /> ' . _( 'View Online' ) . '';

			$download = '<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/download.png" class="button bigger" /> ' . _( 'Download' ) . '';

			if ( filter_var( $value, FILTER_VALIDATE_URL ) !== false ) //embed link
			{
				$return = '<a href="' . $value . '" title="' . $value . '" class="colorboxiframe">' . $view_online . '</a>';
			}
			else
			{
				$return = '<a href="' . $value . '" title="' . str_replace( $PortalNotesFilesPath, '', $value ) . '" target="_blank">' . $download . '</a>';
			}
		}
	}
	else
	{
		$id = 'new';

		$return .= '<div id="divFileAttached' . $id . '" class="rt2colorBox">';
		$return .= '<div>
			<label>
				<input type="radio" name="values[new][FILE_OR_EMBED]" value="FILE">&nbsp;';
		$return .= FileInput( $name . '_FILE' );
		$return .= '</label>
		</div>';
		$return .= '<div style="float:left;">
			<label>
				<input type="radio" name="values[new][FILE_OR_EMBED]" value="EMBED" onclick="javascript:document.getElementById(\'values[new][' . $name . '_EMBED]\').focus();" />&nbsp;' .
		_( 'Embed Link' ) . ': <input type="text" id="values[new][' . $name . '_EMBED]" name="values[new][' . $name . '_EMBED]" size="14" placeholder="http://" />
			</label>
		</div></div>';
	}

	return $return;
}
