<?php
error_reporting(1);
include "./Warehouse.php";
if($_REQUEST['modfunc']=='print')
{
//modif Francois: call PDFStart to generate Print PDF
	if($_REQUEST['expanded_view'])
		$_SESSION['orientation'] = 'landscape';
		
	$print_data = PDFStart();
	
	$_REQUEST = $_SESSION['_REQUEST_vars'];
	$_REQUEST['_ROSARIO_PDF'] = true;
	//modif Francois: replaced ? with & in modname
	/*if(mb_strpos($_REQUEST['modname'],'?')!==false)
		$modname = mb_substr($_REQUEST['modname'],0,mb_strpos($_REQUEST['modname'],'?'));
	else*/
		$modname = $_REQUEST['modname'];
	if(!$wkhtmltopdfPath)
		$_ROSARIO['allow_edit'] = false;
		
	//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
	if (mb_substr($modname, -4, 4)!='.php' || mb_strpos($modname, '..')!==false || !is_file('modules/'.$modname))	
		HackingLog();
	else
		include('modules/'.$modname);
		
//modif Francois: call PDFStop to generate Print PDF
	PDFStop($print_data);
}
elseif($_REQUEST['modfunc']=='help')
{
	if (file_exists('Help_'.mb_substr($locale, 0, 2).'.php')) //modif Francois: translated help
		include 'Help_'.mb_substr($locale, 0, 2).'.php';
	else
		include 'Help.php';

	$profile = User('PROFILE');


	if($help[$_REQUEST['modname']])
	{
		if($student==true)
			$help[$_REQUEST['modname']] = str_replace('your child','yourself',str_replace('your child\'s','your',$help[$_REQUEST['modname']]));

		echo $help[$_REQUEST['modname']];
	}
	else
		echo $help['default'];
}
else
{
?>
<div id="footerwrap">
<a id="BottomButtonMenu" HREF="#" onclick="expandMenu(); return false;" title="<?php echo _('Menu'); ?>" class="BottomButton">&nbsp;<span><?php echo _('Menu'); ?></span></A>
<?php
//modif Francois: icones
	if($_SESSION['List_PHP_SELF'] && (User('PROFILE')=='admin' || User('PROFILE')=='teacher')) {
        switch ($_SESSION['Back_PHP_SELF']) {
            case 'student': $back_text = _('Student List'); break;
            case 'staff': $back_text = _('User List'); break;
            case 'course': $back_text = _('Course List'); break;
            default: $back_text = sprintf(_('%s List'),$_SESSION['Back_PHP_SELF']);
        }
		echo '<A HREF="'.$_SESSION['List_PHP_SELF'].'&bottom_back=true" target="body" title="'.$back_text.'" class="BottomButton"><IMG SRC="assets/back.png" height="32" />&nbsp;<span>'.$back_text.'</span></A>&nbsp;&nbsp;';
    }
	if($_SESSION['Search_PHP_SELF'] && (User('PROFILE')=='admin' || User('PROFILE')=='teacher')) {
        switch ($_SESSION['Back_PHP_SELF']) {
            case 'student': $back_text = _('Student Search'); break;
            case 'staff': $back_text = _('User Search'); break;
            case 'course': $back_text = _('Course Search'); break;
            default: $back_text = sprintf(_('%s Search'),$_SESSION['Back_PHP_SELF']);
        }
		echo '<A HREF="'.$_SESSION['Search_PHP_SELF'].'&bottom_back=true" target="body" title="'.$back_text.'" class="BottomButton"><IMG SRC="assets/back.png" height="32" />&nbsp;<span>'.$back_text.'</span></A>&nbsp;&nbsp;';
	}
    echo '<A HREF="Bottom.php?modfunc=print" target="_blank" title="'._('Print').'" class="BottomButton"><IMG SRC="assets/print.png" height="32" />&nbsp;<span>'._('Print').'</span></A>&nbsp;&nbsp;';
    echo '<A HREF="#" onclick="expandHelp();return false;" title="'._('Help').'" class="BottomButton"><IMG SRC="assets/help.png" height="32" />&nbsp;<span>'._('Help').'</span></A>&nbsp;&nbsp;';
    echo '<A HREF="index.php?modfunc=logout" target="_top" title="'._('Logout').'" class="BottomButton"><IMG SRC="assets/logout.png" height="32" />&nbsp;<span>'._('Logout').'</span></A>&nbsp;&nbsp;</div>';
	echo '<DIV id="footerhelp"></DIV>';
}
?>