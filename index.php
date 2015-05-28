<?php

//FJ bugfix check accept cookies
$default_session_name = session_name();

include( 'Warehouse.php' );

// Logout
if( isset( $_REQUEST['modfunc'] )
	&& $_REQUEST['modfunc'] == 'logout' )
{
	//FJ set logout page to old session locale
	$old_session_locale = $_SESSION['locale'];

	session_unset();

	session_destroy();

	// redirect to index.php with same locale as old session & eventual reason
	header( 'Location: index.php?locale=' . $old_session_locale
		. ( isset( $_REQUEST['reason'] ) ? '&reason=' . $_REQUEST['reason'] : '' ) );

	exit;
}

// Login
elseif( isset( $_POST['USERNAME'] )
	&& $_POST['USERNAME'] != ''
	&& isset( $_POST['PASSWORD'] )
	&& $_POST['PASSWORD'] != '' )
{
	//FJ check accept cookies
	if( !isset( $_COOKIE['RosarioSIS'] )
		&& !isset( $_COOKIE[$default_session_name] ) )
	{
		header( 'Location: index.php?modfunc=logout&reason=cookie' );

		exit;
	}

	//only regenerate session ID if session.auto_start == 0
	elseif( isset( $_COOKIE['RosarioSIS'] ) )
		session_regenerate_id( true ); //and invalidate old session

	$username = DBEscapeString( $_POST['USERNAME'] );

	// lookup for user $username in DB
	$login_RET = DBGet( DBQuery( "SELECT USERNAME,PROFILE,STAFF_ID,LAST_LOGIN,FAILED_LOGIN,PASSWORD
	FROM STAFF
	WHERE SYEAR='" . Config( 'SYEAR' ) . "'
	AND UPPER(USERNAME)=UPPER('" . $username . "')" ) );
	
	if ( $login_RET
		&& match_password( $login_RET[1]['PASSWORD'], $_POST['PASSWORD'] ) )
		unset( $_POST['PASSWORD'] );

	else
		$login_RET = false;
	
	if( !$login_RET )
	{
		// lookup for student $username in DB
		$student_RET = DBGet( DBQuery( "SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,s.FAILED_LOGIN,s.PASSWORD
		FROM STUDENTS s,STUDENT_ENROLLMENT se
		WHERE UPPER(s.USERNAME)=UPPER('" . $username . "')
		AND se.STUDENT_ID=s.STUDENT_ID
		AND se.SYEAR='" . Config( 'SYEAR' ) . "'
		AND CURRENT_DATE>=se.START_DATE
		AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)" ) );
		
		if ( $student_RET
			&& match_password( $student_RET[1]['PASSWORD'], $_POST['PASSWORD'] ) )
			unset( $_POST['PASSWORD'] );

		//student account not verified (enrollment school + start date + last login are NULL)
		elseif ( DBGet( DBQuery( "SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,s.FAILED_LOGIN,se.START_DATE
		FROM STUDENTS s,STUDENT_ENROLLMENT se
		WHERE UPPER(s.USERNAME)=UPPER('" . $username . "')
		AND se.STUDENT_ID=s.STUDENT_ID
		AND se.SYEAR='" . Config( 'SYEAR' ) . "'
		AND se.START_DATE IS NULL
		AND s.LAST_LOGIN IS NULL")))
			$student_RET = 0;

		else
			$student_RET = false;
	}
	
	// Admin, teacher or parent: initiate session
	if( $login_RET
		&& ( $login_RET[1]['PROFILE'] == 'admin'
			|| $login_RET[1]['PROFILE'] == 'teacher'
			|| $login_RET[1]['PROFILE'] == 'parent' ) )
	{
		$_SESSION['STAFF_ID'] = $login_RET[1]['STAFF_ID'];

		$_SESSION['LAST_LOGIN'] = $login_RET[1]['LAST_LOGIN'];

		$failed_login = $login_RET[1]['FAILED_LOGIN'];

		DBQuery( "UPDATE STAFF
			SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL
			WHERE STAFF_ID='" . $login_RET[1]['STAFF_ID'] . "'" );

		// if 1st login, Confirm Successful Installation screen
		if( Config( 'LOGIN' ) == 'No' )
		{
			Warehouse( 'header' ); ?>

	<form action="index.php" method="POST"><BR />

	<?php PopTable( 'header', _( 'Confirm Successful Installation' ) ); ?>

	<span class="center">
		<h4>
			<?php
				echo sprintf(
					_('You have successfully installed %s.'),
					ParseMLField( Config( 'TITLE' ) )
				);
			?>
		</h4>
		<p>
			<?php
				echo sprintf(
					_( 'Check the %s page to spot remaining configuration problems.' ),
					'<a href="diagnostic.php" target="_blank">diagnostic.php</a>'
				);
			?>
		</p>
		<BR /><BR />
		<input type="submit" name="submit" id="submit" value="<?php echo _( 'OK' ); ?>" />
	</span>

	<?php PopTable( 'footer' ); ?>

	</FORM>

	<script>
		$('#submit').click(function(){
			$('form').ajaxFormUnbind();
		});
	</script>

</BODY>
</HTML>
<?php 
			// set Config( 'LOGIN' ) to Yes
			DBQuery( "UPDATE CONFIG
				SET CONFIG_VALUE='Yes'
				WHERE TITLE='LOGIN'" );

			exit;
		}
	}

	// User with No access profile
	elseif( ( $login_RET
			&& $login_RET[1]['PROFILE'] == 'none' )
		|| $student_RET === 0 )
	{
		$error[] = _( 'Your account has not yet been activated.' ) . ' '
			. _( 'You will be notified when it has been verified by a school administrator.' );
	}

	// Student: initiate session
	elseif( $student_RET )
	{
		$_SESSION['STUDENT_ID'] = $student_RET[1]['STUDENT_ID'];

		$_SESSION['LAST_LOGIN'] = $student_RET[1]['LAST_LOGIN'];

		$failed_login = $student_RET[1]['FAILED_LOGIN'];

		DBQuery( "UPDATE STUDENTS
			SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL
			WHERE STUDENT_ID='" . $student_RET[1]['STUDENT_ID'] . "'" );
	}

	// Failed login
	else
	{
		DBQuery( "UPDATE STAFF
			SET FAILED_LOGIN=" . db_case( array( 'FAILED_LOGIN', "''", '1', 'FAILED_LOGIN+1') ) . "
			WHERE UPPER(USERNAME)=UPPER('".$username."')
			AND SYEAR='" . Config( 'SYEAR' ) . "'" );

		DBQuery( "UPDATE STUDENTS
			SET FAILED_LOGIN=" . db_case( array( 'FAILED_LOGIN', "''", '1', 'FAILED_LOGIN+1' ) ) . "
			WHERE UPPER(USERNAME)=UPPER('" . $username . "')" );

		$error[] = _( 'Incorrect username or password.' ) . '&nbsp;'
			. _( 'Please try logging in again.' );
	}
}

//FJ create account
elseif( isset( $_REQUEST['create_account'] ) )
{
	$include = false;

	if ( $_REQUEST['create_account'] == 'user'
		&& Config( 'CREATE_USER_ACCOUNT' ) )
		$include = 'Users/User.php';

	elseif ( $_REQUEST['create_account'] == 'student'
		&& Config( 'CREATE_STUDENT_ACCOUNT' ) )
		$include = 'Students/Student.php';

	if ( !$include )
		unset( $_REQUEST['create_account'] );

	else
	{
		Warehouse( 'header' );

		$_ROSARIO['allow_edit'] = true;

		include( 'modules/' . $include );

		Warehouse( 'footer' );
	}
}


// Login screen
if( empty( $_SESSION['STAFF_ID'] )
	&& empty( $_SESSION['STUDENT_ID'] )
	&& !isset( $_REQUEST['create_account'] ) )
{
	$lang_2_chars = mb_substr( $locale, 0, 2 );

	// Right to left direction
	$RTL_languages = array( 'ar', 'he', 'dv', 'fa', 'ur' );

	$dir_RTL = in_array( $lang_2_chars, $RTL_languages ) ? ' dir="RTL"' : '';

?>
<!doctype html>
<html lang="<?php echo $lang_2_chars; ?>"<?php echo $dir_RTL; ?>>
<head>
	<title><?php echo ParseMLField( Config( 'TITLE' ) ); ?></title>
	<meta charset="UTF-8" />
	<meta name="robots" content="noindex" />
	<meta name="viewport" content="width=device-width" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<noscript>
		<meta http-equiv="REFRESH" content="0;url=index.php?modfunc=logout&amp;reason=javascript" />
	</noscript>
	<link REL="SHORTCUT ICON" HREF="favicon.ico" />
	<link rel="stylesheet" type="text/css" href="assets/themes/<?php echo Config( 'THEME' ); ?>/stylesheet.css" />
</head>
<body>
<BR /><BR />
<?php

	PopTable(
		'header',
		sprintf( _( '%s Login' ), Config( 'NAME' ) ),
		'style="max-width: 550px;"'
	);
	
	if( isset( $_REQUEST['reason'] ) )
	{
		if( $_REQUEST['reason'] == 'javascript' )
			$note[] = sprintf(
				_( 'You must have javascript enabled to use %s.' ),
				Config( 'NAME' )
			);

		//FJ check accept cookies
		elseif( $_REQUEST['reason'] == 'cookie' )
			$note[] = sprintf(
				_( 'You must accept cookies to use %s.' ),
				Config( 'NAME' )
			);

		//FJ create account
		elseif( $_REQUEST['reason'] == 'account_created' )
			$note[] = _( 'Your account has been created.' ) . ' '
				. _( 'You will be notified when it has been verified by a school administrator.' ) . ' '
				. _( 'You will then be able to log in.' );
	}

	if( isset( $error ) )
		echo ErrorMessage( $error );

	if( isset( $note ) )
		echo ErrorMessage( $note, 'note' );

?>

	<table>
		<tr class="st">
		<td class="center">
			<img src="assets/themes/<?php echo Config( 'THEME' ); ?>/logo.png" />
		</td>
		<td>
			<form name="loginform" method="post" action="index.php" class="login">
			<h4><?php echo ParseMLField( Config( 'TITLE' ) ); ?></h4>
			<table class="cellspacing-0 col1-align-right">

			<?php // ng - choose language
			if ( sizeof( $RosarioLocales ) > 1 ) : ?>

				<tr>
					<td>
						<b><?php echo _( 'Language' ); ?></b>
					</td>
					<td>
					<?php foreach ( $RosarioLocales as $loc ) : ?>

						<a href="index.php?locale=<?php echo $loc; ?>">
							<img src="assets/flags/<?php echo $loc; ?>.png" height="32" />
						</a>&nbsp;&nbsp;

					<?php endforeach; ?>

					</td>
				</tr>

			<?php endif; ?>

				<tr>
					<td>
						<label for="USERNAME">
							<b><?php echo _( 'Username' ); ?></b>
						</label>
					</td>
					<td>
						<input type="text" name="USERNAME" id="USERNAME" size="25" maxlength="42" tabindex="1" required autofocus />
					</td>
				</tr>
				<tr>
					<td>
						<label for="PASSWORD">
							<b><?php echo _( 'Password' ); ?></b>
						</label>
					</td>
					<td>
						<input type="password" name="PASSWORD" id="PASSWORD" size="25" maxlength="42" tabindex="2" required />
					</td>
				</tr>
			</table>
			<p class="center">
				<input type="submit" value="<?php echo _( 'Login' ); ?>" class="button-primary" />
			</p>

			<?php if( Config( 'CREATE_USER_ACCOUNT' ) ) : ?>

				<span class="center">[
					<a href="index.php?create_account=user&amp;staff_id=new">
						<?php echo _( 'Create User Account' ); ?>
					</a>
				]</span>

			<?php endif;

			if( Config( 'CREATE_STUDENT_ACCOUNT' ) ) : ?>

				<span class="center">[
					<a href="index.php?create_account=student&amp;student_id=new">
						<?php echo _( 'Create Student Account' ); ?>
					</a>
				]</span>

			<?php endif; ?>

			</form>
		</td>
		</tr>
		<?php // System disclaimer. ?>

		<tr>
			<td colspan="2">
				<span class="size-3">
					<?php
						echo sprintf(
							_( 'This is a restricted network. Use of this network, its equipment, and resources is monitored at all times and requires explicit permission from the network administrator and %s. If you do not have this permission in writing, you are violating the regulations of this network and can and will be prosecuted to the full extent of the law. By continuing into this system, you are acknowledging that you are aware of and agree to these terms.'),
							ParseMLField( Config( 'TITLE' ) )
						);
					?>
				</span>
				<BR /><BR />
			</td>
		</tr>
	</table>
	<span class="center">
		<?php echo sprintf( _( '%s version %s' ), 'RosarioSIS', ROSARIO_VERSION ); ?>
		<BR />&copy; 2004-2009 <a href="http://www.miller-group.net" noreferrer>The Miller Group, Inc</a>
		<BR />&copy; 2009 <a href="http://www.centresis.org" noreferrer>Learners Circle, LLC</a>
		<BR />&copy; 2012-2015 <a href="http://www.rosariosis.org" noreferrer>François Jacquet</a>
	</span>

<?php PopTable( 'footer' ); ?>

<BR />
</body>
</html>
<?php

}

// successfully logged in, display Portal
elseif( !isset( $_REQUEST['create_account'] ) )
{
	$_REQUEST['modname'] = 'misc/Portal.php';

	include( 'Modules.php' );
}

?>