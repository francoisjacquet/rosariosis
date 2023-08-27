<?php
/**
 * Portal Polls and Portal Notes functions.
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
		FROM portal_polls
		WHERE ID='" . (int) $poll_id . "'" );

	$poll_questions_RET = DBGet( "SELECT ID, QUESTION, OPTIONS, VOTES
		FROM portal_poll_questions
		WHERE PORTAL_POLL_ID='" . (int) $poll_id . "'
		ORDER BY ID" );

	if ( ! $poll_RET || ! $poll_questions_RET )
	{
		// Should never be displayed, so do not translate.
		return ErrorMessage( [ 'Poll does not exist' ] );
	}

	// Add user to excluded users list, format = '|[profile_id]:[user_id]'.
	$excluded_user = GetPortalPollUser();

	if ( ! $excluded_user
		|| mb_strpos( $poll_RET[1]['EXCLUDED_USERS'] . '|', $excluded_user . '|' ) !== false )
	{
		// Should never be displayed, so do not translate.
		return ErrorMessage( [ 'User excluded from this poll' ] );
	}

	$excluded_users = $poll_RET[1]['EXCLUDED_USERS'] . $excluded_user;

	$poll_questions_updated = PortalPollsSaveVotes( $poll_questions_RET, $votes_array );

	// Submit query.
	DBQuery( "UPDATE portal_polls
		SET EXCLUDED_USERS='" . $excluded_users . "',
		VOTES_NUMBER=" . db_case( [ 'VOTES_NUMBER', "''", '1', 'VOTES_NUMBER+1' ] ) . "
		WHERE ID='" . (int) $poll_id . "'" );

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
 * save Votes to portal_poll_questions table
 *
 * @return $poll_questions_RET array updated with Votes
 */
function PortalPollsSaveVotes( $poll_questions_RET, $votes_array )
{
	// Add votes.
	$voted_array = [];

	foreach ( (array) $poll_questions_RET as $key => $question )
	{
		if ( ! empty( $question['VOTES'] ) )
		{
			$voted_array[$question['ID']] = explode( '||', $question['VOTES'] );

			if ( isset( $votes_array[$question['ID']] )
				&& is_array( $votes_array[$question['ID']] ) ) // Multiple.
			{
				foreach ( $votes_array[$question['ID']] as $checked_box )
				{
					$voted_array[$question['ID']][$checked_box]++;
				}
			}
			elseif ( isset( $votes_array[$question['ID']] ) ) // Multiple_radio.
			{
				$voted_array[$question['ID']][$votes_array[$question['ID']]]++;
			}
		}
		else // First vote.
		{
			$voted_array[$question['ID']] = [];

			$options_array = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $question['OPTIONS'] ) );

			if ( isset( $votes_array[$question['ID']] )
				&& is_array( $votes_array[$question['ID']] ) ) // Multiple.
			{
				foreach ( $options_array as $option_nb => $option_label )
				{
					$voted_array[$question['ID']][$option_nb] = 0;
				}

				foreach ( $votes_array[$question['ID']] as $checked_box )
				{
					$voted_array[$question['ID']][$checked_box]++;
				}
			}
			else // Multiple_radio.
			{
				foreach ( $options_array as $option_nb => $option_label )
				{
					$voted_array[$question['ID']][$option_nb] = ( isset( $votes_array[$question['ID']] ) && $votes_array[$question['ID']] == $option_nb ? 1 : 0 );
				}
			}
		}

		$voted_array[$question['ID']] = implode( '||', $voted_array[$question['ID']] );

		// Submit query.
		DBQuery( "UPDATE portal_poll_questions
			SET VOTES='" . $voted_array[$question['ID']] . "'
			WHERE ID='" . (int) $question['ID'] . "'" );

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
		FROM portal_polls
		WHERE ID='" . (int) $poll_id . "'" );

	require_once 'ProgramFunctions/Linkify.fnc.php';

	$poll_questions_RET = DBGet( "SELECT ID,QUESTION,OPTIONS,TYPE,VOTES
		FROM portal_poll_questions
		WHERE PORTAL_POLL_ID='" . (int) $poll_id . "'
		ORDER BY ID", [ 'OPTIONS' => 'Linkify' ] );

	if ( ! $poll_RET || ! $poll_questions_RET )
	{
		// Should never be displayed, so do not translate.
		return ErrorMessage( [ 'Poll does not exist' ] );
	}

	$excluded_user = GetPortalPollUser();

	if ( ! $excluded_user )
	{
		// Should never be displayed, so do not translate.
		return ErrorMessage( [ 'User not logged in' ] );
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

	$user_id = User( 'STAFF_ID' ) ? User( 'STAFF_ID' ) : UserStudentID();

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
		action="Modules.php?modname=misc/Portal.php&modfunc=poll_vote"
		target="divPortalPoll' . $poll_id . '">
	<table class="width-100p widefat">';

	foreach ( (array) $poll_questions_RET as $question )
	{
		$poll_form .= '<tr><td class="valign-top"><b>' . $question['QUESTION'] . '</b></td>
		<td><table class="width-100p cellspacing-0">';

		$options_array = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $question['OPTIONS'] ) );

		$checked = true;

		foreach ( $options_array as $option_nb => $option_label )
		{
			if ( $question['TYPE'] == 'multiple_radio' )
			{
				$name = 'votes[' . $poll_id . '][' . $question['ID'] . ']';

				$poll_form .= '<tr><td>
					<label>
					<input type="radio" name="' . AttrEscape( $name ) . '" value="' .
					AttrEscape( $option_nb ) . '" ' . ( $checked ? 'checked' : '' ) . '>&nbsp;' .
					$option_label .
					'</label>
					</td></tr>' . "\n";
			}
			else // Multiple.
			{
				$name = 'votes[' . $poll_id . '][' . $question['ID'] . '][]';

				$poll_form .= '<tr><td>
					<label>
					<input type="checkbox" name="' . AttrEscape( $name ) . '" value="' .
					AttrEscape( $option_nb ) . '">&nbsp;' . $option_label .
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
		return button( 'check' ) .
		'&nbsp;' . _( 'Poll completed' );
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

		foreach ( $votes_array as $votes )
		{
			$total_votes += $votes;
		}

		// Options.
		$options_array = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $question['OPTIONS'] ) );

		$options_array_count = count( $options_array );

		for ( $i = 0; $i < $options_array_count; $i++ )
		{
			$percent = 0;

			if ( $total_votes )
			{
				$percent = round( ( $votes_array[$i] / $total_votes ) * 100 );
			}

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

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function makePublishing( $value, $name )
{
	global $THIS_RET;
	static $profiles = null;

	if ( ! empty( $THIS_RET['ID'] ) )
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
	$return .= '<table class="widefat"><tr><td><b>' . _( 'Visible Between' ) . ':</b><br>';
	$return .= DateInput( $value, 'values[' . $id . '][' . $name . ']' ) . ' ' . _( 'to' ) . ' ';
	$return .= DateInput( issetVal( $THIS_RET['END_DATE'] ), 'values[' . $id . '][END_DATE]' ) . '</td></tr>';

	$return .= '<tr><td style="padding:0;">';

	if ( is_null( $profiles ) )
	{
		$profiles_RET = DBGet( "SELECT ID,TITLE FROM user_profiles ORDER BY ID" );

		$custom_permissions = [];

		$there_is_user_with_custom = function( $profile )
		{
			return (bool) DBGetOne( "SELECT 1 FROM staff
				WHERE PROFILE='" . DBEscapeString( $profile ) . "'
				AND PROFILE_ID IS NULL
				AND SYEAR='" . UserSyear() . "'" );
		};

		if ( $there_is_user_with_custom( 'admin' ) )
		{
			$custom_permissions[] = [ 'ID' => 'admin', 'TITLE' => _( 'Administrator w/Custom' ) ];
		}

		if ( $there_is_user_with_custom( 'teacher' ) )
		{
			$custom_permissions[] = [ 'ID' => 'teacher', 'TITLE' => _( 'Teacher w/Custom' ) ];
		}

		if ( $there_is_user_with_custom( 'parent' ) )
		{
			$custom_permissions[] = [ 'ID' => 'parent', 'TITLE' => _( 'Parent w/Custom' ) ];
		}

		// Add Profiles with Custom permissions to profiles list.
		$profiles = array_merge( $custom_permissions, $profiles_RET );
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
 * Function called by makePublishing()
 * generates the "Visible To" part of the Publishing options
 *
 * @todo Use a Multiple select input to gain space.
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

	// Portal Polls add students teacher.
	// @since 9.2.1 SQL replace use of STRPOS() with LIKE, compatible with MySQL.
	$teachers_RET = DBGet( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
	FROM staff
	WHERE (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)
	AND SYEAR='" . UserSyear() . "'
	AND PROFILE='teacher'
	ORDER BY FULL_NAME" );

	$teachers = [];

	foreach ( (array) $teachers_RET as $teacher )
	{
		$teachers[$teacher['STAFF_ID']] = $teacher['FULL_NAME'];
	}

	$i = 0;

	foreach ( (array) $profiles as $profile )
	{
		$i++;
		$checked = mb_strpos( issetVal( $THIS_RET['PUBLISHED_PROFILES'], '' ), ',' . $profile['ID'] . ',' ) !== false;

		$visibleTo .= '<td>' . CheckboxInput( $checked, 'profiles[' . $id . '][' . $profile['ID'] . ']', _( $profile['TITLE'] ), '', true );

		//FJ Portal Polls add students teacher

		if ( $profile['ID'] === '0' && $_REQUEST['modname'] == 'School_Setup/PortalPolls.php' ) //student & verify this is not a Portal Note!
		{
			$visibleTo .= ': ' . SelectInput(
				issetVal( $THIS_RET['STUDENTS_TEACHER_ID'] ),
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

	return $visibleTo . '</table>';
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

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];

		if ( empty( $value ) )
		{
			return '&nbsp;';
		}

		$filesAttachedCount++;

		//FJ colorbox
		$view_online = '<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/visualize.png" class="button bigger"> ' . _( 'View Online' ) . '';

		$download = '<img src="assets/themes/' . Preferences( 'THEME' ) . '/btn/download.png" class="button bigger"> ' . _( 'Download' ) . '';

		if ( filter_var( $value, FILTER_VALIDATE_URL ) !== false ) //embed link
		{
			return '<a href="' . URLEscape( $value ) . '" title="' . AttrEscape( $value ) . '" class="colorboxiframe">' . $view_online . '</a>';
		}

		return '<a href="' . URLEscape( $value ) . '" title="' . AttrEscape( str_replace( $PortalNotesFilesPath, '', $value ) ) . '" target="_blank">' . $download . '</a>';
	}

	$id = 'new';

	$return = '<div id="divFileAttached' . $id . '" class="rt2colorBox"><div>';

	$return .= FileInput( $name . '_FILE', _( 'File Attached' ) );

	$return .= '<br>' . TextInput(
		'',
		'values[' . $id . '][' . $name . '_EMBED]',
		_( 'Embed Link' ),
		'size="22" placeholder="https://"'
	) . '</div></div>';

	return $return;
}
