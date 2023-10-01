<?php
/**
 * Bottom
 *
 * Displays bottom menu
 * Handles Print & Inline Help functionalities
 *
 * @package RosarioSIS
 */

require_once 'Warehouse.php';

if ( isAJAX() )
{
	ETagCache( 'start' );
}

// Output Bottom menu.
if ( empty( $_REQUEST['bottomfunc'] ) ) : ?>

	<div id="footerwrap">
		<a href="#body" class="a11y-hidden BottomButton">
			<?php echo _( 'Skip to main content' ); // Accessibility link to skip menus. ?>
		</a>
		<a id="BottomButtonMenu" href="#" onclick="expandMenu(); return false;" title="<?php echo AttrEscape( _( 'Menu' ) ); ?>" class="BottomButton">
			<span><?php echo _( 'Menu' ); ?></span>
		</a>

		<?php // FJ icons.

		$btn_path = 'assets/themes/' . Preferences( 'THEME' ) . '/btn/';

		if ( isset( $_SESSION['List_PHP_SELF'] )
			&& isset( $_SESSION['Back_PHP_SELF'] )
			&& ( User( 'PROFILE' ) === 'admin'
				|| User( 'PROFILE' ) === 'teacher') ) :

			switch ( $_SESSION['Back_PHP_SELF'] )
			{
				case 'student':

					$back_text = _( 'Student List' );
				break;

				case 'staff':

					$back_text = _( 'User List' );
				break;

				case 'course':

					$back_text = _( 'Course List' );
				break;

				default:

					$back_text = sprintf( _( '%s List' ), $_SESSION['Back_PHP_SELF'] );
			} ?>

			<a href="<?php echo URLEscape( $_SESSION['List_PHP_SELF'] ); ?>&amp;bottom_back=true" title="<?php echo AttrEscape( $back_text ); ?>" class="BottomButton">
				<img src="<?php echo $btn_path; ?>back.png" alt="">
				<span><?php echo $back_text; ?></span>
			</a>

		<?php endif;

		// @since 5.0 "Back to Student/User/Course Search" button removed.

		// Do bottom_buttons hook.
		do_action( 'Bottom.php|bottom_buttons' ); ?>

		<a href="Bottom.php?bottomfunc=print" target="_blank" title="<?php echo AttrEscape( _( 'Print' ) ); ?>" class="BottomButton">
			<img src="<?php echo $btn_path; ?>print.png" alt="">
			<span><?php echo _( 'Print' ); ?></span>
		</a>
		<a href="#" onclick="toggleHelp();return false;" title="<?php echo AttrEscape( _( 'Help' ) ); ?>" class="BottomButton">
			<img src="<?php echo $btn_path; ?>help.png" alt="">
			<span><?php echo _( 'Help' ); ?></span>
		</a>
		<a href="<?php echo URLEscape( 'index.php?modfunc=logout&token=' . $_SESSION['token'] ); ?>" target="_top" title="<?php echo AttrEscape( _( 'Logout' ) ); ?>" class="BottomButton">
			<img src="<?php echo $btn_path; ?>logout.png" alt="">
			<span><?php echo _( 'Logout' ); ?></span>
		</a>
		<span class="loading BottomButton"></span>
	</div>

	<div id="footerhelp"><div class="footerhelp-content"></div></div>
<?php
// Print PDF.
elseif ( $_REQUEST['bottomfunc'] === 'print' ) :

	$_REQUEST = $_SESSION['_REQUEST_vars'];

	// Fix "Exclude PDF generated using the "Print" button" option for the PDF Header Footer plugin.
	$_REQUEST['bottomfunc'] = 'print';

	// Force search_modfunc to list.
	if ( Preferences( 'SEARCH' ) !== 'Y' )
	{
		$_REQUEST['search_modfunc'] = 'list';
	}
	elseif ( ! isset( $_REQUEST['search_modfunc'] ) )
	{
		$_REQUEST['search_modfunc'] = '';
	}

	if ( ! empty( $_REQUEST['expanded_view'] ) )
	{
		$_SESSION['orientation'] = 'landscape';
	}

	// FJ call PDFStart to generate Print PDF.
	$print_data = PDFStart();

	$modname = $_REQUEST['modname'];

	if ( ! $wkhtmltopdfPath )
	{
		$_ROSARIO['allow_edit'] = false;
	}

	// FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html.
	if ( mb_substr( $modname, -4, 4 ) !== '.php'
		|| mb_strpos( $modname, '..' ) !== false
		|| ! is_file( 'modules/' . $modname ) )
	{
		require_once 'ProgramFunctions/HackingLog.fnc.php';
		HackingLog();
	}
	else
		require_once 'modules/' . $modname;

	// FJ call PDFStop to generate Print PDF.
	PDFStop( $print_data );


// Inline Help.
elseif ( $_REQUEST['bottomfunc'] === 'help' ) :

	require_once 'ProgramFunctions/Help.fnc.php';

	$help_text = GetHelpText( $_REQUEST['modname'] );

	echo $help_text;

endif;

if ( isAJAX() )
{
	ETagCache( 'stop' );
}
