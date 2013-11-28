<?php

if($_REQUEST['month_date'] && $_REQUEST['day_date'] && $_REQUEST['year_date'])
	while(!VerifyDate($date = $_REQUEST['day_date'].'-'.$_REQUEST['month_date'].'-'.$_REQUEST['year_date']))
		$_REQUEST['day_date']--;
else
{
	$_REQUEST['day_date'] = date('d');
	$_REQUEST['month_date'] = mb_strtoupper(date('M'));
	$_REQUEST['year_date'] = date('Y');
	$date = $_REQUEST['day_date'].'-'.$_REQUEST['month_date'].'-'.$_REQUEST['year_date'];
}

DrawHeader(ProgramTitle());

$course_RET = DBGet(DBQuery("SELECT DOES_FS_COUNTS,DAYS,CALENDAR_ID,MP,MARKING_PERIOD_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."'"));
//echo '<pre>'; var_dump($course_RET); echo '</pre>';

if(!trim($course_RET[1]['DOES_FS_COUNTS'],','))
	ErrorMessage(array(_('You cannot take meal counts for this period.')),'fatal');

// the following query is for when doea_fs_counts is a comma quoted string of meal_id's, ex. ,1,2,4,
//$menus_RET = DBGet(DBQuery('SELECT MENU_ID,TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID=\''.UserSchool().'\' AND MENU_ID IN ('.trim($course_RET[1]['DOES_FS_COUNTS'],',').') ORDER BY SORT_ORDER'),array(),array('MENU_ID'));
// use all meal_id's for now
$menus_RET = DBGet(DBQuery('SELECT MENU_ID,TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID=\''.UserSchool().'\' ORDER BY SORT_ORDER'),array(),array('MENU_ID'));
//echo '<pre>'; var_dump($menus_RET); echo '</pre>';
if(!$_REQUEST['menu_id'])
	if(!$_SESSION['FSA_menu_id'] || !$menus_RET[$_SESSION['FSA_menu_id']])
		if(count($menus_RET))
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
		else
			ErrorMessage(array(_('You cannot take meal counts for this period.')),'fatal');
	else
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'];
else
	$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];

if($course_RET[1]['CALENDAR_ID'])
	$calendar_id = $course_RET[1]['CALENDAR_ID'];
else
{
	$calendar_id = DBGet(DBQuery("SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND DEFAULT_CALENDAR='Y'"));
	$calendar_id = $calendar_id['CALENDAR_ID'];
}

$calendar_RET = DBGet(DBQuery("SELECT MINUTES FROM ATTENDANCE_CALENDAR WHERE CALENDAR_ID='$calendar_id' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND SCHOOL_DATE='$date'"));
//echo '<pre>'; var_dump($calendar_RET); echo '</pre>';

if(!$calendar_RET[1]['MINUTES'])
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&menu_id='.$_REQUEST['menu_id'].'" method="POST">';
	DrawHeader(PrepareDate($date,'_date',false,array('submit'=>true)));
	echo '</FORM>';
	ErrorMessage(array(_('The selected date is not a school day!')),'fatal');
}

if(GetCurrentMP($course_RET[1]['MP'],$date)!=$course_RET[1]['MARKING_PERIOD_ID'])
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&menu_id='.$_REQUEST['menu_id'].'" method="POST">';
	DrawHeader(PrepareDate($date,'_date',false,array('submit'=>true)));
	echo '</FORM>';
	ErrorMessage(array(_('This period does not meet in the marking period of the selected date.')),'fatal');
}

$qtr_id = GetCurrentMP('QTR',$date);

$days = $course_RET[1]['DAYS'];
$day = date('D',strtotime($date));
switch($day)
{
	case 'Sun':
		$day = 'U';
	break;
	case 'Thu':
		$day = 'H';
	break;
	default:
		$day = mb_substr($day,0,1);
	break;
}

if(mb_strpos($days,$day)===false)
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&table='.$_REQUEST[table].'" method="POST">';
	DrawHeader(PrepareDate($date,'_date',false,array('submit'=>true)));
	echo '</FORM>';
	ErrorMessage(array(_('This period does not meet on the selected date.')),'fatal');
}

// if running as a teacher program then rosario[allow_edit] will already be set according to admin permissions
if(!isset($_ROSARIO['allow_edit']))
{
	$time = strtotime(DBDate('postgres'));
	if(GetMP($qtr_id,'POST_START_DATE') && ($time<=strtotime(GetMP($qtr_id,'POST_END_DATE'))))
		$_ROSARIO['allow_edit'] = true;
}

$current_RET = DBGet(DBQuery('SELECT ITEM_ID FROM FOOD_SERVICE_COMPLETED WHERE STAFF_ID=\''.User('STAFF_ID').'\' AND SCHOOL_DATE=\''.$date.'\' AND PERIOD_ID=\''.UserPeriod().'\' AND MENU_ID=\''.$_REQUEST['menu_id'].'\''),array(),array('ITEM_ID'));
//echo '<pre>'; var_dump($current_RET); echo '</pre>';
if($_REQUEST['values'] && $_POST['values'])
{
	GetCurrentMP('QTR',$date);
	foreach($_REQUEST['values'] as $id=>$value)
	{
		if($current_RET[$id])
		{
			$sql = 'UPDATE FOOD_SERVICE_COMPLETED SET ';
			$sql .= 'COUNT=\''.$value['COUNT'].'\' ';
			$sql .= 'WHERE STAFF_ID=\''.User('STAFF_ID').'\' AND SCHOOL_DATE=\''.$date.'\' AND PERIOD_ID=\''.UserPeriod().'\' AND MENU_ID=\''.$_REQUEST['menu_id'].'\' AND ITEM_ID=\''.$id.'\'';
		}
		else
		{
			$fields ='STAFF_ID,SCHOOL_DATE,PERIOD_ID,MENU_ID,ITEM_ID,COUNT';
			$values = '\''.User('STAFF_ID').'\',\''.$date.'\',\''.UserPeriod().'\',\''.$_REQUEST['menu_id'].'\',\''.$id.'\',\''.$value['COUNT'].'\'';
			$sql = 'INSERT INTO FOOD_SERVICE_COMPLETED ('.$fields.') values ('.$values.')';
		}
		DBQuery($sql);
	}
	unset($_SESSION['_REQUEST_vars']['values']);
}

if($date != DBDate())
	$date_note = ' <span style="color:red">'._('The selected date is not today').'</span>';

$completed = DBGet(DBQuery('SELECT count(\'Y\') AS COMPLETED FROM FOOD_SERVICE_COMPLETED WHERE STAFF_ID=\''.User('STAFF_ID').'\' AND SCHOOL_DATE=\''.$date.'\' AND PERIOD_ID=\''.UserPeriod().'\' AND MENU_ID=\''.$_REQUEST['menu_id'].'\''));
if($completed[1]['COMPLETED'])
	$note = ErrorMessage(array('<IMG SRC="assets/check_button.png" class="alignImg" />'._('You have taken lunch counts today for this period.')),'note');

echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
DrawHeader(PrepareDate($date,'_date',false,array('submit'=>true)).$date_note,SubmitButton(_('Save')));
DrawHeader($note);

$meal_RET = DBGet(DBQuery('SELECT DESCRIPTION FROM CALENDAR_EVENTS WHERE SYEAR='.UserSyear().' AND SCHOOL_ID='.UserSchool().' AND SCHOOL_DATE=\''.$date.'\' AND TITLE=\''.$menus_RET[$_REQUEST['menu_id']][1]['TITLE'].'\''));

if($meal_RET)
{
	echo '<TABLE class="width-100p">';
	echo '<TR><TD class="center">';
	echo '<B>Today\'s '.$menus_RET[$_REQUEST['menu_id']][1]['TITLE'].':</B> '.$meal_RET[1]['DESCRIPTION'];
	echo '</TD></TR></TABLE><HR>';
}

$items_RET = DBGet(DBQuery('SELECT fsi.ITEM_ID,fsi.DESCRIPTION,fsmi.DOES_COUNT,(SELECT COUNT FROM FOOD_SERVICE_COMPLETED WHERE STAFF_ID=\''.User('STAFF_ID').'\' AND SCHOOL_DATE=\''.$date.'\' AND PERIOD_ID=\''.UserPeriod().'\' AND ITEM_ID=fsi.ITEM_ID AND MENU_ID=fsmi.MENU_ID) AS COUNT FROM FOOD_SERVICE_ITEMS fsi,FOOD_SERVICE_MENU_ITEMS fsmi WHERE fsmi.MENU_ID=\''.$_REQUEST['menu_id'].'\' AND fsi.ITEM_ID=fsmi.ITEM_ID AND fsmi.DOES_COUNT IS NOT NULL ORDER BY fsmi.SORT_ORDER'),array('COUNT'=>'makeTextInput'));

echo '<TABLE class="width-100p"><TR><TD style="width:50%;">';
$LO_columns = array('DESCRIPTION'=>_('Item'),'COUNT'=>_('Count'));

	if(count($menus_RET)>1)
	{
		$tabs = array();
		foreach($menus_RET as $id=>$meal)
			$tabs[] = array('title'=>$meal[1]['TITLE'],'link'=>"Modules.php?modname=$_REQUEST[modname]&menu_id=$id&day_date=$_REQUEST[day_date]&month_date=$_REQUEST[month_date]&year_date=$_REQUEST[year_date]");

		echo '<BR />';
		echo '<span class="center">'.WrapTabs($tabs,"Modules.php?modname=$_REQUEST[modname]&menu_id=$_REQUEST[menu_id]&day_date=$_REQUEST[day_date]&month_date=$_REQUEST[month_date]&year_date=$_REQUEST[year_date]").'</span>';
		$extra = array('count'=>false,'download'=>false,'search'=>false);
	}
	else
	{
		$extra = array('search'=>false);
		$plural = $menus_RET[1][1]['TITLE'].' '._('Items');
		$singular = $menus_RET[1][1]['TITLE'].' '._('Item');
	}

ListOutput($items_RET,$LO_columns,$singular,$plural,false,false,$extra);
echo '<span class="center">'.SubmitButton(_('Save')).'</CENTRE>';
echo '</TD><TD style="width:50%;">';

$extra['SELECT'] .= ',fsa.BALANCE,fssa.STATUS';
$extra['FROM'] .= ',FOOD_SERVICE_ACCOUNTS fsa,FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
$extra['WHERE'] .= ' AND fssa.STUDENT_ID=s.STUDENT_ID AND fsa.ACCOUNT_ID=fssa.ACCOUNT_ID AND fssa.STATUS IS NOT NULL';
if(!$extra['functions'])
	$extra['functions'] = array();
$extra['functions'] += array('BALANCE'=>'red');

$stu_RET = GetStuList($extra);

$LO_columns = array('FULL_NAME'=>_('Student'),'STUDENT_ID'=>_('RosarioSIS ID'),'GRADE_ID'=>_('Grade Level'),'BALANCE'=>_('Balance'),'STATUS'=>_('Status'));
ListOutput($stu_RET,$LO_columns,'Ineligible Student','Ineligible Students',false,false,array('save'=>false,'search'=>false));
echo '</TD></TR></TABLE>';
echo "</FORM>";

function red($value)
{
	if($value<0)
		return '<span style="color:red">'.$value.'</span>';
	else
		return $value;
}

function makeTextInput($value,$name)
{	global $THIS_RET;

	$extra = 'size=6 maxlength=8';
	return TextInput($value,'values['.$THIS_RET['ITEM_ID'].']['.$name.']','',$extra);
}
?>
