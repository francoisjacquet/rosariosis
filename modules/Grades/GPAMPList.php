<?php
DrawHeader(ProgramTitle());
if (!$_REQUEST['LO_sort']) {
    $_REQUEST['LO_sort']="SUM_WEIGHTED_FACTOR";
    $_REQUEST['LO_direction']=-1;
}
if($_REQUEST['search_modfunc'] == 'list')
{
//modif Francois: changed MP list to GradeBreakdown.php style
	/*if(!$_REQUEST['mp'] && GetMP(UserMP(),'POST_START_DATE'))
		$_REQUEST['mp'] = UserMP();
	elseif(mb_strpos(GetAllMP('QTR',UserMP()),$_REQUEST['mp'])===false && mb_strpos(GetChildrenMP('PRO',UserMP()),"'".$_REQUEST['mp']."'")===false && GetMP(UserMP(),'POST_START_DATE'))
		$_REQUEST['mp'] = UserMP();
	
	if(!$_REQUEST['mp'] && GetMP(GetParentMP('SEM',UserMP()),'POST_START_DATE'))
		$_REQUEST['mp'] = GetParentMP('SEM',UserMP());	

	$sem = GetParentMP('SEM',UserMP());
	
//modif Francois: add year to the list
	$year = GetParentMP('FY',$sem);
	$pro = GetChildrenMP('PRO',UserMP());
	$pros = explode(',',str_replace("'",'',$pro));
	$pro_grading = false;
	$pro_select = '';
	foreach($pros as $pro)
	{
		if(GetMP($pro,'DOES_GRADES')=='Y')
		{
			if(!$_REQUEST['mp'])
			{
				$_REQUEST['mp'] = $pro;
				$current_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM STUDENT_REPORT_CARD_GRADES g,COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID='$course_period_id' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."'"),array(),array('STUDENT_ID'));
			}
			$pro_grading = true;
			$pro_select .= '<OPTION value="'.$pro.'"'.(($pro==$_REQUEST['mp'])?' SELECTED="SELECTED"':'').">".GetMP($pro)."</OPTION>";
		}
	}

	$mps_select = '<SELECT name="mp" onChange="ajaxPostForm(this.form,true);">';
	
	if(GetMP(UserMP(),'DOES_GRADES')=='Y')
		$mps_select .= '<OPTION value="'.UserMP().'">'.GetMP(UserMP()).'</OPTION>';
	elseif($_REQUEST['mp']==UserMP())
		$_REQUEST['mp'] = $sem;
	
	if(GetMP($sem,'DOES_GRADES')=='Y')
		$mps_select .= '<OPTION value="'.$sem.'"'.(($sem==$_REQUEST['mp'])?' SELECTED="SELECTED"':'').">".GetMP($sem).'</OPTION>';

//modif Francois: add year to the list
	if(GetMP($year,'DOES_GRADES')=='Y')
        $mps_select .= '<OPTION value="'.$year.'"'.($year==$_REQUEST['mp']?' SELECTED="SELECTED"':'').">".GetMP($year)."</OPTION>";
	
	if($pro_grading)
		$mps_select .= $pro_select;
		
	$mps_select .= '</SELECT>';*/

	if(!$_REQUEST['mp'])
		$_REQUEST['mp'] = UserMP();

	// Get all the mp's associated with the current mp
	$mps_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,0,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."')) AND MP='FY' UNION SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,1,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."') AND MP='SEM' UNION SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,2,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."' UNION SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,3,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID='".UserMP()."' AND MP='PRO' ORDER BY 5,SORT_ORDER"));
 
	//bjj keeping search terms
    $PHP_tmp_SELF = PreparePHP_SELF();
	echo '<FORM action="'.$PHP_tmp_SELF.'" method="POST">';
	
	$mp_select = '<SELECT name="mp" onchange="ajaxPostForm(this.form,true);">';
	foreach($mps_RET as $mp)
	{
		if($mp['DOES_GRADES']=='Y' || $mp['MARKING_PERIOD_ID']==UserMP())
			$mp_select .= '<OPTION value="'.$mp['MARKING_PERIOD_ID'].'"'.($mp['MARKING_PERIOD_ID']==$_REQUEST['mp']?' SELECTED="SELECTED"':'').'>'.$mp['TITLE'].'</OPTION>';
	}
	$mp_select .= "</SELECT>";
	
	DrawHeader($mp_select);
}

Widgets('course');
Widgets('gpa');
Widgets('class_rank');
Widgets('letter_grade'); 

//$extra['SELECT'] .= ',sgc.GPA,sgc.WEIGHTED_GPA,sgc.CLASS_RANK';
$extra['SELECT'] .= ',sms.sum_weighted_factors/sms.gp_credits as sum_weighted_factor, sms.sum_unweighted_factors/sms.gp_credits as sum_unweighted_factor';

if(mb_strpos($extra['FROM'],'STUDENT_MP_STATS sms')===false)
{
	$extra['FROM'] .= ',STUDENT_MP_STATS sms';
	$extra['WHERE'] .= " AND sms.STUDENT_ID=ssm.STUDENT_ID AND sms.MARKING_PERIOD_ID='".$_REQUEST['mp']."'";
}
//modif Francois: add translation 
$extra['columns_after'] = array('SUM_UNWEIGHTED_FACTOR'=>_('Unweighted GPA'),'SUM_WEIGHTED_FACTOR'=>_('Weighted GPA'));
$extra['link']['FULL_NAME'] = false;
$extra['new'] = true;
$extra['functions'] = array('SUM_UNWEIGHTED_FACTOR'=>'_roundGPA','SUM_WEIGHTED_FACTOR'=>'_roundGPA');

if(User('PROFILE')=='parent' || User('PROFILE')=='student')
	$_REQUEST['search_modfunc'] = 'list';
$SCHOOL_RET = DBGet(DBQuery("SELECT * from schools where ID = '".UserSchool()."'"));
Search('student_id',$extra);

function _roundGPA($gpa,$column)
{   GLOBAL $SCHOOL_RET;
    return round($gpa*$SCHOOL_RET[1]['REPORTING_GP_SCALE'],3);

    
}
?>