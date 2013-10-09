<?php

// SEND PrepareDate a name prefix, and a date in oracle format 'd-M-y' as the selected date to have returned a date selection series
// of pull-down menus
// For the default to be Not Specified, send a date of 00-000-00 or send nothing
// The date pull-downs will create three variables, monthtitle, daytitle, yeartitle
// The third parameter (booleen) specifies whether Not Specified should be allowed as an option

function PrepareDate($date,$title='',$allow_na=true,$options='')
{	global $_ROSARIO;

	if($options=='')
		$options = array();
	if(!$options['Y'] && !$options['M'] && !$options['D'] && !$options['C'])
		$options += array('Y'=>true,'M'=>true,'D'=>true,'C'=>true);

	if($options['short']==true)
		$extraM = "style='width:65;' ";
	if($options['submit']==true)
	{
		if($options['C'])
		{
			$return .= '<script type="text/javascript">var date_onclick = document.createElement("a"); date_onclick.href = "'.PreparePHP_SELF($_REQUEST,array('month'.$title,'day'.$title,'year'.$title)).'"; date_onclick.target = "body";</script>';
			$e = 'onchange="date_onclick.href += \'&month'.$title.'=\'+this.form.month'.$title.'.value+\'&day'.$title.'=\'+this.form.day'.$title.'.value+\'&year'.$title.'=\'+this.form.year'.$title.'.value; ajaxLink(date_onclick);"';
			$extraM .= $e;
			$extraD .= $e;
			$extraY .= $e;
		}
		else
		{
			if ($options['M'])
				$return .= '<script type="text/javascript">var month_onclick = document.createElement("a"); month_onclick.href = "'.PreparePHP_SELF($_REQUEST,array('month'.$title)).'"; month_onclick.target = "body";</script>';
			$extraM .= 'onchange="month_onclick.href += \'&month'.$title.'=\'+this.form.month'.$title.'.value; ajaxLink(month_onclick);"';
			
			if ($options['D'])
				$return .= '<script type="text/javascript">var day_onclick = document.createElement("a"); day_onclick.href = "'.PreparePHP_SELF($_REQUEST,array('day'.$title)).'"; day_onclick.target = "body";</script>';
			$extraD .= 'onchange="day_onclick.href += \'&day'.$title.'=\'+this.form.day'.$title.'.value; ajaxLink(day_onclick);"';
			
			if ($options['Y'])
				$return .= '<script type="text/javascript">var year_onclick = document.createElement("a"); year_onclick.href = "'.PreparePHP_SELF($_REQUEST,array('year'.$title)).'"; year_onclick.target = "body";</script>';
			$extraY .= 'onchange="year_onclick.href += \'&amp;year'.$title.'=\'+this.form.year'.$title.'.value; ajaxLink(year_onclick);"';
		}
	}

	if($options['C'])
		$_ROSARIO['PrepareDate']++;

	if(mb_strlen($date)==9) // ORACLE
	{
		$day = mb_substr($date,0,2);
		$month = mb_substr($date,3,3);
		$year = mb_substr($date,7,2);
		if($year=='00' && ($month=='000' && $day=='00'))
			$year = '0000';
		else
			$year = ($year<50?'20':'19').$year;

		$return .= '<!-- '.$year.MonthNWSwitch($month,'tonum').$day.' -->';
	}
	elseif(mb_strlen($date)==10) // POSTGRES
	{
		$day = mb_substr($date,8,2);
		$month = MonthNWSwitch(mb_substr($date,5,2),'tochar');
		$year = mb_substr($date,0,4);

		$return .= '<!-- '.$year.MonthNWSwitch($month,'tonum').$day.' -->';
	}
	else //mb_strlen($date)==11 ORACLE with 4-digit year
	{
		$day = mb_substr($date,0,2);
		$month = mb_substr($date,3,3);
		$year = mb_substr($date,7,4);
		$return .= '<!-- '.$year.MonthNWSwitch($month,'tonum').$day.' -->';
	}
	
	//modif Francois: NOBR on date input
	$return .= '<span style="white-space:nowrap">';
	
	// MONTH  ---------------
	if($options['M'])
	{
		$return .= '<SELECT NAME="month'.$title.'" id="monthSelect'.$_ROSARIO['PrepareDate'].'" SIZE="1" '.$extraM.'>';
		if($allow_na)
		{
			if($month=='000')
				$return .= '<OPTION value="" SELECTED="SELECTED">'._('N/A').'';
			else
				$return .= '<OPTION value="">'._('N/A').'';
		}
		//modif Francois: traduction des mois!
		foreach(array('JAN'=>_('January'),'FEB'=>_('February'),'MAR'=>_('March'),'APR'=>_('April'),'MAY'=>_('May'),'JUN'=>_('June'),'JUL'=>_('July'),'AUG'=>_('August'),'SEP'=>_('September'),'OCT'=>_('October'),'NOV'=>_('November'),'DEC'=>_('December')) as $key=>$name)
			$return .= '<OPTION VALUE="'.$key.'"'.($month==$key?' SELECTED="SELECTED"':'').'>'.$name;
		$return .= '</SELECT>';
	}

	// DAY  ---------------
	if($options['D'])
	{
		$return .= '<SELECT NAME="day'.$title.'" id="daySelect'.$_ROSARIO['PrepareDate'].'" SIZE="1" '.$extraD.'>';
		if($allow_na)
		{
			if($day=='00')
				$return .= '<OPTION value="" SELECTED="SELECTED">'._('N/A').'';
			else
				$return .= '<OPTION value="">'._('N/A').'';
		}

		for($i=1;$i<=31;$i++)
		{
			if(mb_strlen($i)==1)
				$print = '0'.$i;
			else
				$print = $i;

			$return .= '<OPTION VALUE="'.$print.'"'.($day==$print?' SELECTED="SELECTED"':'').'>'.$i;
		}
		$return .= '</SELECT>';
	}

	// YEAR  ---------------
	if($options['Y'])
	{
		if(!$year || $year=='0000')
		{
			$begin = date('Y') - 20;
			$end = date('Y') + 5;
		}
		else
		{
			$begin = $year - 5;
			$end = $year + 5;
		}

		$return .= '<SELECT NAME="year'.$title.'" id="yearSelect'.$_ROSARIO['PrepareDate'].'" SIZE="1" '.$extraY.'>';
		if($allow_na)
		{
			if($year=='0000')
				$return .= '<OPTION value="" SELECTED="SELECTED">'._('N/A').'';
			else
				$return .= '<OPTION value="">'._('N/A').'';
		}

		for($i=$begin;$i<=$end;$i++)
			$return .= '<OPTION VALUE="'.$i.'"'.($year==$i?' SELECTED="SELECTED"':'').'>'.$i;
		$return .= '</SELECT>';
	}

	if($options['C'])
		$return .= '<img src="assets/js/jscalendar/img.png" class="alignImg" id="trigger'.$_ROSARIO['PrepareDate'].'" style="cursor: pointer;"/>';

	//modif Francois: NOBR on date input
	$return .= '</span>';	
	
	if($_REQUEST['_ROSARIO_PDF'])
		$return = ProperDate($date);
	return $return;
}
?>