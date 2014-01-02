<?php
error_reporting(1);

require_once('Warehouse.php');

if($_REQUEST['modfunc']=='logout')
{
	if($_SESSION)
	{
//modif Francois: set logout page to old session locale
		$old_session_locale = $_SESSION['locale'];
		session_destroy();
//modif Francois: fix error Firefox has detected that the server is redirecting the request
//		header("Location: $_SERVER[PHP_SELF]?modfunc=logout".(($_REQUEST['reason'])?'&reason='.$_REQUEST['reason']:''));
//		header("Location: ".$_SERVER['PHP_SELF'].(($_REQUEST['reason'])?'&reason='.$_REQUEST['reason']:''));
		header("Location: ".$_SERVER['PHP_SELF'].'?locale='.$old_session_locale.(($_REQUEST['reason'])?'&reason='.$_REQUEST['reason']:''));
	}
}
elseif($_REQUEST['modfunc']=='create_account')
{
	if(!$ShowCreateAccount)
		unset($_REQUEST['modfunc']);
}

if($_REQUEST['USERNAME'] && $_REQUEST['PASSWORD'])
{
	$_REQUEST['USERNAME'] = DBEscapeString($_REQUEST['USERNAME']);
	//$_REQUEST['PASSWORD'] = DBEscapeString($_REQUEST['PASSWORD']);
//modif Francois: add password encryption
//	$login_RET = DBGet(DBQuery("SELECT USERNAME,PROFILE,STAFF_ID,LAST_LOGIN,FAILED_LOGIN FROM STAFF WHERE SYEAR='$DefaultSyear' AND UPPER(USERNAME)=UPPER('$_REQUEST[USERNAME]') AND UPPER(PASSWORD)=UPPER('$_REQUEST[PASSWORD]')"));
//modif Francois: add WHERE PROFILE<>'admin' to restrict admin login to $RosarioAdmins list
	$login_RET = DBGet(DBQuery("SELECT USERNAME,PROFILE,STAFF_ID,LAST_LOGIN,FAILED_LOGIN,PASSWORD FROM STAFF WHERE PROFILE<>'admin' AND SYEAR='".Config('SYEAR')."' AND UPPER(USERNAME)=UPPER('$_REQUEST[USERNAME]')"));
	if ($login_RET && match_password($login_RET[1]['PASSWORD'], $_REQUEST['PASSWORD']))
		$_REQUEST['PASSWORD'] = '';
	else
		$login_RET = false;
	if(!$login_RET)
	{
//		$student_RET = DBGet(DBQuery("SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,s.FAILED_LOGIN FROM STUDENTS s,STUDENT_ENROLLMENT se WHERE UPPER(s.USERNAME)=UPPER('$_REQUEST[USERNAME]') AND UPPER(s.PASSWORD)=UPPER('$_REQUEST[PASSWORD]') AND se.STUDENT_ID=s.STUDENT_ID AND se.SYEAR='$DefaultSyear' AND CURRENT_DATE>=se.START_DATE AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)"));
		$student_RET = DBGet(DBQuery("SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,s.FAILED_LOGIN,s.PASSWORD FROM STUDENTS s,STUDENT_ENROLLMENT se WHERE UPPER(s.USERNAME)=UPPER('$_REQUEST[USERNAME]') AND se.STUDENT_ID=s.STUDENT_ID AND se.SYEAR='".Config('SYEAR')."' AND CURRENT_DATE>=se.START_DATE AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)"));
		if ($student_RET && match_password($student_RET[1]['PASSWORD'], $_REQUEST['PASSWORD']))
			$_REQUEST['PASSWORD'] = '';
		else
			$student_RET = false;
	}
	if(!$login_RET && !$student_RET && $RosarioAdmins)
	{
//		$admin_RET = DBGet(DBQuery("SELECT STAFF_ID FROM STAFF WHERE PROFILE='admin' AND SYEAR='$DefaultSyear' AND STAFF_ID IN ($RosarioAdmins) AND UPPER(PASSWORD)=UPPER('$_REQUEST[PASSWORD]')"));
		$admin_RET = DBGet(DBQuery("SELECT STAFF_ID,PASSWORD FROM STAFF WHERE PROFILE='admin' AND SYEAR='".Config('SYEAR')."' AND STAFF_ID IN ($RosarioAdmins) AND UPPER(USERNAME)=UPPER('$_REQUEST[USERNAME]')"));
		if ($admin_RET && match_password($admin_RET[1]['PASSWORD'], $_REQUEST['PASSWORD'])) 
		{
			$_REQUEST['PASSWORD'] = '';
			$login_RET = DBGet(DBQuery("SELECT USERNAME,PROFILE,STAFF_ID,LAST_LOGIN,FAILED_LOGIN FROM STAFF WHERE SYEAR='".Config('SYEAR')."' AND STAFF_ID='".$admin_RET[1]['STAFF_ID']."'"));
		}
	}
	if($login_RET && ($login_RET[1]['PROFILE']=='admin' || $login_RET[1]['PROFILE']=='teacher' || $login_RET[1]['PROFILE']=='parent'))
	{
		$_SESSION['STAFF_ID'] = $login_RET[1]['STAFF_ID'];
		$_SESSION['LAST_LOGIN'] = $login_RET[1]['LAST_LOGIN'];
		$failed_login = $login_RET[1]['FAILED_LOGIN'];
		DBQuery("UPDATE STAFF SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL WHERE STAFF_ID='".$login_RET[1]['STAFF_ID']."'");

		if(Config('LOGIN')=='No')
		{
			Warehouse('header');
			echo '<FORM action="index.php" method="POST"><INPUT type="hidden" name="USERNAME" value="'.$_REQUEST['USERNAME'].'"><INPUT type="hidden" name="PASSWORD" value="'.$_REQUEST['PASSWORD'].'"><BR />';
			PopTable('header',_('Confirm Successful Installation'));
			echo '<span class="center">';
			echo '<h4>'.sprintf(_('You have successfully installed %s.'), ParseMLField(Config('TITLE'))).'</h4><BR />';
			echo '<BR /><INPUT type="submit" name="submit" value="'._('OK').'" />';
			echo '</span>';
			PopTable('footer');
			echo '</FORM>';
			Warehouse('footer_plain');
			DBQuery("UPDATE CONFIG SET CONFIG_VALUE='Yes' WHERE TITLE='LOGIN'");
			exit;
		}
	}
	elseif($login_RET && $login_RET[1]['PROFILE']=='none')
		$error[] = _('Your account has not yet been activated.').' '._('You will be notified when it has been verified by a school administrator.');
	elseif($student_RET)
	{
		$_SESSION['STUDENT_ID'] = $student_RET[1]['STUDENT_ID'];
		$_SESSION['LAST_LOGIN'] = $student_RET[1]['LAST_LOGIN'];
		$failed_login = $student_RET[1]['FAILED_LOGIN'];
		DBQuery("UPDATE STUDENTS SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL WHERE STUDENT_ID='".$student_RET[1]['STUDENT_ID']."'");
	}
	else
	{
		DBQuery("UPDATE STAFF SET FAILED_LOGIN=".db_case(array('FAILED_LOGIN',"''",'1','FAILED_LOGIN+1'))." WHERE UPPER(USERNAME)=UPPER('$_REQUEST[USERNAME]') AND SYEAR='".Config('SYEAR')."'");
		DBQuery("UPDATE STUDENTS SET FAILED_LOGIN=".db_case(array('FAILED_LOGIN',"''",'1','FAILED_LOGIN+1'))." WHERE UPPER(USERNAME)=UPPER('$_REQUEST[USERNAME]')");
		$error[] = _('Incorrect username or password.').'&nbsp;'._('Please try logging in again.');
	}
}

if($_REQUEST['modfunc']=='create_account')
{
	Warehouse('header');
	$_ROSARIO['allow_edit'] = true;
	if(!$_REQUEST['staff']['USERNAME'])
	{
		$_REQUEST['staff_id'] = 'new';
		include('modules/Users/User.php');
		Warehouse('footer_plain');
	}
	else
	{
		$_REQUEST['modfunc'] = 'update';
		include('modules/Users/User.php');
		$note[] = _('Your account has been created.').' '._('You will be notified when it has been verified by a school administrator.').' '._('You will then be able to log in.');
		session_destroy();
	}
}

if(!$_SESSION['STAFF_ID'] && !$_SESSION['STUDENT_ID'] && $_REQUEST['modfunc']!='create_account')
{
?>
<!doctype html>
<HTML lang="<?php echo mb_substr($locale,0,2); ?>" <?php echo (mb_substr($locale,0,2)=='he' || mb_substr($locale,0,2)=='ar'?' dir="RTL"':''); ?>>
<HEAD>
	<TITLE><?php echo ParseMLField(Config('TITLE')); ?></TITLE>
	<meta charset="UTF-8" />
	<meta name="robots" content="noindex,nofollow" />
	<meta name="viewport" content="width=device-width" />
	<noscript><META http-equiv="REFRESH" content="0;url=index.php?modfunc=logout&reason=javascript" /></noscript>
	<link REL="SHORTCUT ICON" HREF="favicon.ico" />
	<link rel="stylesheet" type="text/css" href="assets/themes/<?php echo Preferences('THEME'); ?>/stylesheet.css" />
</HEAD>
<BODY>
<BR /><BR />
<?php
	PopTable("header",_('RosarioSIS Login'), 'style="max-width:550px;"');
	
	if($_REQUEST['reason'])
		echo ErrorMessage(array(_('You must have javascript enabled to use RosarioSIS.')),'note');
	echo ErrorMessage($error);
	
	echo '<TABLE>
	<tr class="st">
	<TD style="text-align:center"><img src="assets/themes/'.Preferences('THEME').'/logo.png" /></td>
	<TD class="center"><form name="loginform" method="post" action="index.php" class="login">
	<h4>'.ParseMLField(Config('TITLE')).' </h4>
    <TABLE class="cellpadding-2 cellspacing-0" style="margin:0 auto;">';

    // ng - choose language
    if (sizeof($RosarioLocales) > 1) {
          echo '<tr style="text-align:right"><TD style="text-align:right"><b>'._('Language').'</b></td>';
          echo '<td style="text-align:left;">';
          foreach ($RosarioLocales as $loc)
              echo '<A href="'.$_SERVER['PHP_SELF'].'?locale='.$loc.'"><IMG src="assets/flags/'.$loc.'.png" height="32" /></A>&nbsp;&nbsp;';
          echo '</TD>';
    }
    
	echo '<tr>
		<TD style="text-align:right"><label for="USERNAME"><b>'._('Username').'</b></label></td>
		<td style="text-align:left;"><input type="text" name="USERNAME" id="USERNAME" size="25" maxlength="42" required /></td>
	</tr>
	<tr>
		<TD style="text-align:right"><label for="PASSWORD"><b>'._('Password').'</b></label></td>
		<td style="text-align:left;"><input type="password" name="PASSWORD" id="PASSWORD" size="25" maxlength="42" required /></td>
	</tr>
	</table>
	<p><INPUT type="submit" value="'._('Login').'" class="button-primary" /></p>';
	if($ShowCreateAccount)
		echo '<span class="size-1; text-align:center;">[ <A HREF="index.php?modfunc=create_account">'._('Create Account').'</A> ]</span>';
	echo '</form>
	</td></tr>';

	// System disclaimer.
	echo '
	<tr><td colspan="2">
	<span class="size-3">'.
	sprintf(_('This is a restricted network. Use of this network, its equipment, and resources is monitored at all times and requires explicit permission from the network administrator and %s. If you do not have this permission in writing, you are violating the regulations of this network and can and will be prosecuted to the full extent of the law. By continuing into this system, you are acknowledging that you are aware of and agree to these terms.'),ParseMLField(Config('TITLE')))
	.'</span>
	<BR /><BR />
	</td></tr>
	</table>';
	echo '<span class="center">RosarioSIS '.sprintf(_('version %s'),$RosarioVersion).'
	<BR />&copy; 2004-2009 <A HREF="http://www.miller-group.net">The Miller Group, Inc</A>
	<br />&copy; 2009 <a href="http://www.glenn-abbey.com">Glenn Abbey Software, Inc</a>
	<br />&copy; 2009 <a href="http://www.centresis.org">Learners Circle, LLC</a>
	<br />&copy; 2012-2014 <a href="http://www.rosariosis.org">François Jacquet</a>
	</span>';
	PopTable("footer");
	echo '<BR /></BODY></HTML>';
}
elseif($_REQUEST['modfunc']!='create_account')
{
	Warehouse('header');
?>
<script type="text/javascript">var scrollTop="<?php echo Preferences('SCROLL_TOP'); ?>";</script>
<div id="wrap">
	<footer id="footer" class="mod">
		<?php include('Bottom.php'); ?>
	</footer>	
	<div id="menuback" class="mod"></div>
	<aside id="menu" class="mod">
		<?php include('Side.php'); ?>
	</aside>
	
	<div id="body" tabindex="0" role="main" class="mod">	
	<?php 
		$_REQUEST['modname']='misc/Portal.php';
		$_REQUEST['failed_login']=$failed_login;
		include('Modules.php'); 
	?>
	</div>
	<div style="clear:both;"></div>
</div><!-- wrap -->
</BODY>
</HTML>
<?php
}
?>