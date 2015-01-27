<?php
error_reporting(1);
include('Warehouse.php');

if(isset($_REQUEST['modfunc']))
if($_REQUEST['modfunc']=='logout')
{
	if($_SESSION)
	{
		//modif Francois: set logout page to old session locale
		$old_session_locale = $_SESSION['locale'];
		session_regenerate_id(true);
		session_unset();
		session_destroy();

		header("Location: ".$_SERVER['PHP_SELF'].'?locale='.$old_session_locale.(isset($_REQUEST['reason'])?'&reason='.$_REQUEST['reason']:''));
	}
}

if(isset($_POST['USERNAME']) && $_POST['USERNAME']!='' && isset($_POST['PASSWORD']) && $_POST['PASSWORD']!='')
{
	$_REQUEST['USERNAME'] = DBEscapeString($_REQUEST['USERNAME']);
	$login_RET = DBGet(DBQuery("SELECT USERNAME,PROFILE,STAFF_ID,LAST_LOGIN,FAILED_LOGIN,PASSWORD 
	FROM STAFF 
	WHERE SYEAR='".Config('SYEAR')."' 
	AND UPPER(USERNAME)=UPPER('".$_REQUEST['USERNAME']."')"));
	
	if ($login_RET && match_password($login_RET[1]['PASSWORD'], $_REQUEST['PASSWORD']))
		unset($_REQUEST['PASSWORD'],$_REQUEST['USERNAME']);
	else
		$login_RET = false;
	
	if(!$login_RET)
	{
		$student_RET = DBGet(DBQuery("SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,s.FAILED_LOGIN,s.PASSWORD 
		FROM STUDENTS s,STUDENT_ENROLLMENT se 
		WHERE UPPER(s.USERNAME)=UPPER('".$_REQUEST['USERNAME']."') 
		AND se.STUDENT_ID=s.STUDENT_ID 
		AND se.SYEAR='".Config('SYEAR')."' 
		AND CURRENT_DATE>=se.START_DATE 
		AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)"));
		
		if ($student_RET && match_password($student_RET[1]['PASSWORD'], $_REQUEST['PASSWORD']))
			unset($_REQUEST['PASSWORD'],$_REQUEST['USERNAME']);
		//student account not verified (enrollment school + start date + last login are NULL)
		elseif (DBGet(DBQuery("SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,s.FAILED_LOGIN,se.START_DATE 
		FROM STUDENTS s,STUDENT_ENROLLMENT se 
		WHERE UPPER(s.USERNAME)=UPPER('".$_REQUEST['USERNAME']."') 
		AND se.STUDENT_ID=s.STUDENT_ID 
		AND se.SYEAR='".Config('SYEAR')."' 
		AND se.START_DATE IS NULL
		AND s.LAST_LOGIN IS NULL")))
			$student_RET = 0;
		else
			$student_RET = false;
	}
	
	if($login_RET && ($login_RET[1]['PROFILE']=='admin' || $login_RET[1]['PROFILE']=='teacher' || $login_RET[1]['PROFILE']=='parent'))
	{
		$_SESSION['STAFF_ID'] = $login_RET[1]['STAFF_ID'];
		$_SESSION['LAST_LOGIN'] = $login_RET[1]['LAST_LOGIN'];
		$failed_login = $login_RET[1]['FAILED_LOGIN'];
		DBQuery("UPDATE STAFF SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL WHERE STAFF_ID='".$login_RET[1]['STAFF_ID']."'");

		if(Config('LOGIN')=='No')
		{
			Warehouse('header'); ?>
			<FORM action="index.php" method="POST"><BR />

			<?php PopTable('header',_('Confirm Successful Installation')); ?>

			<span class="center">
			<h4><?php echo sprintf(_('You have successfully installed %s.'), ParseMLField(Config('TITLE'))); ?></h4><BR />
			<BR /><INPUT type="submit" name="submit" id="submit" value="<?php echo _('OK'); ?>" />
			</span>

			<?php PopTable('footer'); ?>

			</FORM>
			<script>$('#submit').click(function(){ $('form').ajaxFormUnbind(); });</script>
</BODY></HTML>
<?php 
			DBQuery("UPDATE CONFIG SET CONFIG_VALUE='Yes' WHERE TITLE='LOGIN'");
			exit;
		}
	}
	elseif(($login_RET && $login_RET[1]['PROFILE']=='none') || $student_RET===0 )
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
		DBQuery("UPDATE STAFF SET FAILED_LOGIN=".db_case(array('FAILED_LOGIN',"''",'1','FAILED_LOGIN+1'))." WHERE UPPER(USERNAME)=UPPER('".$_REQUEST['USERNAME']."') AND SYEAR='".Config('SYEAR')."'");
		DBQuery("UPDATE STUDENTS SET FAILED_LOGIN=".db_case(array('FAILED_LOGIN',"''",'1','FAILED_LOGIN+1'))." WHERE UPPER(USERNAME)=UPPER('".$_REQUEST['USERNAME']."')");
		$error[] = _('Incorrect username or password.').'&nbsp;'._('Please try logging in again.');
	}
}

//modif Francois: create account
if(isset($_REQUEST['create_account']))
{
	$include = false;

	if ($_REQUEST['create_account']=='user' && Config('CREATE_USER_ACCOUNT'))
		$include = 'Users/User.php';
	elseif ($_REQUEST['create_account']=='student' && Config('CREATE_STUDENT_ACCOUNT'))
		$include = 'Students/Student.php';

	if (!$include)
		unset($_REQUEST['create_account']);
	else
	{
		Warehouse('header');

		$_ROSARIO['allow_edit'] = true;
		include('modules/'.$include);

		Warehouse('footer');
	}
}


if(!$_SESSION['STAFF_ID'] && !$_SESSION['STUDENT_ID'] && !isset($_REQUEST['create_account']))
{
	$RTL_languages = array('ar', 'he', 'dv', 'fa', 'ur');
?>
<!doctype html>
<HTML lang="<?php echo mb_substr($locale,0,2); ?>" <?php echo (in_array(mb_substr($locale,0,2), $RTL_languages)?' dir="RTL"':''); ?>>
<HEAD>
	<TITLE><?php echo ParseMLField(Config('TITLE')); ?></TITLE>
	<meta charset="UTF-8" />
	<meta name="robots" content="noindex" />
	<meta name="viewport" content="width=device-width" />
	<noscript><META http-equiv="REFRESH" content="0;url=index.php?modfunc=logout&reason=javascript" /></noscript>
	<link REL="SHORTCUT ICON" HREF="favicon.ico" />
	<link rel="stylesheet" type="text/css" href="assets/themes/<?php echo Preferences('THEME'); ?>/stylesheet.css" />
</HEAD>
<BODY>
<BR /><BR />
<?php PopTable("header",sprintf(_('%s Login'),Config('NAME')), 'style="max-width:550px;"');
	
	if(isset($_REQUEST['reason']) && $_REQUEST['reason']=='javascript')
		$note[] = sprintf(_('You must have javascript enabled to use %s.'),Config('NAME'));

	//modif Francois: create account
	if(isset($_REQUEST['account_created']) && $_REQUEST['account_created'])
		$note[] = _('Your account has been created.').' '._('You will be notified when it has been verified by a school administrator.').' '._('You will then be able to log in.');

	if(isset($error))
		echo ErrorMessage($error);

	if(isset($note))
		echo ErrorMessage($note,'note');

?>

	<TABLE>
		<tr class="st">
		<td class="center"><img src="assets/themes/<?php echo Preferences('THEME'); ?>/logo.png" /></td>
		<td>
			<form name="loginform" method="post" action="index.php" class="login">
			<h4><?php echo ParseMLField(Config('TITLE')); ?></h4>
			<table class="cellspacing-0 col1-align-right">

			<?php // ng - choose language
			if (sizeof($RosarioLocales) > 1) : ?>

				<tr><td><b><?php echo _('Language'); ?></b></td>
				<td>
				<?php foreach ($RosarioLocales as $loc) : ?>

					<A href="<?php echo $_SERVER['PHP_SELF']; ?>?locale=<?php echo $loc; ?>"><IMG src="assets/flags/<?php echo $loc; ?>.png" height="32" /></A>&nbsp;&nbsp;
				<?php endforeach; ?>

				</td></tr>
			<?php endif; ?>

				<tr>
					<td><label for="USERNAME"><b><?php echo _('Username'); ?></b></label></td>
					<td><input type="text" name="USERNAME" id="USERNAME" size="25" maxlength="42" tabindex="1" required /></td>
				</tr>
				<tr>
					<td><label for="PASSWORD"><b><?php echo _('Password'); ?></b></label></td>
					<td><input type="password" name="PASSWORD" id="PASSWORD" size="25" maxlength="42" tabindex="2" required /></td>
				</tr>
			</table>
			<p class="center"><INPUT type="submit" value="<?php echo _('Login'); ?>" class="button-primary" /></p>

			<?php if(Config('CREATE_USER_ACCOUNT')) : ?>

				<span class="center">[ <A HREF="index.php?create_account=user&staff_id=new"><?php echo _('Create User Account'); ?></A> ]</span>
			<?php endif;

			if(Config('CREATE_STUDENT_ACCOUNT')) : ?>

				<span class="center">[ <A HREF="index.php?create_account=student&student_id=new"><?php echo _('Create Student Account'); ?></A> ]</span>
			<?php endif; ?>

			</form>
		</td>
		</tr>
		<?php // System disclaimer. ?>

		<tr><td colspan="2">
			<span class="size-3"><?php echo sprintf(_('This is a restricted network. Use of this network, its equipment, and resources is monitored at all times and requires explicit permission from the network administrator and %s. If you do not have this permission in writing, you are violating the regulations of this network and can and will be prosecuted to the full extent of the law. By continuing into this system, you are acknowledging that you are aware of and agree to these terms.'),ParseMLField(Config('TITLE'))); ?></span>
			<BR /><BR />
		</td></tr>
	</table>
	<span class="center"><?php echo sprintf(_('%s version %s'),'RosarioSIS', $RosarioVersion); ?>
	<BR />&copy; 2004-2009 <A HREF="http://www.miller-group.net" noreferrer>The Miller Group, Inc</A>
	<BR />&copy; 2009 <a href="http://www.glenn-abbey.com" noreferrer>Glenn Abbey Software, Inc</a>
	<BR />&copy; 2009 <a href="http://www.centresis.org" noreferrer>Learners Circle, LLC</a>
	<BR />&copy; 2012-2015 <a href="http://www.rosariosis.org" noreferrer>François Jacquet</a>
	</span>

<?php PopTable("footer"); ?>

<BR />
</BODY></HTML>
<?php
}
elseif(!isset($_REQUEST['create_account']))//successfully logged in, display Portal
{
	$_REQUEST['modname']='misc/Portal.php';
	$_REQUEST['failed_login']=$failed_login;
	include('Modules.php'); 
}
?>
