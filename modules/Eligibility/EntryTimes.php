<?php
// GET ALL THE CONFIG ITEMS FOR ELIGIBILITY
$start_end_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_CONFIG WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND PROGRAM='eligibility'"));
if(count($start_end_RET))
{
	foreach($start_end_RET as $value)
		$$value['TITLE'] = $value['VALUE'];
}

if($_REQUEST['values'])
{
	if($_REQUEST['values']['START_M']=='PM')
		$_REQUEST['values']['START_HOUR']+=12;
	if($_REQUEST['values']['END_M']=='PM')
		$_REQUEST['values']['END_HOUR']+=12;

	$start = $_REQUEST['values']['START_DAY'].$_REQUEST['values']['START_HOUR'].$_REQUEST['values']['START_MINUTE'];
	$end = $_REQUEST['values']['END_DAY'].$_REQUEST['values']['END_HOUR'].$_REQUEST['values']['END_MINUTE'];

	if($start<=$end)
	{
		foreach($_REQUEST['values'] as $key=>$value)
		{
			if(isset($$key))
				DBQuery("UPDATE PROGRAM_CONFIG SET VALUE='$value' WHERE PROGRAM='eligibility' AND TITLE='$key' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'");
			else
				DBQuery("INSERT INTO PROGRAM_CONFIG (SYEAR,SCHOOL_ID,PROGRAM,TITLE,VALUE) values('".UserSyear()."','".UserSchool()."','eligibility','$key','$value')");
		}
	}

	$start_end_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PROGRAM_CONFIG WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND PROGRAM='eligibility'"));
	if(count($start_end_RET))
	{
		foreach($start_end_RET as $value)
			$$value['TITLE'] = $value['VALUE'];
	}
}

DrawHeader(ProgramTitle());
echo '<BR />';

$days = array(_('Sunday'),_('Monday'),_('Tuesday'),_('Wednesday'),_('Thursday'),_('Friday'),_('Saturday'));

for($i=0;$i<7;$i++)
	$day_options[$i] = $days[$i];

for($i=1;$i<=11;$i++)
	$hour_options[$i] = $i;
$hour_options['0'] = '12';

for($i=0;$i<=9;$i++)
	$minute_options[$i] = '0'.$i;
for($i=10;$i<=59;$i++)
	$minute_options[$i] = $i;

$m_options = array('AM'=>'AM','PM'=>'PM');

if($START_HOUR>12)
{
	$START_HOUR-=12;
	$START_M = 'PM';
}
else
	$START_M = 'AM';

if($END_HOUR>12)
{
	$END_HOUR-=12;
	$END_M = 'PM';
}
else
	$END_M = 'AM';


PopTable('header',_('Allow Eligibility Posting'));

echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
echo '<TABLE>';
//modif Francois: add translation 
echo '<TR><TD><B>'._('From').'</B></TD><TD>'.SelectInput($START_DAY,'values[START_DAY]','',$day_options,false,'',false).'</TD><TD>'.SelectInput($START_HOUR,'values[START_HOUR]','',$hour_options,false,'',false).'</TD><TD><B>:</B></TD><TD>'.SelectInput($START_MINUTE,'values[START_MINUTE]','',$minute_options,false,'',false).'</TD><TD>'.SelectInput($START_M,'values[START_M]','',$m_options,false,'',false).'</TD></TR>';
echo '<TR><TD><B>'._('To').'</B></TD><TD>'.SelectInput($END_DAY,'values[END_DAY]','',$day_options,false,'',false).'</TD><TD>'.SelectInput($END_HOUR,'values[END_HOUR]','',$hour_options,false,'',false).'</TD><TD><B>:</B></TD><TD>'.SelectInput($END_MINUTE,'values[END_MINUTE]','',$minute_options,false,'',false).'</TD><TD>'.SelectInput($END_M,'values[END_M]','',$m_options,false,'',false).'</TD></TR>';
echo '<TR><TD colspan="4" class="center">'.SubmitButton(_('Save')).'</TD></TR>';
echo '</TABLE>';
echo '</FORM>';

PopTable('footer');

?>