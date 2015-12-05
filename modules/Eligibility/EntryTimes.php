<?php
// GET ALL THE CONFIG ITEMS FOR ELIGIBILITY
$eligibility_config = ProgramConfig( 'eligibility' );

foreach ( (array)$eligibility_config as $value )
{
	${$value[1]['TITLE']} = $value[1]['VALUE'];
}

if ( $_REQUEST['values'])
{
	if ( $_REQUEST['values']['START_M']=='PM')
		$_REQUEST['values']['START_HOUR']+=12;
	if ( $_REQUEST['values']['END_M']=='PM')
		$_REQUEST['values']['END_HOUR']+=12;

	$start = $_REQUEST['values']['START_DAY'].$_REQUEST['values']['START_HOUR'].$_REQUEST['values']['START_MINUTE'];
	$end = $_REQUEST['values']['END_DAY'].$_REQUEST['values']['END_HOUR'].$_REQUEST['values']['END_MINUTE'];

	if ( $start<=$end)
	{
		foreach ( (array)$_REQUEST['values'] as $key => $value)
		{
			if ( isset( ${$key} ) )
				DBQuery("UPDATE PROGRAM_CONFIG SET VALUE='".$value."' WHERE PROGRAM='eligibility' AND TITLE='".$key."' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'");
			else
				DBQuery("INSERT INTO PROGRAM_CONFIG (SYEAR,SCHOOL_ID,PROGRAM,TITLE,VALUE) values('".UserSyear()."','".UserSchool()."','eligibility','".$key."','".$value."')");
		}
	}

	unset( $_ROSARIO['ProgramConfig'] ); // update ProgramConfig var

	// UPDATE ALL THE CONFIG ITEMS FOR ELIGIBILITY
	$eligibility_config = ProgramConfig( 'eligibility' );

	foreach ( (array)$eligibility_config as $value )
	{
		${$value[1]['TITLE']} = $value[1]['VALUE'];
	}
}

DrawHeader(ProgramTitle());
echo '<br />';

$days = array(_('Sunday'),_('Monday'),_('Tuesday'),_('Wednesday'),_('Thursday'),_('Friday'),_('Saturday'));

for ( $i=0;$i<7;$i++)
	$day_options[$i] = $days[$i];

for ( $i=1;$i<=11;$i++)
	$hour_options[$i] = $i;
$hour_options['0'] = '12';

for ( $i=0;$i<=9;$i++)
	$minute_options[$i] = '0'.$i;
for ( $i=10;$i<=59;$i++)
	$minute_options[$i] = $i;

$m_options = array('AM' => 'AM','PM' => 'PM');

if ( $START_HOUR>12)
{
	$START_HOUR-=12;
	$START_M = 'PM';
}
else
	$START_M = 'AM';

if ( $END_HOUR>12)
{
	$END_HOUR-=12;
	$END_M = 'PM';
}
else
	$END_M = 'AM';


PopTable('header',_('Allow Eligibility Posting'));

echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'" method="POST">';
echo '<table class="cellpadding-5">';
echo '<tr><td><b>'._('From').'</b></td><td>'.SelectInput($START_DAY,'values[START_DAY]','',$day_options,false,'',false).'</td><td>'.SelectInput($START_HOUR,'values[START_HOUR]','',$hour_options,false,'',false).'</td><td><b>:</b></td><td>'.SelectInput($START_MINUTE,'values[START_MINUTE]','',$minute_options,false,'',false).'</td><td>'.SelectInput($START_M,'values[START_M]','',$m_options,false,'',false).'</td></tr>';
echo '<tr><td><b>'._('To').'</b></td><td>'.SelectInput($END_DAY,'values[END_DAY]','',$day_options,false,'',false).'</td><td>'.SelectInput($END_HOUR,'values[END_HOUR]','',$hour_options,false,'',false).'</td><td><b>:</b></td><td>'.SelectInput($END_MINUTE,'values[END_MINUTE]','',$minute_options,false,'',false).'</td><td>'.SelectInput($END_M,'values[END_M]','',$m_options,false,'',false).'</td></tr>';
echo '<tr><td colspan="6" class="center">'.SubmitButton(_('Save')).'</td></tr>';
echo '</table>';
echo '</form>';

PopTable('footer');
