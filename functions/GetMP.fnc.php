<?php

function GetMP($mp,$column='TITLE')
{	global $_ROSARIO;

	// mab - need to translate marking_period_id to title to be useful as a function call from dbget
	// also, it doesn't make sense to ask for same thing you give
	if($column=='MARKING_PERIOD_ID')
		$column='TITLE';

	if(!isset($_ROSARIO['GetMP']))
	{
		$_ROSARIO['GetMP'] = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,POST_START_DATE,POST_END_DATE,MP,SORT_ORDER,SHORT_NAME,START_DATE,END_DATE,DOES_GRADES,DOES_COMMENTS FROM SCHOOL_MARKING_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array(),array('MARKING_PERIOD_ID'));
	}
	$suffix = '';

	if($mp==0 && $column=='TITLE')
		return _('Full Year').$suffix;
	else
		return $_ROSARIO['GetMP'][$mp][1][$column].$suffix;
}

function GetCurrentMP($mp,$date,$error=true)
{	global $_ROSARIO;

	if(!$_ROSARIO['GetCurrentMP'][$date][$mp])
	 	$_ROSARIO['GetCurrentMP'][$date][$mp] = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='$mp' AND '$date' BETWEEN START_DATE AND END_DATE AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));

	if($_ROSARIO['GetCurrentMP'][$date][$mp][1]['MARKING_PERIOD_ID'])
		return $_ROSARIO['GetCurrentMP'][$date][$mp][1]['MARKING_PERIOD_ID'];
	elseif($error)
		ErrorMessage(array(_('You are not currently in a marking period')),'fatal');
}
?>