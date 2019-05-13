<?php

// Plugins configuration, included in Configuration.php

// Core plugins (packaged with RosarioSIS):
// Core plugins cannot be deleted
/* var defined in Warehouse.php
$RosarioCorePlugins = array(
'Moodle'
);*/

$directories_bypass = array();

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
		RedirectURL( array( 'modfunc', 'plugin' ) );
	}
}

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Plugin' ) ) )
	{
		//verify if not in $RosarioCorePlugins but in $RosarioPlugins

		if ( ! in_array( $_REQUEST['plugin'], $RosarioCorePlugins ) && in_array( $_REQUEST['plugin'], array_keys( $RosarioPlugins ) ) && $RosarioPlugins[$_REQUEST['plugin']] == false )
		{
			//delete plugin: execute delete.sql script

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
		RedirectURL( array( 'modfunc', 'plugin' ) );
	}
}

if ( $_REQUEST['modfunc'] == 'deactivate' && AllowEdit() )
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
			$error[] = _( 'Incomplete or inexistant plugin.' );
		}

		// Unset modfunc & plugin & redirect URL.
		RedirectURL( array( 'modfunc', 'plugin' ) );
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
			//install plugin: execute install.sql script

			if ( file_exists( 'plugins/' . $_REQUEST['plugin'] . '/install.sql' ) )
			{
				$install_sql = file_get_contents( 'plugins/' . $_REQUEST['plugin'] . '/install.sql' );
				DBQuery( $install_sql );
			}

			$update_RosarioPlugins = true;
		}
		else
		{
			$error[] = _( 'Incomplete or inexistant plugin.' );
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
		$error[] = _( 'Incomplete or inexistant plugin.' );
	}

	if ( $update_RosarioPlugins )
	{
		//update $RosarioPlugins
		$RosarioPlugins[$_REQUEST['plugin']] = true;

		//save $RosarioPlugins
		_saveRosarioPlugins();
	}

	// Unset modfunc & plugin & redirect URL.
	RedirectURL( array( 'modfunc', 'plugin' ) );
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	$plugins_RET = array( '' );

	foreach ( (array) $RosarioPlugins as $plugin_title => $activated )
	{
		$THIS_RET = array();
		$THIS_RET['DELETE'] = _makeDelete( $plugin_title, $activated );
		$THIS_RET['TITLE'] = _makeReadMe( $plugin_title, $activated );
		$THIS_RET['ACTIVATED'] = _makeActivated( $activated );
		$THIS_RET['CONFIGURATION'] = _makeConfiguration( $plugin_title, $activated );

		$plugins_RET[] = $THIS_RET;

		$directories_bypass[] = 'plugins/' . $plugin_title;
	}

	// scan plugins/ folder for uninstalled plugins
	$plugins = array_diff( glob( 'plugins/*', GLOB_ONLYDIR ), $directories_bypass );

	foreach ( $plugins as $plugin )
	{
		$plugin_title = str_replace( 'plugins/', '', $plugin );

		$THIS_RET = array();
		$THIS_RET['DELETE'] = _makeDelete( $plugin_title );
		$THIS_RET['TITLE'] = _makeReadMe( $plugin_title );
		$THIS_RET['ACTIVATED'] = _makeActivated( false );
		$THIS_RET['CONFIGURATION'] = _makeConfiguration( $plugin_title, false );

		$plugins_RET[] = $THIS_RET;
	}

	$columns = array(
		'DELETE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>',
		'TITLE' => _( 'Title' ),
		'ACTIVATED' => _( 'Activated' ),
		'CONFIGURATION' => _( 'Configuration' ),
	);

	unset( $plugins_RET[0] );

	ListOutput( $plugins_RET, $columns, 'Plugin', 'Plugins' );
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
		$return = '<a href="Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins&modfunc=config&plugin=' . $plugin_title . '">' . _( 'Configuration' ) . '</a>';
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
		$return = button( 'remove', _( 'Deactivate' ), '"Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins&modfunc=deactivate&plugin=' . $plugin_title . '"' );
	}
	else
	{
		if ( file_exists( 'plugins/' . $plugin_title . '/functions.php' ) )
		{
			$return = button(
				'add',
				_( 'Activate' ),
				'"Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins&modfunc=activate&plugin=' . $plugin_title . '"'
			);

			// If not core plugin & already installed, delete link.

			if ( ! in_array( $plugin_title, $RosarioCorePlugins )
				&& in_array( $plugin_title, array_keys( $RosarioPlugins ) ) )
			{
				$return .= '&nbsp;' .
				button(
					'remove',
					_( 'Delete' ),
					'"Modules.php?modname=' . $_REQUEST['modname'] . '&tab=plugins&modfunc=delete&plugin=' . $plugin_title . '"'
				);
			}
		}
		else
		{
			$return = '<span style="color:red">' .
			sprintf( _( '%s file missing or wrong permissions.' ), 'functions.php' ) . '</span>';
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

	$files = array_diff( scandir( $dir ), array( '.', '..' ) );

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
