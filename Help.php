<?php
/**
 * Help
 *
 * Generate the Help / Handbook PDF
 * Translated if locale/[code]/Help.php file exists
 * Based on user profile
 *
 * @package RosarioSIS
 */

require_once 'Warehouse.php';

require_once 'ProgramFunctions/Help.fnc.php';

$help = HelpLoad();

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

<style>.header2{ font-size: larger; }</style>
<table>
	<tr>
		<td>
			<img src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/logo.png" class="logo" />
		</td>
		<td>
			<h1>&nbsp;<?php echo sprintf( _( '%s Handbook' ), $title ); ?></h1>
		</td>
	</tr>
</table>
<hr />

<?php
$old_modcat = '';

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
			continue;
		}

		if ( $modcat != $old_modcat
			&& $modcat != 'Custom' ) : ?>

			<div style="page-break-after: always;"></div>

			<?php
				unset( $_ROSARIO['DrawHeader'] );

				$_ROSARIO['HeaderIcon'] = $modcat;

				$modcat_echo = str_replace( '_', ' ',  $modcat );

				DrawHeader( _( $modcat_echo ) );
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
			. sprintf( _( 'version %s' ), '2.0' );
	}
	else
		echo ( ProgramTitle() == 'RosarioSIS' ? $program : ProgramTitle() );
?>

	</h3>
	<table class="width-100p">
		<tr>
			<td class="header2">

<?php

	$help_text = GetHelpText( $program );

	echo $help_text;
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
