<?php
/**
* @file $Id: CategoryBreakdownTime.php 507 2007-05-11 23:41:24Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

DrawHeader(ProgramTitle());

if($_REQUEST['day_start'] && $_REQUEST['month_start'] && $_REQUEST['year_start'])
{
	while(!VerifyDate($start_date = $_REQUEST['day_start'].'-'.$_REQUEST['month_start'].'-'.$_REQUEST['year_start']))
		$_REQUEST['day_start']--;
}
else
{
	$min_date = DBGet(DBQuery("SELECT min(SCHOOL_DATE) AS MIN_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
	if($min_date[1]['MIN_DATE'])
		$start_date = $min_date[1]['MIN_DATE'];
	else
		$start_date = '01-'.mb_strtoupper(date('M-y'));
}

if($_REQUEST['day_end'] && $_REQUEST['month_end'] && $_REQUEST['year_end'])
{
	while(!VerifyDate($end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end']))
		$_REQUEST['day_end']--;
}
else
	$end_date = DBDate();
	
if(!$_REQUEST['timeframe'])
	$_REQUEST['timeframe'] = 'month';

if($_REQUEST['category_id'])
{
	$category_RET = DBGet(DBQuery("SELECT f.ID,u.TITLE,u.SELECT_OPTIONS,f.DATA_TYPE FROM DISCIPLINE_FIELDS f,DISCIPLINE_FIELD_USAGE u WHERE f.ID='".$_REQUEST['category_id']."' AND u.DISCIPLINE_FIELD_ID=f.ID AND u.SYEAR='".UserSyear()."' AND u.SCHOOL_ID='".UserSchool()."'"));
	$category_RET[1]['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category_RET[1]['SELECT_OPTIONS']));
	$category_RET[1]['SELECT_OPTIONS'] = explode("\r",$category_RET[1]['SELECT_OPTIONS']);
}

if(!$_REQUEST['chart_type'])
	$_REQUEST['chart_type'] = 'column';

if($_REQUEST['modfunc']=='search')
{
	echo '<BR />';
	//Widgets('all');
	$extra['force_search'] = true;
	$extra['search_title'] = _('Advanced');
	$extra['action'] = "&category_id=$_REQUEST[category_id]&chart_type=".str_replace(' ','+',$_REQUEST['chart_type'])."&day_start=$_REQUEST[day_start]&day_end=$_REQUEST[day_end]&month_start=$_REQUEST[month_start]&month_end=$_REQUEST[month_end]&year_start=$_REQUEST[year_start]&year_end=$_REQUEST[year_end]&modfunc=&search_modfunc= target=body";
	Search('student_id',$extra);

}

//if($_REQUEST['modfunc']=='SendChartData' || $_REQUEST['chart_type']=='list')
//{
if($_REQUEST['category_id'])
{
	if($_REQUEST['timeframe']=='month')
		$timeframe = "to_char(dr.ENTRY_DATE,'mm')";
	elseif($_REQUEST['timeframe']=='SYEAR')
		$timeframe = 'dr.SYEAR';

	$chart['chart_type'] = $_REQUEST['chart_type'];
	$chart['series_switch'] = true;
	
	if($category_RET[1]['DATA_TYPE']=='multiple_radio' || $category_RET[1]['DATA_TYPE']=='select')
	{
		$extra = array();

		$extra['SELECT_ONLY'] = "dr.CATEGORY_".$_REQUEST['category_id']." AS TITLE,COUNT(*) AS COUNT,".$timeframe.' AS TIMEFRAME';
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
		$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '$start_date' AND '$end_date' ";
		$extra['GROUP'] = 'CATEGORY_'.$_REQUEST['category_id'].',TIMEFRAME';
		$extra['group'] = array('TITLE','TIMEFRAME');
		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$totals_RET = GetStuList($extra);

		$chart['chart_data'][0][0] = '';

		foreach($category_RET[1]['SELECT_OPTIONS'] as $option)
			$chart['chart_data'][0][] = $option;
	
		if($_REQUEST['timeframe']=='month')
		{
			$start = (MonthNWSwitch($_REQUEST['month_start'],'tonum')*1);
			$end = ((MonthNWSwitch($_REQUEST['month_end'],'tonum')*1)+12*($_REQUEST['year_end']-$_REQUEST['year_start']));
		}
		elseif($_REQUEST['timeframe']=='SYEAR')
		{
			$start = GetSyear($start_date);
			$end = GetSyear($end_date);
		}
		for($i=$start;$i<=$end;$i++)
		{
			$index++;
			if($_REQUEST['timeframe']=='month')
			{
				$tf = str_pad(($i-$start+1),2,'0',STR_PAD_LEFT);
//modif Francois: add translation
				$chart['chart_data'][$index][0] = _(ucwords(mb_strtolower(MonthNWSwitch(str_pad($i%12,2,'0',STR_PAD_LEFT),'tochar'))));
			}
			elseif($_REQUEST['timeframe']=='SYEAR')
			{
				$tf = $i-$start+1;
				$chart['chart_data'][$index][0] = $i;
			}
			
			foreach($category_RET[1]['SELECT_OPTIONS'] as $option)
				$chart['chart_data'][$index][] = (empty($totals_RET[$option][$tf][1]['COUNT']) ? 0 : $totals_RET[$option][$tf][1]['COUNT']);
		}
	}
	elseif($category_RET[1]['DATA_TYPE']=='checkbox')
	{
		$extra = array();

		$extra['SELECT_ONLY'] = "COALESCE(dr.CATEGORY_".$_REQUEST['category_id'].",'N') AS TITLE,COUNT(*) AS COUNT,".$timeframe.' AS TIMEFRAME';
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
		$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '$start_date' AND '$end_date' ";
		$extra['GROUP'] = 'CATEGORY_'.$_REQUEST['category_id'].',TIMEFRAME';
		$extra['group'] = array('TITLE','TIMEFRAME');
		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$totals_RET = GetStuList($extra);

		$chart['chart_data'][0][0] = '';

		$chart['chart_data'][0][] = _('Yes');
		$chart['chart_data'][0][] = _('No');
	
		if($_REQUEST['timeframe']=='month')
		{
			$start = (MonthNWSwitch($_REQUEST['month_start'],'tonum')*1);
			$end = ((MonthNWSwitch($_REQUEST['month_end'],'tonum')*1)+12*($_REQUEST['year_end']-$_REQUEST['year_start']));
		}
		elseif($_REQUEST['timeframe']=='SYEAR')
		{
			$start = GetSyear($start_date);
			$end = GetSyear($end_date);
		}
		for($i=$start;$i<=$end;$i++)
		{
			$index++;
			if($_REQUEST['timeframe']=='month')
			{
				$tf = str_pad(($i-$start+1),2,'0',STR_PAD_LEFT);
				$chart['chart_data'][$index][0] = _(ucwords(mb_strtolower(MonthNWSwitch(str_pad($i%12,2,'0',STR_PAD_LEFT),'tochar'))));
			}
			elseif($_REQUEST['timeframe']=='SYEAR')
			{
				$tf = $i-$start+1;
				$chart['chart_data'][$index][0] = $i;
			}
			
			$chart['chart_data'][$index][] = (empty($totals_RET['Y'][$tf][1]['COUNT']) ? 0 : $totals_RET['Y'][$tf][1]['COUNT']);
			$chart['chart_data'][$index][] = (empty($totals_RET['N'][$tf][1]['COUNT']) ? 0 : $totals_RET['N'][$tf][1]['COUNT']);
		}
	}
	elseif($category_RET[1]['DATA_TYPE']=='multiple_checkbox')
	{
		$extra['SELECT_ONLY'] = "CATEGORY_".$_REQUEST['category_id']." AS TITLE,".$timeframe.' AS TIMEFRAME';
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
		$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '$start_date' AND '$end_date' ";

		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$referrals_RET = GetStuList($extra);

		$chart['chart_data'][0][0] = '';

		foreach($category_RET[1]['SELECT_OPTIONS'] as $option)
			$chart['chart_data'][0][] = $option;
		foreach($referrals_RET as $referral)
		{
			$referral['TITLE'] = explode("||",trim($referral['TITLE'],'|'));
			foreach($referral['TITLE'] as $option)
				$options_count[$referral['TIMEFRAME']][$option]++;
		}

		for($i=(MonthNWSwitch($_REQUEST['month_start'],'tonum')*1);$i<=((MonthNWSwitch($_REQUEST['month_end'],'tonum')*1)+12*($_REQUEST['year_end']-$_REQUEST['year_start']));$i++)
		{
			$index++;
			$chart['chart_data'][$index][0] = _(ucwords(mb_strtolower(MonthNWSwitch(str_pad($i%12,2,'0',STR_PAD_LEFT),'tochar'))));
			foreach($category_RET[1]['SELECT_OPTIONS'] as $option)
				$chart['chart_data'][$index][] = (empty($options_count[str_pad(($i%12==0?12:$i%12),2,'0',STR_PAD_LEFT)][$option]) ? 0 : $options_count[str_pad(($i%12==0?12:$i%12),2,'0',STR_PAD_LEFT)][$option]);
		}
	}
	elseif($category_RET[1]['DATA_TYPE']=='numeric')
	{
		$chart['axis_category']['orientation'] = '';

		$extra['SELECT_ONLY'] = "COALESCE(max(CATEGORY_".$_REQUEST['category_id']."),0) as MAX,COALESCE(min(CATEGORY_".$_REQUEST['category_id']."),0) AS MIN ";
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr';
		$extra['WHERE'] = " AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '$start_date' AND '$end_date' ";
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$max_min_RET = GetStuList($extra);

		$diff = $max_min_RET[1]['MAX'] - $max_min_RET[1]['MIN'];

		if($diff>5)
		{
			$index = 0;
			for($i=(MonthNWSwitch($_REQUEST['month_start'],'tonum')*1);$i<=((MonthNWSwitch($_REQUEST['month_end'],'tonum')*1)+12*($_REQUEST['year_end']-$_REQUEST['year_start']));$i++)
			{
				$index++;
				$chart['chart_data'][$index][0] = _(ucwords(mb_strtolower(MonthNWSwitch(str_pad($i%12,2,'0',STR_PAD_LEFT),'tochar'))));
			}
			for($o=1;$o<=5;$o++)
			{
				$chart['chart_data'][0][$o] = (ceil($diff/5)*($o-1)).' - '.((ceil($diff/5)*$o)-1);
				$mins[$o] = (ceil($diff/5)*($o-1));
				$index = 0;
				for($i=(MonthNWSwitch($_REQUEST['month_start'],'tonum')*1);$i<=((MonthNWSwitch($_REQUEST['month_end'],'tonum')*1)+12*($_REQUEST['year_end']-$_REQUEST['year_start']));$i++)
				{
					$index++;
					$chart['chart_data'][$index][$o] = 0;
				}
			}
			$chart['chart_data'][0][$o-1] = (ceil($diff/5)*($o-2)).'+';
			$mins[$o] = (ceil($diff/5)*($o-1));
		}
		
		$extra['SELECT_ONLY'] = "CATEGORY_".$_REQUEST['category_id']." AS TITLE,$_REQUEST[timeframe] AS TIMEFRAME";
		$extra['FROM'] = ",DISCIPLINE_REFERRALS dr";
		$extra['WHERE'] = " AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '$start_date' AND '$end_date' ";
		$extra['functions'] = array('TITLE'=>'_makeNumeric');

		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$referrals_RET = GetStuList($extra);
	}
	if($_ROSARIO['SearchTerms'])
		$chart['draw_text'][] = array('x'=>0,'y'=>35,'width'=>$width+200,'height'=>100,'h_align'=>'center','v_align'=>'top','rotation'=>0,'text'=>strip_tags(str_replace('<BR />',"\n",$_ROSARIO['SearchTerms'])),'font'=>'Arial','color'=>'000000','alpha'=>25,'size'=>20);

	if($_REQUEST['chart_type']!='list')
	{
//modif Francois: jqplot charts
?>
		<script type="text/javascript">
<?php
		$datacolumns = 0;
		$series_labels = array();
		foreach($chart['chart_data'] as $chart_data)
		{
			if ($datacolumns == 0)
			{
				$jsData .= 'var ticks = [';
				$jump = true;
				foreach ($chart_data as $tick)
				{
					if ($jump)
						$jump = false;
					else
						$jsData .= "'".$tick."', ";
				}
				$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 2);
				$jsData .= "];\n";
				
			} else {
			
				$jsData .= 'var datacolumn'.$datacolumns.' = [';
				$serie = true;
				foreach ($chart_data as $data)
				{
					if ($serie)
					{
						$serie = false;
						$series_labels[] = $data;
					} else {
						$jsData .= $data.", ";
					}
				}
				$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 2);
				$jsData .= "];\n";
			}
			$datacolumns ++;
		}
		echo $jsData;
		//modif Francois: responsive labels: limit label to 20 char max.
?>
			if (screen.width<768)
			{
				if (window.ticks)
					for(i=0; i<ticks.length; i++)
						ticks[i] = ticks[i].substr(0, 20);
			}
		</script>
<?php
	}
	if ($_ROSARIO['SearchTerms'])
		$_ROSARIO['SearchTerms'] = ' - '.strip_tags(str_replace('<BR />'," - ",mb_substr($_ROSARIO['SearchTerms'], 0, -6)));
}


if(empty($_REQUEST['modfunc']))

{
	unset($_REQUEST['PHPSESSID']);
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&amp;chart_type='.str_replace(' ','+',$_REQUEST['chart_type']).'" method="POST">';
	
	$categories_RET = DBGet(DBQuery("SELECT f.ID,u.TITLE,u.SELECT_OPTIONS,f.DATA_TYPE FROM DISCIPLINE_FIELDS f,DISCIPLINE_FIELD_USAGE u WHERE u.DISCIPLINE_FIELD_ID=f.ID AND f.DATA_TYPE NOT IN ('textarea','text','date') AND u.SYEAR='".UserSyear()."' AND u.SCHOOL_ID='".UserSchool()."' ORDER BY u.SORT_ORDER"));
	$select = '<SELECT name=category_id onchange="ajaxPostForm(this.form,true);"><OPTION value="">'._('Please choose a category').'</OPTION>';
	
	if(count($categories_RET))
	{
		foreach($categories_RET as $category)
			$select .= '<OPTION value="'.$category['ID'].'"'.(($_REQUEST['category_id']==$category['ID'])?' SELECTED="SELECTED"':'').'>'.$category['TITLE'].'</OPTION>';
	}
	$select .= '</SELECT>';
	$advanced_link = ' <A HREF="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=search&category_id='.$_REQUEST['category_id'].'&chart_type='.$_REQUEST['chart_type'].'&day_start='.$_REQUEST['day_start'].'&day_end='.$_REQUEST['day_end'].'&month_start='.$_REQUEST['month_start'].'&month_end='.$_REQUEST['month_end'].'&year_start='.$_REQUEST['year_start'].'&year_end='.$_REQUEST['year_end'].'&include_top=false">'._('Advanced').'</A>';

	DrawHeader($select);
	DrawHeader(_('Timeframe').': <label><INPUT type="radio" name=timeframe value=month'.($_REQUEST['timeframe']=='month'?' checked':'').'>&nbsp;'._('Month').'</label> &nbsp;<label><INPUT type="radio" name=timeframe value=SYEAR'.($_REQUEST['timeframe']=='SYEAR'?' checked':'').'>&nbsp;'._('School Year').'</label>');
	DrawHeader('<B>'._('Report Timeframe').': </B>'.PrepareDate($start_date,'_start').' - '.PrepareDate($end_date,'_end').$advanced_link,SubmitButton(_('Go')));

	echo '<BR />';
	if($_REQUEST['category_id'])
	{
		$tmp_REQUEST = $_REQUEST;
		unset($tmp_REQUEST['chart_type']);
		$link = PreparePHP_SELF($tmp_REQUEST);
		$tabs = array(array('title'=>_('Column'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=column',$link)),array('title'=>_('List'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=list',$link)));

		$_ROSARIO['selected_tab'] = str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type='.str_replace(' ','+',$_REQUEST['chart_type']),$link);
		PopTable('header',$tabs);

		if($_REQUEST['chart_type']=='list')
		{
			// IGNORE THE 'Series' RECORD
			$columns = array('TITLE'=>_('Option'));
			foreach($chart['chart_data'] as $timeframe=>$values)
			{	
				if($timeframe!=0)
				{
					$columns += array($timeframe=>$values[0]);
					unset($values[0]);
					foreach($values as $key=>$value)
						$chart_data[$key] += array($timeframe=>$value);
				}
				else
				{
					unset($values[0]);
					foreach($values as $key=>$value)
						$chart_data[$key] = array('TITLE'=>$value);
				}
			}
			unset($chart_data[0]);
			$LO_options['responsive'] = false;
			ListOutput($chart_data,$columns,'Option','Options',array(),array(),$LO_options);
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
			<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.barRenderer.min.js"></script>
			<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
			<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
			<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
			<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
			<script type="text/javascript">
				$(document).ready(function(){
					var plotcolumn = $.jqplot('chart', [<?php
					for ($i = 1; $i<$datacolumns; $i++)
						echo 'datacolumn'.$i.($i<($datacolumns-1) ? ', ' : '');
					?>], {
						stackSeries: true,
						series:[<?php
						foreach ($series_labels as $serie_label)
							echo "{label:'".$serie_label."'},";
						?>],
						seriesDefaults:{
							renderer:$.jqplot.BarRenderer,
							rendererOptions: { 
								varyBarColor: true
							},
							pointLabels: { show: true }
						},
						axes: {
							// yaxis: { autoscale: true },
							xaxis: {
								renderer: $.jqplot.CategoryAxisRenderer,
								ticks: ticks,
								tickRenderer: $.jqplot.CanvasAxisTickRenderer,
								tickOptions:{
									angle:-20
								}
							},
						},
						legend: {
							show: true,
							location: 'e',
							placement: 'outside'
						},     
						title: '<?php echo ParseMLField($category_RET[1]['TITLE']).' '._('Breakdown').$_ROSARIO['SearchTerms']; ?>'
					});
				});		
			</script>
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

function _makeNumeric($number,$column)
{	global $max_min_RET,$chart,$diff,$mins,$THIS_RET;
	
	$index = (($THIS_RET['TIMEFRAME']*1)-(MonthNWSwitch($_REQUEST['month_start'],'tonum')*1)+1);
	if(!$number)
		$number=0;
	if($diff==0)
	{
		$chart['chart_data'][0][1] = $number;
		$chart['chart_data'][$index][1]++;
	}
	elseif($diff<5)
	{
		$chart['chart_data'][0][((int) $number - (int) $max_min_RET[1]['MIN']+1)] = (int) $number;
		$chart['chart_data'][$index][((int) $number - (int) $max_min_RET[1]['MIN']+1)]++;
	}
	else
	{
		for($i=1;$i<=5;$i++)
		{
			if(($number>=$mins[$i] && $number<$mins[$i+1]) || $i==5)
			{
				$chart['chart_data'][$index][$i]++;
				break;
			}
		}
	}
	
	return $number;
}
?>
