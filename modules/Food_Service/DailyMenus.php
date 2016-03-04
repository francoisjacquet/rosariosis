<?php

if ( ! $_REQUEST['month'] )
{
	$_REQUEST['month'] = date( 'm' );
}

if ( ! $_REQUEST['year'] )
{
	$_REQUEST['year'] = date( 'Y' );
}

$last = 31;
while (!checkdate($_REQUEST['month'],$last,$_REQUEST['year']))
	$last--;

$time = mktime(0,0,0,$_REQUEST['month'],1,$_REQUEST['year']);
$time_last = mktime(0,0,0,$_REQUEST['month'],$last,$_REQUEST['year']);

// use the dafault calendar
$default_RET = DBGet(DBQuery("SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND DEFAULT_CALENDAR='Y'"));
if (count($default_RET))
	$calendar_id = $default_RET[1]['CALENDAR_ID'];
else
{
	$calendars_RET = DBGet(DBQuery("SELECT CALENDAR_ID FROM ATTENDANCE_CALENDARS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
	if (count($calendars_RET))
		$calendar_id = $calendars_RET[1]['CALENDAR_ID'];
	else
		ErrorMessage(array(_('There are no calendars yet setup.')),'fatal');
}

$menus_RET = DBGet(DBQuery('SELECT MENU_ID,TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID=\''.UserSchool().'\' ORDER BY SORT_ORDER'),array(),array('MENU_ID'));
if ( ! $_REQUEST['menu_id'])
	if ( ! $_SESSION['FSA_menu_id'])
		if (count($menus_RET))
			$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'] = key($menus_RET);
		else
			ErrorMessage(array(_('There are no menus yet setup.')),'fatal');
	else
		$_REQUEST['menu_id'] = $_SESSION['FSA_menu_id'];
else
		$_SESSION['FSA_menu_id'] = $_REQUEST['menu_id'];

if ( $_REQUEST['submit']['save'] && $_REQUEST['food_service'] && $_POST['food_service'] && AllowEdit())
{
	$events_RET = DBGet(DBQuery("SELECT ID,SCHOOL_DATE
	FROM CALENDAR_EVENTS 
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time )."' AND '" . date( 'Y-m-d', $time_last ) . "' 
	AND SYEAR='".UserSyear()."' 
	AND SCHOOL_ID='".UserSchool()."' 
	AND TITLE='".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."'"),array(),array('SCHOOL_DATE'));
	//echo '<pre>'; var_dump($events_RET); echo '</pre>';

	foreach ( (array) $_REQUEST['food_service'] as $school_date => $description)
	{
		if ( $events_RET[ $school_date ])
			if ( $description['text'] || $description['select'])
				DBQuery("UPDATE CALENDAR_EVENTS SET DESCRIPTION='".$description['text'].$description['select']."' WHERE ID='".$events_RET[ $school_date ][1]['ID']."'");
			else
				DBQuery("DELETE FROM CALENDAR_EVENTS WHERE ID='".$events_RET[ $school_date ][1]['ID']."'");
		else
			if ( $description['text'] || $description['select'])
				DBQuery("INSERT INTO CALENDAR_EVENTS (ID,SYEAR,SCHOOL_ID,SCHOOL_DATE,TITLE,DESCRIPTION) values(".db_seq_nextval('CALENDAR_EVENTS_SEQ').",'".UserSyear()."','".UserSchool()."','".$school_date."','".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."','".$description['text'].$description['select']."')");
	}
	unset($_REQUEST['food_service']);
	unset($_SESSION['_REQUEST_vars']['food_service']);
}

if ( $_REQUEST['submit']['print'])
{
	$events_RET = DBGet(DBQuery("SELECT TITLE,DESCRIPTION,SCHOOL_DATE 
	FROM CALENDAR_EVENTS 
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time )."' AND '" . date( 'Y-m-d', $time_last ) . "' 
	AND SYEAR='".UserSyear()."' 
	AND SCHOOL_ID='".UserSchool()."' 
	AND (TITLE='".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."' OR TITLE='No School')"),array(),array('SCHOOL_DATE'));

	$skip = date("w",$time);

	echo '<!-- MEDIA TOP 1in --><p class="center">';
	echo '<table style="background-color: #fff;" class="width-100p">'."\n";
	if ( $_REQUEST['_ROSARIO_PDF'])
		if (is_file('assets/dailymenu'.UserSchool().'.jpg'))
		{
			echo '<tr class="center"><td colspan="3"><img src="assets/dailymenu'.UserSchool().'.jpg" /></td></tr>'."\n";
		}
		else
			echo '<tr class="center"><td colspan="3"><span style="color:black" class="sizep2"><b>'.SchoolInfo('TITLE').'</b></span></td></tr>'."\n";
//FJ display locale with strftime()
	echo '<tr class="center"><td>'.$menus_RET[$_REQUEST['menu_id']][1]['TITLE'].'</td><td><span style="color:black" class="sizep2"><b>'.ProperDate( date( 'Y-m-d', mktime(0,0,0,$_REQUEST['month'],1,$_REQUEST['year']))).'</b></span></td><td>'.$menus_RET[$_REQUEST['menu_id']][1]['TITLE'].'</td></tr></table>'."\n";
	echo '<table style="border: solid 2px; background-color: #fff;" id="calendar"><thead><tr style="text-align:center; background-color:#808080; color:white;">'."\n";
	echo '<th>'.mb_substr(_('Sunday'),0,3).'<span>'.mb_substr(_('Sunday'),3).'</span>'.'</th><th>'.mb_substr(_('Monday'),0,3).'<span>'.mb_substr(_('Monday'),3).'</span>'.'</th><th>'.mb_substr(_('Tuesday'),0,3).'<span>'.mb_substr(_('Tuesday'),3).'</span>'.'</th><th>'.mb_substr(_('Wednesday'),0,3).'<span>'.mb_substr(_('Wednesday'),3).'</span>'.'</th><th>'.mb_substr(_('Thursday'),0,3).'<span>'.mb_substr(_('Thursday'),3).'</span>'.'</th><th>'.mb_substr(_('Friday'),0,3).'<span>'.mb_substr(_('Friday'),3).'</span>'.'</th><th>'.mb_substr(_('Saturday'),0,3).'<span>'.mb_substr(_('Saturday'),3).'</span>'.'</th>'."\n";
	echo '</tr></thead><tbody>';

	if ( $skip)
		echo '<tr><td style="background-color:#C0C0C0;" colspan="'.$skip.'">&nbsp;</td>'."\n";

	for ( $i = 1; $i <= $last; $i++)
	{
		if ( $skip%7==0)
			echo '<tr>';

		$day_time = mktime(0,0,0,$_REQUEST['month'],$i,$_REQUEST['year']);

		$date =  date( 'Y-m-d', $day_time );

		echo '<td class="valign-top" style="height:100%; '.(count($events_RET[ $date ]) ? 'background-color:#ffaaaa;' : '').'"><table class="calendar-day'.(count($events_RET[ $date ]) ? ' hover"><tr><td><b>'.$i.'</b>' : '"><tr><td>'.$i);

		if (count($events_RET[ $date ]))
		{
			foreach ( (array) $events_RET[ $date ] as $event)
			{
				if ( $event['TITLE']!=$menus_RET[$_REQUEST['menu_id']][1]['TITLE'])
					echo '<br /><i>'.$event['TITLE'].'</i>';
				echo '<br />'.htmlspecialchars($event['DESCRIPTION'],ENT_QUOTES);
			}
		}
		echo '</td></tr></table></td>';

		$skip++;

		if ( $skip%7==0)
			echo '</tr>';
	}
	if ( $skip%7!=0)
		echo '<td style="background-color:#C0C0C0;" colspan="'.(7-$skip%7).'">&nbsp;</td></tr>';

	echo '</tbody></table></p>';
}
else
{
	DrawHeader(ProgramTitle());

	if (AllowEdit())
	{
		$description_RET = DBGet(DBQuery("SELECT DISTINCT DESCRIPTION FROM CALENDAR_EVENTS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND TITLE='".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."' AND DESCRIPTION IS NOT NULL ORDER BY DESCRIPTION"));
		if (count($description_RET))
		{
			$description_select = '<option value="">'._('or select previous meal').'</option>';
			foreach ( (array) $description_RET as $description)
				$description_select .= '<option value="'.$description['DESCRIPTION'].'">'.$description['DESCRIPTION'].'</option>';
			$description_select .= '</select>';
		}
	}

	$calendar_RET = DBGet(DBQuery("SELECT SCHOOL_DATE 
	FROM ATTENDANCE_CALENDAR 
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time )."' AND '" . date( 'Y-m-d', $time_last ) . "' 
	AND SYEAR='".UserSyear()."' 
	AND SCHOOL_ID='".UserSchool()."' 
	AND CALENDAR_ID='".$calendar_id."' 
	AND MINUTES>0 
	ORDER BY SCHOOL_DATE"),array(),array('SCHOOL_DATE'));

	$events_RET = DBGet(DBQuery("SELECT ID,TITLE,DESCRIPTION,SCHOOL_DATE 
	FROM CALENDAR_EVENTS 
	WHERE SCHOOL_DATE BETWEEN '" . date( 'Y-m-d', $time )."' AND '" . date( 'Y-m-d', $time_last ) . "' 
	AND SYEAR='".UserSyear()."' 
	AND SCHOOL_ID='".UserSchool()."' 
	AND TITLE='".$menus_RET[$_REQUEST['menu_id']][1]['TITLE']."' 
	ORDER BY SCHOOL_DATE"),array('DESCRIPTION' => 'makeDescriptionInput','SCHOOL_DATE' => 'ProperDate'));

	$events_RET[0] = array(); // make sure indexing from 1
	foreach ( (array) $calendar_RET as $school_date => $value)
		$events_RET[] = array('ID' => 'new','SCHOOL_DATE'=>ProperDate($school_date),'DESCRIPTION'=>TextInput('','food_service['.$school_date.'][text]','','size=20').($description_select ? '<select name="food_service['.$school_date.'][select]">'.$description_select : ''));
	unset($events_RET[0]);
	$LO_columns = array('ID' => _('ID'),'SCHOOL_DATE' => _('Date'),'DESCRIPTION' => _('Description'));

	echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&menu_id='.$_REQUEST['menu_id'].'&month='.$_REQUEST['month'].'&year='.$_REQUEST['year'].'" method="POST">';
	DrawHeader(PrepareDate(mb_strtoupper(date("d-M-y",$time)),'',false,array('M'=>1,'Y'=>1,'submit'=>true)),SubmitButton(_('Save'),'submit[save]').SubmitButton(_('Generate Menu'),'submit[print]'));
	echo '<br />';

	$tabs = array();
	foreach ( (array) $menus_RET as $id => $meal)
		$tabs[] = array('title' => $meal[1]['TITLE'],'link' => 'Modules.php?modname='.$_REQUEST['modname'].'&menu_id=$id&month='.$_REQUEST['month'].'&year='.$_REQUEST['year']);

	$extra = array('save'=>false,'search'=>false,
		'header'=>WrapTabs($tabs,'Modules.php?modname='.$_REQUEST['modname'].'&menu_id='.$_REQUEST['menu_id'].'&month='.$_REQUEST['month'].'&year='.$_REQUEST['year']));
	$singular = sprintf(_('%s Day'), $menus_RET[$_REQUEST['menu_id']][1]['TITLE']);
	$plural = sprintf(_('%s Days'), $menus_RET[$_REQUEST['menu_id']][1]['TITLE']);

//FJ add translation
	ListOutput($events_RET,$LO_columns,$singular,$plural,array(),array(),$extra);

	echo '<br /><div class="center">' . SubmitButton(_('Save'),'submit[save]') . '</div>';
	echo '</form>';
}

function makeDescriptionInput($value,$name)
{	global $THIS_RET,$calendar_RET;

	if ( $calendar_RET[$THIS_RET['SCHOOL_DATE']])
		unset($calendar_RET[$THIS_RET{'SCHOOL_DATE'}]);

	return TextInput($value,'food_service['.$THIS_RET['SCHOOL_DATE'].'][text]','','size=20');
}
