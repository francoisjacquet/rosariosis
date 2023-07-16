<?php

// Plugins configuration, included in Configuration.php

// Core plugins (packaged with RosarioSIS):
// Core plugins cannot be deleted
/* var defined in Warehouse.php
$RosarioCorePlugins = array(
'Moodle'
);*/

$directories_bypass = [];

//hacking protections

if ( isset( $_REQUEST['plugin'] ) && strpos( $_REQUEST['plugin'], '..' ) !== false )
{
	require_once 'ProgramFunctions/HackingLog.fnc.php';
	HackingLog();
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
			_saveRosarioPlugins();

			if ( is_dir( 'plugins/' . $_REQUEST['plugin'] ) )
			{
				//remove files & dir

				if ( ! _delTree( 'plugins/' . $_REQUEST['plugin'] ) )
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
		// Extract zip file.
		$zip = new ZipArchive;
		$zip_open = $zip->open( $addon_zip_path );

		if ( $zip_open === true )
		{
			$zip->extractTo( $FileUploadsPath . 'upload-plugin/' );

			$zip->close();

			$addon_dir_path = glob( $FileUploadsPath . 'upload-plugin/*', GLOB_ONLYDIR );

			$addon_dir_path = reset( $addon_dir_path );

			// Remove path.
			$addon_dir = str_replace( $FileUploadsPath . 'upload-plugin/', '', $addon_dir_path );

			if ( mb_substr( $addon_dir, -7, 7 ) === '-master' )
			{
				// Remove trailing '-master'.
				$addon_dir = mb_substr( $addon_dir, 0, mb_strlen( $addon_dir ) -7 );
			}

			// Check add-on is not a core plugin...
			if ( ! in_array( $addon_dir, $RosarioCorePlugins ) )
			{
				if ( ! file_exists( 'plugins/' . $addon_dir )
					|| _delTree( 'plugins/' . $addon_dir ) )
				{
					// Remove warning if directory already exists: just overwrite.
					rename( $addon_dir_path, 'plugins/' . $addon_dir );

					$note[] = _( 'Add-on successfully uploaded.' );
				}
				else
				{
					$error[] = sprintf( _( 'Folder not writable' ) . ': %s', 'plugins/' . $addon_dir );
				}
			}

			_delTree( $FileUploadsPath . 'upload-plugin/' );
		}
		else
		{
			$error[] = _( 'Cannot open file.' );
		}

		unlink( $addon_zip_path );
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
			_saveRosarioPlugins();
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

	//verify not already in $RosarioPlugins

	if ( ! in_array( $_REQUEST['plugin'], array_keys( $RosarioPlugins ) ) )
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

			// @since 10.9.3 Add-on SQL translation file can be named "install_es.sql" or "install_pt_BR.sql"
			$install_locale_paths = [
				'plugins/' . $_REQUEST['plugin'] . '/install_' . mb_substr( $locale, 0, 2 ) . '.sql',
				'plugins/' . $_REQUEST['plugin'] . '/install_' . mb_substr( $locale, 0, 5 ) . '.sql',
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

			$update_RosarioPlugins = true;
		}
		else
		{
			$error[] = _( 'Incomplete or nonexistent plugin.' );
		}
	}

	//verify in $RosarioPlugins
	elseif ( $RosarioPlugins[$_REQUEST['plugin']] == false && is_dir( 'plugins/' . $_REQUEST['plugin'] ) )
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
		_saveRosarioPlugins();
	}

	// Unset modfunc & plugin & redirect URL.
	RedirectURL( [ 'modfunc', 'plugin' ] );
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	$plugins_RET = [ '' ];

	foreach ( (array) $RosarioPlugins as $plugin_title => $activated )
	{
		$THIS_RET = [];
		$THIS_RET['DELETE'] = _makeDelete( $plugin_title, $activated );
		$THIS_RET['TITLE'] = _makeReadMe( $plugin_title, $activated );
		$THIS_RET['ACTIVATED'] = _makeActivated( $activated );
		$THIS_RET['CONFIGURATION'] = _makeConfiguration( $plugin_title, $activated );

		$plugins_RET[] = $THIS_RET;

		$directories_bypass[] = 'plugins/' . $plugin_title;
	}

	// Scan plugins/ folder for uninstalled plugins.
	$plugins = array_diff( glob( 'plugins/*', GLOB_ONLYDIR ), $directories_bypass );

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

		$plugin_title = str_replace( 'plugins/', '', $plugin );

		$THIS_RET = [];
		$THIS_RET['DELETE'] = _makeDelete( $plugin_title );
		$THIS_RET['TITLE'] = _makeReadMe( $plugin_title );
		$THIS_RET['ACTIVATED'] = _makeActivated( false );
		$THIS_RET['CONFIGURATION'] = _makeConfiguration( $plugin_title, false );

		$plugins_RET[] = $THIS_RET;
	}

	$columns = [
		'DELETE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>',
		'TITLE' => _( 'Title' ),
		'ACTIVATED' => _( 'Activated' ),
		'CONFIGURATION' => _( 'Configuration' ),
	];

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
 * @param $activated
 * @return mixed
 */
function _makeActivated( $activated )
{
	if ( $activated )
	{
		$return = button( 'check' );
	}
	else
	{
		$return = button( 'x' );
	}

	if ( isset( $_REQUEST['LO_save'] ) )
	{
		if ( $activated )
		{
			$return = _( 'Yes' );
		}
		else
		{
			$return = _( 'No' );
		}
	}

	return $return;
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

/**
 * @param $plugin_title
 * @param $activated
 * @return mixed
 */
function _makeReadMe( $plugin_title, $activated = null )
{
	global $RosarioCorePlugins;

	//format & translate plugin title

	if ( ! in_array( $plugin_title, $RosarioCorePlugins ) && $activated )
	{
		$plugin_title_echo = dgettext( $plugin_title, str_replace( '_', ' ', $plugin_title ) );
	}
	else
	{
		$plugin_title_echo = _( str_replace( '_', ' ', $plugin_title ) );
	}

	$readme_path = 'plugins/' . $plugin_title . '/README';

	//if README.md file, display in Colorbox

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& ( file_exists( $readme_path )
			|| (  ( $readme_path = $readme_path . '.md' )
				&& file_exists( $readme_path ) ) ) )
	{
		//get README.md content
		$readme_content = file_get_contents( $readme_path );

		// Convert MarkDown text to HTML.
		$readme_content = '<div class="markdown-to-html">' . $readme_content . '</div>';

		$return = '<div style="display:none;"><div id="README_' . $plugin_title . '">' .
			$readme_content . '</div></div>';

		$return .= '<a class="colorboxinline" href="#README_' . $plugin_title . '">' .
			$plugin_title_echo . '</a>';
	}
	else
	{
		$return = $plugin_title_echo;
	}

	return $return;
}

function _saveRosarioPlugins()
{
	global $RosarioPlugins;

	$PLUGINS = DBEscapeString( serialize( $RosarioPlugins ) );

	DBQuery( "UPDATE config SET config_value='" . $PLUGINS . "' WHERE title='PLUGINS'" );

	return true;
}

/**
 * Delete Tree
 * Recursively delete a directory and its files.
 *
 * If one of the files cannot be deleted,
 * no files are deleted & `false` is returned.
 * Dry run is always performed first.
 *
 * @param  string  $dir  Directory to delete.
 * @param  string  $mode delete|dryrun Mode (optional). Defaults to 'delete'.
 * @return boolean true on success, else false.
 */
function _delTree( $dir, $mode = 'delete' )
{
	$return = true;

	if ( $mode === 'delete' )
	{
		// Run dry run mode first.
		$can_delete = _delTree( $dir, 'dryrun' );

		if ( ! $can_delete )
		{
			return false;
		}
	}

	$files = array_diff( scandir( $dir ), [ '.', '..' ] );

	foreach ( (array) $files as $file )
	{
		if ( is_dir( $dir . '/' . $file ) )
		{
			$return = _delTree( $dir . '/' . $file, $mode );
		}
		elseif ( is_writable( $dir . '/' . $file ) )
		{
			if ( $mode !== 'dryrun' )
			{
				unlink( $dir . '/' . $file );
			}
		}
		else
		{
			return false;
		}
	}

	return $mode === 'dryrun' ? $return && is_writable( $dir ) : rmdir( $dir );
}
