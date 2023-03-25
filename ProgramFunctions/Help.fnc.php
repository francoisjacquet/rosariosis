<?php
/**
 * Help functions
 * Mainly used in Help.php, Help_en.php & Bottom.php
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Gettext translation function for Help texts.
 * Registers domain on first run.
 * Adds "_help" suffix to add-ons domain.
 *
 * Add-ons, Poedit files: locale/[locale_code]/LC_MESSAGES/My_Addon_Folder_help.po
 * @example _help( 'My add-on help text.', 'My_Addon_Folder' );
 *
 * @since  3.9
 * @since  4.3 Moved from Help_en.php to ProgramFunctions/Help.fnc.php
 *
 * @uses HelpBindTextDomain()
 *
 * @param  string $text      Text to translate.
 * @param  string $domain    Gettext domain, defaults to 'help'. For add-ons, use the module / plugin name / folder.
 * @return string Translated help text.
 */
function _help( $text, $domain = 'help' )
{
	$bound = HelpBindTextDomain( $domain );

	if ( ! $bound )
	{
		return $text;
	}

	// Add "_help" suffix to add-ons domain.
	$addon_safe_domain = mb_strpos( $domain, 'help' ) === false ? $domain . '_help' : $domain;

	return dgettext( $addon_safe_domain, $text );
}


/**
 * Help bind text domain
 *
 * @since 4.3
 *
 * @param string $domain Help text domain / add-on folder.
 *
 * @return boolean True if text domain boud.
 */
function HelpBindTextDomain( $domain )
{
	global $LocalePath;

	static $domains_bound = [];

	$locale_path = $LocalePath;

	$addon = $domain;

	// Add "_help" suffix to add-ons domain.
	$domain = mb_strpos( $domain, 'help' ) === false ? $domain . '_help' : $domain;

	if ( isset( $domains_bound[$domain] ) )
	{
		return $domains_bound[$domain];
	}

	if ( $addon !== 'help' )
	{
		$locale_path = 'modules/' . $addon . '/locale';

		if ( ! file_exists( $locale_path ) )
		{
			// Is plugin?
			$locale_path = 'plugins/' . $addon . '/locale';

			if ( ! file_exists( $locale_path ) )
			{
				$domains_bound[$domain] = false;

				return false;
			}
		}
	}

	// Binds the messages domain to the locale folder.
	bindtextdomain( $domain, $locale_path );

	// Sets the domain name, this means gettext will be looking for a file called Addon_help.mo.
	textdomain( $domain );

	// Ensures text returned is utf-8, quite often this is iso-8859-1 by default.
	bind_textdomain_codeset( $domain, 'UTF-8' );

	$domains_bound[$domain] = true;

	return true;
}



/**
 * Load Help
 * English, translated (locale) & non core modules help texts
 *
 * @since 4.3
 *
 * @param boolean $force Force loading help if was already loaded.
 *
 * @return array $help
 */
function HelpLoad( $force = false )
{
	static $help_loaded = false;

	global $RosarioModules,
		$RosarioCoreModules,
		$help,
		$locale;

	if ( $help_loaded
		&& ! $force )
	{
		return $help;
	}

	require_once 'Help_en.php';

	// Add help for non-core modules.
	$non_core_modules = array_diff( array_keys( $RosarioModules ), $RosarioCoreModules );

	$help_english = 'Help_en.php';

	// @deprecated since 3.9 use help text domain: help.po Gettext files.
	$help_translated = 'Help_' . substr( $locale, 0, 2 ) . '.php';

	foreach ( $non_core_modules as $non_core_module )
	{
		if ( ! $RosarioModules[ $non_core_module ] )
		{
			// Module is not activated, skip.
			continue;
		}

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

	$help_loaded = true;

	return $help;
}


/**
 * Get Help text for program (modname)
 * Defaults to 'default' and formats Help:
 * - Replace 'your child' with 'you' & 'your child\'s' with 'your' for students
 * - Replace 'RosarioSIS' with configured app name.
 *
 * @since 4.3
 *
 * @uses GetHelpTextRaw()
 *
 * @param string $modname Program, typically $_REQUEST['modname'].
 *
 * @return string Help text.
 */
function GetHelpText( $modname )
{
	$help_text = GetHelpTextRaw( $modname );

	// Get default help text.
	if ( empty( $help_text ) )
	{
		$help = HelpLoad();

		$help_text = $help['default'];
	}

	if ( User( 'PROFILE' ) === 'student' )
	{
		$help_text = str_replace(
			[ 'your child\'s', 'your child' ],
			[ 'your', 'yourself' ],
			$help_text
		);
	}

	// Replace RosarioSIS with configured app name.
	$help_text = str_replace( 'RosarioSIS', Config( 'NAME' ), $help_text );

	return $help_text;
}



/**
 * Get raw Help text for program (modname)
 * No default and no formatting.
 *
 * @since 4.3
 *
 * @uses HelpLoad()
 *
 * @example $program_has_help_text = GetHelpTextRaw( $_REQUEST['modname'] );
 *
 * @param string $modname Program, typically $_REQUEST['modname'].
 *
 * @return string Help text.
 */
function GetHelpTextRaw( $modname )
{
	$help = HelpLoad();

	if ( empty( $modname ) )
	{
		return '';
	}

	if ( ! empty( $help[ $modname ] ) )
	{
		return $help[ $modname ];
	}

	foreach ( $help as $program => $help_txt )
	{
		// FJ fix bug URL Modules.php?modname=Student_Billing/Statements.php&_ROSARIO_PDF.
		if ( ( mb_strpos( $program, $modname ) === 0
				&& mb_strpos( $_SERVER['QUERY_STRING'], $program ) === 21 ) )
		{
			return $help_txt;
		}
	}

	return '';
}
