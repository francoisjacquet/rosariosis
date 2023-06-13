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
require_once 'ProgramFunctions/FirstLogin.fnc.php';

// Logout.
if ( isset( $_REQUEST['modfunc'] )
	&& $_REQUEST['modfunc'] === 'logout' )
{
	// Redirect to index.php with same locale as old session & eventual reason & redirect to URL.
	header( 'Location: ' . URLEscape( 'index.php?locale=' . $_SESSION['locale'] .
		( isset( $_REQUEST['reason'] ) ? '&reason=' . $_REQUEST['reason'] : '' ) .
		( isset( $_REQUEST['redirect_to'] ) ?
			'&redirect_to=' . urlencode( $_REQUEST['redirect_to'] ) :
			'' ) ) );

	if ( ! empty( $_REQUEST['token'] )
		&& $_SESSION['token'] === $_REQUEST['token'] )
	{
		session_unset();

		session_destroy();
	}

	exit;
}

// First login.
elseif ( isset( $_REQUEST['modfunc'] )
	&& $_REQUEST['modfunc'] === 'first-login' )
{
	// @since 7.3 Before First Login form action hook.
	// @example Parent Agreement plugin: Add a form before first login form without interfering with logic.
	do_action( 'index.php|before_first_login_form' );

	/**
	 * First Login Form
	 *
	 * Password Change & Poll after install.
	 *
	 * @since 5.3 Force password change on first login
	 */
	if ( HasFirstLoginForm() )
	{
		$first_login_done = false;

		if ( ! empty( $_POST['first_login'] ) )
		{
			// Save Password and set LAST_LOGIN.
			$first_login_done = DoFirstLoginForm( $_REQUEST['first_login'] );
		}

		if ( ! $first_login_done )
		{
			$_ROSARIO['page'] = 'first-login';

			Warehouse( 'header' );

			echo FirstLoginForm();

			Warehouse( 'footer' );

			exit;
		}
	}

	$_REQUEST['modfunc'] = false;
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
		header( 'Location: index.php?modfunc=logout&reason=cookie&token=' . $_SESSION['token'] );

		exit;
	}

	// Only regenerate session ID if session.auto_start == 0.
	elseif ( isset( $_COOKIE['RosarioSIS'] ) )
	{
		session_regenerate_id( true ); // And invalidate old session.

		/**
		 * Add CSRF token to protect unauthenticated requests
		 *
		 * @since 9.0
		 * @since 11.0 Fix PHP fatal error if openssl PHP extension is missing
		 * @link https://stackoverflow.com/questions/5207160/what-is-a-csrf-token-what-is-its-importance-and-how-does-it-work
		 */
		$_SESSION['token'] = bin2hex( function_exists( 'openssl_random_pseudo_bytes' ) ?
			openssl_random_pseudo_bytes( 16 ) :
			( function_exists( 'random_bytes' ) ? random_bytes( 16 ) :
				mb_substr( sha1( rand( 999999999, 9999999999 ), true ), 0, 16 ) ) );
	}

	$username = $_REQUEST['USERNAME'];

	unset( $_REQUEST['USERNAME'], $_POST['USERNAME'] );

	// Lookup for user $username in DB.
	$login_RET = DBGet( "SELECT USERNAME,PROFILE,STAFF_ID,LAST_LOGIN,FAILED_LOGIN,PASSWORD
	FROM staff
	WHERE SYEAR='" . Config( 'SYEAR' ) . "'
	AND UPPER(USERNAME)=UPPER('" . $username . "')" );

	if ( $login_RET
		&& match_password( $login_RET[1]['PASSWORD'], $_POST['PASSWORD'] ) )
	{
		unset( $_REQUEST['PASSWORD'], $_POST['PASSWORD'] );
	}
	else
		$login_RET = false;

	if ( ! $login_RET )
	{
		// Lookup for student $username in DB.
		$student_RET = DBGet( "SELECT s.USERNAME,s.STUDENT_ID,s.LAST_LOGIN,
			s.FAILED_LOGIN,s.PASSWORD,se.START_DATE
			FROM students s,student_enrollment se
			WHERE se.STUDENT_ID=s.STUDENT_ID
			AND se.SYEAR='" . Config( 'SYEAR' ) . "'
			AND CURRENT_DATE>=se.START_DATE
			AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)
			AND UPPER(s.USERNAME)=UPPER('" . $username . "')" );

		if ( $student_RET
			&& match_password( $student_RET[1]['PASSWORD'], $_POST['PASSWORD'] ) )
		{
			unset( $_REQUEST['PASSWORD'], $_POST['PASSWORD'] );
		}
		else
		{
			// Student may be inactive or not verified, see below for corresponding errors.
			$student_RET = DBGet( "SELECT s.USERNAME,s.STUDENT_ID,
				s.LAST_LOGIN,s.FAILED_LOGIN,se.START_DATE,s.PASSWORD
			FROM students s,student_enrollment se
			WHERE se.STUDENT_ID=s.STUDENT_ID
			AND se.SYEAR='" . Config( 'SYEAR' ) . "'
			AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)
			AND UPPER(s.USERNAME)=UPPER('" . $username . "')" );

			if ( ! $student_RET
				|| ! match_password( $student_RET[1]['PASSWORD'], $_POST['PASSWORD'] ) )
			{
				$student_RET = false;
			}
		}
	}

	$login_status = '';

	$is_banned = false;

	$ip = ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )
		// Filter IP, HTTP_* headers can be forged.
		&& filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ) ?
		$_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );

	if ( Config( 'FAILED_LOGIN_LIMIT' ) )
	{
		// Failed login ban if >= X failed attempts within 10 minutes.
		$failed_login_RET = DBGet( "SELECT
			COUNT(CASE WHEN STATUS IS NULL OR STATUS='B' THEN 1 END) AS FAILED_COUNT,
			COUNT(CASE WHEN STATUS='B' THEN 1 END) AS BANNED_COUNT
			FROM access_log
			WHERE CREATED_AT > (CURRENT_TIMESTAMP - INTERVAL " . ( $DatabaseType === 'mysql' ? '10 minute' : "'10 minute'" ) . ")
			AND USER_AGENT='" . DBEscapeString( $_SERVER['HTTP_USER_AGENT'] ) . "'
			AND IP_ADDRESS='" . $ip . "'" );

		if ( $failed_login_RET[1]['BANNED_COUNT']
			|| $failed_login_RET[1]['FAILED_COUNT'] >= Config( 'FAILED_LOGIN_LIMIT' ) )
		{
			// Ban in every case.
			$is_banned = true;

			$login_RET = $student_RET = false;

			// Banned status code: B.
			$login_status = 'B';
		}
	}

	// Admin, teacher or parent: initiate session.
	if ( $login_RET
		&& ( $login_RET[1]['PROFILE'] === 'admin'
			|| $login_RET[1]['PROFILE'] === 'teacher'
			|| $login_RET[1]['PROFILE'] === 'parent' ) )
	{
		$_SESSION['STAFF_ID'] = $login_RET[1]['STAFF_ID'];

		// Invalidate any active Student session.
		unset( $_SESSION['STUDENT_ID'] );

		unset( $_SESSION['UserSchool'] );

		$_SESSION['LAST_LOGIN'] = $login_RET[1]['LAST_LOGIN'];

		$failed_login = $login_RET[1]['FAILED_LOGIN'];

		$login_status = 'Y';
	}

	// User with No access profile.
	elseif ( $login_RET
			&& $login_RET[1]['PROFILE'] == 'none' )
	{
		$error[] = _( 'Your account has not yet been activated.' ) . ' '
			. _( 'You will be notified when it has been verified by a school administrator.' );
	}

	// Student account inactive (today < Attendance start date).
	elseif ( $student_RET
			&& DBDate() < $student_RET[1]['START_DATE'] )
	{
		$error[] = _( 'Your account has not yet been activated.' );
	}

	// Student account not verified (enrollment school + start date + last login are NULL).
	elseif ( $student_RET
			&& ! $student_RET[1]['START_DATE']
			&& ! $student_RET[1]['LAST_LOGIN'] )
	{
		$error[] = _( 'Your account has not yet been activated.' ) . ' '
			. _( 'You will be notified when it has been verified by a school administrator.' );
	}

	// Student: initiate session.
	elseif ( $student_RET )
	{
		$_SESSION['STUDENT_ID'] = $student_RET[1]['STUDENT_ID'];

		// Invalidate any active User session.
		unset( $_SESSION['STAFF_ID'] );

		unset( $_SESSION['UserSchool'] );

		$_SESSION['LAST_LOGIN'] = $student_RET[1]['LAST_LOGIN'];

		$failed_login = $student_RET[1]['FAILED_LOGIN'];

		$login_status = 'Y';
	}

	// Failed login.
	else
	{
		DBQuery( "UPDATE staff
			SET FAILED_LOGIN=" . db_case( [ 'FAILED_LOGIN', "''", '1', 'FAILED_LOGIN+1' ] ) . "
			WHERE UPPER(USERNAME)=UPPER('" . $username . "')
			AND SYEAR='" . Config( 'SYEAR' ) . "';
			UPDATE students
			SET FAILED_LOGIN=" . db_case( [ 'FAILED_LOGIN', "''", '1', 'FAILED_LOGIN+1' ] ) . "
			WHERE UPPER(USERNAME)=UPPER('" . $username . "')" );

		if ( $is_banned )
		{
			// Failed login ban if >= X failed attempts within 10 minutes.
			$error[] = _( 'Too many Failed Login Attempts.' ) . '&nbsp;'
				. _( 'Please try logging in later.' );
		}
		else
		{
			$error[] = _( 'Incorrect username or password.' ) . '&nbsp;'
				. _( 'Please try logging in again.' );
		}
	}

	// Access Log.
	if ( ! function_exists( 'AccessLogRecord' ) )
	{
		DBInsert(
			'access_log',
			[
				'SYEAR' => Config( 'SYEAR' ),
				'USERNAME' => $username,
				'PROFILE' => User( 'PROFILE' ),
				'IP_ADDRESS' => $ip,
				'USER_AGENT' => DBEscapeString( $_SERVER['HTTP_USER_AGENT'] ),
				'STATUS' => $login_status,
			]
		);
	}

	// Set current SchoolYear on login.
	if ( $login_status === 'Y'
		&& ! UserSyear() )
	{
		$_SESSION['UserSyear'] = Config( 'SYEAR' );
	}

	// @since 2.9.8 Login check action hook.
	do_action( 'index.php|login_check', $username );

	if ( HasFirstLoginForm() )
	{
		// First Login.
		header( 'Location: index.php?locale=' . $_SESSION['locale'] . '&modfunc=first-login' );

		exit;
	}

	// Set LAST_LOGIN, reset FAILED_LOGIN.
	if ( $login_status === 'Y'
		&& User( 'STAFF_ID' ) )
	{
		DBQuery( "UPDATE staff
			SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL
			WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'" );
	}
	elseif ( $login_status === 'Y' )
	{
		DBQuery( "UPDATE students
			SET LAST_LOGIN=CURRENT_TIMESTAMP,FAILED_LOGIN=NULL
			WHERE STUDENT_ID='" . (int) $_SESSION['STUDENT_ID'] . "'" );
	}
}

// FJ create account.
elseif ( isset( $_REQUEST['create_account'] ) )
{
	$include = false;

	unset( $_SESSION['STAFF_ID'], $_SESSION['STUDENT_ID'] );

	if ( $_REQUEST['create_account'] === 'user'
		&& Config( 'CREATE_USER_ACCOUNT' ) )
	{
		$include = 'Users/User.php';

		if ( UserStaffID() )
		{
			unset( $_SESSION['staff_id'] );
		}
	}

	elseif ( $_REQUEST['create_account'] === 'student'
		&& Config( 'CREATE_STUDENT_ACCOUNT' ) )
	{
		$include = 'Students/Student.php';

		// @since 6.0 Create Student Account: add school_id param to URL.
		if ( ! empty( $_REQUEST['school_id'] ) )
		{
			$_SESSION['UserSchool'] = DBGetOne( "SELECT ID FROM schools
				WHERE SYEAR='" . Config( 'SYEAR' ) . "'
				AND ID='" . (int) $_REQUEST['school_id'] . "'" );
		}

		if ( ! UserSchool() )
		{
			RedirectURL( 'school_id' );

			// @since 6.3 Create Student Account Default School.
			// @link https://stackoverflow.com/questions/1250156/how-do-i-return-rows-with-a-specific-value-first#comment-67097263
			$sql_order_by = Config( 'CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL' ) ?
				"ID='" . Config( 'CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL' ) . "' DESC,ID" : "ID";

			$_SESSION['UserSchool'] = DBGetOne( "SELECT ID FROM schools
				WHERE SYEAR='" . Config( 'SYEAR' ) . "'
				ORDER BY " . $sql_order_by );
		}

		if ( UserStudentID() )
		{
			unset( $_SESSION['student_id'] );
		}
	}

	if ( ! $include )
	{
		// Do not use RedirectURL() here (no JS loaded).
		header( 'Location: index.php' );
	}
	else
	{
		if ( ! isset( $_REQUEST['modfunc'] ) )
		{
			$_REQUEST['modfunc'] = false;
		}

		$_REQUEST['modname'] = false;

		$_ROSARIO['page'] = 'create-account';

		Warehouse( 'header' );

		$_ROSARIO['allow_edit'] = true;

		// FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html.
		if ( mb_substr( $include, -4, 4 ) !== '.php'
			|| mb_strpos( $include, '..' ) !== false
			|| ! is_file( 'modules/' . $include ) )
		{
			require_once 'ProgramFunctions/HackingLog.fnc.php';
			HackingLog();
		}
		else
			require_once 'modules/' . $include;

		Warehouse( 'footer' );

		if ( UserSchool() )
		{
			// Unset UserSchool() so we get correct Config values if next request changes school.
			unset( $_SESSION['UserSchool'] );
		}
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

		// @since 5.9 Automatic Student Account Activation.
		elseif ( $_REQUEST['reason'] == 'account_activated' )
		{
			$note[] = _( 'Your account has been created.' );
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

	<img src="assets/themes/<?php echo URLEscape( Config( 'THEME' ) ); ?>/logo.png" class="logo center" alt="Logo" />
	<h4 class="center"><?php echo ParseMLField( Config( 'TITLE' ) ); ?></h4>
	<form name="loginform" id="loginform" method="post">
	<table class="cellspacing-0 width-100p">

	<?php // Choose language.
	if ( count( $RosarioLocales ) > 1 ) : ?>

		<tr>
			<td>
			<?php foreach ( $RosarioLocales as $loc ) :
				$language = function_exists( 'locale_get_display_language' ) ?
					ucfirst( locale_get_display_language( $loc, $locale ) ) :
					str_replace( '.utf8', '', $loc ); ?>

				<a href="index.php?locale=<?php echo $loc; ?>" title="<?php echo AttrEscape( $language ); ?>">
					<img src="locale/<?php echo $loc; ?>/flag.png" width="32" alt="<?php echo AttrEscape( $language ); ?>" />
				</a>&nbsp;

			<?php endforeach; ?>
			<br />
			<?php echo _( 'Language' ); ?>
			</td>
		</tr>

	<?php endif; ?>

		<tr>
			<td>
				<label>
					<input type="text" name="USERNAME" id="USERNAME" size="20" maxlength="100" required autofocus />
					<?php echo _( 'Username' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<td>
				<label>
					<input type="password" name="PASSWORD" id="PASSWORD" size="20" maxlength="42" required />
					<?php echo _( 'Password' ); ?>
				</label>
				<div class="align-right">
					<a href="PasswordReset.php" rel="nofollow">
						<?php echo _( 'Password help' ); ?>
					</a>
				</div>
			</td>
		</tr>
	</table>
	<p class="center">
		<input type="submit" value="<?php echo AttrEscape( _( 'Login' ) ); ?>" class="button-primary" />
	</p>

	<?php // @since 7.6 Login form link action hook.
	do_action( 'index.php|login_form_link' ); ?>

	<?php if ( Config( 'CREATE_USER_ACCOUNT' ) ) : ?>

		<p class="align-right">
			<a href="index.php?create_account=user&amp;staff_id=new" rel="nofollow">
				<?php echo _( 'Create User Account' ); ?>
			</a>
		</p>

	<?php endif;

	if ( Config( 'CREATE_STUDENT_ACCOUNT' ) ) : ?>

		<p class="align-right">
			<a href="index.php?create_account=student&amp;student_id=new" rel="nofollow">
				<?php echo _( 'Create Student Account' ); ?>
			</a>
		</p>

	<?php endif;

	if ( ! empty( $_REQUEST['redirect_to'] ) ) :
		/**
		 * Redirect to Modules.php URL after login.
		 *
		 * @since 3.8
		 */
		?>
		<input type="hidden" name="redirect_to" value="<?php echo URLEscape( $_REQUEST['redirect_to'] ); ?>" />
	<?php endif; ?>
	</form>
	<details class="about-rosariosis">
		<summary><?php echo _( 'About' ); ?></summary>
		<?php // System disclaimer. ?>
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
			&copy; 2004-2009 The Miller Group &amp; Learners Circle
			<br />&copy; 2012-2023 <a href="https://www.rosariosis.org" noreferrer>RosarioSIS</a>
		</p>
	</details>

<?php PopTable( 'footer' );

	Warehouse( 'footer' );
}

// Successfully logged in, display Portal.
elseif ( ! isset( $_REQUEST['create_account'] ) )
{
	/**
	 * Redirect to Modules.php URL after login.
	 * Defaults to modname=misc/Portal.php.
	 * Sanitize redirect_to.
	 * Check profile ID, so we avoid HackingLog
	 * when login with a less privileged user.
	 *
	 * @since 3.8
	 */
	$redirect_to = empty( $_REQUEST['redirect_to'] ) ?
		'modname=misc/Portal.php' : // Fix #173 resend login form: redirect to Modules.php.
		str_replace(
			[ '&_ROSARIO_PDF=true', '&_ROSARIO_PDF', '&LO_save=1' ],
			'',
			$_REQUEST['redirect_to']
		);

	header( 'Location: ' . URLEscape( 'Modules.php?' . $redirect_to ) );

	exit;
}
