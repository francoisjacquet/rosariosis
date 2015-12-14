<?php

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

if($_REQUEST['day_end'] && $_REQUEST['month_end'] && $_REQUEST['year_end'])
	$end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end'];
else
	$end_date = DBDate();

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
	$extra['new'] = true;
	$extra['search_title'] = _('Advanced');
	$extra['action'] = "&category_id=$_REQUEST[category_id]&chart_type=".str_replace(' ','+',$_REQUEST['chart_type'])."&day_start=$_REQUEST[day_start]&day_end=$_REQUEST[day_end]&month_start=$_REQUEST[month_start]&month_end=$_REQUEST[month_end]&year_start=$_REQUEST[year_start]&year_end=$_REQUEST[year_end]&modfunc=&search_modfunc= target=body";
	Search('student_id',$extra);

}

//if($_REQUEST['modfunc']=='SendChartData' || $_REQUEST['chart_type']=='list')
//{
if($_REQUEST['category_id'])
{
	if(in_array($category_RET[1]['TYPE'], array('select','autos','edits','exports')))
	{
		$extra = array();
		$extra['SELECT_ONLY'] = "COALESCE(s.CUSTOM_".intval($_REQUEST['category_id']).",'*BLANK*') AS TITLE,COUNT(*) AS COUNT ";
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
		$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '".$start_date."' AND '".$end_date."' ";
		$extra['GROUP'] = 'CUSTOM_'.intval($_REQUEST['category_id']);
		$extra['group'] = array('TITLE');
		//Widgets('all');
//FJ fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

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
		$extra['SELECT_ONLY'] = "CUSTOM_".intval($_REQUEST['category_id'])." AS TITLE ";
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
		$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '".$start_date."' AND '".$end_date."' ";
		//Widgets('all');
//FJ fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$referrals_RET = GetStuList($extra);

		foreach($referrals_RET as $referral)
		{
			$referral['TITLE'] = explode("||",trim($referral['TITLE'],'|'));
			foreach($referral['TITLE'] as $option)
				$options_count[$option]++;
		}

		foreach($category_RET[1]['OPTIONS'] as $option)
		{
			$chart['chart_data'][0][] = $option;
			$chart['chart_data'][1][] = (empty($options_count[$option]) ? 0 : $options_count[$option]);
		}		
	}
	elseif($category_RET[1]['TYPE']=='radio')
	{
		$extra = array();
		$extra['SELECT_ONLY'] = db_case(array("s.CUSTOM_".intval($_REQUEST['category_id']),"'Y'","'"._('Yes')."'","'"._('No')."'"))." AS TITLE,COUNT(*) AS COUNT ";
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
		$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '".$start_date."' AND '".$end_date."' ";
		$extra['GROUP'] = 'CUSTOM_'.intval($_REQUEST['category_id']);
		$extra['group'] = array('TITLE');
		//Widgets('all');
//FJ fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$totals_RET = GetStuList($extra);

		$chart['chart_data'][0][0] = _('Yes');
		$chart['chart_data'][1][0] = (empty($totals_RET['Yes'][1]['COUNT']) ? 0 : $totals_RET['Yes'][1]['COUNT']);			
		$chart['chart_data'][0][0] = _('No');
		$chart['chart_data'][1][0] = (empty($totals_RET['No'][1]['COUNT']) ? 0 : $totals_RET['No'][1]['COUNT']);			
	}
	elseif($category_RET[1]['TYPE']=='numeric')
	{
		$chart['axis_category']['orientation'] = '';

		$extra['SELECT_ONLY'] = "COALESCE(max(CUSTOM_".intval($_REQUEST['category_id'])."),0) as MAX,COALESCE(min(CUSTOM_".intval($_REQUEST['category_id'])."),0) AS MIN ";
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr';
		$extra['WHERE'] = " AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '".$start_date."' AND '".$end_date."' ";
//FJ fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$max_min_RET = GetStuList($extra);

		$diff = $max_min_RET[1]['MAX'] - $max_min_RET[1]['MIN'];

		if($diff>10 && $_REQUEST['chart_type']!='column')
		{
//FJ correct numeric chart
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
		else //FJ transform column chart in line chart
		{ 
			$chartline = true;
		}
		
		$extra['SELECT_ONLY'] = "CUSTOM_".intval($_REQUEST['category_id'])." AS TITLE";
		$extra['FROM'] = ",DISCIPLINE_REFERRALS dr";
		$extra['WHERE'] = " AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '".$start_date."' AND '".$end_date."' AND CUSTOM_".intval($_REQUEST['category_id'])." IS NOT NULL ";
		$extra['functions'] = array('TITLE'=>'_makeNumeric');
		//Widgets('all');
//FJ fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$referrals_RET = GetStuList($extra);
		if (!$referrals_RET) //FJ bugfix no results for numeric fields chart 
			$chart['chart_data'][0][0] = $chart['chart_data'][1][0] = 0;
	}
	if($_ROSARIO['SearchTerms'])
		$chart['draw_text'][] = array('x'=>0,'y'=>35,'width'=>$width+200,'height'=>100,'h_align'=>'center','v_align'=>'top','rotation'=>0,'text'=>strip_tags(str_replace('<BR />',"\n",$_ROSARIO['SearchTerms'])),'font'=>'Arial','color'=>'000000','alpha'=>25,'size'=>20);
	
	if($_REQUEST['chart_type']!='list')
	{
//FJ jqplot charts
?>
		<script>
<?php
		if (isset($chartline))
		{
			$jsData = 'var dataline = [';
			foreach ($chart['chart_data'][1] as $index => $y)
			{
				if (is_numeric($chart['chart_data'][0][$index]))
					$jsData .= "[".$chart['chart_data'][0][$index].", ".$y."],";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 1);
			$jsData .= "];\n";		
		}
		elseif ($_REQUEST['chart_type']=='column')
		{
			$jsData = 'var ticks = [';
			foreach ($chart['chart_data'][0] as $tick)
			{
				$jsData .= json_encode($tick).", ";
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
			$chart_data_count = count($chart['chart_data'][0]);
			for ($i=0; $i<=$chart_data_count; $i++)
			{
				//limit label to 30 char max.
				$jsData .= "['".htmlspecialchars(mb_substr($chart['chart_data'][0][$i], 0, 30),ENT_QUOTES)."', ".$chart['chart_data'][1][$i]."],";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 1);
			$jsData .= "];\n";
					
		}
		echo $jsData;
		//FJ responsive labels: limit label to 20 char max.
?>
			if (screen.width<768)
			{
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

if(empty($_REQUEST['modfunc']))
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&amp;chart_type='.str_replace(' ','+',$_REQUEST['chart_type']).'" method="POST">';
	
	$fields_RET = DBGet(DBQuery("SELECT ID,TITLE,SELECT_OPTIONS AS OPTIONS,CATEGORY_ID FROM CUSTOM_FIELDS WHERE TYPE NOT IN ('textarea','text','date','log','holder') ORDER BY SORT_ORDER,TITLE"),array(),array('CATEGORY_ID'));
	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES"),array(),array('ID'));
	$select = '<SELECT name=category_id onchange="ajaxPostForm(this.form,true);"><OPTION value="">'._('Please choose a student field').'</OPTION>';
	
	if(count($fields_RET))
	{
		foreach($fields_RET as $category_id=>$fields)
		{
//FJ add translation
//			$select .= '<OPTGROUP label="'.$categories_RET[$category_id][1]['TITLE'].'">';
			$select .= '<OPTGROUP label="'.ParseMLField($categories_RET[$category_id][1]['TITLE']).'">';
			foreach($fields as $field)
				$select .= '<OPTION value="'.$field['ID'].'"'.(($_REQUEST['category_id']==$field['ID'])?' SELECTED':'').'>'.ParseMLField($field['TITLE']).'</OPTION>';
			$select .= '</OPTGROUP>';
		}
	}
	$select .= '</SELECT>';
	$advanced_link = ' <A HREF="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=search&category_id='.$_REQUEST['category_id'].'&chart_type='.$_REQUEST['chart_type'].'&day_start='.$_REQUEST['day_start'].'&day_end='.$_REQUEST['day_end'].'&month_start='.$_REQUEST['month_start'].'&month_end='.$_REQUEST['month_end'].'&year_start='.$_REQUEST['year_start'].'&year_end='.$_REQUEST['year_end'].'&include_top=false">'._('Advanced').'</A>';

	DrawHeader($select);
	DrawHeader('<B>'._('Report Timeframe').': </B>'.PrepareDate($start_date,'_start').' - '.PrepareDate($end_date,'_end').$advanced_link,SubmitButton(_('Go')));

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
			ListOutput($chart_data,array('TITLE'=>_('Option'),'VALUE'=>_('Number of Referrals')),'Option','Options',array(),array(),$LO_options);
		}
		else
		{
			$_REQUEST['modfunc'] = 'SendChartData';
//FJ jqplot charts
//FJ colorbox
?>
			<script src="assets/js/jqplot/jquery.jqplot.min.js"></script>
			<link rel="stylesheet" type="text/css" href="assets/js/jqplot/jquery.jqplot.min.css" />
			<script>	
				var saveImgText = <?php echo json_encode(_('Right Click to Save Image As...')); ?>;
				var chartTitle = <?php echo json_encode(sprintf(_('%s Breakdown'),ParseMLField($category_RET[1]['TITLE'])).$_ROSARIO['SearchTerms']); ?>;
			</script>
<?php
			if (isset($chartline)) //FJ line chart
			{
?>
				<script src="assets/js/jqplot/plugins/jqplot.highlighter.min.js"></script>
				<script>
					$(document).ready(function(){
						window.setTimeout(function () {
							var plotline = $.jqplot('chart',[dataline], {
								highlighter: {
									show: true,
									tooltipAxes: 'both',
									formatString:'<span style="font-size:larger;font-weight:bold;">%s; %s</span>',
								},
								title: chartTitle
							});
						}, 500);
					});		
				</script>
<?php
			}
			elseif($_REQUEST['chart_type']=='column')
			{
?>
				<script src="assets/js/jqplot/plugins/jqplot.barRenderer.min.js"></script>
				<script src="assets/js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
				<script src="assets/js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
				<script src="assets/js/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
				<script src="assets/js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
				<script>
					$(document).ready(function(){
						window.setTimeout(function () {
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
								title: chartTitle
							});
						}, 500);
					});		
				</script>
<?php
			} 
			else //pie chart
			{
?>		
				<script src="assets/js/jqplot/plugins/jqplot.pieRenderer.min.js"></script>
				<script>
					$(document).ready(function(){ 
						window.setTimeout(function () {
							var plotpie = $.jqplot('chart', [datapie], {
								seriesDefaults:{
									renderer:$.jqplot.PieRenderer,
									rendererOptions: {
										showDataLabels: true,
									},
								},
								legend:{show:true},
								title: chartTitle
							});
						}, 500);
					});	
				</script>
<?php
			}	
?>
			<div id="chart"></div>
			<script src="assets/js/colorbox/jquery.colorbox-min.js"></script>
			<link rel="stylesheet" href="assets/js/colorbox/colorbox.css" type="text/css" media="screen" />
			<script src="assets/js/jquery.jqplottocolorbox.js"></script>
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
