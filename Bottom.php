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
else
{
//modif Francois: fix bug Internet Explorer Quirks Mode, add DOCTYPE
?>
<!DOCTYPE html>
<HTML lang="<?php echo mb_substr($locale,0,2); ?>" <?php echo (mb_substr($locale,0,2)=='he' || mb_substr($locale,0,2)=='ar'?' dir="RTL"':''); ?>>
<HEAD><TITLE><?php echo Config('TITLE'); ?></TITLE>
<meta charset="UTF-8" />
<script type="text/javascript">
size = 30;
function expandFrame(){
	if(size==30){
		parent.document.getElementById('mainframeset').rows="*,170";
		size = 170;
	}else{
		parent.document.getElementById('mainframeset').rows="*,30";
		size = 30;
	}
}
</SCRIPT>
<link rel="stylesheet" type="text/css" href="assets/themes/<?php echo Preferences('THEME'); ?>/stylesheet.css">
</HEAD>
<BODY id="BottomBody" class="bgcolor">
<TABLE style="margin:0 auto;"><TR>
<?php
//modif Francois: icones
	if($_SESSION['List_PHP_SELF'] && (User('PROFILE')=='admin' || User('PROFILE')=='teacher')) {
        switch ($_SESSION['Back_PHP_SELF']) {
            case 'student': $back_text = _('Back to Student List'); break;
            case 'staff': $back_text = _('Back to User List'); break;
            case 'course': $back_text = _('Back to Course List'); break;
            default: $back_text = sprintf(_('Back to %s List'),$_SESSION['Back_PHP_SELF']);
        }
		echo '<TD style="width:24px;"><A HREF="'.$_SESSION['List_PHP_SELF'].'&bottom_back=true" target="body"><IMG SRC="assets/back.png" height="24"></A></TD><TD class="BottomButton"><A HREF="'.$_SESSION['List_PHP_SELF'].'&bottom_back=true" target="body">'.$back_text.'</A></TD>';
    }
	if($_SESSION['Search_PHP_SELF'] && (User('PROFILE')=='admin' || User('PROFILE')=='teacher')) {
        switch ($_SESSION['Back_PHP_SELF']) {
            case 'student': $back_text = _('Back to Student Search'); break;
            case 'staff': $back_text = _('Back to User Search'); break;
            case 'course': $back_text = _('Back to Course Search'); break;
            default: $back_text = sprintf(_('Back to %s Search'),$_SESSION['Back_PHP_SELF']);
        }
		echo '<TD style="width:24px;"><A HREF="'.$_SESSION['Search_PHP_SELF'].'&bottom_back=true" target="body"><IMG SRC="assets/back.png" height="24" /></A></TD><TD class="BottomButton"><A HREF="'.$_SESSION['Search_PHP_SELF'].'&bottom_back=true" target="body">'.$back_text.'</A></TD>';
	}
    echo '<TD><A HREF="Bottom.php?modfunc=print" target="body"><IMG SRC="assets/print.png" height="24" /></A></TD><TD class="BottomButton"><A HREF="Bottom.php?modfunc=print" target="body">'._('Print').'</A></TD>';
    echo '<TD><A HREF="#" onclick="expandFrame();return false;"><IMG SRC="assets/help.png" height="24" /></A></TD><TD class="BottomButton"><A HREF="#" onclick="expandFrame();return false;">'._('Help').'</A></TD>';
    echo '<TD><A HREF="index.php?modfunc=logout" target="_top"><IMG SRC="assets/logout.png" height="24" /></A></TD><TD class="BottomButton"><A HREF="index.php?modfunc=logout" target="_top">'._('Logout').'</A></TD></TR></TABLE>';

	if (file_exists('Help_'.mb_substr($locale, 0, 2).'.php')) //modif Francois: translated help
		include 'Help_'.mb_substr($locale, 0, 2).'.php';
	else
		include 'Help.php';
//	include 'Menu.php';

	$profile = User('PROFILE');

	echo '<DIV id="BottomHelp">';
/*	if($_REQUEST['modcat'])
	{
		echo '<b>'.str_replace('_',' ',$_REQUEST['modcat']);
		echo ' : '.$_ROSARIO['Menu'][$_REQUEST['modcat']][$_REQUEST['modname']];
		echo '</b>';
	}
	else*/
//modif Francois: add help in a popup
	//echo '<b>'._('Welcome to Rosario Help').'</b>';

	if($help[$_REQUEST['modname']])
	{
		if($student==true)
			$help[$_REQUEST['modname']] = str_replace('your child','yourself',str_replace('your child\'s','your',$help[$_REQUEST['modname']]));

		echo $help[$_REQUEST['modname']];
	}
	else
		echo $help['default'];
	echo '</DIV>';
	echo '</BODY>';
	echo '</HTML>';
}
?>