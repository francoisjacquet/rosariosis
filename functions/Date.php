<?php

function DBDate($type='')
{
	if($type=='postgres')
		return date('Y-m-d');
	return mb_strtoupper(date('d-M-Y'));
}

/*
Outputs a pretty date when sent an oracle or postgres date.
*/

function ProperDate($date='',$length='long')
{
	if($date)
	{
	if(mb_strlen($date)==9) // ORACLE
	{
		$months_number = array('JAN'=>'01','FEB'=>'02','MAR'=>'03','APR'=>'04','MAY'=>'05','JUN'=>'06','JUL'=>'07','AUG'=>'08','SEP'=>'09','OCT'=>'10','NOV'=>'11','DEC'=>'12');
		$year = mb_substr($date,7,2);
		$year = ($year<50?'20':'19').$year;
		$month = $months_number[mb_strtoupper(mb_substr($date,3,3))];
		$day = mb_substr($date,0,2);
	}
	elseif(mb_strlen($date)==10) // POSTGRES
	{
		$year = mb_substr($date,0,4);
		$month = mb_substr($date,5,2);
		$day = mb_substr($date,8,2);
	}
	else //mb_strlen($date)==11 ORACLE with 4-digit year
	{
		$months_number = array('JAN'=>'01','FEB'=>'02','MAR'=>'03','APR'=>'04','MAY'=>'05','JUN'=>'06','JUL'=>'07','AUG'=>'08','SEP'=>'09','OCT'=>'10','NOV'=>'11','DEC'=>'12');
		$year = mb_substr($date,7,4);
		$day = mb_substr($date,0,2);
		$month = $months_number[mb_strtoupper(mb_substr($date,3,3))];
	}
	$comment = '<!-- '.$year.$month.$day.' -->';

	if(!empty($_REQUEST['_ROSARIO_PDF']) && $_REQUEST['LO_save'] && Preferences('E_DATE')=='MM/DD/YYYY')
		return $comment.$month.'/'.$day.'/'.$year;

	//modif Francois: display locale with strftime()
//	if((Preferences('MONTH')=='m' || Preferences('MONTH')=='M') && (Preferences('DAY')=='j' || Preferences('DAY')=='d') && Preferences('YEAR'))
	if((Preferences('MONTH')=='%m' || Preferences('MONTH')=='%b') && Preferences('DAY')=='%d' && Preferences('YEAR'))
		$sep = '/';
	else
		$sep = ' ';

	//modif Francois: display locale with strftime()
	//modif Francois: NOBR on date
	return $comment.'<span style="white-space:nowrap">'.mb_convert_case(iconv('','UTF-8',strftime((($length=='long' || Preferences('MONTH')!='%B')?Preferences('MONTH'):'%b').$sep.Preferences('DAY').$sep.Preferences('YEAR'),mktime(0,0,0,$month+0,$day+0,$year+0))), MB_CASE_TITLE).'</span>';
//	return $comment.date((($length=='long' || Preferences('MONTH')!='F')?Preferences('MONTH'):'M').$sep.Preferences('DAY').$sep.Preferences('YEAR'),mktime(0,0,0,$month+0,$day+0,$year+0));
	}
}


function VerifyDate($date)
{
	if(mb_strlen($date)==9) // ORACLE
	{
		$day = mb_substr($date,0,2)+0;
		$month = MonthNWSwitch(mb_substr($date,3,3),'tonum')+0;
		$year = mb_substr($date,7,2);
		$year = (($year<50)?20:19) . $year;
	}
	elseif(mb_strlen($date)==10) // POSTGRES
	{
		$day = mb_substr($date,8,2)+0;
		$month = mb_substr($date,5,2)+0;
		$year = mb_substr($date,0,4);
	}
	elseif(mb_strlen($date)==11) // ORACLE with 4-digit year
	{
		$day = mb_substr($date,0,2)+0;
		$month = MonthNWSwitch(mb_substr($date,3,3),'tonum')+0;
		$year = mb_substr($date,7,4);
	}
	else
		return false;

	return checkdate($month,$day,$year);
}

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
			$return .= '<script>var date_onclick = document.createElement("a"); date_onclick.href = "'.PreparePHP_SELF($_REQUEST,array('month'.$title,'day'.$title,'year'.$title)).'"; date_onclick.target = "body";</script>';
			$e = 'onchange="date_onclick.href += \'&month'.$title.'=\'+this.form.month'.$title.'.value+\'&day'.$title.'=\'+this.form.day'.$title.'.value+\'&year'.$title.'=\'+this.form.year'.$title.'.value; ajaxLink(date_onclick);"';
			$extraM .= $e;
			$extraD .= $e;
			$extraY .= $e;
		}
		else
		{
			if ($options['M'])
				$return .= '<script>var month_onclick = document.createElement("a"); month_onclick.href = "'.PreparePHP_SELF($_REQUEST,array('month'.$title)).'"; month_onclick.target = "body";</script>';
			$extraM .= 'onchange="month_onclick.href += \'&month'.$title.'=\'+this.form.month'.$title.'.value; ajaxLink(month_onclick);"';
			
			if ($options['D'])
				$return .= '<script>var day_onclick = document.createElement("a"); day_onclick.href = "'.PreparePHP_SELF($_REQUEST,array('day'.$title)).'"; day_onclick.target = "body";</script>';
			$extraD .= 'onchange="day_onclick.href += \'&day'.$title.'=\'+this.form.day'.$title.'.value; ajaxLink(day_onclick);"';
			
			if ($options['Y'])
				$return .= '<script>var year_onclick = document.createElement("a"); year_onclick.href = "'.PreparePHP_SELF($_REQUEST,array('year'.$title)).'"; year_onclick.target = "body";</script>';
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
				$return .= '<OPTION value="" SELECTED>'._('N/A').'';
			else
				$return .= '<OPTION value="">'._('N/A').'';
		}
		//modif Francois: traduction des mois!
		foreach(array('JAN'=>_('January'),'FEB'=>_('February'),'MAR'=>_('March'),'APR'=>_('April'),'MAY'=>_('May'),'JUN'=>_('June'),'JUL'=>_('July'),'AUG'=>_('August'),'SEP'=>_('September'),'OCT'=>_('October'),'NOV'=>_('November'),'DEC'=>_('December')) as $key=>$name)
			$return .= '<OPTION VALUE="'.$key.'"'.($month==$key?' SELECTED':'').'>'.$name;
		$return .= '</SELECT>';
	}

	// DAY  ---------------
	if($options['D'])
	{
		$return .= '<SELECT NAME="day'.$title.'" id="daySelect'.$_ROSARIO['PrepareDate'].'" SIZE="1" '.$extraD.'>';
		if($allow_na)
		{
			if($day=='00')
				$return .= '<OPTION value="" SELECTED>'._('N/A').'';
			else
				$return .= '<OPTION value="">'._('N/A').'';
		}

		for($i=1;$i<=31;$i++)
		{
			if(mb_strlen($i)==1)
				$print = '0'.$i;
			else
				$print = $i;

			$return .= '<OPTION VALUE="'.$print.'"'.($day==$print?' SELECTED':'').'>'.$i;
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
			//modif Francois: show 20 previous years instead of 5
			$begin = $year - 20;
			$end = $year + 5;
		}

		$return .= '<SELECT NAME="year'.$title.'" id="yearSelect'.$_ROSARIO['PrepareDate'].'" SIZE="1" '.$extraY.'>';
		if($allow_na)
		{
			if($year=='0000')
				$return .= '<OPTION value="" SELECTED>'._('N/A').'';
			else
				$return .= '<OPTION value="">'._('N/A').'';
		}

		for($i=$begin;$i<=$end;$i++)
			$return .= '<OPTION VALUE="'.$i.'"'.($year==$i?' SELECTED':'').'>'.$i;
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

function MonthNWSwitch($month, $direction='both')
{
	if($direction=='tonum')
	{
		if(mb_strlen($month)<3) // assume already num.
			return $month;
		else
			return __mnwswitch_char2num($month);
	}
	elseif($direction=='tochar')
	{
		if(mb_strlen($month)==3) // assume already char.
			return $month;
		else
			return __mnwswitch_num2char($month);
	}
	else
	{
		$month=__mnwswitch_num2char($month);
		$month=__mnwswitch_char2num($month);
		return $month;
	}
} 

function __mnwswitch_num2char($month)
{
	if(mb_strlen($month)==1)
		$month='0'.$month;
		
	if($month=='01'){$out="JAN";}
	elseif($month=='02'){$out="FEB";}
	elseif($month=='03'){$out="MAR";}
	elseif($month=='04'){$out="APR";}
	elseif($month=='05'){$out="MAY";}
	elseif($month=='06'){$out="JUN";}
	elseif($month=='07'){$out="JUL";}
	elseif($month=='08'){$out="AUG";}
	elseif($month=='09'){$out="SEP";}
	elseif($month=='10'){$out="OCT";}
	elseif($month=='11'){$out="NOV";}
	elseif($month=='12' || $month=='00'){$out="DEC";}
	else $out=$month;
	return $out;
}

function __mnwswitch_char2num($month)
{
	if(mb_strtoupper($month)=='JAN'){$out="01";}
	elseif(mb_strtoupper($month)=='FEB'){$out="02";}
	elseif(mb_strtoupper($month)=='MAR'){$out="03";}
	elseif(mb_strtoupper($month)=='APR'){$out="04";}
	elseif(mb_strtoupper($month)=='MAY'){$out="05";}
	elseif(mb_strtoupper($month)=='JUN'){$out="06";}
	elseif(mb_strtoupper($month)=='JUL'){$out="07";}
	elseif(mb_strtoupper($month)=='AUG'){$out="08";}
	elseif(mb_strtoupper($month)=='SEP'){$out="09";}
	elseif(mb_strtoupper($month)=='OCT'){$out="10";}
	elseif(mb_strtoupper($month)=='NOV'){$out="11";}
	elseif(mb_strtoupper($month)=='DEC'){$out="12";}
	else $out=$month;
	return $out;
}
?>