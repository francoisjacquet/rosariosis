<?php
//modif Francois: Portal Polls functions

function PortalPollsVote($poll_id, $votes_array)
{
	//get poll:
	$poll_RET = DBGet(DBQuery('SELECT EXCLUDED_USERS, VOTES_NUMBER, DISPLAY_VOTES FROM PORTAL_POLLS WHERE ID='.$poll_id));
	$poll_questions_RET = DBGet(DBQuery('SELECT ID, QUESTION, OPTIONS, VOTES FROM PORTAL_POLL_QUESTIONS WHERE PORTAL_POLL_ID='.$poll_id.' ORDER BY ID'));
	if (!$poll_RET || !$poll_questions_RET)
		return ErrorMessage(array('Poll does not exist'));//should never be displayed, so do not translate
		
	//add user to excluded users list (format = '|[profile_id]:[user_id]')
	$profile_id = $_POST['profile_id'];
	$user_id = $_POST['user_id'];
	$excluded_user = '|'.$profile_id.':'.$user_id;
	
	if (strpos($poll_RET[1]['EXCLUDED_USERS'], $excluded_user) !== false)//!!
		return ErrorMessage(array('User excluded from this poll'));//should never be displayed, so do not translate
		
	$excluded_users = $poll_RET[1]['EXCLUDED_USERS'].$excluded_user;
	
	//add votes
	$voted_array = array();
	foreach ($poll_questions_RET as $key=>$question)
	{
		if (!empty($question['VOTES']))
		{
			$voted_array[$question['ID']] = explode('||', $question['VOTES']);
			if (is_array($votes_array[$question['ID']])) //multiple
				foreach ($votes_array[$question['ID']] as $checked_box)
					$voted_array[$question['ID']][$checked_box]++;
			else //multiple_radio
				$voted_array[$question['ID']][$votes_array[$question['ID']]]++;
		}
		else //first vote
		{
			$voted_array[$question['ID']] = array();
			$options_array = explode("\r", $question['OPTIONS']);
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
		DBQuery("UPDATE PORTAL_POLL_QUESTIONS SET VOTES='".$voted_array[$question['ID']]."' WHERE ID=".$question['ID']);
		$poll_questions_RET[$key]['VOTES'] = $voted_array[$question['ID']];
	}
	
	//submit query
	DBQuery("UPDATE PORTAL_POLLS SET EXCLUDED_USERS='".$excluded_users."', VOTES_NUMBER=(SELECT CASE WHEN VOTES_NUMBER ISNULL THEN 1 ELSE VOTES_NUMBER+1 END FROM PORTAL_POLLS WHERE ID=".$poll_id.") WHERE ID=".$poll_id);
	
	return PortalPollsVotesDisplay($poll_id, $poll_RET[1]['DISPLAY_VOTES'], $poll_questions_RET, (empty($poll_RET[1]['VOTES_NUMBER'])? 1 : $poll_RET[1]['VOTES_NUMBER']+1));
}

function PortalPollsDisplay($value,$name)
{ global $THIS_RET;
	static $js_included = false;

	$poll_id = $THIS_RET['ID'];
	//get poll:
	$poll_RET = DBGet(DBQuery('SELECT EXCLUDED_USERS, VOTES_NUMBER, DISPLAY_VOTES FROM PORTAL_POLLS WHERE ID='.$poll_id));
	$poll_questions_RET = DBGet(DBQuery('SELECT ID, QUESTION, OPTIONS, TYPE, VOTES FROM PORTAL_POLL_QUESTIONS WHERE PORTAL_POLL_ID='.$poll_id.' ORDER BY ID'));
	if (!$poll_RET || !$poll_questions_RET)
		return ErrorMessage(array('Poll does not exist'));//should never be displayed, so do not translate
	
	//verify if user is in excluded users list (format = '|[profile_id]:[user_id]')
	$profile_id = User('PROFILE_ID');
	if($_SESSION['STAFF_ID'])
		$user_id = User('STAFF_ID');
	elseif($_SESSION['STUDENT_ID'])
		$user_id = User('STUDENT_ID');
	$excluded_user = '|'.$profile_id.':'.$user_id;
	
	if (strpos($poll_RET[1]['EXCLUDED_USERS'], $excluded_user) !== false)
		return PortalPollsVotesDisplay($poll_id, $poll_RET[1]['DISPLAY_VOTES'], $poll_questions_RET, $poll_RET[1]['VOTES_NUMBER']); //user already voted, display votes
	
	$PollForm = '';
	if (!$js_included) //include JS once!
	{
		$PollForm .= includeOnceJquery();
		$PollForm .= '<script type="text/javascript" src="assets/js/jquery.form.js"></script>';
		$PollForm .= '<script type="text/javascript">
			$(document).ready(function() {
				$(\'.formPortalPoll\').ajaxForm({ //send the votes in AJAX
					success: function(data,status,xhr,form) {
						$(form).parent().html(data);
					}
				});
			});
		</script>';
		$js_included = true;
	}
	$PollForm .= '<div id="divPortalPoll'.$poll_id.'"><form method="POST" class="formPortalPoll" action="ProgramFunctions/PortalPolls.fnc.php"><input type="hidden" name="profile_id" value="'.$profile_id.'" /><input type="hidden" name="user_id" value="'.$user_id.'" /><input type="hidden" name="total_votes_string" value="'._('Total Participants').'" /><TABLE  class="width-100p cellspacing-0">';
		
	foreach ($poll_questions_RET as $question)
	{
		$PollForm .= '<TR><TD><b>'.$question['QUESTION'].'</b></TD><TD><TABLE class="width-100p cellspacing-0">';
		$options_array = explode("\r", $question['OPTIONS']);
		$checked = true;
		foreach ($options_array as $option_nb => $option_label)
		{
			if ($question['TYPE'] == 'multiple_radio')
				$PollForm .= '<TR><TD><label><input type="radio" name="votes['.$poll_id.']['.$question['ID'].']" value="'.$option_nb.'" '.($checked?'checked':'').' /> '.$option_label.'</label></TD></TR>'."\n";
			else //multiple
				$PollForm .= '<TR><TD><label><input type="checkbox" name="votes['.$poll_id.']['.$question['ID'].'][]" value="'.$option_nb.'" /> '.$option_label.'</label></TD></TR>'."\n";
			$checked = false;
		}
		$PollForm .= '</TABLE></TD></TR>';
	}
	
	$PollForm .= '</TD></TR></TABLE><P><input type="submit" value="'._('Submit').'" /></P></form></div>';
	
	return $PollForm;	
	
}

function PortalPollsVotesDisplay($poll_id, $display_votes, $poll_questions_RET, $votes_number)
{
	
	if (!$display_votes)
		return ErrorMessage(array('<IMG SRC="assets/check.png" class="alignImg">&nbsp;'._('Poll completed')),'Note');
	
	$votes_display = '<DIV style="max-height:350px; overflow-y:auto;">'."\n";
	
	foreach ($poll_questions_RET as $question)
	{
		$total_votes = 0;
		//question
		$votes_display .= '<P><B>'.$question['QUESTION'].'</B></P><TABLE class="width-100p cellspacing-0">'."\n";
		
		//votes
		$votes_array = explode('||', $question['VOTES']);
		foreach ($votes_array as $votes)
			$total_votes += $votes;

		//options
		$options_array = explode("\r", $question['OPTIONS']);
		for ($i=0; $i < count($options_array); $i++)
		{
			$percent = round(($votes_array[$i]/$total_votes)*100);
			$votes_display .= '<TR><TD style="text-align:right">'.$options_array[$i].'</TD><TD style="width:104px;"><div class="PortalPollBar" style="width:'.$percent.'px; height:12px; background-color:#cc4400;">&nbsp;</div></TD><TD style="width:25px;"><strong> '.$percent.'%</strong></TD></TR>'."\n";
		}
		$votes_display .= '</TABLE><BR />'."\n";
	}
	
	$votes_display .= '</DIV><p>'.(isset($_POST['total_votes_string'])? $_POST['total_votes_string'] : _('Total Participants')).': '.$votes_number.'</p>'."\n"; 
	
	return $votes_display;
}

//AJAX vote call:
if (isset($_POST['votes']) && is_array($_POST['votes']))
{
	chdir('../');
	error_reporting(E_ALL ^ E_NOTICE);
	require('config.inc.php');
	require('database.inc.php');
	// Load functions.
	if($handle = opendir("functions"))
	{
		if(!is_array($IgnoreFiles))
			$IgnoreFiles=Array();

		while (false !== ($file = readdir($handle)))
		{
			// if filename isn't '.' '..' or in the Ignore list... load it.
			if($file!='.' && $file!='..' && !in_array($file,$IgnoreFiles))
				require_once('functions/'.$file);
		}
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
?>