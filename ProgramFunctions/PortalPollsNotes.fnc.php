<?php
// Portal Notes attached files Path
// You can override the Path definition in the config.inc.php file
if (!isset($PortalNotesFilesPath))
	$PortalNotesFilesPath = 'assets/PortalNotesFiles/';

//FJ Portal Polls functions

function PortalPollsVote($poll_id, $votes_array)
{
	//get poll:
	$poll_RET = DBGet(DBQuery("SELECT EXCLUDED_USERS, VOTES_NUMBER, DISPLAY_VOTES FROM PORTAL_POLLS WHERE ID='".$poll_id."'"));

	$poll_questions_RET = DBGet(DBQuery("SELECT ID, QUESTION, OPTIONS, VOTES
		FROM PORTAL_POLL_QUESTIONS
		WHERE PORTAL_POLL_ID='".$poll_id."'
		ORDER BY ID"));

	if (!$poll_RET || !$poll_questions_RET)
		return ErrorMessage(array('Poll does not exist'));//should never be displayed, so do not translate
		
	//add user to excluded users list (format = '|[profile_id]:[user_id]')
	$profile_id = $_POST['profile_id'];
	$user_id = $_POST['user_id'];
	$excluded_user = '|'.$profile_id.':'.$user_id;
	
	if (mb_strpos($poll_RET[1]['EXCLUDED_USERS'].'|', $excluded_user.'|') !== false)
		return ErrorMessage(array('User excluded from this poll'));//should never be displayed, so do not translate
		
	$excluded_users = $poll_RET[1]['EXCLUDED_USERS'].$excluded_user;

	$poll_questions_updated = PortalPollsSaveVotes($poll_questions_RET, $votes_array);

	//submit query
	DBQuery("UPDATE PORTAL_POLLS
	SET EXCLUDED_USERS='".$excluded_users."',
	VOTES_NUMBER=(SELECT CASE WHEN VOTES_NUMBER ISNULL THEN 1 ELSE VOTES_NUMBER+1 END FROM PORTAL_POLLS WHERE ID='".$poll_id."')
	WHERE ID='".$poll_id."'");
	
	return PortalPollsVotesDisplay(
		$poll_id,
		$poll_RET[1]['DISPLAY_VOTES'],
		$poll_questions_updated,
		(empty($poll_RET[1]['VOTES_NUMBER'])? 1 : $poll_RET[1]['VOTES_NUMBER']+1),
		true
	);
}

/**
 * function called by PortalPollsVote()
 * save Votes to PORTAL_POLL_QUESTIONS table
 *
 * @return $poll_questions_RET array updated with Votes
 */
function PortalPollsSaveVotes($poll_questions_RET, $votes_array)
{
	//add votes
	$voted_array = array();

	foreach ($poll_questions_RET as $key=>$question)
	{
		if (!empty($question['VOTES']))
		{
			$voted_array[$question['ID']] = explode('||', $question['VOTES']);

			if (is_array($votes_array[$question['ID']])) //multiple
			{
				foreach ($votes_array[$question['ID']] as $checked_box)
					$voted_array[$question['ID']][$checked_box]++;
			}
			else //multiple_radio
				$voted_array[$question['ID']][$votes_array[$question['ID']]]++;
		}
		else //first vote
		{
			$voted_array[$question['ID']] = array();
			$options_array = explode('<br />', nl2br($question['OPTIONS']));

			if (is_array($votes_array[$question['ID']])) //multiple
			{
				foreach ($options_array as $option_nb => $option_label)
					$voted_array[$question['ID']][$option_nb] = 0;

				foreach ($votes_array[$question['ID']] as $checked_box)
					$voted_array[$question['ID']][$checked_box]++;
			}
			else //multiple_radio
				foreach ($options_array as $option_nb => $option_label)
					$voted_array[$question['ID']][$option_nb] = ($votes_array[$question['ID']] == $option_nb ? 1 : 0);
		}

		$voted_array[$question['ID']] = implode('||', $voted_array[$question['ID']]);
		
		//submit query
		DBQuery("UPDATE PORTAL_POLL_QUESTIONS SET VOTES='".$voted_array[$question['ID']]."' WHERE ID='".$question['ID']."'");

		//update the $poll_questions_RET array with Votes
		$poll_questions_RET[$key]['VOTES'] = $voted_array[$question['ID']];
	}
	
	return $poll_questions_RET;
}


function PortalPollsDisplay($value,$name)
{	 global $THIS_RET;

	$poll_id = $THIS_RET['ID'];

	//get poll:
	$poll_RET = DBGet(DBQuery("SELECT EXCLUDED_USERS, VOTES_NUMBER, DISPLAY_VOTES FROM PORTAL_POLLS WHERE ID='".$poll_id."'"));

	include_once('ProgramFunctions/Linkify.fnc.php');

	$poll_questions_RET = DBGet(DBQuery("SELECT ID, QUESTION, OPTIONS, TYPE, VOTES
		FROM PORTAL_POLL_QUESTIONS
		WHERE PORTAL_POLL_ID='".$poll_id."'
		ORDER BY ID"), array('OPTIONS'=>'Linkify'));

	if (!$poll_RET || !$poll_questions_RET)
		return ErrorMessage(array('Poll does not exist'));//should never be displayed, so do not translate
	
	//verify if user is in excluded users list (format = '|[profile_id]:[user_id]')
	$profile_id = User('PROFILE_ID');

	if($profile_id != 0) //FJ call right Student/Staff ID
		$user_id = $_SESSION['STAFF_ID'];
	else
		$user_id = $_SESSION['STUDENT_ID'];

	$excluded_user = '|'.$profile_id.':'.$user_id;

	if (mb_strpos($poll_RET[1]['EXCLUDED_USERS'].'|', $excluded_user.'|') !== false)
		return PortalPollsVotesDisplay($poll_id,
			$poll_RET[1]['DISPLAY_VOTES'],
			$poll_questions_RET,
			$poll_RET[1]['VOTES_NUMBER']
		); //user already voted, display votes

	return PortalPollForm($poll_id, $profile_id, $user_id, $poll_questions_RET);
}


/**
 * function called by PortalPollsDisplay()
 * generates the Portal Poll's HTML form
 *
 * @return $PollForm HTML form
 */
function PortalPollForm($poll_id, $profile_id, $user_id, $poll_questions_RET)
{
	$PollForm = '';
	
	//FJ responsive rt td too large
	if (!isset($_REQUEST['_ROSARIO_PDF']))
		$PollForm .= '<div id="divPortalPoll'.$poll_id.'" class="divPortalPoll rt2colorBox">';

	$PollForm .= '<form method="POST" id="formPortalPoll'.$poll_id.'" action="ProgramFunctions/PortalPollsNotes.fnc.php" target="divPortalPoll'.$poll_id.'">
	<input type="hidden" name="profile_id" value="'.$profile_id.'" />
	<input type="hidden" name="user_id" value="'.$user_id.'" />
	<input type="hidden" name="total_votes_string" value="'._('Total Participants').'" />
	<input type="hidden" name="poll_completed_string" value="'._('Poll completed').'" />
	<TABLE class="width-100p cellspacing-0 widefat">';

	foreach ($poll_questions_RET as $question)
	{
		$PollForm .= '<TR><TD style="vertical-align:top;"><b>'.$question['QUESTION'].'</b></TD>
		<TD><TABLE class="width-100p cellspacing-0">';

		$options_array = explode('<br />', nl2br($question['OPTIONS']));

		$checked = true;
		foreach ($options_array as $option_nb => $option_label)
		{
			if ($question['TYPE'] == 'multiple_radio')
			{
				$PollForm .= '<TR><TD>
					<label>
					<input type="radio" name="votes['.$poll_id.']['.$question['ID'].']" value="'.$option_nb.'" '.($checked?'checked':'').' /> '.$option_label.'
					</label>
					</TD></TR>'."\n";
			}
			else //multiple
			{
				$PollForm .= '<TR><TD>
					<label>
					<input type="checkbox" name="votes['.$poll_id.']['.$question['ID'].'][]" value="'.$option_nb.'" /> '.$option_label.'
					</label>
					</TD></TR>'."\n";
			}

			$checked = false;
		}

		$PollForm .= '</TABLE></TD></TR>';
	}

	$PollForm .= '</TD></TR></TABLE>
	<P><input type="submit" value="'._('Submit').'" id="pollSubmit'.$poll_id.'" /></P></form>';

	if (!isset($_REQUEST['_ROSARIO_PDF']))
		$PollForm .= '</div>';

	return $PollForm;
}


function PortalPollsVotesDisplay($poll_id, $display_votes, $poll_questions_RET, $votes_number, $js_included_is_voting = false)
{
	
	if (!$display_votes)
	{
		$poll_completed_str = isset($_POST['poll_completed_string']) ? $_POST['poll_completed_string'] : _('Poll completed');

		return ErrorMessage( array( button('check', '', '', 'bigger') .'&nbsp;'.$poll_completed_str, 'Note' ) );
	}
	
	//FJ responsive rt td too large
	if (!$js_included_is_voting)
	{
		$votes_display .= '<DIV id="divPortalPoll'.$poll_id.'" class="divPortalPoll rt2colorBox">'."\n";
	}
	
	foreach ($poll_questions_RET as $question)
	{
		$total_votes = 0;
		//question
		$votes_display .= '<P><B>'.$question['QUESTION'].'</B></P>
			<TABLE class="cellspacing-0 widefat col1-align-right">'."\n";
		
		//votes
		$votes_array = explode('||', $question['VOTES']);
		foreach ($votes_array as $votes)
			$total_votes += $votes;

		//options
		$options_array = explode('<br />', nl2br($question['OPTIONS']));
		$options_array_count = count($options_array);

		for ($i=0; $i < $options_array_count; $i++)
		{
			$percent = round(($votes_array[$i]/$total_votes)*100);

			$votes_display .= '<TR>
				<TD>'.$options_array[$i].'</TD>
				<TD><div class="bar" style="width:'.$percent.'px;">&nbsp;</div></TD>
				<TD><b> '.$percent.'%</b></TD>
			</TR>'."\n";
		}
		$votes_display .= '</TABLE>'."\n";
	}

	$total_votes_str = isset($_POST['total_votes_string']) ? $_POST['total_votes_string'] : _('Total Participants');

	$votes_display .= '<p>'.$total_votes_str.': '.$votes_number.'</p>';

	if (!$js_included_is_voting)
		$votes_display .= '</DIV>'; 
	
	return $votes_display;
}


//AJAX vote call:
if (isset($_POST['votes']) && is_array($_POST['votes']))
{
	if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])
		|| $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest')
		die('Error: no AJAX');
		
	chdir('../');

	require('config.inc.php');
	require('database.inc.php');

	// Load functions.
	$functions = glob('functions/*.php');
	foreach ($functions as $function)
	{
		include($function);
	}
	
	foreach ($_POST['votes'] as $poll_id=>$votes_array)
	{
		if (!empty($votes_array))
		{
			echo PortalPollsVote($poll_id, $votes_array);
			break;
		}
	}
}


function makePublishing($value,$name)
{	global $THIS_RET;
	static $profiles = null;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	//FJ responsive rt td too large
	$return = '<DIV id="divPublishing'.$id.'" class="rt2colorBox">'."\n";

	//FJ remove LO_field
	$return .= '<TABLE class="cellspacing-0 widefat"><TR><TD><b>'._('Visible Between').':</b><BR />';
	$return .= DateInput($value,'values['.$id.']['.$name.']').' '._('to').' ';
	$return .= DateInput($THIS_RET['END_DATE'],'values['.$id.'][END_DATE]').'</TD></TR>';

	$return .= '<TR><TD style="padding:0;">';

	if(is_null($profiles))
	{
		$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE FROM USER_PROFILES ORDER BY ID"));

		//add Profiles with Custom permissions to profiles list
		$profiles = array_merge(array(
		array('ID'=>'admin', 'TITLE'=>_('Administrator w/Custom')),
		array('ID'=>'teacher', 'TITLE'=>_('Teacher w/Custom')),
		array('ID'=>'parent', 'TITLE'=>_('Parent w/Custom'))
		), $profiles_RET);
	}

	$return .= makePublishingVisibleTo($profiles, $THIS_RET, $id);

	$return .= '</TD></TR></TABLE>';

	if (!isset($_REQUEST['_ROSARIO_PDF']))
		$return .= '</DIV>';
		
	return $return;
}


/**
 * function called by makePublishing()
 * generates the "Visible To" part of the Publishing options
 *
 * @return $visibleTo HTML form
 */
function makePublishingVisibleTo($profiles, $THIS_RET, $id)
{
	$visibleTo = '<TABLE class="width-100p cellspacing-0">
	<TR>
		<TD colspan="2"><b>'._('Visible To').':</b></TD>
	</TR>
	<TR class="st">';

	//FJ Portal Polls add students teacher
	$teachers_RET = DBGet(DBQuery("SELECT STAFF_ID,LAST_NAME,FIRST_NAME,MIDDLE_NAME
	FROM STAFF
	WHERE (SCHOOLS IS NULL OR STRPOS(SCHOOLS,',".UserSchool().",')>0)
	AND SYEAR='".UserSyear()."'
	AND PROFILE='teacher'
	ORDER BY LAST_NAME,FIRST_NAME"));

	$teachers = array();

	if(count($teachers_RET))
	{
		foreach($teachers_RET as $teacher)
			$teachers[$teacher['STAFF_ID']] = $teacher['LAST_NAME'].', '.$teacher['FIRST_NAME'];
	}

	$i=0;
	foreach($profiles as $profile)
	{
		$i++;
		$checked = mb_strpos($THIS_RET['PUBLISHED_PROFILES'],','.$profile['ID'].',')!==false;

		$visibleTo .= '<TD>'.CheckboxInput($checked, 'profiles['.$id.']['.$profile['ID'].']', _($profile['TITLE']), '', true);

		//FJ Portal Polls add students teacher
		if ($profile['ID'] === '0' && $_REQUEST['modname']=='School_Setup/PortalPolls.php') //student & verify this is not a Portal Note!
		{
			$visibleTo .= ': ' . SelectInput($THIS_RET['STUDENTS_TEACHER_ID'],
				'values['.$id.'][STUDENTS_TEACHER_ID]',
				_('Limit to Teacher'),
				$teachers,
				true,
				'',
				true
			);
		}

		$visibleTo .= '</TD>';
			
		if($i%2==0 && $i!=count($profiles))
			$visibleTo .= '</TR><TR class="st">';
	}

	for(;$i%2!=0;$i++)
		$visibleTo .= '<TD>&nbsp;</TD>';

	$visibleTo .= '</TR>';
	
	if ($_REQUEST['modname']=='School_Setup/PortalNotes.php')
	{
		//hook
		$args = $id;
		do_action('School_Setup/PortalNotes.php|portal_note_field',$args);
	}
		
	$visibleTo .= '</TABLE>';
	
	return $visibleTo;
}


//FJ file attached to portal notes
function makeFileAttached($value,$name)
{	global $THIS_RET, $PortalNotesFilesPath;
	static $filesAttachedCount = 0;

	if($THIS_RET['ID'])
	{
		
		$id = $THIS_RET['ID'];
		if (empty($value))
		{
			$return = '&nbsp;';
		}			
		else
		{
			$filesAttachedCount ++;
			
			//FJ colorbox
			$view_online = '<img src="assets/themes/'. Preferences('THEME') .'/btn/visualize.png" class="button bigger" /> '._('View Online').'';

			$download = '<img src="assets/themes/'. Preferences('THEME') .'/btn/download.png" class="button bigger" /> '._('Download').'';

			if (filter_var($value, FILTER_VALIDATE_URL) !== false) //embed link
			{
				$return = '<a href="'.$value.'" title="'.$value.'" class="colorboxiframe">'. $view_online .'</a>';
			}
			else
			{
				$return = '<a href="'.$value.'" title="'.str_replace($PortalNotesFilesPath, '', $value).'" target="_blank">'. $download.'</a>';
			}
		}
	}
	else
	{
		$id = 'new';
		
		$return .= '<DIV id="divFileAttached'.$id.'" class="rt2colorBox">';
		$return .= '<div>
			<label>
				<input type="radio" name="values[new][FILE_OR_EMBED]" value="FILE">&nbsp;
				<input type="file" id="'.$name.'_FILE" name="'.$name.'_FILE" size="14" title="' . sprintf( _( 'Maximum file size: %01.0fMb' ), FileUploadMaxSize() ) . '" />
				<span id="loading"></span>
			</label>
		</div>';
		$return .= '<div style="float:left;">
			<label>
				<input type="radio" name="values[new][FILE_OR_EMBED]" value="EMBED" onclick="javascript:document.getElementById(\'values[new]['.$name.'_EMBED]\').focus();" />&nbsp;'.
				_('Embed Link').': <input type="text" id="values[new]['.$name.'_EMBED]" name="values[new]['.$name.'_EMBED]" size="14" placeholder="http://" />
			</label>
		</div></DIV>';
	}
		
	return $return;
}
