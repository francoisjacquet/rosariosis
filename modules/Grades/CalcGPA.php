<?php
$QI = DBQuery("SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER ");
$RET = DBGet($QI);

//FJ fix bug scale for the working year
$SCALE_RET = DBGet(DBQuery("SELECT * from schools where ID = '".UserSchool()."' AND SYEAR='".UserSyear()."'"));

DrawHeader(ProgramTitle());

$mps = GetAllMP('PRO',UserMP());
$mps = explode(',',str_replace("'",'',$mps));
//FJ add translation
$table = '<TABLE><TR class="st"><TD class="valign-top"><TABLE>
	<TR>
		<TD class="valign-top"><span class="legend-gray">'._('Calculate GPA for').'</span></TD>
		<TD>';

foreach($mps as $mp)
{
	if ($mp!='0')
//FJ add <label> on radio
		$table .= '<label><INPUT type="radio" name="marking_period_id" value="'.$mp.'"'.($mp==UserMP()?' checked':'').'> '.GetMP($mp).'</label><BR />';
}

$table .= '</TD>
	</TR>
	<TR>
		<TD colspan="2" class="center"><span class="legend-gray">'.sprintf(_('GPA based on a scale of %d'),$SCALE_RET[1]['REPORTING_GP_SCALE']).'</span></TD>
	</TR>'.
'</TABLE></TD><TD style="max-width:300px;">'._('GPA calculation modifies existing records.').'<BR /><BR />'._('Weighted and unweighted GPA is calculated by dividing the weighted and unweighted grade points configured for each letter grade (assigned in the Report Card Codes setup program) by the base grading scale specified in the school setup.').' </TD></TR></TABLE>';

$go = Prompt(_('GPA Calculation'),_('Calculate GPA and Class Rank'),$table);
if ($go)
{
	//FJ waiting message
	echo '<BR />';
	PopTable('header',_('Calculating GPA and class rank'));
	echo '<DIV id="statusDIV" class="center"><span class="loading"></span> '._('Calculating ...').' </DIV>';
	PopTable('footer');
	ob_flush();
	flush();
	//FJ no time limit for this script!
	set_time_limit (0);
	
	DBQuery("SELECT calc_cum_gpa_mp('".$_REQUEST['marking_period_id']."')");
    DBQuery("SELECT set_class_rank_mp('".$_REQUEST['marking_period_id']."')");
//FJ remove STUDENT_GPA_CALCULATED table
	//DBQuery("UPDATE STUDENT_GPA_CALCULATED SET CLASS_RANK='".$rank."' WHERE STUDENT_ID='".$student['STUDENT_ID']."' AND MARKING_PERIOD_ID='".$_REQUEST['marking_period_id']."'");
	unset($_REQUEST['delete_ok']);

	//FJ ending message
	echo '<script>document.getElementById("statusDIV").innerHTML='.json_encode(button('check', '', '', 'bigger') . ' '.sprintf(_('GPA and class rank for %s has been calculated.'),GetMP($_REQUEST['marking_period_id']))).';';
	echo '</script>';
	ob_end_flush();
	Prompt(_('GPA Calculation'),_('Calculate GPA and Class Rank'),$table);
}
