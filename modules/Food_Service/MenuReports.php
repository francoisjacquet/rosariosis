<?php

if($_REQUEST['day_start'] && $_REQUEST['month_start'] && $_REQUEST['year_start'])
	while(!VerifyDate($start_date = $_REQUEST['day_start'].'-'.$_REQUEST['month_start'].'-'.$_REQUEST['year_start']))
		$_REQUEST['day_start']--;
else
{
	$_REQUEST['day_start'] = '01';
	$_REQUEST['month_start'] = mb_strtoupper(date('M'));
	$_REQUEST['year_start'] = date('Y');
	$start_date = $_REQUEST['day_start'].'-'.$_REQUEST['month_start'].'-'.$_REQUEST['year_start'];
}

if($_REQUEST['day_end'] && $_REQUEST['month_end'] && $_REQUEST['year_end'])
	while(!VerifyDate($end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end']))
		$_REQUEST['day_end']--;
else
{
	$_REQUEST['day_end'] = date('d');
	$_REQUEST['month_end'] = mb_strtoupper(date('M'));
	$_REQUEST['year_end'] = date('Y');
	$end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end'];
}

DrawHeader(ProgramTitle());

$menus_RET = DBGet(DBQuery("SELECT MENU_ID,TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"),array(),array('MENU_ID'));
if($_REQUEST['menu_id'])
{
	if($_REQUEST['menu_id']!='new')
		if($menus_RET[$_REQUEST['menu_id']])
			$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];
		elseif(count($menus_RET))
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
		else
			ErrorMessage(array(_('There are no menus yet setup.')),'fatal');
	elseif(count($menus_RET))
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
	else
		ErrorMessage(array(_('There are no menus yet setup.')),'fatal');
}
else
{
	if($_SESSION['FSA_menu_id'])
		if($menus_RET[$_SESSION['FSA_menu_id']])
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'];
		elseif(count($menus_RET))
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
		else
			ErrorMessage(array(_('There are no menus yet setup.')),'fatal');
	else
		if(count($menus_RET))
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
		else
			ErrorMessage(array(_('There are no menus yet setup.')),'fatal');
}

$users = array('Student'=>array(''=>array('ELLIGIBLE'=>0,'PARTICIPATED'=>0),
				'Reduced'=>array('ELLIGIBLE'=>0,'PARTICIPATED'=>0),
				'Free'=>array('ELLIGIBLE'=>0,'PARTICIPATED'=>0)
				),
	       'User'=>array(''=>array('ELLIGIBLE'=>0,'PARTICIPATED'=>0)
			     )
	       );

$users_totals = array('Student'=>array('ELLIGIBLE'=>0,'PARTICIPATED'=>0),
		      'User'=>array('ELLIGIBLE'=>0,'PARTICIPATED'=>0),
		      ''=>array('ELLIGIBLE'=>0,'PARTICIPATED'=>0)
		      );

$users_columns = array('ELLIGIBLE'=>_('Eligible'),'DAYS_POSSIBLE'=>_('Days Possible'),'TOTAL_ELLIGIBLE'=>_('Total Eligible'),'PARTICIPATED'=>_('Participated'));

$items_RET = DBGet(DBQuery('SELECT SHORT_NAME,DESCRIPTION FROM FOOD_SERVICE_ITEMS WHERE SCHOOL_ID=\''.UserSchool().'\' ORDER BY SORT_ORDER'));
$items = array();
$items_columns = array();
foreach($items_RET as $value)
{
	$items += array($value['SHORT_NAME']=>0);
	$items_columns += array($value['SHORT_NAME']=>$value['DESCRIPTION']);
}
//echo '<pre>'; var_dump($items); echo '</pre>';
//echo '<pre>'; var_dump($items_columns); echo '</pre>';

$types = array('Student'=>array(''=>$items,
				'Reduced'=>$items,
				'Free'=>$items
				),
	       'User'=>array(''=>$items
			     )
	       );

$types_totals = array('Student'=>$items,
		      'User'=>$items,
		      ''=>$items
		      );

$types_columns = $items_columns;

$type_select = '<SELECT name=type_select onchange="this.form.submit()"><OPTION value=participation'.($_REQUEST['type_select']=='sales' ? '' : ' selected').'>'._('Participation').'</OPTION><OPTION value=sales'.($_REQUEST['type_select']=='sales' ? ' selected' : '').'>'._('Sales').'</OPTION></SELECT>';

//$calendars_RET = DBGet(DBQuery("SELECT acs.CALENDAR_ID,(SELECT count(1) FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID=acs.CALENDAR_ID AND SCHOOL_DATE BETWEEN '".$start_date."' AND '".$end_date."') AS DAY_COUNT FROM ATTENDANCE_CALENDARS acs WHERE acs.SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

$RET = DBGet(DBQuery("SELECT 'Student' AS TYPE, fssa.DISCOUNT,count(1) AS DAYS,(SELECT count(1) FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID=ac.CALENDAR_ID AND SCHOOL_DATE BETWEEN '".$start_date."' AND '".$end_date."') AS ELLIGIBLE FROM FOOD_SERVICE_STUDENT_ACCOUNTS fssa,STUDENT_ENROLLMENT ssm,ATTENDANCE_CALENDAR ac WHERE ac.CALENDAR_ID=ssm.CALENDAR_ID                                                                                                                        AND ac.SCHOOL_DATE BETWEEN '".$start_date."' AND '".$end_date."' AND fssa.STATUS IS NULL AND ssm.STUDENT_ID=fssa.STUDENT_ID AND ssm.SYEAR='".UserSyear()."' AND ssm.SCHOOL_ID='".UserSchool()."' AND (ac.SCHOOL_DATE BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL AND ac.SCHOOL_DATE>=ssm.START_DATE) GROUP BY fssa.DISCOUNT,ac.CALENDAR_ID"),array('ELLIGIBLE'=>'bump_dep','DAYS'=>'bump_dep'));
//echo '<pre>'; var_dump($RET); echo '</pre>';

$RET = DBGet(DBQuery("SELECT 'User'    AS TYPE,'' AS DISCOUNT,count(1) AS DAYS,(SELECT count(1) FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID=ac.CALENDAR_ID AND SCHOOL_DATE BETWEEN '".$start_date."' AND '".$end_date."') AS ELLIGIBLE FROM   FOOD_SERVICE_STAFF_ACCOUNTS fssa,STAFF s,               ATTENDANCE_CALENDAR ac WHERE ac.CALENDAR_ID=(SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND DEFAULT_CALENDAR='Y') AND ac.SCHOOL_DATE BETWEEN '".$start_date."' AND '".$end_date."' AND fssa.STATUS IS NULL AND s.STAFF_ID=fssa.STAFF_ID AND (s.SCHOOLS IS NULL OR position(','||'".UserSchool()."'||',' IN s.SCHOOLS)>0) GROUP BY ac.CALENDAR_ID"),array('ELLIGIBLE'=>'bump_dep','DAYS'=>'bump_dep'));
//echo '<pre>'; var_dump($RET); echo '</pre>';

$RET = DBGet(DBQuery("SELECT DISTINCT ON (STUDENT_ID) 'Student' AS TYPE,      DISCOUNT,count(1) AS PARTICIPATED FROM FOOD_SERVICE_TRANSACTIONS       WHERE SYEAR='".UserSyear()."' AND SHORT_NAME='".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."' AND TIMESTAMP BETWEEN '".$start_date."' AND date '".$end_date."' +1 AND SCHOOL_ID='".UserSchool()."' GROUP BY STUDENT_ID,DISCOUNT"),array('PARTICIPATED'=>'bump_dep'));

$RET = DBGet(DBQuery("SELECT DISTINCT ON (STAFF_ID)      'User' AS TYPE,'' AS DISCOUNT,count(1) AS PARTICIPATED FROM FOOD_SERVICE_STAFF_TRANSACTIONS WHERE SYEAR='".UserSyear()."' AND SHORT_NAME='".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."' AND TIMESTAMP BETWEEN '".$start_date."' AND date '".$end_date."' +1 AND SCHOOL_ID='".UserSchool()."' GROUP BY STAFF_ID"),array('PARTICIPATED'=>'bump_dep'));

if($_REQUEST['type_select']=='sales')
{
	$RET = DBGet(DBQuery("SELECT 'Student' AS TYPE,fsti.SHORT_NAME,  fst.DISCOUNT,-sum((SELECT AMOUNT FROM FOOD_SERVICE_TRANSACTION_ITEMS WHERE TRANSACTION_ID=fsti.TRANSACTION_ID AND ITEM_ID=fsti.ITEM_ID))                  AS COUNT FROM FOOD_SERVICE_TRANSACTIONS fst,FOOD_SERVICE_TRANSACTION_ITEMS fsti             WHERE fsti.TRANSACTION_ID=fst.TRANSACTION_ID AND fst.SYEAR='".UserSyear()."' AND fst.SCHOOL_ID='".UserSchool()."' AND fst.SHORT_NAME='".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."' AND fst.TIMESTAMP BETWEEN '".$start_date."' AND date '".$end_date."' +1 GROUP BY fsti.SHORT_NAME,fst.DISCOUNT"),array('SHORT_NAME'=>'bump_count'));
	$RET = DBGet(DBQuery("SELECT    'User' AS TYPE,fsti.SHORT_NAME,'' AS DISCOUNT,-sum((SELECT sum(AMOUNT) FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID=fsti.TRANSACTION_ID AND SHORT_NAME=fsti.SHORT_NAME)) AS COUNT FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst,FOOD_SERVICE_STAFF_TRANSACTION_ITEMS fsti WHERE fsti.TRANSACTION_ID=fst.TRANSACTION_ID AND fst.SYEAR='".UserSyear()."' AND fst.SCHOOL_ID='".UserSchool()."' AND fst.SHORT_NAME='".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."' AND fst.TIMESTAMP BETWEEN '".$start_date."' AND date '".$end_date."' +1 GROUP BY fsti.SHORT_NAME"),array('SHORT_NAME'=>'bump_count'));

	$LO_types = array(0=>array(array()));
	foreach($users as $user=>$discounts)
	{
		$TMP_types = array(0=>array());
		foreach($discounts as $discount=>$value)
		{
			$total = array_sum($types[$user][$discount]);
			$TMP_types[] = array('TYPE'=>$user,'DISCOUNT'=>$discount,'ELLIGIBLE'=>number_format($value['ELLIGIBLE'],1),'DAYS_POSSIBLE'=>number_format((!empty($value['ELLIGIBLE']) ? $value['DAYS']/$value['ELLIGIBLE'] : 0),1),'TOTAL_ELLIGIBLE'=>$value['DAYS'],'PARTICIPATED'=>$value['PARTICIPATED'],'TOTAL'=>'<b>'.number_format($total,2).'</b>') + array_map('format',$types[$user][$discount]);
		}
		$total = array_sum($types_totals[$user]);
//modif Francois: add translation
		$TMP_types[] = array('TYPE'=>'<b>'.$user.'</b>','DISCOUNT'=>'<b>'._('Totals').'</b>','ELLIGIBLE'=>'<b>'.number_format($users_totals['']['ELLIGIBLE'],1).'</b>','DAYS_POSSIBLE'=>'<b>'.number_format((!empty($users_totals[$user]['ELLIGIBLE']) ? $users_totals[$user]['DAYS']/$users_totals[$user]['ELLIGIBLE'] : 0),1).'</b>','TOTAL_ELLIGIBLE'=>'<b>'.$users_totals[$user]['DAYS'].'</b>','PARTICIPATED'=>'<b>'.$users_totals[$user]['PARTICIPATED'].'</b>','TOTAL'=>'<b>'.number_format($total,2).'</b>') + array_map('bold_format',$types_totals[$user]);
		unset($TMP_types[0]);
		$LO_types[] = $TMP_types;
	}
	$total = array_sum($types_totals['']);
	foreach($types_totals[''] as $key=>$value)
		if($value==0)
			unset($types_columns[$key]);
	$LO_types[] = array(array('TYPE'=>'<b>'._('Totals').'</b>','ELLIGIBLE'=>'<b>'.number_format($users_totals['']['ELLIGIBLE'],1).'</b>','DAYS_POSSIBLE'=>'<b>'.number_format($users_totals['']['DAYS']/$users_totals['']['ELLIGIBLE'],1).'</b>','TOTAL_ELLIGIBLE'=>'<b>'.$users_totals['']['DAYS'].'</b>','PARTICIPATED'=>'<b>'.$users_totals['']['PARTICIPATED'].'</b>','TOTAL'=>'<b>'.number_format($total,2).'</b>') + array_map('bold_format',$types_totals['']));
	unset($LO_types[0]);
	$LO_columns = array('TYPE'=>_('Type'),'DISCOUNT'=>_('Discount')) + $users_columns + $types_columns + array('TOTAL'=>_('Total'));
}
else
{
	$RET = DBGet(DBQuery("SELECT 'Student' AS TYPE,  fst.DISCOUNT,fsti.SHORT_NAME,count(*) FROM FOOD_SERVICE_TRANSACTIONS fst,FOOD_SERVICE_TRANSACTION_ITEMS fsti             WHERE fsti.TRANSACTION_ID=fst.TRANSACTION_ID AND fst.SYEAR='".UserSyear()."' AND fst.SCHOOL_ID='".UserSchool()."' AND fst.SHORT_NAME='".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."' AND fst.TIMESTAMP BETWEEN '".$start_date."' AND date '".$end_date."' +1 GROUP BY fsti.SHORT_NAME,fst.DISCOUNT"),array('SHORT_NAME'=>'bump_count'));
	$RET = DBGet(DBQuery("SELECT 'User'    AS TYPE,'' AS DISCOUNT,fsti.SHORT_NAME,count(*) FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst,FOOD_SERVICE_STAFF_TRANSACTION_ITEMS fsti WHERE fsti.TRANSACTION_ID=fst.TRANSACTION_ID AND fst.SYEAR='".UserSyear()."' AND fst.SCHOOL_ID='".UserSchool()."' AND fst.SHORT_NAME='".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."' AND fst.TIMESTAMP BETWEEN '".$start_date."' AND date '".$end_date."' +1 GROUP BY fsti.SHORT_NAME"),array('SHORT_NAME'=>'bump_count'));

	$LO_types = array(0=>array());
//modif Francois: add translation
	$users_locale = array('Student'=>_('Student'), 'User'=>_('User'));

	foreach($users as $user=>$discounts)
	{
		$TMP_types = array(0=>array());
		foreach($discounts as $discount=>$value)
		{
//modif Francois: fix error Warning: Division by zero
			$TMP_types[] = array('TYPE'=>(empty($users_locale[$user])?$user:$users_locale[$user]),'DISCOUNT'=>$discount,'ELLIGIBLE'=>number_format($value['ELLIGIBLE'],1),'DAYS_POSSIBLE'=>($value['ELLIGIBLE']==0?'0.0':number_format($value['DAYS']/$value['ELLIGIBLE'],1)),'TOTAL_ELLIGIBLE'=>$value['DAYS'],'PARTICIPATED'=>$value['PARTICIPATED']) + $types[$user][$discount];
		}
		$TMP_types[] = array('TYPE'=>'<b>'.(empty($users_locale[$user])?$user:$users_locale[$user]).'</b>','DISCOUNT'=>'<b>'._('Totals').'</b>','ELLIGIBLE'=>'<b>'.number_format($users_totals[$user]['ELLIGIBLE'],1).'</b>','DAYS_POSSIBLE'=>'<b>'.number_format((empty($users_totals[$user]['ELLIGIBLE']) ? 0 : $users_totals[$user]['DAYS']/$users_totals[$user]['ELLIGIBLE']),1).'</b>','TOTAL_ELLIGIBLE'=>'<b>'.$users_totals[$user]['DAYS'].'</b>','PARTICIPATED'=>'<b>'.$users_totals[$user]['PARTICIPATED'].'</b>') + array_map('bold',$types_totals[$user]);
		unset($TMP_types[0]);
		$LO_types[] = $TMP_types;
	}
	foreach($types_totals[''] as $key=>$value)
		if($value == 0)
			unset($types_columns[$key]);
	$LO_types[] = array(array('TYPE'=>'<b>'._('Totals').'</b>','ELLIGIBLE'=>'<b>'.number_format($users_totals['']['ELLIGIBLE'],1).'</b>','DAYS_POSSIBLE'=>'<b>'.number_format((empty($users_totals['']['ELLIGIBLE']) ? 0 : $users_totals['']['DAYS']/$users_totals['']['ELLIGIBLE']),1).'</b>','TOTAL_ELLIGIBLE'=>'<b>'.$users_totals['']['DAYS'].'</b>','PARTICIPATED'=>'<b>'.$users_totals['']['PARTICIPATED'].'</b>') + array_map('bold',$types_totals['']));
	unset($LO_types[0]);
	$LO_columns = array('TYPE'=>_('Type'),'DISCOUNT'=>_('Discount')) + $users_columns + $types_columns;
}

$PHP_tmp_SELF = PreparePHP_SELF();
echo '<FORM action="'.$PHP_tmp_SELF.'" method="POST">';
DrawHeader(_('Timeframe').': '.PrepareDate($start_date,'_start').' '._('to').' '.PrepareDate($end_date,'_end').' : <INPUT type="submit" value="'._('Go').'" />');
DrawHeader($type_select);
echo '<BR />';

$tabs = array();
foreach($menus_RET as $id=>$menu)
	$tabs[] = array('title'=>$menu[1]['TITLE'],'link'=>"Modules.php?modname=$_REQUEST[modname]&menu_id=$id&day_start=$_REQUEST[day_start]&month_start=$_REQUEST[month_start]&year_start=$_REQUEST[year_start]&day_end=$_REQUEST[day_end]&month_end=$_REQUEST[month_end]&year_end=$_REQUEST[year_end]&type_select=$_REQUEST[type_select]");

$LO_options = array('count'=>false,'download'=>false,'search'=>false,
	'header'=>WrapTabs($tabs,"Modules.php?modname=$_REQUEST[modname]&menu_id=$_REQUEST[menu_id]&day_start=$_REQUEST[day_start]&month_start=$_REQUEST[month_start]&year_start=$_REQUEST[year_start]&day_end=$_REQUEST[day_end]&month_end=$_REQUEST[month_end]&year_end=$_REQUEST[year_end]&type_select=$_REQUEST[type_select]"));

ListOutput($LO_types,$LO_columns,'.','.',array(),array(array('')),$LO_options);
echo '</FORM>';

function format($item)
{
	return number_format($item,2);
}

function bold($item)
{
	return '<b>'.$item.'</b>';
}

function bold_format($item)
{
	return '<b>'.number_format($item,2).'</b>';
}

// days, elligibile, participated
function bump_dep($value,$column)
{	global $THIS_RET,$users,$users_totals;

	if($column=='ELLIGIBLE')
		$value = $THIS_RET['DAYS']/$value;

	if(!$users[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']])
		$users[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']] = array('DAYS'=>0,'ELLIGIBLE'=>0,'PARTICIPATED'=>0);
	$users[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']][$column] += $value;
	$users_totals[$THIS_RET['TYPE']][$column] += $value;
	$users_totals[''][$column] += $value;
	return $THIS_RET[$column];
}

function bump_count($value,$column)
{	global $THIS_RET,$types,$types_columns,$types_totals;

	if($types[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']])
		$types[$THIS_RET['TYPE']][$THIS_RET['DISCOUNT']][$value] += $THIS_RET['COUNT'];
	else
		$types[$THIS_RET['TYPE']] += array($THIS_RET['DISCOUNT']=>array($value=>$THIS_RET['COUNT']));
	if(!$types_columns[$value])
	{
		$types_columns += array($value=>'<span style="color:red">'.$value.'</span>');
		$types_totals['Student'][$value] = 0;
		$types_totals['User'][$value] = 0;
		$types_totals[$THIS_RET['TYPE']][$value] = $THIS_RET['COUNT'];
		$types_totals[''][$value] = $THIS_RET['COUNT'];
	}
	else
	{
		$types_totals[$THIS_RET['TYPE']][$value] += $THIS_RET['COUNT'];
		$types_totals[''][$value] += $THIS_RET['COUNT'];
	}
	return $value;
}
?>
