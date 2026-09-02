<?php

require_once 'modules/School_Setup/includes/Addon.fnc.php';

// Plugins configuration, included in Configuration.php

// Core plugins (packaged with RosarioSIS):
// Core plugins cannot be deleted
/* var defined in Warehouse.php
$RosarioCorePlugins = array(
'Content_Security_Policy',
'Moodle'
);*/

$directories_bypass = [];

//hacking protections

if ( isset( $_REQUEST['plugin'] ) && strpos( $_REQUEST['plugin'], '..' ) !== false )
{
	(new RosarioSIS\Functions\Hacking)->log();
}

if ( $_REQUEST['modfunc'] === 'config' )
{
	//if the plugin is activated, show configuration (call the plugin's config.inc.php file)

	if ( in_array( $_REQUEST['plugin'], array_keys( $RosarioPlugins ) ) && $RosarioPlugins[$_REQUEST['plugin']] == true && file_exists( 'plugins/' . $_REQUEST['plugin'] . '/config.inc.php' ) )
	{
		require_once 'plugins/' . $_REQUEST['plugin'] . '/config.inc.php';
	}
	else
	{
		// Unset modfunc & plugin & redirect URL.
		RedirectURL( [ 'modfunc', 'plugin' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Plugin' ) ) )
	{
		// @since 8.0 Add-on disable delete.
		$can_delete = ! defined( 'ROSARIO_DISABLE_ADDON_DELETE' ) || ! ROSARIO_DISABLE_ADDON_DELETE;

		// Verify if not in $RosarioCorePlugins but in $RosarioPlugins.
		$can_delete = $can_delete
			&& ! in_array( $_REQUEST['plugin'], $RosarioCorePlugins )
			&& in_array( $_REQUEST['plugin'], array_keys( $RosarioPlugins ) )
			&& $RosarioPlugins[$_REQUEST['plugin']] == false;

		if ( $can_delete )
		{
			// Delete plugin: execute delete.sql script.
			if ( file_exists( 'plugins/' . $_REQUEST['plugin'] . '/delete.sql' ) )
			{
				$delete_sql = file_get_contents( 'plugins/' . $_REQUEST['plugin'] . '/delete.sql' );
				DBQuery( $delete_sql );
			}

			//update $RosarioPlugins
			unset( $RosarioPlugins[$_REQUEST['plugin']] );

			//save $RosarioPlugins
			Config( 'PLUGINS', serialize( $RosarioPlugins ) );

			if ( is_dir( 'plugins/' . $_REQUEST['plugin'] ) )
			{
				//remove files & dir

				if ( ! AddonDelTree( 'plugins/' . $_REQUEST['plugin'] ) )
				{
					$error[] = _( 'Files not eraseable.' );
				}
			}
		}

		// Unset modfunc & plugin & redirect URL.
		RedirectURL( [ 'modfunc', 'plugin' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'upload'
	&& AllowEdit() )
{
	// @since 6.4 Add-on zip upload.
	if ( ( ! defined( 'ROSARIO_DISABLE_ADDON_UPLOAD' )
			|| ! ROSARIO_DISABLE_ADDON_UPLOAD )
		&& class_exists( 'ZipArchive' )
		&& ( $addon_zip_path = FileUpload( 'upload', $FileUploadsPath, [ '.zip' ], FileUploadMaxSize(), $error ) ) )
	{
		$extract_to_path = $FileUploadsPath . 'upload-plugin/';

		$addon_dir_path = AddonUnzip( 'plugin', $addon_zip_path, $extract_to_path );

		if ( $addon_dir_path )
		{
			// Remove path.
			$addon_dir = str_replace( $extract_to_path, '', $addon_dir_path );

			if ( mb_substr( $addon_dir, -7, 7 ) === '-master' )
			{
				// Remove trailing '-master'.
				$addon_dir = mb_substr( $addon_dir, 0, mb_strlen( $addon_dir ) -7 );
			}

			if ( mb_substr( $addon_dir, -5, 5 ) === '-main' )
			{
				// Remove trailing '-main'.
				$addon_dir = mb_substr( $addon_dir, 0, mb_strlen( $addon_dir ) -5 );
			}

			// Check add-on is not a core plugin...
			if ( ! in_array( $addon_dir, $RosarioCorePlugins ) )
			{
				if ( ! file_exists( 'plugins/' . $addon_dir )
					|| AddonDelTree( 'plugins/' . $addon_dir ) )
				{
					/**
					 * Remove warning for directories across filesystems or devices
					 *
					 * @link https://www.php.net/manual/en/function.rename.php#113943
					 */
					if ( ! @rename( $addon_dir_path, 'plugins/' . $addon_dir ) )
					{
						/**
						 * Workaround: exec mv (move on Windows)
						 *
						 * @link https://bugs.php.net/bug.php?id=54097
						 */
						$move_cmd = stripos( PHP_OS, 'WIN' ) === 0 ? 'move' : 'mv';

						exec( $move_cmd . ' ' . escapeshellarg( $addon_dir_path ) . ' ' .
							escapeshellarg( 'plugins/' . $addon_dir ) );
					}

					$note[] = button( 'check' ) . '&nbsp;' . _( 'Add-on successfully uploaded.' );
				}
				else
				{
					$error[] = sprintf( _( 'Folder not writable' ) . ': %s', 'plugins/' . $addon_dir );
				}
			}
		}

		AddonDelTree( $extract_to_path );

		FileDelete( $addon_zip_path, '.zip' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] == 'deactivate'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Plugin' ), _( 'Deactivate' ) ) )
	{
		//verify if activated

		if ( in_array( $_REQUEST['plugin'], array_keys( $RosarioPlugins ) ) && $RosarioPlugins[$_REQUEST['plugin']] == true )
		{
			//update $RosarioPlugins
			$RosarioPlugins[$_REQUEST['plugin']] = false;

			//save $RosarioPlugins
			Config( 'PLUGINS', serialize( $RosarioPlugins ) );
		}

		//verify plugin dir exists

		if ( ! is_dir( 'plugins/' . $_REQUEST['plugin'] ) || ! file_exists( 'plugins/' . $_REQUEST['plugin'] . '/functions.php' ) )
		{
			$error[] = _( 'Incomplete or nonexistent plugin.' );
		}

		// Unset modfunc & plugin & redirect URL.
		RedirectURL( [ 'modfunc', 'plugin' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'activate'
	&& AllowEdit() )
{
	$update_RosarioPlugins = false;

	// @since 12.2 Error if add-on folder has "-master" or "-main" suffix
	if ( mb_substr( $_REQUEST['plugin'], -7, 7 ) === '-master' )
	{
		$error[] = sprintf(
			_( 'Please rename the add-on directory: remove the "%s" suffix.' ),
			'-master'
		);
	}
	elseif ( mb_substr( $_REQUEST['plugin'], -5, 5 ) === '-main' )
	{
		$error[] = sprintf(
			_( 'Please rename the add-on directory: remove the "%s" suffix.' ),
			'-main'
		);
	}
	else
	{
		// @since 13.0 Check add-on requirements
		(new RosarioSIS\Functions\AddonInfo( 'plugin', $_REQUEST['plugin'] ))->checkRequirements();
	}

	//verify not already in $RosarioPlugins

	if ( ! $error
		&& ! in_array( $_REQUEST['plugin'], array_keys( $RosarioPlugins ) ) )
	{
		//verify directory exists

		if ( file_exists( 'plugins/' . $_REQUEST['plugin'] . '/functions.php' ) )
		{
			// Install plugin: execute install.sql script
			$install_sql_file = 'plugins/' . $_REQUEST['plugin'] . '/install.sql';

			if ( $DatabaseType === 'mysql' )
			{
				// @since 10.0 Install plugin: execute the install_mysql.sql script for MySQL
				$install_sql_file = 'plugins/' . $_REQUEST['plugin'] . '/install_mysql.sql';

				// @since 10.4.3 MySQL always use InnoDB (default), avoid MyISAM
				DBQuery( "SET default_storage_engine=InnoDB;" );
			}

			if ( file_exists( $install_sql_file ) )
			{
				$install_sql = file_get_contents( $install_sql_file );

				if ( $DatabaseType === 'mysql' )
				{
					// @since 10.0 Remove DELIMITER $$ declarations before procedures or functions.
					$install_sql = MySQLRemoveDelimiter( $install_sql );
				}

				DBQuery( $install_sql );
			}

			$install_locale_paths = [
				// @since 10.9.3 Add-on SQL translation file can be named "install_es.sql" or "install_pt_BR.sql"
				'plugins/' . $_REQUEST['plugin'] . '/install_' . mb_substr( $locale, 0, 2 ) . '.sql',
				'plugins/' . $_REQUEST['plugin'] . '/install_' . mb_substr( $locale, 0, 5 ) . '.sql',
				// @since 12.1 Add-on SQL translation file can be under `locale/[locale_code]/` folder
				'plugins/' . $_REQUEST['plugin'] . '/locale/' . $locale . '/install.sql',
			];

			foreach ( $install_locale_paths as $install_locale_path )
			{
				if ( file_exists( $install_locale_path ) )
				{
					// @since 7.3 Translate database on add-on install: run 'install_fr.sql' file.
					$install_locale_sql = file_get_contents( $install_locale_path );

					DBQuery( $install_locale_sql );

					break;
				}
			}

			AddonInstallationStatisticsPost( 'plugin', $_REQUEST['plugin'] );

			$update_RosarioPlugins = true;
		}
		else
		{
			$error[] = _( 'Incomplete or nonexistent plugin.' );
		}
	}

	//verify in $RosarioPlugins
	elseif ( ! $error
		&& $RosarioPlugins[$_REQUEST['plugin']] == false && is_dir( 'plugins/' . $_REQUEST['plugin'] ) )
	{
		$update_RosarioPlugins = true;
	}

	//no plugin dir
	elseif ( ! file_exists( 'plugins/' . $_REQUEST['plugin'] . '/functions.php' ) )
	{
		$error[] = _( 'Incomplete or nonexistent plugin.' );
	}

	if ( $update_RosarioPlugins )
	{
		//update $RosarioPlugins
		$RosarioPlugins[$_REQUEST['plugin']] = true;

		//save $RosarioPlugins
		Config( 'PLUGINS', serialize( $RosarioPlugins ) );
	}

	// Unset modfunc & plugin & redirect URL.
	RedirectURL( [ 'modfunc', 'plugin' ] );
}

if ( $_REQUEST['modfunc'] === 'installation_statistics_post'
	&& AllowEdit() )
{
	ob_clean();

	if ( ! empty( $_REQUEST['addon_dir'] ) )
	{
		// cURL POST in the background (AJAX request)
		AddonInstallationStatisticsPost( 'plugin', $_REQUEST['addon_dir'] );
	}

	exit;
}

$check_updates = false;

if ( $_REQUEST['modfunc'] === 'check_updates'
	&& AllowEdit() )
{
	// @since 13.0 Check for add-on updates
	$check_updates = true;

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( ! $_REQUEST['modfunc'] )
{
	$plugins_RET = [ '' ];

	foreach ( (array) $RosarioPlugins as $plugin_title => $activated )
	{
		$THIS_RET = [];
		$THIS_RET['DELETE'] = _makeDelete( $plugin_title, $activated );
		$THIS_RET['TITLE'] = AddonMakeReadMe( 'plugin', $plugin_title, $activated );
		$THIS_RET['ACTIVATED'] = AddonMakeActivated( $activated );
		$THIS_RET['CONFIGURATION'] = _makeConfiguration( $plugin_title, $activated );
		$THIS_RET['UPDATE_AVAILABLE'] = '';

		if ( ! in_array( $plugin_title, $RosarioCorePlugins ) )
		{
			if ( $check_updates )
			{
				// @since 13.0 Check for add-on updates
				$THIS_RET['UPDATE_AVAILABLE'] = AddonMakeUpdateAvailable( 'plugin', $plugin_title );
			}
		}

		$plugins_RET[] = $THIS_RET;

		$directories_bypass[] = 'plugins/' . $plugin_title;
	}

	// Scan plugins/ folder for uninstalled plugins.
	$plugins = array_diff( glob( 'plugins/*', GLOB_ONLYDIR ), $directories_bypass );

	$has_non_core_plugins = ( count( $RosarioCorePlugins ) < count( $RosarioPlugins ) ) || $plugins;

	$check_updates_link = '';

	if ( $has_non_core_plugins
		&& strpos( $_SERVER['HTTP_HOST'], '.rosariosis.com' ) === false
		&& AllowEdit() )
	{
		// @since 13.0 Check for add-on updates
		$check_updates_link = '<a href="Modules.php?modname=School_Setup/Configuration.php&tab=plugins&modfunc=check_updates">' .
			_( 'Check for updates' ) . '</a>';
	}

	$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

	$plugins_url = 'https://www.rosariosis.org/' .
		( $lang_2_chars === 'es' || $lang_2_chars === 'fr' ? $lang_2_chars . '/' : '' ) .
		'plugins/';

	DrawHeader(
		'<a href="' . $plugins_url . '" target="_blank" rel="noreferrer">' . $plugins_url .  '</a>',
		$check_updates_link
	);

	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );

	foreach ( $plugins as $plugin )
	{
		if ( mb_substr( $plugin, -7, 7 ) === '-master'
			&& is_writable( $plugin ) )
		{
			// @since 11.0.2 Remove "-master" suffix from manually uploaded add-ons
			$plugin_without_master = mb_substr( $plugin, 0, mb_strlen( $plugin ) -7 );

			if ( ! file_exists( $plugin_without_master )
				&& @rename( $plugin, $plugin_without_master ) )
			{
				$plugin = $plugin_without_master;
			}
		}

		if ( mb_substr( $plugin, -5, 5 ) === '-main'
			&& is_writable( $plugin ) )
		{
			// @since 12.0.1 Remove "-main" suffix from manually uploaded add-ons
			$plugin_without_main = mb_substr( $plugin, 0, mb_strlen( $plugin ) -5 );

			if ( ! file_exists( $plugin_without_main )
				&& @rename( $plugin, $plugin_without_main ) )
			{
				$plugin = $plugin_without_main;
			}
		}

		$plugin_title = str_replace( 'plugins/', '', $plugin );

		$THIS_RET = [];
		$THIS_RET['DELETE'] = _makeDelete( $plugin_title );
		$THIS_RET['TITLE'] = AddonMakeReadMe( 'plugin', $plugin_title );
		$THIS_RET['ACTIVATED'] = AddonMakeActivated( false );
		$THIS_RET['CONFIGURATION'] = _makeConfiguration( $plugin_title, false );

		if ( $check_updates )
		{
			// @since 13.0 Check for add-on updates
			$THIS_RET['UPDATE_AVAILABLE'] = AddonMakeUpdateAvailable( 'plugin', $plugin_title );
		}

		$plugins_RET[] = $THIS_RET;
	}

	$columns = [
		'DELETE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>',
		'TITLE' => _( 'Title' ),
		'ACTIVATED' => _( 'Activated' ),
		'CONFIGURATION' => _( 'Configuration' ),
	];

	if ( $check_updates )
	{
		$columns['UPDATE_AVAILABLE'] = _( 'Update available' );
	}

	unset( $plugins_RET[0] );

	ListOutput( $plugins_RET, $columns, 'Plugin', 'Plugins' );

	if ( ( ! defined( 'ROSARIO_DISABLE_ADDON_UPLOAD' )
			|| ! ROSARIO_DISABLE_ADDON_UPLOAD )
		&& class_exists( 'ZipArchive' )
		&& is_writable( $FileUploadsPath )
		&& is_writable( 'plugins/' ) )
	{
		// @since 6.4 Add-on zip upload.
		echo '<br /><form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=' . $_REQUEST['tab'] .
			'&modfunc=upload' ) . '" method="POST" enctype="multipart/form-data">';

		echo FileInput( 'upload', '', 'required accept=".zip"' );

		echo SubmitButton( _( 'Upload' ), '', '' );

		echo FormatInputTitle(
			button( 'add', '', '', 'smaller' ) . ' ' . _( 'Plugin' ) . ' (.zip)',
			'upload'
		);

		echo '</form>';
	}
}


/**
 * @param $plugin_title
 * @param $activated
 * @return mixed
 */
function _makeConfiguration( $plugin_title, $activated )
{
	//verify plugin is activated & config.inc.php file exists

	if ( $activated && file_exists( 'plugins/' . $plugin_title . '/config.inc.php' ) )
	{
		$return = '<a href="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins&modfunc=config&plugin=' . $plugin_title ) . '">' . _( 'Configuration' ) . '</a>';
	}
	else
	{
		$return = '';
	}

	return $return;
}

/**
 * @param $plugin_title
 * @param $activated
 * @return mixed
 */
function _makeDelete( $plugin_title, $activated = null )
{
	global $RosarioPlugins, $RosarioCorePlugins;

	$return = '';

	if ( ! AllowEdit() )
	{
		return $return;
	}

	if ( $activated )
	{
		$return = button(
			'remove',
			_( 'Deactivate' ),
			URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins&modfunc=deactivate&plugin=' . $plugin_title )
		);
	}
	else
	{
		if ( file_exists( 'plugins/' . $plugin_title . '/functions.php' )
			&& ! file_exists( 'plugins/' . $plugin_title . '/Menu.php' ) )
		{
			$return = button(
				'add',
				_( 'Activate' ),
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins&modfunc=activate&plugin=' . $plugin_title )
			);

			// @since 8.0 Add-on disable delete.
			$can_delete = ! defined( 'ROSARIO_DISABLE_ADDON_DELETE' ) || ! ROSARIO_DISABLE_ADDON_DELETE;

			// If not core plugin & already installed, delete link.
			$can_delete = $can_delete
				&& ! in_array( $plugin_title, $RosarioCorePlugins )
				&& in_array( $plugin_title, array_keys( $RosarioPlugins ) );

			if ( $can_delete )
			{
				$return .= '&nbsp;' .
				button(
					'remove',
					_( 'Delete' ),
					URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins&modfunc=delete&plugin=' . $plugin_title )
				);
			}
		}
		else
		{
			$error_msg = sprintf( _( '%s file missing or wrong permissions.' ), 'functions.php' );

			if ( file_exists( 'plugins/' . $plugin_title . '/Menu.php' ) )
			{
				$error_msg = _( 'Probably a module. Move it to the modules/ folder.' );
			}

			$return = '<span style="color:red">' . $error_msg . '</span>';
		}
	}

	return $return;
}
