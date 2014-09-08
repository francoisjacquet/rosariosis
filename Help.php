<?php

error_reporting(0);
include 'Warehouse.php';

if (file_exists('Help_'.mb_substr($locale, 0, 2).'.php')) //modif Francois: translated help
	include 'Help_'.mb_substr($locale, 0, 2).'.php';
else
	include 'Help_en.php';

switch(User('PROFILE'))
{
	case 'admin':
		$title = _('Administrator');
	break;

	case 'teacher':
		$title = _('Teacher');
	break;

	case 'parent':
		$title = _('Parent');
	break;
	
	case 'student':
		$title = _('Student');
	break;
}

$handle = PDFStart();
echo '<TABLE><TR><TD><IMG SRC="assets/themes/'.Preferences('THEME').'/logo.png" /></TD><TD><h1>'.$_REQUEST['modname'] = sprintf(_('%s Handbook'),$title).'</h1></TD></TR></TABLE><HR>';

foreach($help as $program=>$value)
{
	if(mb_strpos($program,'/'))
	{
		$modcat = str_replace('_',' ',mb_substr($program,0,mb_strpos($program,'/')));
		
		if (!$RosarioModules[str_replace(' ','_',$modcat)]) //module not activated
			break;
	
		if($modcat!=$old_modcat)
			echo '<div style="page-break-after: always;"></div><TABLE><TR><TD><h2><IMG SRC="assets/icons/'.str_replace(' ','_',$modcat).'.png" class="headerIcon" /> '._($modcat).'</h2></TD></TR></TABLE><HR>';
		$old_modcat = $modcat;
	}
	$_REQUEST['modname'] = $program;
	echo '<div style="page-break-inside: avoid;"><h3>';
	if($program=='default')
		echo ParseMLField(Config('TITLE')).' - '.sprintf(_('%s Handbook'),$title).'<BR />'.sprintf(_('version %s'),'1.1');
	else
		echo (ProgramTitle() == 'RosarioSIS' ? $program : ProgramTitle());
	echo '</h3>';
	echo '<TABLE class="width-100p cellpadding-5"><TR><TD class="header2">';
	if($student==true)
		$value = str_replace('your child','yourself',str_replace('your child\'s','your',$value));
	$value = str_replace('RosarioSIS', Config('NAME'),$value);
	echo $value;
	echo '</TD></TR></TABLE></div><BR />';
}
echo '<div style="text-align:center;font-weight:bold;"><a href="http://www.rosariosis.org/">http://www.rosariosis.org/</a></div>';
$_REQUEST['modname'] = '';
PDFStop($handle);
?>