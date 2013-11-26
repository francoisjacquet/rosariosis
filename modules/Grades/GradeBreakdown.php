<?php
DrawHeader(ProgramTitle());

if(!$_REQUEST['mp'])
	$_REQUEST['mp'] = UserMP();

if(!$_REQUEST['chart_type'])
	$_REQUEST['chart_type'] = 'column';

// Get all the mp's associated with the current mp
$mps_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,0,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."')) AND MP='FY' UNION SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,1,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=(SELECT PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."') AND MP='SEM' UNION SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,2,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='".UserMP()."' UNION SELECT MARKING_PERIOD_ID,TITLE,DOES_GRADES,3,SORT_ORDER FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID='".UserMP()."' AND MP='PRO' ORDER BY 5,SORT_ORDER"));
echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
$mp_select = '<SELECT name="mp" onchange="ajaxPostForm(this.form,true);">';
foreach($mps_RET as $mp)
{
    if($mp['DOES_GRADES']=='Y' || $mp['MARKING_PERIOD_ID']==UserMP())
        $mp_select .= '<OPTION value="'.$mp['MARKING_PERIOD_ID'].'"'.($mp['MARKING_PERIOD_ID']==$_REQUEST['mp']?' SELECTED="SELECTED"':'').'>'.($UserMPTitle = $mp['TITLE']).'</OPTION>';
}
$mp_select .= '</SELECT>';

DrawHeader($mp_select);
echo '</FORM>';

$sql = "SELECT s.LAST_NAME||', '||s.FIRST_NAME as FULL_NAME,s.STAFF_ID,g.REPORT_CARD_GRADE_ID FROM STUDENT_REPORT_CARD_GRADES g,STAFF s,COURSE_PERIODS cp WHERE g.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cp.TEACHER_ID=s.STAFF_ID AND cp.SYEAR=s.SYEAR AND cp.SYEAR=g.SYEAR AND cp.SYEAR='".UserSyear()."' AND g.MARKING_PERIOD_ID='".$_REQUEST['mp']."'";
$grouped_RET = DBGet(DBQuery($sql),array(),array('STAFF_ID','REPORT_CARD_GRADE_ID'));

$grades_RET = DBGet(DBQuery("SELECT rg.ID,rg.TITLE,rg.GPA_VALUE FROM REPORT_CARD_GRADES rg,REPORT_CARD_GRADE_SCALES rs WHERE rg.SCHOOL_ID='".UserSchool()."' AND rg.SYEAR='".UserSyear()."' AND rs.ID=rg.GRADE_SCALE_ID ORDER BY rs.SORT_ORDER,rs.ID,rg.BREAK_OFF IS NOT NULL DESC,rg.BREAK_OFF DESC,rg.SORT_ORDER"));

//modif Francois: jqplot charts
//modif Francois: colorbox
if(count($grouped_RET))
{
	$tmp_REQUEST = $_REQUEST;
	unset($tmp_REQUEST['chart_type']);
	$link = PreparePHP_SELF($tmp_REQUEST);
	$tabs = array(array('title'=>_('Line'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=column',$link)),array('title'=>_('List'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=list',$link)));

	$_ROSARIO['selected_tab'] = str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type='.str_replace(' ','+',$_REQUEST['chart_type']),$link);
	echo '<BR />';

	if($_REQUEST['chart_type']=='list')
	{
		$columns = array();
		
		$columns = array('GRADES'=>_('Grades'));
		foreach($grades_RET as $grade)
		{
			$i++;
			$teachers_RET[$i]['GRADES'] = $grade['TITLE'];
		}
			
		foreach($grouped_RET as $staff_id=>$grades)
		{
			$columns[$staff_id] = $grades[key($grades)][1]['FULL_NAME'];
			foreach($grades_RET as $grade)
			{
				$j++;
				$teachers_RET[$j][$staff_id] = count($grades[$grade['ID']]);
			}
		}

		$LO_options['header'] = WrapTabs($tabs,str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=list',$link));
		ListOutput($teachers_RET,$columns,'Grade','Grades',array(),array(),$LO_options);
	}
	else
	{
		PopTable('header',$tabs);
		
		$_REQUEST['modfunc'] = 'SendChartData';
		//$_REQUEST['_ROSARIO_PDF'] = 'true';

?>
		<script type="text/javascript" src="assets/js/jqplot/jquery.jqplot.min.js"></script>
		<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.highlighter.min.js"></script>
		<link rel="stylesheet" type="text/css" href="assets/js/jqplot/jquery.jqplot.min.css" />	
		<script type="text/javascript">
			var saveImgText = '<?php echo _('Right Click to Save Image As...'); ?>';
			$(document).ready(function(){
<?php
		foreach($grouped_RET as $staff_id=>$grades)
		{
			$i++;
			$chartData = 'var chartTitle'.$i." = '".$grades[key($grades)][1]['FULL_NAME'].' - '.$UserMPTitle.' - '._('Grade Breakdown')."';\n"; 
			$chartData .= 'var jsData'.$i.' = [';
			foreach($grades_RET as $grade)
			{
				$chartData .= "[".$grade['GPA_VALUE'].", ".count($grades[$grade['ID']]).'], ';
			}
			$chartData = mb_substr($chartData, 0, mb_strlen($chartData) - 2);
			$chartData .= "];\n";
			echo $chartData;
	?>
				var plot<?php echo $i; ?> = $.jqplot('chart<?php echo $i; ?>',[jsData<?php echo $i; ?>], {
					axesDefaults: {
						pad: 0 //start axes at 0
					},
					highlighter: {
						show: true,
						showLabel: true,
						tooltipAxes: 'x',
					},
					title: chartTitle<?php echo $i; ?>
				});
				
<?php
		}
?>
			});
		</script>
<?php
		for ($j=1; $j<=$i; $j++)
		{
?>
			<div class="gradeBreakdownCol" style="float: left;">
				<div id="chart<?php echo $j; ?>" class="chart"></div>
			</div>
<?php
		}
?>
		<script type="text/javascript" src="assets/js/colorbox/jquery.colorbox-min.js"></script>
		<link rel="stylesheet" href="assets/js/colorbox/colorbox.css" type="text/css" media="screen" />
		<script type="text/javascript" src="assets/js/jquery.jqplottocolorbox.js"></script>
<?php
		PopTable('footer');
	}

} else {

	echo '<BR /><span class="center"><B>'.sprintf(_('No %s were found.'),_('Teacher')).'</span></B>';

}
?>