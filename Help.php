<?php
/**
 * Help
 *
 * Generate the Help / Handbook PDF
 * Translated if Help_[2_letters_locale].php file exists
 * Based on user profile
 *
 * @package RosarioSIS
 */

require_once 'Warehouse.php';

$help_translated = 'Help_' . mb_substr( $locale, 0, 2 ) . '.php';
$help_english = 'Help_en.php';

if ( file_exists( $help_translated ) ) // FJ translated help.
{
	require_once $help_translated;
}
else
	require_once $help_english;

// FJ add help for non-core modules.
$non_core_modules = array_diff( array_keys( $RosarioModules ), $RosarioCoreModules );

foreach ( (array) $non_core_modules as $non_core_module )
{
	$non_core_dir = 'modules/' . $non_core_module . '/';

	if ( file_exists( $non_core_dir . $help_translated ) ) // FJ translated help.
	{
		require_once $non_core_dir . $help_translated;
	}
	elseif ( file_exists( $non_core_dir . $help_english ) )
	{
		require_once $non_core_dir . $help_english;
	}
}

switch ( User( 'PROFILE' ) )
{
	case 'admin':

		$title = _( 'Administrator' );
	break;

	case 'teacher':

		$title = _( 'Teacher' );
	break;

	case 'parent':

		$title = _( 'Parent' );
	break;

	case 'student':

		$title = _( 'Student' );
	break;
}

$handle = PDFStart(); ?>

<table>
	<tr>
		<td>
			<img src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/logo.png" />
		</td>
		<td>
			<h1>&nbsp;<?php echo sprintf( _( '%s Handbook' ), $title ); ?></h1>
		</td>
	</tr>
</table>
<hr />

<?php
foreach ( (array) $help as $program => $value ) :

	// FJ zap programs which are not allowed.
	if ( $program !== 'default'
		&& ! AllowUse( $program ) )
	{
		continue;
	}

	$_REQUEST['modname'] = $program;

	if ( mb_strpos( $program, '/' ) )
	{
		$modcat = mb_substr( $program, 0, mb_strpos( $program, '/' ) );

		if ( ! $RosarioModules[ $modcat ] ) // Module not activated.
		{
			break;
		}

		if ( $modcat != $old_modcat
			&& $modcat != 'Custom' ) : ?>

			<div style="page-break-after: always;"></div>

			<?php
				unset( $_ROSARIO['DrawHeader'] );

				$_ROSARIO['HeaderIcon'] = 'modules/' . $modcat . '/icon.png';

				$modcat_echo = str_replace( '_', ' ',  $modcat );

				echo DrawHeader( _( $modcat_echo ) );
			?>
			<hr />

		<?php
		endif;

		if ( $modcat != 'Custom' )
		{
			$old_modcat = $modcat;
		}
	}
?>

<div style="page-break-inside: avoid;">
	<h3>

<?php
	if ( $program == 'default' )
	{
		echo ParseMLField( Config( 'TITLE' ) )
			. ' - ' . sprintf( _( '%s Handbook' ), $title ) . '<br />'
			. sprintf( _( 'version %s' ), '1.1' );
	}
	else
		echo ( ProgramTitle() == 'RosarioSIS' ? $program : ProgramTitle() );
?>

	</h3>
	<table class="width-100p">
		<tr>
			<td class="header2">

<?php
	if ( User( 'PROFILE' ) == 'student' )
	{
		$value = str_replace(
			'your child',
			'yourself',
			str_replace( 'your child\'s', 'your', $value )
		);
	}

	$value = str_replace( 'RosarioSIS', Config( 'NAME' ), $value );

	echo $value;
?>

			</td>
		</tr>
	</table>
</div>
<br />

<?php endforeach; ?>

<div class="center">
	<b><a href="https://www.rosariosis.org/">https://www.rosariosis.org/</a></b>
</div>

<?php

$_REQUEST['modname'] = '';

PDFStop( $handle );
