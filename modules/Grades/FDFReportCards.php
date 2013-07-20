<?php
ini_set('MAX_EXECUTION_TIME',0);
if(count($_REQUEST['mp_arr']))
{
	foreach($_REQUEST['mp_arr'] as $mp)
		$mp_list .= ",'$mp'";
	$mp_list = mb_substr($mp_list,1);
}

$extra['search'] = '<TR><TD style="text-align:right">'._('Marking Periods').'</TD><TD><TABLE>';

$mps_RET = DBGet(DBQuery("SELECT PARENT_ID,MARKING_PERIOD_ID,SHORT_NAME FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"),array(),array('SEMESTER_ID'));
foreach($mps_RET as $sem=>$quarters)
{
	$extra['search'] .= '<TR>';
	foreach($quarters as $qtr)
	{
		$pro = GetChildrenMP('PRO',$qtr['MARKING_PERIOD_ID']);
		$pros = explode(',',str_replace("'",'',$pro));
		foreach($pros as $pro)
//modif Francois: add <label> on checkbox
			$extra['search'] .= '<TD><label><INPUT type="checkbox" name="mp_arr[]" value="'.$pro.'">'.GetMP($pro,'SHORT_NAME').'</label></TD>';
		$extra['search'] .= '<TD><label><INPUT type="checkbox" name="mp_arr[]" value="'.$qtr['MARKING_PERIOD_ID'].'">'.$qtr['SHORT_NAME'].'</label></TD>';
	}
	$extra['search'] .= '<TD><label><INPUT type="checkbox" name="mp_arr[]" value="E'.$sem.'">'.sprintf(_('%s Exam'),GetMP($sem,'SHORT_NAME')).'</label></TD><TD><label><INPUT type="checkbox" name="mp_arr[]" value="'.$sem.'" checked />'.GetMP($sem,'SHORT_NAME').'</label></TD>';
	$extra['search'] .= '</TR>';
}
$extra['search'] .= '</TABLE></TD></TR>';
if($_REQUEST['modfunc']!='gradelist')
	Widgets('mailing_labels');

$extra['SELECT'] .= ",smc.COMMENT AS STUDENT_MP_COMMENT,rpg.TITLE as GRADE_TITLE,sg1.STUDENT_ID,sg1.COURSE_PERIOD_ID,sg1.MARKING_PERIOD_ID,sg1.COMMENT,c.TITLE as COURSE_TITLE,
				(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
					WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE='A' AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND ap.STUDENT_ID=ssm.STUDENT_ID) AS YTD_ABSENCES,
				(SELECT count(*) FROM ATTENDANCE_PERIOD ap,ATTENDANCE_CODES ac
					WHERE ac.ID=ap.ATTENDANCE_CODE AND ac.STATE_CODE='A' AND ap.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND sg1.MARKING_PERIOD_ID=ap.MARKING_PERIOD_ID AND ap.STUDENT_ID=ssm.STUDENT_ID) AS MP_ABSENCES ";
$extra['FROM'] .= ",STUDENT_REPORT_CARD_GRADES sg1 LEFT OUTER JOIN REPORT_CARD_GRADES rpg ON (rpg.ID=sg1.REPORT_CARD_GRADE_ID) LEFT OUTER JOIN STUDENT_MP_COMMENTS smc ON (smc.STUDENT_ID=sg1.STUDENT_ID AND smc.MARKING_PERIOD_ID=sg1.MARKING_PERIOD_ID),
					COURSES c,COURSE_PERIODS cp1,SCHOOL_PERIODS sp";
$extra['WHERE'] .= " AND sg1.MARKING_PERIOD_ID IN (".$mp_list.")
					AND cp1.COURSE_PERIOD_ID=sg1.COURSE_PERIOD_ID AND c.COURSE_ID = cp1.COURSE_ID AND sg1.STUDENT_ID=ssm.STUDENT_ID AND sp.PERIOD_ID=cp1.PERIOD_ID";
$extra['ORDER'] .= ",sp.SORT_ORDER";
$extra['group']	= array('STUDENT_ID');
$extra['group'][] = 'COURSE_PERIOD_ID';
$extra['group'][] = 'MARKING_PERIOD_ID';
$extra['force_search'] = true;

Widgets('course');
Widgets('gpa');
Widgets('class_rank');
Widgets('letter_grade');

$extra['action'] .= "&_ROSARIO_PDF=true";

if(!$_REQUEST['search_modfunc'] || $_ROSARIO['modules_search'])
{
	DrawHeader(ProgramTitle());

	$extra['new'] = true;
	Search('student_id',$extra);
}
else
{
	if(!$_REQUEST['mp_arr'])
		BackPrompt(_('You must choose at least one marking period'));

	$RET = GetStuList($extra);
	// GET THE ATTENDANCE
	unset($extra);
	$extra['SELECT'] .= ",ac.SHORT_NAME AS ATTENDANCE_CODE,ap.MARKING_PERIOD_ID ";
	$extra['FROM'] .= ",ATTENDANCE_CODES ac,ATTENDANCE_PERIOD ap ";
	$extra['WHERE'] .= " AND ac.ID=ap.ATTENDANCE_CODE AND (ac.DEFAULT_CODE!='Y' OR ac.DEFAULT_CODE IS NULL) AND ac.SYEAR=ssm.SYEAR AND ap.STUDENT_ID=ssm.STUDENT_ID";
	$extra['group'][] = 'STUDENT_ID';
	$extra['group'][] = 'MARKING_PERIOD_ID';
	$extra['group'][] = 'ATTENDANCE_CODE';
	Widgets('course');
	Widgets('gpa');
	Widgets('class_rank');
	Widgets('letter_grade');
	$attendance_RET = GetStuList($extra);
	$attendance_codes = DBGet(DBQuery("SELECT SHORT_NAME FROM ATTENDANCE_CODES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND TABLE_NAME='0'"));

	// GET THE DAILY ATTENDANCE
	unset($extra);
	$extra['SELECT'] .= ",ad.MARKING_PERIOD_ID ";
	$extra['FROM'] .= ",ATTENDANCE_DAY ad ";
	$extra['WHERE'] .= " AND ad.STUDENT_ID=ssm.STUDENT_ID AND ad.SYEAR=ssm.SYEAR AND ad.STATE_VALUE='0.0'";
	$extra['group'][] = 'STUDENT_ID';
	$extra['group'][] = 'MARKING_PERIOD_ID';
	Widgets('course');
	Widgets('gpa');
	Widgets('class_rank');
	Widgets('letter_grade');
	$attendance_day_RET = GetStuList($extra);

	$form = 'assets/report_card.pdf';
	$FP=@fopen($form,"r");
	while(!feof($FP))
		$original_PDF .= fgets($FP,4096);
	fclose($FP);

	$comment_codes = DBGet(DBQuery("SELECT TITLE FROM REPORT_CARD_COMMENTS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY TITLE"));
	for($i=1;$i<=count($comment_codes);$i++)
		$original_PDF = str_replace('(default[comment_codes]['.$i.'])','('.$comment_codes[$i]['TITLE'].')',$original_PDF);
	for($i;$i<=18;$i++)
		$original_PDF = str_replace('(default[comment_codes]['.$i.'])','( )',$original_PDF);


	if(count($RET))
	{
		foreach($RET as $student_id=>$course_periods)
		{
			$tempfile=tempnam('','html');
			exec("chmod 777 $tempfile");
			$FP=@fopen($tempfile,"w");
			if(!$FP)
				die("Can't open $tempfile");
			$this_PDF = $original_PDF;

			$student_count++;

			$i = array();
			foreach($course_periods as $course_period_id=>$mps)
			{
				foreach($_REQUEST['mp_arr'] as $mp)
				{
					//$this_PDF = str_replace('/T(values[courses]['.GetMP($mp,'SORT_ORDER').']['.$i.'])','/T(values['.$student_count.'][courses]['.GetMP($mp,'SORT_ORDER').']['.$i.']',$this_PDF);
					if($mps[$mp])
					{
						$i[$mp]++;
						$this_PDF = str_replace('(default[courses]['.GetMP($mp,'SORT_ORDER').']['.$i[$mp].'])','('.$mps[$mp][1]['COURSE_TITLE'].')',$this_PDF);
						//$this_PDF = str_replace('/T(values[grades]['.GetMP($mp,'SORT_ORDER').']['.$i.'])','/T(values['.$student_count.'][grades]['.GetMP($mp,'SORT_ORDER').']['.$i.']',$this_PDF);
						$this_PDF = str_replace('(default[grades]['.GetMP($mp,'SORT_ORDER').']['.$i[$mp].'])','('.$mps[$mp][1]['GRADE_TITLE'].')',$this_PDF);
						$this_PDF = str_replace('(default[comments]['.GetMP($mp,'SORT_ORDER').']['.$i[$mp].'])','('.$mps[$mp][1]['COMMENT'].')',$this_PDF);

						$last_mp = $mp;
					}
				}
				//$grades_RET[$i]['COMMENT'] = $mps[$last_mp][1]['COMMENT_TITLE'];
				$grades_RET[$i]['ABSENCES'] = $mps[$last_mp][1]['YTD_ABSENCES'].' / '.$mps[$last_mp][1]['MP_ABSENCES'];
			}

			foreach($_REQUEST['mp_arr'] as $mp)
			{
				for($i;$i[$mp]<=11;$i[$mp]++)
				{
					$this_PDF = str_replace('(default[grades]['.GetMP($mp,'SORT_ORDER').']['.$i[$mp].'])','( )',$this_PDF);
					$this_PDF = str_replace('(default[courses]['.GetMP($mp,'SORT_ORDER').']['.$i[$mp].'])','( )',$this_PDF);
					$this_PDF = str_replace('(default[comments]['.GetMP($mp,'SORT_ORDER').']['.$i[$mp].'])','( )',$this_PDF);
				}

				$this_PDF = str_replace('/T(values[students]['.GetMP($mp,'SORT_ORDER').'][COMMENT])',"/T(values[$student_count][students][COMMENT])",$this_PDF);

				// If the student doesn't have a grade, his MP comment won't show up.
				foreach($course_periods as $index=>$mps)
				{
					if($mps[$mp][1]['GRADE_TITLE'])
						break;
				}

				//if(!$mps[$mp])
				$course_periods[$index][$mp][1]['STUDENT_MP_COMMENT'] = str_replace("\r",'`',str_replace('(','{',str_replace(')','}',$course_periods[$index][$mp][1]['STUDENT_MP_COMMENT'])));
				$course_periods[$index][$mp][1]['STUDENT_MP_COMMENT'] = wordwrap($course_periods[$index][$mp][1]['STUDENT_MP_COMMENT'],59,"`");
				$comments = explode('`',$course_periods[$index][$mp][1]['STUDENT_MP_COMMENT']);
				for($c=0;$c<=count($comments);$c++)
				{
					$this_PDF = str_replace('/T(values[student_mp_comments]['.GetMP($mp,'SORT_ORDER').']['.($c+1).'])',"/T(values[$student_count][student_mp_comments][".GetMP($mp,'SORT_ORDER').']['.($c+1).'])',$this_PDF);
					$this_PDF = str_replace('(default[student_mp_comments]['.GetMP($mp,'SORT_ORDER').']['.($c+1).'])','('.$comments[$c].')',$this_PDF);
				}
				for(;$c<=19;$c++)
					$this_PDF = str_replace('(default[student_mp_comments]['.GetMP($mp,'SORT_ORDER').']['.($c+1).'])','( )',$this_PDF);

				$qtr = str_replace("'",'',GetChildrenMP('SEM',$mp));
				if(mb_strpos(',',$qtr)!==false)
					$qtr = mb_substr($qtr,mb_strpos(',',$qtr));

				foreach($attendance_codes as $attendance_code)
				{
					$attendance_code = $attendance_code['SHORT_NAME'];
					$value = $attendance_RET[$student_id][$qtr][$attendance_code];

					$this_PDF = str_replace('/T(values[ac]['.$attendance_code.']['.GetMP($mp,'SORT_ORDER').'])',"/T(values[$student_count][ac][".$attendance_code.'][1])',$this_PDF);
					$this_PDF = str_replace('(default[ac]['.$attendance_code.']['.GetMP($mp,'SORT_ORDER').'])','( '.count($value).' )',$this_PDF);
				}
				$this_PDF = str_replace('/T(values[abs]['.GetMP($mp,'SORT_ORDER').'])',"/T(values[$student_count][abs][1])",$this_PDF);
				$this_PDF = str_replace('(default[abs]['.GetMP($mp,'SORT_ORDER').'])','( '.count($attendance_day_RET[$student_id][$qtr]).' )',$this_PDF);
			}

			foreach($_REQUEST['mp_arr'] as $mp)
				$columns[$mp] = GetMP($mp,$mp_TITLE);

			$this_PDF = str_replace('/T(values[students][USER_NAME])',"/T(values[$student_count][students][USER_NAME])",$this_PDF);
			$this_PDF = str_replace('(default[students][USER_NAME])','('.User('NAME').')',$this_PDF);

			$this_PDF = str_replace('/T(values[students][FULL_NAME])',"/T(values[$student_count][students][FULL_NAME])",$this_PDF);
			$this_PDF = str_replace('(default[students][FULL_NAME])','('.$course_periods[$course_period_id][key($course_periods[$course_period_id])][1]['FULL_NAME'].')',$this_PDF);

			$this_PDF = str_replace('/T(values[students][STUDENT_ID])',"/T(values[$student_count][students][STUDENT_ID])",$this_PDF);
			$this_PDF = str_replace('(default[students][STUDENT_ID])','('.$course_periods[$course_period_id][key($course_periods[$course_period_id])][1]['STUDENT_ID'].')',$this_PDF);

			$this_PDF = str_replace('/T(values[students][GRADE_ID])',"/T(values[$student_count][students][GRADE_ID])",$this_PDF);
			$this_PDF = str_replace('(default[students][GRADE_ID])','('.preg_replace("/<!-- [0-9]+ -->/",'',$course_periods[$course_period_id][key($course_periods[$course_period_id])][1]['GRADE_ID']).')',$this_PDF);

			$this_PDF = str_replace('/T(values[students][SCHOOL])',"/T(values[$student_count][students][SCHOOL])",$this_PDF);
			$this_PDF = str_replace('(default[students][SCHOOL])','('.GetSchool(UserSchool()).')',$this_PDF);

			$this_PDF = str_replace('/T(values[students][SYEAR])',"/T(values[$student_count][students][SYEAR])",$this_PDF);
//modif Francois: school year over one/two calendar years format
			$this_PDF = str_replace('(default[students][SYEAR])','('.FormatSyear(UserSyear(),Config('SCHOOL_SYEAR_OVER_2_YEARS')).')',$this_PDF);

			fwrite($FP,$this_PDF);
			@fclose($FP);

			$card = $tempfile;
			$tempfile = tempnam('','html');
			exec("/usr/local/bin/pdftk $card output $tempfile flatten");
			@unlink($card);
			$cards .= $tempfile." *\n";
			$delete_cards[] = $tempfile;
			$count_cards++;
		}

		$cards = mb_substr($cards,0,-1);
		$sourcefile = tempnam('','html');
		$FP=@fopen($sourcefile,"w");
		fwrite($FP,$cards);
		$tempfile = tempnam('','html');
		set_time_limit(0);
		//echo ("/usr/local/bin/mbtPdfAsm -d$tempfile -s$sourcefile");
		exec("/usr/local/bin/mbtPdfAsm -d$tempfile -s$sourcefile");
		@unlink($sourcefile);
		foreach($delete_cards as $card)
			@unlink($card);

		header("Cache-Control: public");
		header("Pragma: ");
		header("Content-Type: application/pdf");
		header("Content-Disposition: inline; filename=\"ReportCards.pdf\"\n");

		set_time_limit(0);
		echo file_get_contents($tempfile);
		@unlink($tempfile);
		flush();
		exit;
	}
	else
		BackPrompt(_('No Students were found.'));
}

?>