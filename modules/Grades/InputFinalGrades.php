<?php
DrawHeader(ProgramTitle());

//modif Francois: add School Configuration
$program_config = DBGet(DBQuery("SELECT * FROM PROGRAM_CONFIG WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PROGRAM='grades'"),array(),array('TITLE'));

$sem = GetParentMP('SEM',UserMP());
$fy = GetParentMP('FY',$sem);
$pros = GetChildrenMP('PRO',UserMP());

// if the UserMP has been changed, the REQUESTed MP may not work
if(!$_REQUEST['mp'] || mb_strpos($str="'".UserMP()."','".$sem."','".$fy."',".$pros,"'".$_REQUEST['mp']."'")===false)
	$_REQUEST['mp'] = UserMP();

$course_period_id = UserCoursePeriod();
if(empty($course_period_id))                                  
	ErrorMessage(array(_('You cannot enter grades for this course period.')),'fatal');

//modif Francois: add CLASS_RANK
//modif Francois: add Credit Hours
//$course_RET = DBGet(DBQuery("SELECT cp.COURSE_ID,c.TITLE as COURSE_NAME, cp.TITLE, cp.GRADE_SCALE_ID, credit($course_period_id, '".$_REQUEST['mp']."') AS CREDITS, (SELECT ATTENDANCE FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID) AS ATTENDANCE FROM COURSE_PERIODS cp, COURSES c WHERE cp.COURSE_ID = c.COURSE_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."'"));
$course_RET = DBGet(DBQuery("SELECT cp.COURSE_ID,c.TITLE as COURSE_NAME, cp.TITLE, cp.GRADE_SCALE_ID, credit($course_period_id, '".$_REQUEST['mp']."') AS CREDITS, DOES_CLASS_RANK AS CLASS_RANK, c.CREDIT_HOURS FROM COURSE_PERIODS cp, COURSES c WHERE cp.COURSE_ID = c.COURSE_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."'"));
if(!$course_RET[1]['GRADE_SCALE_ID'])                                  
	ErrorMessage(array(_('You cannot enter grades for this course period.')),'fatal');
$course_title = $course_RET[1]['TITLE'];
$grade_scale_id = $course_RET[1]['GRADE_SCALE_ID'];
$course_id = $course_RET[1]['COURSE_ID'];

$current_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM STUDENT_REPORT_CARD_GRADES g,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."'"),array(),array('STUDENT_ID'));
$current_completed = count(DBGet(DBQuery("SELECT '' FROM GRADES_COMPLETED WHERE STAFF_ID='".User('STAFF_ID')."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND COURSE_PERIOD_ID='".$course_period_id."'")));

$grades_RET = DBGet(DBQuery("SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP, rcg.UNWEIGHTED_GP ,gs.GP_SCALE  FROM REPORT_CARD_GRADES rcg, REPORT_CARD_GRADE_SCALES gs WHERE rcg.grade_scale_id = gs.id AND rcg.SYEAR='".UserSyear()."' AND rcg.SCHOOL_ID='".UserSchool()."' AND rcg.GRADE_SCALE_ID='$grade_scale_id' ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER"),array(),array('ID'));

$categories_RET = DBGet(DBQuery("SELECT rc.ID,rc.TITLE,rc.COLOR,1,rc.SORT_ORDER FROM REPORT_CARD_COMMENT_CATEGORIES rc WHERE rc.COURSE_ID='".$course_id."' AND (SELECT count(1) FROM REPORT_CARD_COMMENTS WHERE COURSE_ID=rc.COURSE_ID AND CATEGORY_ID=rc.ID)>0
			UNION SELECT 0,'All Courses',NULL,2,NULL WHERE (SELECT count(1) FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND COURSE_ID='0' AND SYEAR='".UserSyear()."')>0
			UNION SELECT -1,'"._('General')."',NULL,3,NULL WHERE (SELECT count(1) FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND COURSE_ID IS NULL AND SYEAR='".UserSyear()."')>0
			ORDER BY 4,SORT_ORDER"),array(),array('ID'));
if($_REQUEST['tab_id']=='' || !$categories_RET[$_REQUEST['tab_id']])
	$_REQUEST['tab_id'] = key($categories_RET).'';

$comment_codes_RET = DBGet(DBQuery("SELECT SCALE_ID,TITLE,SHORT_NAME FROM REPORT_CARD_COMMENT_CODES WHERE SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER,ID"),array(),array('SCALE_ID'));
$commentsA_select = array();
foreach($comment_codes_RET as $scale_id=>$codes)
	foreach($codes as $code)
		$commentsA_select[$scale_id][$code['TITLE']] = $code['SHORT_NAME'] ? array($code['TITLE'],$code['SHORT_NAME']) : $code['TITLE'];

if($_REQUEST['tab_id']=='-1')
{
	$commentsB_RET = DBGet(DBQuery("SELECT ID,TITLE,SORT_ORDER FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID IS NULL ORDER BY SORT_ORDER"),array(),array('ID'));
	$current_commentsB_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID FROM STUDENT_REPORT_CARD_COMMENTS g,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM REPORT_CARD_COMMENTS WHERE COURSE_ID IS NULL)"),array(),array('STUDENT_ID'));
	$max_current_commentsB = 0;
	foreach($current_commentsB_RET as $comments)
		if(count($comments)>$max_current_commentsB)
			$max_current_commentsB = count($comments);
}
elseif($_REQUEST['tab_id']=='0')
{
	$commentsA_RET = DBGet(DBQuery("SELECT ID,TITLE,SCALE_ID FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID='0' ORDER BY SORT_ORDER"));
	$current_commentsA_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM STUDENT_REPORT_CARD_COMMENTS g,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM REPORT_CARD_COMMENTS WHERE COURSE_ID='0')"),array(),array('STUDENT_ID','REPORT_CARD_COMMENT_ID'));
}
elseif($_REQUEST['tab_id'])
{
	$commentsA_RET = DBGet(DBQuery("SELECT ID,TITLE,SCALE_ID FROM REPORT_CARD_COMMENTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND COURSE_ID='".$course_id."' AND CATEGORY_ID='".$_REQUEST['tab_id']."' ORDER BY SORT_ORDER"));
	$current_commentsA_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM STUDENT_REPORT_CARD_COMMENTS g,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM REPORT_CARD_COMMENTS WHERE CATEGORY_ID='".$_REQUEST['tab_id']."')"),array(),array('STUDENT_ID','REPORT_CARD_COMMENT_ID'));
}

$grades_select = array(''=>'');
foreach($grades_RET as $key=>$grade)
{
	$grade = $grade[1];
	$grades_select += array($grade['ID']=>array($grade['TITLE'],'<b>'.$grade['TITLE'].'</b>'));
}
$commentsB_select = array();
if(0)
foreach($commentsB_RET as $id=>$comment)
	$commentsB_select += array($id=>array($comment[1]['SORT_ORDER'],$comment[1]['TITLE']));
elseif (is_array($commentsB_RET))
foreach($commentsB_RET as $id=>$comment)
	$commentsB_select += array($id=>array($comment[1]['SORT_ORDER'].' - '.(mb_strlen($comment[1]['TITLE']) > 99+3?mb_substr($comment[1]['TITLE'],0,99).'...':$comment[1]['TITLE']),$comment[1]['TITLE']));

if($_REQUEST['modfunc']=='gradebook')
{
	if($_REQUEST['mp'])
	{
		$config_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='Gradebook'"),array(),array('TITLE'));
		if(count($config_RET))
			foreach($config_RET as $title=>$value)
				$programconfig[User('STAFF_ID')][$title] = $value[1]['VALUE'];
		else
			$programconfig[User('STAFF_ID')] = true;
		$_ROSARIO['_makeLetterGrade']['courses'][$course_period_id] = DBGet(DBQuery("SELECT DOES_BREAKOFF,GRADE_SCALE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$course_period_id."'"));

		include 'ProgramFunctions/_makeLetterGrade.fnc.php';

		if(GetMP($_REQUEST['mp'],'MP')=='QTR' || GetMP($_REQUEST['mp'],'MP')=='PRO')
		{
			// Note: The 'active assignment' determination is not fully correct.  It would be easy to be fully correct here but the same determination
			// as in Grades.php is used to avoid apparent inconsistencies in the grade calculations.  See also the note at top of Grades.php.
			$extra['SELECT_ONLY'] = "s.STUDENT_ID, gt.ASSIGNMENT_TYPE_ID,sum(".db_case(array('gg.POINTS',"'-1'","'0'",'gg.POINTS')).") AS PARTIAL_POINTS,sum(".db_case(array('gg.POINTS',"'-1'","'0'",'ga.POINTS')).") AS PARTIAL_TOTAL,    gt.FINAL_GRADE_PERCENT";
			$extra['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON ((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID OR ga.COURSE_ID=cp.COURSE_ID AND ga.STAFF_ID=cp.TEACHER_ID) AND ga.MARKING_PERIOD_ID='".UserMP()."') LEFT OUTER JOIN GRADEBOOK_GRADES gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),GRADEBOOK_ASSIGNMENT_TYPES gt";
			$extra['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=cp.COURSE_ID AND (gg.POINTS IS NOT NULL OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";
			$extra['WHERE'] .=" AND (gg.POINTS IS NOT NULL OR ga.DUE_DATE IS NULL OR ((ga.DUE_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)) AND (ga.DUE_DATE>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";
			$extra['GROUP'] = "gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";
			$extra['group'] = array('STUDENT_ID');
			$points_RET = GetStuList($extra);
			//echo '<pre>'; var_dump($points_RET); echo '</pre>';
			unset($extra);

			if(count($points_RET))
			{
				foreach($points_RET as $student_id=>$student)
				{
					$total = $total_percent = 0;
					foreach($student as $partial_points)
						if($partial_points['PARTIAL_TOTAL']!=0 || $programconfig[User('STAFF_ID')]['WEIGHT']!='Y')
						{
							$total += $partial_points['PARTIAL_POINTS']*($programconfig[User('STAFF_ID')]['WEIGHT']=='Y'?$partial_points['FINAL_GRADE_PERCENT']/$partial_points['PARTIAL_TOTAL']:1);
							$total_percent += ($programconfig[User('STAFF_ID')]['WEIGHT']=='Y'?$partial_points['FINAL_GRADE_PERCENT']:$partial_points['PARTIAL_TOTAL']);
						}
					if($total_percent!=0)
						$total /= $total_percent;

					$import_RET[$student_id] = array(1=>array('REPORT_CARD_GRADE_ID'=>_makeLetterGrade($total,$course_period_id,0,'ID'),'GRADE_PERCENT'=>round(100*$total,1)));
				}
			}
		}
		elseif(GetMP($_REQUEST['mp'],'MP')=='SEM' || GetMP($_REQUEST['mp'],'MP')=='FY')
		{
			if(GetMP($_REQUEST['mp'],'MP')=='SEM')
			{
				$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,'Y' AS DOES_GRADES FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND PARENT_ID='$_REQUEST[mp]' UNION SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND MARKING_PERIOD_ID='$_REQUEST[mp]'"));
				$prefix = 'SEM-';
			}
			else
			{
				$RET = DBGet(DBQuery("SELECT q.MARKING_PERIOD_ID,'Y' AS DOES_GRADES FROM SCHOOL_MARKING_PERIODS q,SCHOOL_MARKING_PERIODS s WHERE q.MP='QTR' AND s.MP='SEM' AND q.PARENT_ID=s.MARKING_PERIOD_ID AND s.PARENT_ID='$_REQUEST[mp]' UNION SELECT MARKING_PERIOD_ID,DOES_GRADES FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND PARENT_ID='$_REQUEST[mp]' UNION SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND MARKING_PERIOD_ID='$_REQUEST[mp]'"));
				$prefix = 'FY-';
			}
			foreach($RET as $mp)
			{
				if($mp['DOES_GRADES']=='Y')
					$mps .= "'$mp[MARKING_PERIOD_ID]',";
			}
			$mps = mb_substr($mps,0,-1);

			//modif Francois: select all the course periods (for all the selected mps) of the same course
			//$percents_RET = DBGet(DBQuery("SELECT STUDENT_ID,GRADE_PERCENT,MARKING_PERIOD_ID FROM STUDENT_REPORT_CARD_GRADES WHERE COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID IN ($mps)"),array(),array('STUDENT_ID'));
			$percents_RET = DBGet(DBQuery("SELECT STUDENT_ID,GRADE_PERCENT,MARKING_PERIOD_ID FROM STUDENT_REPORT_CARD_GRADES WHERE COURSE_TITLE=(SELECT c.TITLE FROM COURSES c, COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='".$course_period_id."' AND c.COURSE_ID=cp.COURSE_ID) AND MARKING_PERIOD_ID IN ($mps)"),array(),array('STUDENT_ID'));			

			foreach($percents_RET as $student_id=>$percents)
			{
				$total = $total_percent = 0;
				foreach($percents as $percent)
				{
					$total += $percent['GRADE_PERCENT'] * $programconfig[User('STAFF_ID')][$prefix.$percent['MARKING_PERIOD_ID']];
					$total_percent += $programconfig[User('STAFF_ID')][$prefix.$percent['MARKING_PERIOD_ID']];
				}
				$total /= $total_percent;

				$import_RET[$student_id] = array(1=>array('REPORT_CARD_GRADE_ID'=>_makeLetterGrade($total/100,$course_period_id,0,'ID'),'GRADE_PERCENT'=>round($total,1)));

				//modif Francois: automatic comment on yearly grades
				if (GetMP($_REQUEST['mp'],'MP')=='FY')
				{
					//modif Francois: use Report Card Grades comments
					$comment = _makeLetterGrade($total/100,$course_period_id,0,'COMMENT');
					$import_comments_RET[$student_id][1]['COMMENT'] = $comment;
				}
			}

		}
	}
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if($_REQUEST['modfunc']=='grades')
{
	if($_REQUEST['prev_mp'])
	{
		include 'ProgramFunctions/_makePercentGrade.fnc.php';

		$import_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT FROM STUDENT_REPORT_CARD_GRADES g,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['prev_mp']."'"),array(),array('STUDENT_ID'));
		foreach($import_RET as $student_id=>$grade)
		{
			$import_RET[$student_id][1]['GRADE_PERCENT'] = _makePercentGrade($grade[1]['REPORT_CARD_GRADE_ID'],$course_period_id);
			$import_RET[$student_id][1]['REPORT_CARD_GRADE_ID'] = $grade[1]['REPORT_CARD_GRADE_ID'];
		}

		unset($_SESSION['_REQUEST_vars']['prev_mp']);
	}
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if($_REQUEST['modfunc']=='comments')
{
	if($_REQUEST['prev_mp'])
	{
		$import_comments_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM STUDENT_REPORT_CARD_GRADES g WHERE g.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['prev_mp']."'"),array(),array('STUDENT_ID'));
		$import_commentsA_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM STUDENT_REPORT_CARD_COMMENTS g WHERE g.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['prev_mp']."' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM REPORT_CARD_COMMENTS WHERE COURSE_ID IS NOT NULL)"),array(),array('STUDENT_ID','REPORT_CARD_COMMENT_ID'));
		//echo '<pre>'; var_dump($import_commentsA_RET); echo '</pre>';
		$import_commentsB_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID FROM STUDENT_REPORT_CARD_COMMENTS g WHERE g.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['prev_mp']."' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM REPORT_CARD_COMMENTS WHERE COURSE_ID IS NULL)"),array(),array('STUDENT_ID'));

		foreach($import_commentsB_RET as $comments)
			if(count($comments)>$max_current_commentsB)
				$max_current_commentsB = count($comments);

		unset($_SESSION['_REQUEST_vars']['prev_mp']);
	}
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if($_REQUEST['modfunc']=='clearall')
{
	foreach($current_RET as $student_id=>$prev)
	{
		$current_RET[$student_id][1]['REPORT_CARD_GRADE_ID'] = '';
		$current_RET[$student_id][1]['GRADE_PERCENT'] = '';
		$current_RET[$student_id][1]['COMMENT'] = '';
	}
	if (is_array($current_commentsA_RET))
	{
		foreach($current_commentsA_RET as $student_id=>$comments)
			foreach($comments as $id=>$comment)
				$current_commentsA_RET[$student_id][$id][1]['COMMENT'] = '';
	}
	if (is_array($current_commentsB_RET))
	{
		foreach($current_commentsB_RET as $student_id=>$comment)
			foreach($comment as $i=>$comment)
				$current_commentsB_RET[$student_id][$i] = '';
	}
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if($_REQUEST['values'] && $_POST['values'])
{
	include 'ProgramFunctions/_makeLetterGrade.fnc.php';
	include 'ProgramFunctions/_makePercentGrade.fnc.php';
	$completed = true;
	
	//modif Francois: add precision to year weighted GPA if not year course period
	$course_period_mp = DBGet(DBQuery("SELECT MP FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$course_period_id."'"));
	$course_period_mp = $course_period_mp[1]['MP'];
	
	foreach($_REQUEST['values'] as $student_id=>$columns)
	{
		$sql = $sep = '';
		if($current_RET[$student_id])
		{
			if($columns['percent']!='')
			{
				$percent = rtrim($columns['percent'],'%');
				if($percent>999.9)
					$percent = '999.9';
				elseif($percent<0)
					$percent = '0';
				if($columns['grade'] || $percent!='')
				{
					$grade = ($columns['grade']?$columns['grade']:_makeLetterGrade($percent/100,$course_period_id,0,'ID'));
					$letter = $grades_RET[$grade][1]['TITLE'];
					$weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
					$unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];
					
					//modif Francois: add precision to year weighted GPA if not year course period
					if (GetMP($_REQUEST['mp'],'MP')=='FY' && $course_period_mp!='FY')
					{
						$weighted = $percent/100*$grades_RET[$grade][1]['GP_SCALE'];
					}
					$scale = $grades_RET[$grade][1]['GP_SCALE'];
				}
				else
					$grade = $letter = $weighted = $unweighted = $scale = '';
				$sql .= "GRADE_PERCENT='".$percent."'";
				$sql .= ",REPORT_CARD_GRADE_ID='".$grade."',GRADE_LETTER='".$letter."',WEIGHTED_GP='".$weighted."',UNWEIGHTED_GP='".$unweighted."',GP_SCALE='".$scale."'";
				//bjj can we use $percent all the time?  TODO: rework this so updates to credits occur when grade is changed
				$sql .= ",COURSE_TITLE='".$course_RET[1]['COURSE_NAME']."'";
				$sql .= ",CREDIT_ATTEMPTED='".$course_RET[1]['CREDITS']."'";
				$sql .= ",CREDIT_EARNED='".($weighted&&$weighted>0?$course_RET[1]['CREDITS']:'0')."'";
				$sep = ',';
			}
			elseif($columns['grade'])
			{
				$percent = _makePercentGrade($columns['grade'],$course_period_id);
				$grade = $columns['grade'];
				$letter = $grades_RET[$grade][1]['TITLE'];
				$weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

				//modif Francois: add precision to year weighted GPA if not year course period
				if (GetMP($_REQUEST['mp'],'MP')=='FY' && $course_period_mp!='FY')
				{
					$weighted = $percent/100*$grades_RET[$grade][1]['GP_SCALE'];
				}
				$unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];
				$scale = $grades_RET[$grade][1]['GP_SCALE'];
				$sql .= "GRADE_PERCENT='".$percent."'";
				$sql .= ",REPORT_CARD_GRADE_ID='".$grade."',GRADE_LETTER='".$letter."',WEIGHTED_GP='".$weighted."',UNWEIGHTED_GP='".$unweighted."',GP_SCALE='".$scale."'";
				$sql .= ",COURSE_TITLE='".$course_RET[1]['COURSE_NAME']."'";
				$sql .= ",CREDIT_ATTEMPTED='".$course_RET[1]['CREDITS']."'";
				$sql .= ",CREDIT_EARNED='".($weighted&&$weighted>0?$course_RET[1]['CREDITS']:'0')."'";
				$sep = ',';
			}
			elseif(isset($columns['percent']) || isset($columns['grade']))
			{
				$percent = $grade = '';
				$sql .= "GRADE_PERCENT=NULL";
				//modif Francois: bugfix SQL bug 'NULL' instead of NULL
				//$sql .= ",REPORT_CARD_GRADE_ID=NULL,GRADE_LETTER=NULL,WEIGHTED_GP='NULL',UNWEIGHTED_GP='NULL',GP_SCALE='NULL'";
				$sql .= ",REPORT_CARD_GRADE_ID=NULL,GRADE_LETTER=NULL,WEIGHTED_GP=NULL,UNWEIGHTED_GP=NULL,GP_SCALE=NULL";
				$sql .= ",COURSE_TITLE='".$course_RET[1]['COURSE_NAME']."'";
				$sql .= ",CREDIT_ATTEMPTED='".$course_RET[1]['CREDITS']."'";
				$sql .= ",CREDIT_EARNED='0'";
				$sep = ',';
			}
			else
			{
				$percent = $current_RET[$student_id][1]['GRADE_PERCENT'];
				$grade = $current_RET[$student_id][1]['REPORT_CARD_GRADE_ID'];
			}

			if(isset($columns['comment']))
				$sql .= $sep."COMMENT='".$columns['comment']."'";
			if($sql)
				$sql = "UPDATE STUDENT_REPORT_CARD_GRADES SET ".$sql." WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."'";
		}
		elseif($columns['percent']!='' || $columns['grade'] || $columns['comment'])
		{
			if($columns['percent']!='')
			{
				$percent = rtrim($columns['percent'],'%');
				if($percent>999.9)
					$percent = '999.9';
				elseif($percent<0)
					$percent = '0';
				if($columns['grade'])
					$grade = $columns['grade'];
				else
					$grade = ($percent!=''?_makeLetterGrade($percent/100,$course_period_id,0,'ID'):'');
			}
			elseif($columns['grade'])
			{
					$percent = _makePercentGrade($columns['grade'],$course_period_id);
					$grade = $columns['grade'];
					$letter = $grades_RET[$grade][1]['TITLE'];
					$weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

					//modif Francois: add precision to year weighted GPA if not year course period
					if (GetMP($_REQUEST['mp'],'MP')=='FY' && $course_period_mp!='FY')
					{
						$weighted = $percent/100*$grades_RET[$grade][1]['GP_SCALE'];
					}
					$unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];
					$scale = $grades_RET[$grade][1]['GP_SCALE'];
			}
			else
				$percent = $grade = $letter = $weighted = $unweighted = $scale = '';

//modif Francois: fix bug SQL ID=NULL
//modif Francois: add CLASS_RANK
//modif Francois: add Credit Hours
 			$sql = "INSERT INTO STUDENT_REPORT_CARD_GRADES (ID, SYEAR,SCHOOL_ID,STUDENT_ID,COURSE_PERIOD_ID,MARKING_PERIOD_ID,REPORT_CARD_GRADE_ID,GRADE_PERCENT,COMMENT,GRADE_LETTER,WEIGHTED_GP,UNWEIGHTED_GP,GP_SCALE,COURSE_TITLE,CREDIT_ATTEMPTED,CREDIT_EARNED,CLASS_RANK,CREDIT_HOURS)
				values(".db_seq_nextval('student_report_card_grades_seq').",'".UserSyear()."','".UserSchool()."','".$student_id."','".$course_period_id."','".$_REQUEST['mp']."','".$grade."','".$percent."','".$columns['comment']."','".$grades_RET[$grade][1]['TITLE']."','".$weighted."','".$unweighted."','".$scale."','".$course_RET[1]['COURSE_NAME']."','".$course_RET[1]['CREDITS']."','".($weighted&&$weighted>0?$course_RET[1]['CREDITS']:'0')."','".$course_RET[1]['CLASS_RANK']."',".(is_null($course_RET[1]['CREDIT_HOURS']) ? 'NULL' : $course_RET[1]['CREDIT_HOURS']).")";
		}
		else
			$percent = $grade = '';

		if($sql)
		{
			DBQuery($sql);
		}
		//DBQuery("DELETE FROM STUDENT_REPORT_CARD_GRADES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."'");
		if(!($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']<0?$grade:($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']>0?$percent!='':$percent!=''&&$grade)))
			$completed = false;

		if (is_array($columns['commentsA']))
			foreach($columns['commentsA'] as $id=>$comment)
				if($current_commentsA_RET[$student_id][$id])
					if($comment)
						DBQuery("UPDATE STUDENT_REPORT_CARD_COMMENTS SET COMMENT='".$comment."' WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND REPORT_CARD_COMMENT_ID='".$id."'");
					else
						DBQuery("DELETE FROM STUDENT_REPORT_CARD_COMMENTS WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND REPORT_CARD_COMMENT_ID='".$id."'");
				elseif($comment)
						DBQuery("INSERT INTO STUDENT_REPORT_CARD_COMMENTS (SYEAR,SCHOOL_ID,STUDENT_ID,COURSE_PERIOD_ID,MARKING_PERIOD_ID,REPORT_CARD_COMMENT_ID,COMMENT)
							values('".UserSyear()."','".UserSchool()."','".$student_id."','".$course_period_id."','".$_REQUEST['mp']."','".$id."','".$comment."')");

		// create mapping for current
		$old = array();
		if (is_array($current_commentsB_RET[$student_id]))
			foreach($current_commentsB_RET[$student_id] as $i=>$comment)
				$old[$comment['REPORT_CARD_COMMENT_ID']] = $i;
		// create change list
		$change = array();
		foreach($columns['commentsB'] as $i=>$comment)
			$change[$i] = array('REPORT_CARD_COMMENT_ID'=>0);
		// prune changes already in current set and reserve if in change list
		foreach($columns['commentsB'] as $i=>$comment)
			if($comment)
				if($old[$comment])
				{
					if($change[$old[$comment]])
						$change[$old[$comment]]['REPORT_CARD_COMMENT_ID'] = $comment;
					$columns['commentsB'][$i] = false;
				}
		// assign changes at their index if possible
		$new = array();
		foreach($columns['commentsB'] as $i=>$comment)
			if($comment)
				if(!$new[$comment])
				{
					if(!$change[$i]['REPORT_CARD_COMMENT_ID'])
					{
						$change[$i]['REPORT_CARD_COMMENT_ID'] = $comment;
						$new[$comment] = $i;
						$columns['commentsB'][$i] = false;
					}
				}
				else
					$columns['commentsB'][$i] = false;
		// assign remaining changes to first available
		reset($change);
		foreach($columns['commentsB'] as $i=>$comment)
			if($comment)
			{
				if(!$new[$comment])
				{
					while($change[key($change)]['REPORT_CARD_COMMENT_ID'])
						next($change);
					$change[key($change)]['REPORT_CARD_COMMENT_ID'] = $comment;
					$new[$comment] = key($change);
				}
				$columns['commentsB'][$i] = false;
			}

		// update the db
		foreach($change as $i=>$comment)
			if($current_commentsB_RET[$student_id][$i])
				if($comment['REPORT_CARD_COMMENT_ID'])
				{
					if($comment['REPORT_CARD_COMMENT_ID']!=$current_commentsB_RET[$student_id][$i]['REPORT_CARD_COMMENT_ID'])
						DBQuery("UPDATE STUDENT_REPORT_CARD_COMMENTS SET REPORT_CARD_COMMENT_ID='".$comment['REPORT_CARD_COMMENT_ID']."' WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND REPORT_CARD_COMMENT_ID='".$current_commentsB_RET[$student_id][$i]['REPORT_CARD_COMMENT_ID']."'");
				}
				else
					DBQuery("DELETE FROM STUDENT_REPORT_CARD_COMMENTS WHERE STUDENT_ID='".$student_id."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND REPORT_CARD_COMMENT_ID='".$current_commentsB_RET[$student_id][$i]['REPORT_CARD_COMMENT_ID']."'");
			else
				if($comment['REPORT_CARD_COMMENT_ID'])
					DBQuery("INSERT INTO STUDENT_REPORT_CARD_COMMENTS (SYEAR,SCHOOL_ID,STUDENT_ID,COURSE_PERIOD_ID,MARKING_PERIOD_ID,REPORT_CARD_COMMENT_ID)
						values('".UserSyear()."','".UserSchool()."','".$student_id."','".$course_period_id."','".$_REQUEST['mp']."','".$comment['REPORT_CARD_COMMENT_ID']."')");
	}

	if($completed)
	{
		if(!$current_completed)
			DBQuery("INSERT INTO GRADES_COMPLETED (STAFF_ID,MARKING_PERIOD_ID,COURSE_PERIOD_ID) values('".User('STAFF_ID')."','".$_REQUEST['mp']."','$course_period_id')");
	}
	else
		if($current_completed)
			DBQuery("DELETE FROM GRADES_COMPLETED WHERE STAFF_ID='".User('STAFF_ID')."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND COURSE_PERIOD_ID='".$course_period_id."'");

	$current_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM STUDENT_REPORT_CARD_GRADES g WHERE g.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."'"),array(),array('STUDENT_ID'));
	if($_REQUEST['tab_id']=='-1')
	{
        	$current_commentsB_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID FROM STUDENT_REPORT_CARD_COMMENTS g,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM REPORT_CARD_COMMENTS WHERE COURSE_ID IS NULL)"),array(),array('STUDENT_ID'));
        	$max_current_commentsB = 0;
        	foreach($current_commentsB_RET as $comments)
                	if(count($comments)>$max_current_commentsB)
                        	$max_current_commentsB = count($comments);
	}
	elseif($_REQUEST['tab_id']=='0')
        	$current_commentsA_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM STUDENT_REPORT_CARD_COMMENTS g,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM REPORT_CARD_COMMENTS WHERE COURSE_ID='0')"),array(),array('STUDENT_ID','REPORT_CARD_COMMENT_ID'));
	elseif($_REQUEST['tab_id'])
		$current_commentsA_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM STUDENT_REPORT_CARD_COMMENTS g,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='".$course_period_id."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM REPORT_CARD_COMMENTS WHERE CATEGORY_ID='".$_REQUEST['tab_id']."')"),array(),array('STUDENT_ID','REPORT_CARD_COMMENT_ID'));
	$current_completed = count(DBGet(DBQuery("SELECT '' FROM GRADES_COMPLETED WHERE STAFF_ID='".User('STAFF_ID')."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND COURSE_PERIOD_ID='".$course_period_id."'")));
	unset($_SESSION['_REQUEST_vars']['values']);
}

if($_REQUEST['values'] && $_POST['values'] && $_REQUEST['submit']['cancel'])
{
	unset($_SESSION['_REQUEST_vars']['values']);
}

$time = strtotime(DBDate('postgres'));

$mps_select = '<script type="text/javascript">var mponchange = document.createElement("a"); mponchange.href = "Modules.php?modname='.$_REQUEST['modname'].'&include_inactive='.$_REQUEST['include_inactive'].'&mp="; mponchange.target = "body";</script>';
$mps_select .= '<SELECT name="mp" onchange="mponchange.href += this.options[selectedIndex].value; ajaxLink(mponchange);">';
if($pros!='')
	foreach(explode(',',str_replace("'",'',$pros)) as $pro)
	{
		if($_REQUEST['mp']==$pro && GetMP($pro,'POST_START_DATE') && ($time>=strtotime(GetMP($pro,'POST_START_DATE')) && $time<=strtotime(GetMP($pro,'POST_END_DATE'))))
			$allow_edit = true;
		if(GetMP($pro,'DOES_GRADES')=='Y')
			$mps_select .= '<OPTION value="'.$pro.'"'.(($pro==$_REQUEST['mp'])?' SELECTED="SELECTED"':'').">".GetMP($pro)."</OPTION>";
	}

if($_REQUEST['mp']==UserMP() && GetMP(UserMP(),'POST_START_DATE') && ($time>=strtotime(GetMP(UserMP(),'POST_START_DATE')) && $time<=strtotime(GetMP(UserMP(),'POST_END_DATE'))))
	$allow_edit = true;
$mps_select .= '<OPTION value="'.UserMP().'"'.((UserMP()==$_REQUEST['mp'])?' SELECTED="SELECTED"':'').">".GetMP(UserMP())."</OPTION>";

if(($_REQUEST['mp']==$sem) && GetMP($sem,'POST_START_DATE') && ($time>=strtotime(GetMP($sem,'POST_START_DATE')) && $time<=strtotime(GetMP($sem,'POST_END_DATE'))))
	$allow_edit = true;
if(GetMP($sem,'DOES_GRADES')=='Y')
	$mps_select .= '<OPTION value="'.$sem.'"'.(($sem==$_REQUEST['mp'])?' SELECTED="SELECTED"':'').">".GetMP($sem)."</OPTION>";

if(($_REQUEST['mp']==$fy) && GetMP($fy,'POST_START_DATE') && ($time>=strtotime(GetMP($fy,'POST_START_DATE')) && $time<=strtotime(GetMP($fy,'POST_END_DATE'))))
	$allow_edit = true;
if(GetMP($fy,'DOES_GRADES')=='Y')
	$mps_select .= '<OPTION value="'.$fy.'"'.(($fy==$_REQUEST['mp'])?' SELECTED="SELECTED"':'').">".GetMP($fy)."</OPTION>";

$mps_select .= '</SELECT>';

// modif Francois: add Grade posting dates (see Marking periods) limitation for teachers:
$grade_posting_RET = DBGet(DBQuery("SELECT 1 FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND (POST_START_DATE IS NULL OR POST_START_DATE<=CURRENT_DATE) AND (POST_END_DATE IS NULL OR POST_END_DATE>=CURRENT_DATE)"));

// if running as a teacher program then rosario[allow_edit] will already be set according to admin permissions
if(!isset($_ROSARIO['allow_edit']))
	$_ROSARIO['allow_edit'] = ($program_config['GRADES_TEACHER_ALLOW_EDIT'][1]['VALUE']||$allow_edit)&&!empty($grade_posting_RET);

$extra['SELECT'] = ",ssm.STUDENT_ID AS REPORT_CARD_GRADE";
$extra['functions'] = array('REPORT_CARD_GRADE'=>'_makeLetterPercent');

if(GetMP($_REQUEST['mp'],'DOES_COMMENTS')=='Y')
{
	//modif Francois: fix error Warning: Invalid argument supplied for foreach()
	if (isset($commentsA_RET))
	{
		foreach($commentsA_RET as $value)
		{
			$extra['SELECT'] .= ',\''.$value['ID'].'\' AS CA'.$value['ID'].',\''.$value['SCALE_ID'].'\' AS CAC'.$value['ID'];
			$extra['functions'] += array('CA'.$value['ID']=>'_makeCommentsA');
		}
	}
	for($i=1; $i<=$max_current_commentsB; $i++)
	{
		$extra['SELECT'] .= ',\''.$i.'\' AS CB'.$i;
		$extra['functions'] += array('CB'.$i=>'_makeCommentsB');
	}
	if(count($commentsB_select) && AllowEdit())
	{
		$extra['SELECT'] .= ',\''.$i.'\' AS CB'.$i;
		$extra['functions'] += array('CB'.$i=>'_makeCommentsB');
	}
}
$extra['SELECT'] .= ",'' AS COMMENTS,'' AS COMMENT";
$extra['functions'] += array('COMMENT'=>'_makeComment');
$extra['MP'] = UserMP();
$extra['DATE'] = GetMP($_REQUEST['mp'],'END_DATE');

$stu_RET = GetStuList($extra);

echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].(count($categories_RET)&&GetMP($_REQUEST['mp'],'DOES_COMMENTS')=='Y'?'&tab_id='.$_REQUEST['tab_id']:'').'" method="POST">';

if(!isset($_REQUEST['_ROSARIO_PDF']))
{
	if(count($commentsB_RET))
	{
		foreach($commentsB_RET as $comment)
			$tipmessage .= $comment[1]['SORT_ORDER'].' - '.str_replace("'",'&acute;',$comment[1]['TITLE']).'<BR />';
//		$tipmessage = button('comment','Comment Codes','# onClick=\'stm(["Report Card Comments","'.$tipmessage.'"],["white","#333366","","","",,"black","#e8e8ff","","","",,,,2,"#333366",2,,,,,"",5,3,50,50]);\'','');
		$tipmessage = button('comment',_('Comment Codes'),'"#" onmouseover=\'stm(["'._('Report Card Comments').'","'.str_replace('"','\"',str_replace("'",'&#39;',$tipmessage)).'"],tipmessageStyle); return false;\' onmouseout=\'htm()\'',24);
	}

//modif Francois: add label on checkbox
	DrawHeader($mps_select,SubmitButton(_('Save')),'<label>'.CheckBoxOnclick('include_inactive').'&nbsp;'._('Include Inactive Students').'</label>');
	
//modif FRancois: add grade posting dates
	$grade_posting_dates = DBGet(DBQuery("SELECT POST_START_DATE,POST_END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".$_REQUEST['mp']."' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' LIMIT 1"));
	$grade_posting_dates_text = '';
	if ($grade_posting_dates)
		$grade_posting_dates_text = ' '.sprintf(_('Grade Posting dates: %s - %s'),ProperDate($grade_posting_dates[1]['POST_START_DATE']),ProperDate($grade_posting_dates[1]['POST_END_DATE']));
		
//modif Francois: add translation
	DrawHeader(($current_completed?'<span style="color:green">'._('These grades are complete.').'</span>':'<span style="color:red">'._('These grades are NOT complete.').'</span>').(AllowEdit()?' | <span style="color:green">'._('You can edit these grades.').$grade_posting_dates_text.'</span>':' | <span style="color:red">'._('You can not edit these grades.').$grade_posting_dates_text.'</span>'));

	if(AllowEdit())
	{
		$gb_header .= '<A HREF="Modules.php?modname='.$_REQUEST['modname'].'&include_inactive='.$_REQUEST['include_inactive'].'&modfunc=gradebook&mp='.$_REQUEST['mp'].'">'._('Get Gradebook Grades.').'</A>';
		$prev_mp = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,START_DATE FROM SCHOOL_MARKING_PERIODS WHERE MP='".GetMP($_REQUEST['mp'],'MP')."' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND START_DATE<'".GetMP($_REQUEST['mp'],'START_DATE')."' ORDER BY START_DATE DESC LIMIT 1"));
		$prev_mp = $prev_mp[1];
		//modif Francois: remove Get previous MP Grades & Comments if course period's marking period is a quarter
		$mp_is_quarter = DBGet(DBQuery("SELECT '' FROM COURSE_PERIODS WHERE MP='QTR' AND COURSE_PERIOD_ID='".$course_period_id."'"));
		if($prev_mp && !$mp_is_quarter)
		{
			$gb_header .= ' | <A HREF="Modules.php?modname='.$_REQUEST['modname'].'&include_inactive='.$_REQUEST['include_inactive'].'&modfunc=grades&tab_id='.$_REQUEST['tab_id'].'&mp='.$_REQUEST['mp'].'&prev_mp='.$prev_mp['MARKING_PERIOD_ID'].'">'.sprintf(_('Get %s Grades'),$prev_mp['TITLE']).'</A>';
			$gb_header .= ' | <A HREF="Modules.php?modname='.$_REQUEST['modname'].'&include_inactive='.$_REQUEST['include_inactive'].'&modfunc=comments&tab_id='.$_REQUEST['tab_id'].'&mp='.$_REQUEST['mp'].'&prev_mp='.$prev_mp['MARKING_PERIOD_ID'].'">'.sprintf(_('Get %s Comments'),$prev_mp['TITLE']).'</A>';
		}
		$gb_header .= ' | ';
		$gb_header .= '<A HREF="Modules.php?modname='.$_REQUEST['modname'].'&include_inactive='.$_REQUEST['include_inactive'].'&modfunc=clearall&tab_id='.$_REQUEST['tab_id'].'&mp='.$_REQUEST['mp'].'">'._('Clear All').'</A>';
	}
	DrawHeader($gb_header,$tipmessage);
}
else
{
	DrawHeader($course_title);
	DrawHeader(GetMP(UserMP()));
}

$LO_columns = array('FULL_NAME'=>_('Student'),'STUDENT_ID'=>_('RosarioSIS ID'));
if($_REQUEST['include_inactive']=='Y')
	$LO_columns += array('ACTIVE'=>_('School Status'),'ACTIVE_SCHEDULE'=>_('Course Status'));
$LO_columns += array('REPORT_CARD_GRADE'=>($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']<0?_('Letter'):($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']>0?_('Percent'):'<span class="nobr">'._('Letter').' '._('Percent').'</span>')));

if(GetMP($_REQUEST['mp'],'DOES_COMMENTS')=='Y')
{
	//modif Francois: fix error Warning: Invalid argument supplied for foreach()
	if (isset($commentsA_RET))
	{
		foreach($commentsA_RET as $value)
			$LO_columns += array('CA'.$value['ID']=>$value['TITLE']);
	}
	for($i=1; $i<=$max_current_commentsB; $i++)
		$LO_columns += array('CB'.$i=>sprintf(_('Comment %d'),$i));
	if(count($commentsB_select) && AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
		$LO_columns += array('CB'.$i=>_('Add Comment'));
}
if(!$program_config['GRADES_HIDE_NON_ATTENDANCE_COMMENT'][1]['VALUE'] || $course_RET[1]['ATTENDANCE']=='Y')
	$LO_columns += array('COMMENT'=>_('Comment'));

foreach($categories_RET as $id=>$category)
	$tabs[] = array('title'=>$category[1]['TITLE'],'link'=>"Modules.php?modname=$_REQUEST[modname]&mp=$_REQUEST[mp]&tab_id=$id")+($category[1]['COLOR']?array('color'=>$category[1]['COLOR']):array());
$LO_options = array('yscroll'=>true,'save'=>false,'search'=>false);
if(count($categories_RET) && GetMP($_REQUEST['mp'],'DOES_COMMENTS')=='Y')
{
	$LO_options['header'] = WrapTabs($tabs,"Modules.php?modname=$_REQUEST[modname]&mp=$_REQUEST[mp]&tab_id=$_REQUEST[tab_id]");
	if($categories_RET[$_REQUEST['tab_id']][1]['COLOR'])
		$LO_options['header_color'] = $categories_RET[$_REQUEST['tab_id']][1]['COLOR'];
}
echo '<BR />';
ListOutput($stu_RET,$LO_columns,'Student','Students',false,array(),$LO_options);
echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
echo '</FORM>';

function _makeLetterPercent($student_id,$column)
{	global $THIS_RET,$current_RET,$import_RET,$grades_select,$student_count,$tabindex,$grade_scale_id,$program_config;

	if($import_RET[$student_id])
	{
		$select_percent = $import_RET[$student_id][1]['GRADE_PERCENT'];
		$select_grade = $import_RET[$student_id][1]['REPORT_CARD_GRADE_ID'];
		$div = false;
	}
	else
	{
		$select_percent = $current_RET[$student_id][1]['GRADE_PERCENT'];
		$select_grade = $current_RET[$student_id][1]['REPORT_CARD_GRADE_ID'];
		$div = true;
	}

	if(!isset($_REQUEST['_ROSARIO_PDF']))
	{
		$student_count++;
		$tabindex = $student_count;

		if($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']<0)
			$return = SelectInput($select_grade,'values['.$student_id.'][grade]','',$grades_select,false,'tabindex='.$tabindex,$div);
		elseif($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']>0)
			$return = TextInput($select_percent==''?'':$select_percent.'%',"values[$student_id][percent]",'','size=5 tabindex='.$tabindex,$div);
		else
		{
			if(AllowEdit() && $div && $select_percent!='' && $select_grade && Preferences('HIDDEN')=='Y')
			{
				$return = '<DIV id="'.$student_id.'"><div class="onclick" onclick=\'addHTML("';
				
				$select = '<span class="nobr">'.SelectInput($select_grade,'values['.$student_id.'][grade]','',$grades_select,false,'tabindex="'.$tabindex.'"',false).' '.TextInput($select_percent!=''?$select_percent.'%':'',"values[$student_id][percent]",'','size="5" tabindex="'.($tabindex+=100).'"',false).'</span>';
				
				$return .=  str_replace('"','\"',$select);
				$return .= '","'.$student_id.'",true);\'><span class="underline-dots">'.'<span class="nobr">'.($grades_select[$select_grade]?$grades_select[$select_grade][1]:'<span style="color:red">'.$select_grade.'</span>').' '.$select_percent.'%'.'</span>'.'</span></div></DIV>';
			}
			else
				$return = '<span class="nobr">'.SelectInput($select_grade?$select_grade:($select_percent!=''?' ':''),'values['.$student_id.'][grade]','',$grades_select,false,'tabindex="'.$tabindex.'"',false).' '.TextInput($select_percent!=''?$select_percent.'%':($select_grade?'%':''),"values[$student_id][percent]",'','size="5" tabindex="'.($tabindex+=100).'"',false).'</span>';
		}
	}
	else
	{
		if($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']<0)
			$return = ($grades_select[$select_grade]?$grades_select[$select_grade][1]:'<span style="color:red">'.$select_grade.'</span>');
		elseif($program_config['GRADES_DOES_LETTER_PERCENT'][1]['VALUE']>0)
			$return = $select_percent.'%';
		else
			$return = '<span class="nobr">'.($grades_select[$select_grade]?$grades_select[$select_grade][1]:'<span style="color:red">'.$select_grade.'</span>').' '.$select_percent.'%'.'</span>';
	}

	return $return;
}

function _makeComment($value,$column)
{	global $THIS_RET,$current_RET,$import_comments_RET,$tabindex;

	if($import_comments_RET[$THIS_RET['STUDENT_ID']])
	{
		$select = $import_comments_RET[$THIS_RET['STUDENT_ID']][1]['COMMENT'];
		$div = false;
	}
	else
	{
		$select = $current_RET[$THIS_RET['STUDENT_ID']][1]['COMMENT'];
		$div = true;
	}

	if(!isset($_REQUEST['_ROSARIO_PDF']))
		$return = TextInput($select,"values[$THIS_RET[STUDENT_ID]][comment]",'','size=20 maxlength=255 tabindex='.($tabindex+=100),$div);
	else
		$return = $select;

	return $return;
}

function _makeCommentsA($value,$column)
{	global $THIS_RET,$current_commentsA_RET,$import_commentsA_RET,$commentsA_select,$tabindex;

	if($import_commentsA_RET[$THIS_RET['STUDENT_ID']][$value])
	{
		$select = $import_commentsA_RET[$THIS_RET['STUDENT_ID']][$value][1]['COMMENT'];
		$div = false;
	}
	else
	{
		if(!$current_commentsA_RET[$THIS_RET['STUDENT_ID']][$value][1]['COMMENT'] && !$import_commentsA_RET && AllowEdit())
		{
			$select = Preferences('COMMENT_'.$THIS_RET['CAC'.$value],'Gradebook');
			$div = false;
		}
		else
		{
			$select = $current_commentsA_RET[$THIS_RET['STUDENT_ID']][$value][1]['COMMENT'];
			$div = true;
		}
	}

	if(!isset($_REQUEST['_ROSARIO_PDF']))
		$return = SelectInput($select,'values['.$THIS_RET['STUDENT_ID'].'][commentsA]['.$value.']','',$commentsA_select[$THIS_RET['CAC'.$value]],_('N/A'),'tabindex='.($tabindex+=100),$div);
	else
		$return = $select!=' ' ? $select : 'o';

	return $return;
}
function _makeCommentsB($value,$column)
{	global $THIS_RET,$current_commentsB_RET,$import_commentsB_RET,$commentsB_RET,$max_current_commentsB,$commentsB_select,$tabindex;

	if($import_commentsB_RET[$THIS_RET['STUDENT_ID']][$value])
	{
		$select = $import_commentsB_RET[$THIS_RET['STUDENT_ID']][$value]['REPORT_CARD_COMMENT_ID'];
		$div = false;
	}
	else
	{
		$select = $current_commentsB_RET[$THIS_RET['STUDENT_ID']][$value]['REPORT_CARD_COMMENT_ID'];
		$div = true;
	}

	if(!isset($_REQUEST['_ROSARIO_PDF']))
		if($value>$max_current_commentsB)
			$return = SelectInput('','values['.$THIS_RET['STUDENT_ID'].'][commentsB]['.$value.']','',$commentsB_select,_('N/A'),'tabindex='.($tabindex+=100));
		elseif($import_commentsB_RET[$THIS_RET['STUDENT_ID']][$value] || isset($current_commentsB_RET[$THIS_RET['STUDENT_ID']][$value]))
			$return = SelectInput($select,'values['.$THIS_RET['STUDENT_ID'].'][commentsB]['.$value.']','',$commentsB_select,_('N/A'),'tabindex='.($tabindex+=100),$div);
		else
			$return = '';
	else
		$return = ''.$commentsB_RET[$select][1]['TITLE'].'';

	return $return;
}
?>