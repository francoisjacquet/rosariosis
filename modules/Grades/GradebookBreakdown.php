<?php
/**
* @file $Id: StudentFieldBreakdown.php 295 2006-11-15 04:39:23Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

include 'ProgramFunctions/_makeLetterGrade.fnc.php';

if(!$_REQUEST['assignment_id'])
	$_REQUEST['assignment_id'] = 'totals';

//modif Francois: fix errors relation «course_weights» doesnt exist & columns c.grad_subject_id & cp.does_grades & cp.does_gpa do not exist
//$course_id = DBGet(DBQuery("SELECT c.GRAD_SUBJECT_ID,cp.COURSE_ID,cp.TITLE,c.TITLE AS COURSE_TITLE,c.SHORT_NAME AS COURSE_NUM,cw.CREDITS,cw.GPA_MULTIPLIER,cp.DOES_GRADES,cp.GRADE_SCALE_ID,cp.DOES_GPA as AFFECTS_GPA FROM COURSE_PERIODS cp,COURSES c,COURSE_WEIGHTS cw WHERE cw.COURSE_ID=cp.COURSE_ID AND cw.COURSE_WEIGHT=cp.COURSE_WEIGHT AND c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".UserCoursePeriod()."'"));
$course_id = DBGet(DBQuery("SELECT cp.COURSE_ID,cp.TITLE,c.TITLE AS COURSE_TITLE,c.SHORT_NAME AS COURSE_NUM,cp.GRADE_SCALE_ID FROM COURSE_PERIODS cp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.COURSE_PERIOD_ID='".UserCoursePeriod()."'"));
$grade_scale_id = $course_id[1]['GRADE_SCALE_ID'];
$course_id = $course_id[1]['COURSE_ID'];

//modif Francois: fix error column scale_id doesnt exist
//$grades_RET = DBGet(DBQuery("SELECT ID,TITLE FROM REPORT_CARD_GRADES WHERE SCALE_ID='".$grade_scale_id."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
$grades_RET = DBGet(DBQuery("SELECT ID,TITLE,GPA_VALUE FROM REPORT_CARD_GRADES WHERE GRADE_SCALE_ID='".$grade_scale_id."' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
$grades = array();
foreach($grades_RET as $grade)
{
	$grades[] = array('TITLE' => $grade['TITLE'], 'GPA_VALUE' => $grade['GPA_VALUE']);
}
//$grades[] = _('N/A');

//modif Francois: fix error column USERNAME doesnt exist
//$config_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_USER_CONFIG WHERE USERNAME='".User('USERNAME')."' AND PROGRAM='Gradebook'"),array(),array('TITLE'));
$config_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_USER_CONFIG WHERE USER_ID='".User('STAFF_ID')."' AND PROGRAM='Gradebook'"),array(),array('TITLE'));
if(count($config_RET))
{
	foreach($config_RET as $title=>$value)
		$programconfig[$title] = $value[1]['VALUE'];
}

$sql = "SELECT ASSIGNMENT_TYPE_ID,TITLE FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE STAFF_ID='".User('STAFF_ID')."' AND COURSE_ID='".$course_id."' ORDER BY TITLE";
$types_RET = DBGet(DBQuery($sql));

$assignments_RET = DBGet(DBQuery("SELECT ASSIGNMENT_ID,TITLE,POINTS FROM GRADEBOOK_ASSIGNMENTS WHERE STAFF_ID='".User('STAFF_ID')."' AND ((COURSE_ID='$course_id' AND STAFF_ID='".User('STAFF_ID')."') OR COURSE_PERIOD_ID='".UserCoursePeriod()."') AND MARKING_PERIOD_ID='".UserMP()."' ORDER BY ".Preferences('ASSIGNMENT_SORTING','Gradebook')." DESC"));
$assignment_select = '<script type="text/javascript">var assignment_idonchange = document.createElement("a"); assignment_idonchange.href = "Modules.php?modname='.$_REQUEST['modname'].'&assignment_id="; assignment_idonchange.target = "body";</script>';
$assignment_select .= '<SELECT name="assignment_id" id="assignment_id" onchange="assignment_idonchange.href += this.options[selectedIndex].value; ajaxLink(assignment_idonchange);"><OPTION value="totals"'.($_REQUEST['assignment_id']=='totals'?' SELECTED="SELECTED"':'').'>'._('Totals').'</OPTION>';
foreach($types_RET as $type)
{
	$assignment_select .= '<OPTION value="totals'.$type['ASSIGNMENT_TYPE_ID'].'"'.(($_REQUEST['assignment_id']==('totals'.$type['ASSIGNMENT_TYPE_ID']))?' SELECTED="SELECTED"':'').'>'.$type['TITLE'].'</OPTION>';
	if($_REQUEST['assignment_id']==('totals'.$type['ASSIGNMENT_TYPE_ID']))
		$title = $type['TITLE'];
}

if(count($assignments_RET))
{
	foreach($assignments_RET as $assignment)
	{
		$assignment_select .= '<OPTION value="'.$assignment['ASSIGNMENT_ID'].'"'.(($_REQUEST['assignment_id']==$assignment['ASSIGNMENT_ID'])?' SELECTED="SELECTED"':'').'>'.$assignment['TITLE'].'</OPTION>';
		if($_REQUEST['assignment_id']==$assignment['ASSIGNMENT_ID'])
			$title = $assignment['TITLE'];
	}
}
$assignment_select .= '</SELECT>';

if($_REQUEST['assignment_id']=='totals')
{
	$title = _('Grade');
	$extra['SELECT_ONLY'] .= "ssm.STUDENT_ID,'' AS LETTER_GRADE";
	$extra['functions'] = array('LETTER_GRADE'=>'_makeGrade');

	$current_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,sum(".db_case(array('g.POINTS',"'-1'","'0'",'g.POINTS')).") AS POINTS,sum(".db_case(array('g.POINTS',"'-1'","'0'",'a.POINTS')).") AS TOTAL_POINTS FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND a.MARKING_PERIOD_ID='".UserMP()."' AND g.COURSE_PERIOD_ID='".UserCoursePeriod()."' AND (a.COURSE_PERIOD_ID='".UserCoursePeriod()."' OR a.COURSE_ID='$course_id') GROUP BY g.STUDENT_ID"),array(),array('STUDENT_ID'));

	if($programconfig['WEIGHT']=='Y')
		$percent_RET = DBGet(DBQuery("SELECT gt.ASSIGNMENT_TYPE_ID,gg.STUDENT_ID,".db_case(array("sum(".db_case(array('gg.POINTS',"'-1'","'0'",'ga.POINTS')).")","'0'","'0'","(sum(".db_case(array('gg.POINTS',"'-1'","'0'",'gg.POINTS')).") * gt.FINAL_GRADE_PERCENT / sum(".db_case(array('gg.POINTS',"'-1'","'0'",'ga.POINTS'))."))"))." AS PARTIAL_PERCENT FROM GRADEBOOK_GRADES gg, GRADEBOOK_ASSIGNMENTS ga, GRADEBOOK_ASSIGNMENT_TYPES gt WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND ga.ASSIGNMENT_ID=gg.ASSIGNMENT_ID AND ga.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") AND gg.COURSE_PERIOD_ID='".UserCoursePeriod()."' AND gt.COURSE_ID='$course_id' GROUP BY gg.STUDENT_ID,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT"),array(),array('STUDENT_ID','ASSIGNMENT_TYPE_ID'));

	foreach($assignments_RET as $assignment)
		$total_points[$assignment['ASSIGNMENT_ID']] = $assignment['POINTS'];
}
elseif(!is_numeric($_REQUEST['assignment_id']))
{
	$type_id = mb_substr($_REQUEST['assignment_id'],6);
	$extra['SELECT_ONLY'] .= "ssm.STUDENT_ID,'' AS LETTER_GRADE";
	$extra['functions'] = array('LETTER_GRADE'=>'_makeGrade');

	$current_RET = DBGet(DBQuery("SELECT g.STUDENT_ID,sum(".db_case(array('g.POINTS',"'-1'","'0'",'g.POINTS')).") AS POINTS,sum(".db_case(array('g.POINTS',"'-1'","'0'",'a.POINTS')).") AS TOTAL_POINTS FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND a.MARKING_PERIOD_ID='".UserMP()."' AND g.COURSE_PERIOD_ID='".UserCoursePeriod()."' AND (a.COURSE_PERIOD_ID='".UserCoursePeriod()."' OR a.COURSE_ID='$course_id') AND a.ASSIGNMENT_TYPE_ID='$type_id' GROUP BY g.STUDENT_ID"),array(),array('STUDENT_ID'));

	if($programconfig['WEIGHT']=='Y')
		$percent_RET = DBGet(DBQuery("SELECT gt.ASSIGNMENT_TYPE_ID,gg.STUDENT_ID,".db_case(array("sum(".db_case(array('gg.POINTS',"'-1'","'0'",'ga.POINTS')).")","'0'","'0'","(sum(".db_case(array('gg.POINTS',"'-1'","'0'",'gg.POINTS')).") / sum(".db_case(array('gg.POINTS',"'-1'","'0'",'ga.POINTS'))."))"))." AS PARTIAL_PERCENT FROM GRADEBOOK_GRADES gg, GRADEBOOK_ASSIGNMENTS ga, GRADEBOOK_ASSIGNMENT_TYPES gt WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND ga.ASSIGNMENT_TYPE_ID='$type_id' AND ga.ASSIGNMENT_ID=gg.ASSIGNMENT_ID AND ga.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") AND gg.COURSE_PERIOD_ID='".UserCoursePeriod()."' AND gt.COURSE_ID='$course_id' GROUP BY gg.STUDENT_ID,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT"),array(),array('STUDENT_ID','ASSIGNMENT_TYPE_ID'));

	foreach($assignments_RET as $assignment)
		$total_points[$assignment['ASSIGNMENT_ID']] = $assignment['POINTS'];	
}
elseif($_REQUEST['assignment_id'])
{
	$extra['SELECT_ONLY'] .= "ssm.STUDENT_ID,'' AS LETTER_GRADE";
	$extra['functions'] = array('LETTER_GRADE'=>'_makeGrade');
	$total_points = DBGet(DBQuery("SELECT POINTS FROM GRADEBOOK_ASSIGNMENTS WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]'"));
	$total_points = $total_points[1]['POINTS'];
	$current_RET = DBGet(DBQuery("SELECT STUDENT_ID,POINTS,COMMENT,ASSIGNMENT_ID FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='$_REQUEST[assignment_id]' AND COURSE_PERIOD_ID='".UserCoursePeriod()."'"),array(),array('STUDENT_ID','ASSIGNMENT_ID'));
}

if(!$_REQUEST['chart_type'])
	$_REQUEST['chart_type'] = 'column';

//		$_REQUEST['modfunc'] = 'SendChartData';

//if($_REQUEST['modfunc']=='SendChartData' || $_REQUEST['chart_type']=='list')
//{
	$chart['chart_type'] = $_REQUEST['chart_type'];
	$chart['series_switch'] = true;
	
	if($category_RET[1]['TYPE']=='select' || true)
	{
		$stu_RET = GetStuList($extra);
		foreach($stu_RET as $stu)
			$RET[$stu['LETTER_GRADE']]++;

		$chart['chart_data'][1] = array();
		foreach($grades as $option)
		{
			$chart['chart_data'][0][] = $option['GPA_VALUE'];
			$chart['chart_data'][1][] = (empty($RET[$option['TITLE']]) ? 0 : $RET[$option['TITLE']]);
		}
	}
	
	if($_REQUEST['chart_type']!='list')
	{
//modif Francois: jqplot charts
?>
		<script type="text/javascript">
<?php
		if($_REQUEST['chart_type']=='column')
		{
			$jsData .= 'var datacolumn = [';
			for ($i=0; $i<count($chart['chart_data'][0]); $i++)
			{
				$jsData .= "[".$chart['chart_data'][0][$i].", ".$chart['chart_data'][1][$i]."],";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 1);
			$jsData .= "];\n";
		} else { //pie chart
			$jsData = 'var datapie = [';
			for ($i=1; $i<=count($chart['chart_data'][0]); $i++)
			{
				if ($chart['chart_data'][1][$i] > 0) //remove empty slices not to overload the legends
					$jsData .= "['".$chart['chart_data'][0][$i]."', ".$chart['chart_data'][1][$i]."],";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 1);
			$jsData .= "];\n";
					
		}
		echo $jsData;
		//modif Francois: responsive labels: limit label to 20 char max.
?>
			if (screen.width<768)
			{
				if (window.datapie)
					for(i=0; i<datapie.length; i++)
						datapie[i][0] = datapie[i][0].substr(0, 20);
			}
		</script>
<?php
	}
//}

if(empty($_REQUEST['modfunc']))

{
	unset($_REQUEST['PHPSESSID']);
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&amp;chart_type='.str_replace(' ','+',$_REQUEST['chart_type']).'" method="POST">';
	DrawHeader(ProgramTitle());
	
	DrawHeader($assignment_select.$advanced_link,SubmitButton(_('Go')));


	echo '<BR />';
	if($_REQUEST['assignment_id'])
	{
		$tmp_REQUEST = $_REQUEST;
		unset($tmp_REQUEST['chart_type']);
		$link = PreparePHP_SELF($tmp_REQUEST);
		$tabs = array(array('title'=>_('Line'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=column',$link)),array('title'=>_('Pie'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=3d+pie',$link)),array('title'=>_('List'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=list',$link)));

		$_ROSARIO['selected_tab'] = str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type='.str_replace(' ','+',$_REQUEST['chart_type']),$link);
		PopTable('header',$tabs);

		if($_REQUEST['chart_type']=='list')
		{
			$chart_data = array('0'=>'');

			// IGNORE THE 'Series' ELEMENT
			unset($chart['chart_data'][1][0]);
			foreach($chart['chart_data'][1] as $key=>$value)
				$chart_data[] = array('TITLE'=>$chart['chart_data'][0][$key],'VALUE'=>$value);
			unset($chart_data[0]);
			$LO_options['responsive'] = false;
			ListOutput($chart_data,array('TITLE'=>_('Option'),'VALUE'=>_('Number of Students')),'Grade','Grades',array(),array(),$LO_options);
		}
		else
		{
			$_REQUEST['modfunc'] = 'SendChartData';
			//$_REQUEST['_ROSARIO_PDF'] = 'true';
//modif Francois: jqplot charts
//modif Francois: colorbox
?>
			<script type="text/javascript" src="assets/js/jqplot/jquery.jqplot.min.js"></script>
			<link rel="stylesheet" type="text/css" href="assets/js/jqplot/jquery.jqplot.min.css" />
			<script type="text/javascript">	
				var saveImgText = '<?php echo _('Right Click to Save Image As...'); ?>';
			</script>
<?php
			if($_REQUEST['chart_type']=='column')
			{
?>
				<script type="text/javascript" src="assets/js/jqplot/jquery.jqplot.min.js"></script>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.highlighter.min.js"></script>
				<script type="text/javascript">
					$(document).ready(function(){
							var plotcolumn = $.jqplot('chart', [datacolumn], {
								axesDefaults: {
									pad: 0 //start axes at 0
								},
								highlighter: {
									show: true,
									showLabel: true,
									tooltipAxes: 'x',
								},
								title: '<?php echo $title.' '._('Breakdown'); ?>'
							});
						});		
				</script>
<?php
			} else { //pie chart
?>		
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.pieRenderer.min.js"></script>
				<script type="text/javascript">
					$(document).ready(function(){ 
						var plotpie = $.jqplot('chart', [datapie], {
							seriesDefaults:{
								renderer:$.jqplot.PieRenderer,
							},
							legend:{show:true},
							title: '<?php echo $title.' '._('Breakdown'); ?>'
						});
					});	
				</script>
<?php
			}
?>
			<div id="chart"></div>
			<script type="text/javascript" src="assets/js/colorbox/jquery.colorbox-min.js"></script>
			<link rel="stylesheet" href="assets/js/colorbox/colorbox.css" type="text/css" media="screen" />
			<script type="text/javascript" src="assets/js/jquery.jqplottocolorbox.js"></script>
<?php
			unset($_REQUEST['_ROSARIO_PDF']);
		}
		PopTable('footer');
	}
	echo '</FORM>';
}

function _makeGrade($value,$column)
{	global $THIS_RET,$total_points,$current_RET,$percent_RET,$programconfig;

	if(!is_numeric($_REQUEST['assignment_id']) && !$_REQUEST['student_id'])
	{
		if($programconfig['WEIGHT']=='Y' && count($percent_RET[$THIS_RET['STUDENT_ID']]))
		{
			$total = 0;
			foreach($percent_RET[$THIS_RET['STUDENT_ID']] as $type_id=>$type)
				$total += $type[1]['PARTIAL_PERCENT'];
		}
		elseif($current_RET[$THIS_RET['STUDENT_ID']][1]['TOTAL_POINTS'])
			$total = $current_RET[$THIS_RET['STUDENT_ID']][1]['POINTS'] / $current_RET[$THIS_RET['STUDENT_ID']][1]['TOTAL_POINTS'];
		else
			$total = 0;

		return _makeLetterGrade($total,UserCoursePeriod());
	}
	else
	{
		if($current_RET[$THIS_RET['STUDENT_ID']][$_REQUEST['assignment_id']][1]['POINTS']!='*')
			return _makeLetterGrade($current_RET[$THIS_RET['STUDENT_ID']][$_REQUEST['assignment_id']][1]['POINTS']/$total_points,UserCoursePeriod());
		else
			return _('N/A');
	}
}
?>