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
		$e = "onchange='document.location.href=\"".PreparePHP_SELF($_REQUEST,array('month'.$title,'day'.$title,'year'.$title))."&amp;month$title=\"+this.form.month$title.value+\"&amp;day$title=\"+this.form.day$title.value+\"&amp;year$title=\"+this.form.year$title.value;'";
		$extraM .= $e;
		$extraD .= $e;
		$extraY .= $e;
		}
		else
		{
		$extraM .= "onchange='document.location.href=\"".PreparePHP_SELF($_REQUEST,array('month'.$title))."&amp;month$title=\"+this.form.month$title.value;'";
		$extraD .= "onchange='document.location.href=\"".PreparePHP_SELF($_REQUEST,array('day'.$title))."&amp;day$title=\"+this.form.day$title.value;'";
		$extraY .= "onchange='document.location.href=\"".PreparePHP_SELF($_REQUEST,array('year'.$title))."&amp;year$title=\"+this.form.year$title.value;'";
		}
	}

	if($options['C'])
		$_ROSARIO['PrepareDate']++;

	if(strlen($date)==9) // ORACLE
	{
		$day = substr($date,0,2);
		$month = substr($date,3,3);
		$year = substr($date,7,2);
		if($year=='00' && ($month=='000' && $day=='00'))
			$year = '0000';
		else
			$year = ($year<50?'20':'19').$year;

		$return .= '<!-- '.$year.MonthNWSwitch($month,'tonum').$day.' -->';
	}
	elseif(strlen($date)==10) // POSTGRES
	{
		$day = substr($date,8,2);
		$month = MonthNWSwitch(substr($date,5,2),'tochar');
		$year = substr($date,0,4);

		$return .= '<!-- '.$year.MonthNWSwitch($month,'tonum').$day.' -->';
	}
	else //strlen($date)==11 ORACLE with 4-digit year
	{
		$day = substr($date,0,2);
		$month = substr($date,3,3);
		$year = substr($date,7,4);
		$return .= '<!-- '.$year.MonthNWSwitch($month,'tonum').$day.' -->';
	}

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
			if(strlen($i)==1)
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

	if($_REQUEST['_ROSARIO_PDF'])
		$return = ProperDate($date);
	return $return;
}
?>