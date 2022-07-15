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
require_once 'Menu.php';

require_once 'ProgramFunctions/Help.fnc.php';

$profiles = [
	'admin' => _( 'Administrator' ),
	'teacher' => _( 'Teacher' ),
	'parent' => _( 'Parent' ),
	'student' => _( 'Student' ),
];

$title = $profiles[ User( 'PROFILE' ) ];

$handle = PDFStart(); ?>

<style>.header2{ font-size: larger; }</style>
<div class="header1">
	<h2><img src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/logo.png" class="module-icon" />
		<?php echo sprintf( _( '%s Handbook' ), $title ); ?></h2>
</div>

<?php
$help = HelpLoad();

$old_modcat = '';

$non_core_modules = array_diff_key( $RosarioModules, array_flip( $RosarioCoreModules ) );

foreach ( (array) $help as $program => $value ) :

	// Zap programs which are not allowed.
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

				$modcat_title = _( str_replace( '_', ' ',  $modcat ) );

				if ( in_array( $modcat, $non_core_modules ) )
				{
					$modcat_title = dgettext( $modcat, str_replace( '_', ' ',  $modcat ) );
				}

				if ( ! empty( $_ROSARIO['Menu'][ $modcat ]['title'] ) )
				{
					$modcat_title = $_ROSARIO['Menu'][ $modcat ]['title'];
				}

				DrawHeader( $modcat_title );
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
		echo ParseMLField( Config( 'TITLE' ) ) . ' ' . ROSARIO_VERSION;
	}
	else
		echo ( ProgramTitle() == 'RosarioSIS' ? $program : ProgramTitle() );
?>

	</h3>
	<div class="header2">

<?php

	$help_text = GetHelpText( $program );

	echo $help_text;
?>

	</div>
</div>
<br />

<?php endforeach; ?>

<div class="center">
	<b><a href="https://www.rosariosis.org/">https://www.rosariosis.org/</a></b>
</div>

<?php

$_REQUEST['modname'] = '';

PDFStop( $handle );
