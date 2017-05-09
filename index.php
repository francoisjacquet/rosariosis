<?php
/**
 * Index
 *
 * Login screen
 *
 * @package RosarioSIS
 */

// FJ bugfix check accept cookies.
$default_session_name = session_name();

require_once 'Warehouse.php';

// Logout.
if ( isset( $_REQUEST['modfunc'] )
	&& $_REQUEST['modfunc'] === 'logout' )
{
	// FJ set logout page to old session locale.
	$old_session_locale = $_SESSION['locale'];

	session_unset();

	session_destroy();

	// Redirect to index.php with same locale as old session & eventual reason.
	header( 'Location: index.php?locale=' . $old_session_locale .
		( isset( $_REQUEST['reason'] ) ? '&reason=' . $_REQUEST['reason'] : '' ) );

	exit;
}

// Login.
elseif ( isset( $_POST['USERNAME'] )
	&& $_REQUEST['USERNAME'] !== ''
	&& isset( $_POST['PASSWORD'] )
	&& $_REQUEST['PASSWORD'] !== '' )
{
	// FJ check accept cookies.
	if ( ! isset( $_COOKIE['RosarioSIS'] )
		&& ! isset( $_COOKIE[ $default_session_name ] ) )
	{
		header( 'Location: index.php?modfunc=logout&reason=cookie' );

		exit;
	}

	// Only regenerate session ID if session.auto_start == 0.
	elseif ( isset( $_COOKIE['RosarioSIS'] ) )
	{
		session_regenerate_id( true ); // And invalidate old session.
	}

	$username = $_REQUEST['USERNAME'];

	unset( $_REQUEST['USERNAME'], $_POST['USERNAME'] );

	// Lookup for user $username in DB.
	$login_RET = DBGet( DBQuery( "SELECT USERNAME,PROFILE,STAFF_ID,LAST_LOGIN,FAILED_LOGIN,PASSWORD
	FROM STAFF
	WHERE SYEAR='" . Config( 'SYEAR' ) . "'
	AND UPPER(USERNAME)=UPPER('" . $username . "')" ) );

	if ( $login_RET
		&& match_password( $login_RET[1]['PASSWORD'], $_REQUEST['PASSWORD'] ) )
	{
		unset( $_REQUEST['PASSWORD'], $_POST['PASSWORD'] );
	}
	else
		$login_RET = false;

	if ( ! $login_RET )
	{
		// Lookup for student $username in DB.
		$student_RET = DBGet( DBQuery( "SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,s.FAILED_LOGIN,s.PASSWORD
			FROM STUDENTS s,STUDENT_ENROLLMENT se
			WHERE se.STUDENT_ID=s.STUDENT_ID
			AND se.SYEAR='" . Config( 'SYEAR' ) . "'
			AND CURRENT_DATE>=se.START_DATE
			AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)
			AND UPPER(s.USERNAME)=UPPER('" . $username . "')" ) );

		if ( $student_RET
			&& match_password( $student_RET[1]['PASSWORD'], $_REQUEST['PASSWORD'] ) )
		{
			unset( $_REQUEST['PASSWORD'], $_POST['PASSWORD'] );
		}

		// Student account not verified (enrollment school + start date + last login are NULL).
		elseif ( DBGet( DBQuery( "SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,s.FAILED_LOGIN,se.START_DATE
			FROM STUDENTS s,STUDENT_ENROLLMENT se
			WHERE se.STUDENT_ID=s.STUDENT_ID
			AND se.SYEAR='" . Config( 'SYEAR' ) . "'
			AND se.START_DATE IS NULL
			AND s.LAST_LOGIN IS NULL
			AND UPPER(s.USERNAME)=UPPER('" . $username . "')")))
		{
			$student_RET = 0;
		}
		else
			$student_RET = false;
	}

	// Admin, teacher or parent: initiate session.
	if ( $login_RET
		&& ( $login_RET[1]['PROFILE'] === 'admin'
			|| $login_RET[1]['PROFILE'] === 'teacher'
			|| $login_RET[1]['PROFILE'] === 'parent' ) )
	{
		$_SESSION['STAFF_ID'] = $login_RET[1]['STAFF_ID'];

		$_SESSION['LAST_LOGIN'] = $login_RET[1]['LAST_LOGIN'];

		$failed_login = $login_RET[1]['FAILED_LOGIN'];

		DBQuery( "UPDATE STAFF
			SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL
			WHERE STAFF_ID='" . $login_RET[1]['STAFF_ID'] . "'" );

		$login_status = 'Y';

		// If 1st login, Confirm Successful Installation screen.
		if ( Config( 'LOGIN' ) === 'No' ) :

			$_ROSARIO['page'] = 'first-login';

			Warehouse( 'header' ); ?>

	<form action="Modules.php?modname=misc/Portal.php" method="POST">

	<?php PopTable( 'header', _( 'Confirm Successful Installation' ) ); ?>

	<div class="center">
		<h4>
			<?php
				echo sprintf(
					_( 'You have successfully installed %s.' ),
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
		<br />
		<?php echo Buttons( _( 'OK' ) ); ?>
	</div>

	<?php PopTable( 'footer' ); ?>

	</form>
<?php
			Warehouse( 'footer' );

			// Set Config( 'LOGIN' ) to Yes.
			DBQuery( "UPDATE CONFIG
				SET CONFIG_VALUE='Yes'
				WHERE TITLE='LOGIN'" );

			exit;

		endif;
	}

	// User with No access profile.
	elseif ( ( $login_RET
			&& $login_RET[1]['PROFILE'] == 'none' )
		|| $student_RET === 0 )
	{
		$error[] = _( 'Your account has not yet been activated.' ) . ' '
			. _( 'You will be notified when it has been verified by a school administrator.' );
	}

	// Student: initiate session.
	elseif ( $student_RET )
	{
		$_SESSION['STUDENT_ID'] = $student_RET[1]['STUDENT_ID'];

		$_SESSION['LAST_LOGIN'] = $student_RET[1]['LAST_LOGIN'];

		$failed_login = $student_RET[1]['FAILED_LOGIN'];

		DBQuery( "UPDATE STUDENTS
			SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL
			WHERE STUDENT_ID='" . $student_RET[1]['STUDENT_ID'] . "'" );

		$login_status = 'Y';
	}

	// Failed login.
	else
	{
		DBQuery( "UPDATE STAFF
			SET FAILED_LOGIN=" . db_case( array( 'FAILED_LOGIN', "''", '1', 'FAILED_LOGIN+1' ) ) . "
			WHERE UPPER(USERNAME)=UPPER('" . $username . "')
			AND SYEAR='" . Config( 'SYEAR' ) . "'" );

		DBQuery( "UPDATE STUDENTS
			SET FAILED_LOGIN=" . db_case( array( 'FAILED_LOGIN', "''", '1', 'FAILED_LOGIN+1' ) ) . "
			WHERE UPPER(USERNAME)=UPPER('" . $username . "')" );

		$error[] = _( 'Incorrect username or password.' ) . '&nbsp;'
			. _( 'Please try logging in again.' );

		$login_status = '';
	}

	// Access Log.
	if ( ! function_exists( 'AccessLogRecord' ) )
	{
		DBQuery( "INSERT INTO ACCESS_LOG
			(SYEAR,USERNAME,PROFILE,LOGIN_TIME,IP_ADDRESS,USER_AGENT,STATUS)
			values('" . Config( 'SYEAR' ) . "',
			'" . $username . "',
			'" . User( 'PROFILE' ) . "',
			CURRENT_TIMESTAMP,
			'" . ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ?
				$_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] ) . "',
			'" . DBEscapeString( $_SERVER['HTTP_USER_AGENT'] ) .
			"','" . $login_status . "' )" );
	}

	do_action( 'index.php|login_check', $username );
}

// FJ create account.
elseif ( isset( $_REQUEST['create_account'] ) )
{
	$include = false;

	if ( $_REQUEST['create_account'] === 'user'
		&& Config( 'CREATE_USER_ACCOUNT' ) )
	{
		$include = 'Users/User.php';
	}

	elseif ( $_REQUEST['create_account'] === 'student'
		&& Config( 'CREATE_STUDENT_ACCOUNT' ) )
	{
		$include = 'Students/Student.php';
	}

	if ( ! $include )
	{
		// Do not use RedirectURL() here (no JS loaded).
		header( 'Location: index.php' );
	}
	else
	{
		$_ROSARIO['page'] = 'create-account';

		Warehouse( 'header' );

		$_ROSARIO['allow_edit'] = true;

		require_once 'modules/' . $include;

		Warehouse( 'footer' );
	}
}


// Login screen.
if ( empty( $_SESSION['STAFF_ID'] )
	&& empty( $_SESSION['STUDENT_ID'] )
	&& ! isset( $_REQUEST['create_account'] ) )
{
	$_ROSARIO['page'] = 'login';

	Warehouse( 'header' );

	PopTable(
		'header',
		sprintf( _( '%s Login' ), Config( 'NAME' ) )
	);

	if ( isset( $_REQUEST['reason'] ) )
	{
		if ( $_REQUEST['reason'] == 'javascript' )
		{
			$note[] = sprintf(
				_( 'You must have javascript enabled to use %s.' ),
				Config( 'NAME' )
			);
		}

		// FJ check accept cookies.
		elseif ( $_REQUEST['reason'] == 'cookie' )
		{
			$note[] = sprintf(
				_( 'You must accept cookies to use %s.' ),
				Config( 'NAME' )
			);
		}

		// FJ create account.
		elseif ( $_REQUEST['reason'] == 'account_created' )
		{
			$note[] = _( 'Your account has been created.' ) . ' '
				. _( 'You will be notified when it has been verified by a school administrator.' ) . ' '
				. _( 'You will then be able to log in.' );
		}

		// Password recovery.
		elseif ( $_REQUEST['reason'] == 'password_reset' )
		{
			$note[] = _( 'If you supplied a correct email address then please check your email for the password reset instructions.' );
		}
	}

	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );

?>

	<table>
		<tr class="st">
		<td class="center">
			<img src="assets/themes/<?php echo Config( 'THEME' ); ?>/logo.png" class="logo" alt="Logo" />
		</td>
		<td>
			<form name="loginform" method="post" action="index.php">
			<h4 class="center"><?php echo ParseMLField( Config( 'TITLE' ) ); ?></h4>
			<table class="cellspacing-0 col1-align-right width-100p">

			<?php // Choose language.
			if ( count( $RosarioLocales ) > 1 ) : ?>

				<tr>
					<td>
						<b><?php echo _( 'Language' ); ?></b>
					</td>
					<td>
					<?php foreach ( $RosarioLocales as $loc ) : ?>

						<a href="index.php?locale=<?php echo $loc; ?>" title="<?php echo str_replace( '.utf8', '', $loc ); ?>">
							<img src="assets/flags/<?php echo $loc; ?>.png" height="32" />
						</a>&nbsp;

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
						<input type="text" name="USERNAME" id="USERNAME" size="25" maxlength="100" tabindex="1" required autofocus />
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
				<tr>
					<td colspan="2">
						<a href="PasswordReset.php" rel="nofollow"><?php echo _( 'Password help' ); ?></a>
					</td>
				</tr>
			</table>
			<p class="center">
				<input type="submit" value="<?php echo _( 'Login' ); ?>" class="button-primary" />
			</p>

			<?php if ( Config( 'CREATE_USER_ACCOUNT' ) ) : ?>

				<div class="center">[
					<a href="index.php?create_account=user&amp;staff_id=new" rel="nofollow">
						<?php echo _( 'Create User Account' ); ?>
					</a>
				]</div>

			<?php endif;

			if ( Config( 'CREATE_STUDENT_ACCOUNT' ) ) : ?>

				<div class="center">[
					<a href="index.php?create_account=student&amp;student_id=new" rel="nofollow">
						<?php echo _( 'Create Student Account' ); ?>
					</a>
				]</div>

			<?php endif; ?>

			</form>
		</td>
		</tr>
	</table>
	<?php // System disclaimer. ?>
	<input class="toggle" type="checkbox" id="toggle1" />
	<label class="toggle" for="toggle1"><?php echo _( 'About' ); ?></label>
	<div class="toggle-me">
		<p class="size-3">
			<?php
				echo sprintf(
					_( 'This is a restricted network. Use of this network, its equipment, and resources is monitored at all times and requires explicit permission from the network administrator and %s. If you do not have this permission in writing, you are violating the regulations of this network and can and will be prosecuted to the full extent of the law. By continuing into this system, you are acknowledging that you are aware of and agree to these terms.'),
					ParseMLField( Config( 'TITLE' ) )
				);
			?>
		</p>
		<p class="center">
			<?php echo sprintf( _( '%s version %s' ), 'RosarioSIS', ROSARIO_VERSION ); ?>
		</p>
		<p class="center size-1">
			&copy; 2004-2009 <a href="http://www.centresis.org" noreferrer>The Miller Group &amp; Learners Circle</a>
			<br />&copy; 2012-2017 <a href="https://www.rosariosis.org" noreferrer>RosarioSIS</a>
		</p>
	</div>

<?php PopTable( 'footer' );

	Warehouse( 'footer' );
}

// Successfully logged in, display Portal.
elseif ( ! isset( $_REQUEST['create_account'] ) )
{
	// Fix #173 resend login form: redirect to Modules.php.
	header( 'Location: Modules.php?modname=misc/Portal.php' );

	exit;
}
