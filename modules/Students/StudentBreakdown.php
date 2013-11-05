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

DrawHeader(ProgramTitle());

if($_REQUEST['category_id'])
{
	$category_RET = DBGet(DBQuery("SELECT TITLE,SELECT_OPTIONS AS OPTIONS,TYPE FROM CUSTOM_FIELDS WHERE ID='".$_REQUEST['category_id']."'"));
	$category_RET[1]['OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category_RET[1]['OPTIONS']));
	$category_RET[1]['OPTIONS'] = explode("\r",$category_RET[1]['OPTIONS']);
}

if(!$_REQUEST['chart_type'])
	$_REQUEST['chart_type'] = 'column';

if($_REQUEST['modfunc']=='search')
{
	echo '<BR />';
	//Widgets('all');
	$extra['force_search'] = true;
	$extra['new'] = true;
	$extra['search_title'] = _('Advanced');
	$extra['action'] = '&category_id='.$_REQUEST['category_id'].'&chart_type='.str_replace(' ','+',$_REQUEST['chart_type']).'&modfunc=&searchmodfunc=" target="body';
	Search('student_id',$extra);
}

//if($_REQUEST['modfunc']=='SendChartData' || $_REQUEST['chart_type']=='list')
//{
if($_REQUEST['category_id'])
{
	if($category_RET[1]['TYPE']=='select')
	{
		$extra = array();
		$extra['SELECT_ONLY'] = "COALESCE(s.CUSTOM_".$_REQUEST['category_id'].",'*BLANK*') AS TITLE,COUNT(*) AS COUNT ";
		$extra['GROUP'] = 'CUSTOM_'.$_REQUEST['category_id'];
		$extra['group'] = array('TITLE');
		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] = appendSQL('');
		$extra['WHERE'] .= CustomFields('where');
		$totals_RET = GetStuList($extra);

		$chart['chart_data'][0][] = _('No Value');
		$chart['chart_data'][1][] = (empty($totals_RET['*BLANK*'][1]['COUNT']) ? 0 : $totals_RET['*BLANK*'][1]['COUNT']);
		foreach($category_RET[1]['OPTIONS'] as $option)
		{
			$chart['chart_data'][0][] = $option;
			$chart['chart_data'][1][] = (empty($totals_RET[$option][1]['COUNT']) ? 0 : $totals_RET[$option][1]['COUNT']);			
		}
	}
	elseif($category_RET[1]['TYPE']=='multiple')
	{
		$extra['SELECT_ONLY'] = "CUSTOM_".$_REQUEST['category_id']." AS TITLE ";
		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] = appendSQL('');
		$extra['WHERE'] .= CustomFields('where');
		$student_RET = GetStuList($extra);

		foreach($student_RET as $student)
		{
			$student['TITLE'] = explode("||",trim($student['TITLE'],'|'));
			foreach($student['TITLE'] as $option)
				$options_count[$option]++;
		}

		foreach($category_RET[1]['OPTIONS'] as $option)
		{
			$chart['chart_data'][0][] = $option;
			$chart['chart_data'][1][] = $options_count[$option];
		}		
	}
	elseif($category_RET[1]['TYPE']=='radio')
	{
		$extra = array();
		$extra['SELECT_ONLY'] = db_case(array("s.CUSTOM_".$_REQUEST['category_id'],"'Y'","'"._('Yes')."'","'"._('No')."'"))." AS TITLE,COUNT(*) AS COUNT ";
		$extra['GROUP'] = 'CUSTOM_'.$_REQUEST['category_id'];
		$extra['group'] = array('TITLE');
		//Widgets('all');		
//modif Francois: fix Advanced Search
		$extra['WHERE'] = appendSQL('');
		$extra['WHERE'] .= CustomFields('where');
		$totals_RET = GetStuList($extra);

		$chart['chart_data'][0][] = _('Yes');
		$chart['chart_data'][1][] = (empty($totals_RET['Yes'][1]['COUNT']) ? 0 : $totals_RET['Yes'][1]['COUNT']);			
		$chart['chart_data'][0][] = _('No');
		$chart['chart_data'][1][] = (empty($totals_RET['No'][1]['COUNT']) ? 0 : $totals_RET['No'][1]['COUNT']);			
	}
	elseif($category_RET[1]['TYPE']=='numeric')
	{

		$extra['SELECT_ONLY'] = "COALESCE(max(CUSTOM_".$_REQUEST['category_id']."),0) as MAX,COALESCE(min(CUSTOM_".$_REQUEST['category_id']."),0) AS MIN ";
		//modif Francois: remove NULL entries
		$extra['WHERE'] = "AND CUSTOM_".$_REQUEST['category_id']." IS NOT NULL";
		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$max_min_RET = GetStuList($extra);

		$diff = $max_min_RET[1]['MAX'] - $max_min_RET[1]['MIN'];

		if($diff>10 && $_REQUEST['chart_type']!='column')
		{
//modif Francois: correct numeric chart
			for($i=1;$i<=10;$i++)
			{
				/*$chart['chart_data'][0][$i] = (ceil($diff/5)*($i-1)).' - '.((ceil($diff/5)*$i)-1);
				$mins[$i] = (ceil($diff/5)*($i-1));
				$chart['chart_data'][1][$i] = 0;*/
				$chart['chart_data'][0][$i] = ($max_min_RET[1]['MIN'] + (ceil($diff/10)*($i-1))).' - '.($max_min_RET[1]['MIN'] + ((ceil($diff/10)*$i)-1));
				$mins[$i] = ($max_min_RET[1]['MIN'] + (ceil($diff/10)*($i-1)));
				$chart['chart_data'][1][$i] = 0;
			}
			//$chart['chart_data'][0][$i-1] = ($max_min_RET[1]['MIN'] + (ceil($diff/5)*($i-2))).'+';
			$mins[$i] = (ceil($diff/10)*($i-1));
		} 
		else //modif Francois: transform column chart in line chart
		{ 
			$chartline = true;
		}
		
		$extra['SELECT_ONLY'] = "CUSTOM_".$_REQUEST['category_id']." AS TITLE";
		$extra['functions'] = array('TITLE'=>'_makeNumeric');
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		//Widgets('all');
		$referrals_RET = GetStuList($extra);
	}
strip_tags(str_replace('<BR />',"\n",$_ROSARIO['SearchTerms']));
		
	if($_REQUEST['chart_type']!='list')
	{
//modif Francois: jqplot charts
?>
		<script type="text/javascript">
<?php
		if (isset($chartline))
		{
			$jsData = 'var dataline = [';
			for ($i=1; $i<=count($chart['chart_data'][0]); $i++)
			{
				if (is_numeric($chart['chart_data'][0][$i]))
					$jsData .= "[".$chart['chart_data'][0][$i].", ".$chart['chart_data'][1][$i]."],";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 1);
			$jsData .= "];\n";		
		}
		elseif ($_REQUEST['chart_type']=='column')
		{
			$jsData = 'var ticks = [';
			foreach ($chart['chart_data'][0] as $tick)
			{
				$jsData .= "'".$tick."', ";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 2);
			$jsData .= "];\n";
			
			$jsData .= 'var datacolumn = [';
			foreach ($chart['chart_data'][1] as $data)
			{
				$jsData .= $data.", ";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 2);
			$jsData .= "];\n";
		} 
		else //pie chart
		{
			$jsData = 'var datapie = [';
			for ($i=0; $i<=count($chart['chart_data'][0]); $i++)
			{
				//limit label to 30 char max.
				$jsData .= "['".mb_substr($chart['chart_data'][0][$i], 0, 30)."', ".$chart['chart_data'][1][$i]."],";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 1);
			$jsData .= "];\n";
					
		}
		echo $jsData;
		//modif Francois: responsive labels: limit label to 20 char max.
?>
			if (screen.width<768)
			{
				if (window.dataline)
					for(i=0; i<dataline.length; i++)
						dataline[i][0] = dataline[i][0].substr(0, 20);
				if (window.ticks)
					for(i=0; i<ticks.length; i++)
						ticks[i] = ticks[i].substr(0, 20);
				if (window.datapie)
					for(i=0; i<datapie.length; i++)
						datapie[i][0] = datapie[i][0].substr(0, 20);
			}
		</script>
<?php
	}
	if ($_ROSARIO['SearchTerms'])
		$_ROSARIO['SearchTerms'] = ' - '.strip_tags(str_replace('<BR />'," - ",mb_substr($_ROSARIO['SearchTerms'], 0, -6)));
}

if(!$_REQUEST['modfunc'])
{
	unset($_REQUEST['PHPSESSID']);
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&amp;chart_type='.str_replace(' ','+',$_REQUEST['chart_type']).'" method="POST">';
	
	$fields_RET = DBGet(DBQuery("SELECT ID,TITLE,SELECT_OPTIONS AS OPTIONS,CATEGORY_ID FROM CUSTOM_FIELDS WHERE TYPE NOT IN ('textarea','text','date','log','holder') ORDER BY SORT_ORDER,TITLE"),array(),array('CATEGORY_ID'));
	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES"),array(),array('ID'));
	$select = '<SELECT name=category_id onchange="ajaxPostForm(this.form,true);"><OPTION value="">'._('Please choose a student field').'</OPTION>';
	
	if(count($fields_RET))
	{
		foreach($fields_RET as $category_id=>$fields)
		{
//modif Francois: add translation
//			$select .= '<OPTGROUP label="'.$categories_RET[$category_id][1]['TITLE'].'">';
			$select .= '<OPTGROUP label="'.ParseMLField($categories_RET[$category_id][1]['TITLE']).'">';
			foreach($fields as $field)
				$select .= '<OPTION value="'.$field['ID'].'"'.(($_REQUEST['category_id']==$field['ID'])?' SELECTED="SELECTED"':'').'>'.ParseMLField($field['TITLE']).'</OPTION>';
			$select .= '</OPTGROUP>';
		}
	}
	$select .= '</SELECT>';
	$advanced_link = ' <A HREF="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=search&category_id='.$_REQUEST['category_id'].'&chart_type='.$_REQUEST['chart_type'].'&day_start='.$_REQUEST['day_start'].'&day_end='.$_REQUEST['day_end'].'&month_start='.$_REQUEST['month_start'].'&month_end='.$_REQUEST['month_end'].'&year_start='.$_REQUEST['year_start'].'&year_end='.$_REQUEST['year_end'].'&include_top=false">'._('Advanced').'</A>';

	DrawHeader($select.$advanced_link,SubmitButton(_('Go')));

	echo '<BR />';
	if($_REQUEST['category_id'])
	{
		$tmp_REQUEST = $_REQUEST;
		unset($tmp_REQUEST['chart_type']);
		$link = PreparePHP_SELF($tmp_REQUEST);
		$tabs = array(array('title'=>_('Column'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=column',$link)),array('title'=>_('Pie'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=3d+pie',$link)),array('title'=>_('List'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=list',$link)));

		$_ROSARIO['selected_tab'] = str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type='.str_replace(' ','+',$_REQUEST['chart_type']),$link);
		PopTable('header',$tabs);

		if($_REQUEST['chart_type']=='list')
		{
			$chart_data = array('0'=>'');

			foreach($chart['chart_data'][1] as $key=>$value)
				$chart_data[] = array('TITLE'=>$chart['chart_data'][0][$key],'VALUE'=>$value);
			unset($chart_data[0]);
			$LO_options['responsive'] = false;
			ListOutput($chart_data,array('TITLE'=>_('Option'),'VALUE'=>_('Number of Students')),'Option','Options',array(),array(),$LO_options);
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
			if (isset($chartline)) //modif Francois: line chart
			{
?>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.highlighter.min.js"></script>
				<script type="text/javascript">
					$(document).ready(function(){
						var plotline = $.jqplot('chart',[dataline], {
							highlighter: {
								show: true,
								showLabel: true,
								tooltipAxes: 'both',
							},
							title: '<?php echo ParseMLField($category_RET[1]['TITLE']).' '._('Breakdown').$_ROSARIO['SearchTerms']; ?>'
						});
					});		
				</script>
<?php
			}
			elseif($_REQUEST['chart_type']=='column')
			{
?>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.barRenderer.min.js"></script>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
				<script type="text/javascript">
					$(document).ready(function(){
						var plotcolumn = $.jqplot('chart', [datacolumn], {
							seriesDefaults:{
								renderer:$.jqplot.BarRenderer,
								rendererOptions: { 
									fillToZero: true,
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
							title: '<?php echo ParseMLField($category_RET[1]['TITLE']).' '._('Breakdown').$_ROSARIO['SearchTerms']; ?>'
						});
					});
				</script>
<?php
			} 
			else //pie chart
			{
?>		
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.pieRenderer.min.js"></script>
				<script type="text/javascript">
					$(document).ready(function(){ 
						var plotpie = $.jqplot('chart', [datapie], {
							seriesDefaults:{
								renderer:$.jqplot.PieRenderer,
							},
							legend:{show:true},
							title: '<?php echo ParseMLField($category_RET[1]['TITLE']).' '._('Breakdown').$_ROSARIO['SearchTerms']; ?>'
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

function _makeNumeric($number,$column)
{	global $max_min_RET,$chart,$diff,$mins,$chartline;
	
	if(!$number)
		$number=0;
	if($diff==0)
	{
		$chart['chart_data'][0][1] = $number;
		$chart['chart_data'][1][1]++;
	}
	//elseif($diff<5)
	elseif($diff<10 || isset($chartline))
	{
		$chart['chart_data'][0][((int) $number - (int) $max_min_RET[1]['MIN']+1)] = (int) $number;
		$chart['chart_data'][1][((int) $number - (int) $max_min_RET[1]['MIN']+1)]++;
	}
	else
	{
		//for($i=1;$i<=5;$i++)
		for($i=1;$i<=10;$i++)
		{
			if(($number>=$mins[$i] && $number<$mins[$i+1]) || $i==10)
			{
				$chart['chart_data'][1][$i]++;
				break;
			}
		}
	}
	
	return;
}
?>