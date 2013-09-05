<?php

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

//modif Francois: fix error Warning: Missing argument 2 for ShortDate()
//function ShortDate($date='',$column)
function ShortDate($date='')
{
	return ProperDate($date,'short');
}
?>