<?php

// Modules configuration, included in Configuration.php

// Core modules (packaged with RosarioSIS):
// Core modules cannot be deleted
/* var defined in Warehouse.php
$RosarioCoreModules = array(
'School_Setup',
'Students',
'Users',
'Scheduling',
'Grades',
'Attendance',
'Eligibility',
'Discipline',
'Accounting',
'Student_Billing',
'Food_Service',
'Resources',
'Custom'
);*/

// Core modules that will generate errors if deactivated
$always_activated = [
	'School_Setup',
];

$directories_bypass = [
	'modules/misc',
];

//hacking protections

if ( isset( $_REQUEST['module'] ) && strpos( $_REQUEST['module'], '..' ) !== false )
{
	require_once 'ProgramFunctions/HackingLog.fnc.php';
	HackingLog();
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
			$zip->extractTo( $FileUploadsPath . 'upload-module/' );

			$zip->close();

			$addon_dir_path = glob( $FileUploadsPath . 'upload-module/*', GLOB_ONLYDIR );

			$addon_dir_path = reset( $addon_dir_path );

			// Remove path.
			$addon_dir = str_replace( $FileUploadsPath . 'upload-module/', '', $addon_dir_path );

			if ( mb_substr( $addon_dir, -7, 7 ) === '-master' )
			{
				// Remove trailing '-master'.
				$addon_dir = mb_substr( $addon_dir, 0, mb_strlen( $addon_dir ) -7 );
			}

			// Check add-on is not a core module...
			if ( ! in_array( $addon_dir, $RosarioCoreModules ) )
			{
				if ( ! file_exists( 'modules/' . $addon_dir )
					|| _delTree( 'modules/' . $addon_dir ) )
				{
					// Remove warning if directory already exists: just overwrite.
					rename( $addon_dir_path, 'modules/' . $addon_dir );

					$note[] = _( 'Add-on successfully uploaded.' );
				}
				else
				{
					$error[] = sprintf( _( 'Folder not writable' ) . ': %s', 'modules/' . $addon_dir );
				}
			}

			_delTree( $FileUploadsPath . 'upload-module/' );
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

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Module' ) ) )
	{
		// @since 8.0 Add-on disable delete.
		$can_delete = ! defined( 'ROSARIO_DISABLE_ADDON_DELETE' ) || ! ROSARIO_DISABLE_ADDON_DELETE;

		// Verify if not in $always_activated & not in $RosarioCoreModules but in $RosarioModules.
		$can_delete = $can_delete
			&& ! in_array( $_REQUEST['module'], $always_activated )
			&& ! in_array( $_REQUEST['module'], $RosarioCoreModules )
			&& in_array( $_REQUEST['module'], array_keys( $RosarioModules ) )
			&& $RosarioModules[$_REQUEST['module']] == false;

		if ( $can_delete )
		{
			// Delete module: execute delete.sql script.

			if ( file_exists( 'modules/' . $_REQUEST['module'] . '/delete.sql' ) )
			{
				$delete_sql = file_get_contents( 'modules/' . $_REQUEST['module'] . '/delete.sql' );
				DBQuery( $delete_sql );
			}

			//update $RosarioModules
			unset( $RosarioModules[$_REQUEST['module']] );

			//save $RosarioModules
			_saveRosarioModules();

			if ( is_dir( 'modules/' . $_REQUEST['module'] ) )
			{
				//remove files & dir

				if ( ! _delTree( 'modules/' . $_REQUEST['module'] ) )
				{
					$error[] = _( 'Files not eraseable.' );
				}
			}
		}

		// Unset modfunc & module & redirect URL.
		RedirectURL( [ 'modfunc', 'module' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'deactivate'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Module' ), _( 'Deactivate' ) ) )
	{
		//verify if not in $always_activated  & activated

		if ( ! in_array( $_REQUEST['module'], $always_activated ) && in_array( $_REQUEST['module'], array_keys( $RosarioModules ) ) && $RosarioModules[$_REQUEST['module']] == true )
		{
			//update $RosarioModules
			$RosarioModules[$_REQUEST['module']] = false;

			//save $RosarioModules
			_saveRosarioModules();

			//reload menu
			_reloadMenu();
		}

		//verify module dir exists

		if ( ! file_exists( 'modules/' . $_REQUEST['module'] . '/Menu.php' ) )
		{
			$error[] = _( 'Incomplete or nonexistent module.' );
		}

		// Unset modfunc & module & redirect URL.
		RedirectURL( [ 'modfunc', 'module' ] );
	}
}

if ( $_REQUEST['modfunc'] === 'activate'
	&& AllowEdit() )
{
	$update_RosarioModules = false;

	//verify not already in $RosarioModules

	if ( ! in_array( $_REQUEST['module'], array_keys( $RosarioModules ) ) )
	{
		//verify directory exists

		if ( file_exists( 'modules/' . $_REQUEST['module'] . '/Menu.php' ) )
		{
			// Install module: execute install.sql script
			$install_sql_file = 'modules/' . $_REQUEST['module'] . '/install.sql';

			if ( $DatabaseType === 'mysql' )
			{
				// @since 10.0 Install module: execute the install_mysql.sql script for MySQL
				$install_sql_file = 'modules/' . $_REQUEST['module'] . '/install_mysql.sql';

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
				'modules/' . $_REQUEST['module'] . '/install_' . mb_substr( $locale, 0, 2 ) . '.sql',
				'modules/' . $_REQUEST['module'] . '/install_' . mb_substr( $locale, 0, 5 ) . '.sql',
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

			$update_RosarioModules = true;
		}
		else
		{
			$error[] = _( 'Incomplete or nonexistent module.' );
		}
	}

	//verify in $RosarioModules
	elseif ( $RosarioModules[$_REQUEST['module']] == false && is_dir( 'modules/' . $_REQUEST['module'] ) )
	{
		$update_RosarioModules = true;
	}

	//no module dir
	elseif ( ! file_exists( 'modules/' . $_REQUEST['module'] . '/Menu.php' ) )
	{
		$error[] = _( 'Incomplete or nonexistent module.' );
	}

	if ( $update_RosarioModules )
	{
		//update $RosarioModules
		$RosarioModules[$_REQUEST['module']] = true;

		//save $RosarioModules
		_saveRosarioModules();

		//reload menu
		_reloadMenu();
	}

	// Unset modfunc & module & redirect URL.
	RedirectURL( [ 'modfunc', 'module' ] );
}

if ( ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );

	$modules_RET = [ '' ];

	foreach ( (array) $RosarioModules as $module_title => $activated )
	{
		$THIS_RET = [];
		$THIS_RET['DELETE'] = _makeDelete( $module_title, $activated );
		$THIS_RET['TITLE'] = _makeReadMe( $module_title, $activated );
		$THIS_RET['ACTIVATED'] = _makeActivated( $activated );

		$modules_RET[] = $THIS_RET;

		$directories_bypass[] = 'modules/' . $module_title;
	}

	// Scan modules/ folder for uninstalled modules.
	$modules = array_diff( glob( 'modules/*', GLOB_ONLYDIR ), $directories_bypass );

	foreach ( $modules as $module )
	{
		if ( mb_substr( $module, -7, 7 ) === '-master'
			&& is_writable( $module ) )
		{
			// @since 11.0.2 Remove "-master" suffix from manually uploaded add-ons
			$module_without_master = mb_substr( $module, 0, mb_strlen( $module ) -7 );

			if ( ! file_exists( $module_without_master )
				&& @rename( $module, $module_without_master ) )
			{
				$module = $module_without_master;
			}
		}

		$module_title = str_replace( 'modules/', '', $module );

		$THIS_RET = [];
		$THIS_RET['DELETE'] = _makeDelete( $module_title );
		$THIS_RET['TITLE'] = _makeReadMe( $module_title );
		$THIS_RET['ACTIVATED'] = _makeActivated( false );

		$modules_RET[] = $THIS_RET;
	}

	$columns = [
		'DELETE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>',
		'TITLE' => _( 'Title' ),
		'ACTIVATED' => _( 'Activated' ),
	];

	unset( $modules_RET[0] );

	ListOutput( $modules_RET, $columns, 'Module', 'Modules' );

	if ( ( ! defined( 'ROSARIO_DISABLE_ADDON_UPLOAD' )
			|| ! ROSARIO_DISABLE_ADDON_UPLOAD )
		&& class_exists( 'ZipArchive' )
		&& is_writable( $FileUploadsPath )
		&& is_writable( 'modules/' ) )
	{
		// @since 6.4 Add-on zip upload.
		echo '<br /><form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=' . $_REQUEST['tab'] .
			'&modfunc=upload' ) . '" method="POST" enctype="multipart/form-data">';

		echo FileInput( 'upload', '', 'required accept=".zip"' );

		echo SubmitButton( _( 'Upload' ), '', '' );

		echo FormatInputTitle(
			button( 'add', '', '', 'smaller' ) . ' ' . _( 'Module' ) . ' (.zip)',
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
 * @param $module_title
 * @param $activated
 * @return mixed
 */
function _makeDelete( $module_title, $activated = null )
{
	global $RosarioModules, $always_activated, $RosarioCoreModules;

	$return = '';

	if ( ! AllowEdit() )
	{
		return $return;
	}

	if ( $activated )
	{
		if ( ! in_array( $module_title, $always_activated ) )
		{
			$return = button(
				'remove',
				_( 'Deactivate' ),
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=modules&modfunc=deactivate&module=' . $module_title )
			);
		}
	}
	else
	{
		if ( file_exists( 'modules/' . $module_title . '/Menu.php' ) )
		{
			$return = button(
				'add',
				_( 'Activate' ),
				URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=modules&modfunc=activate&module=' . $module_title )
			);

			// @since 8.0 Add-on disable delete.
			$can_delete = ! defined( 'ROSARIO_DISABLE_ADDON_DELETE' ) || ! ROSARIO_DISABLE_ADDON_DELETE;

			// If not core module & already installed, delete link.
			$can_delete = $can_delete
				&& ! in_array( $module_title, $always_activated )
				&& ! in_array( $module_title, $RosarioCoreModules )
				&& in_array( $module_title, array_keys( $RosarioModules ) );

			if ( $can_delete )
			{
				$return .= '&nbsp;' .
				button(
					'remove',
					_( 'Delete' ),
					URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&tab=modules&modfunc=delete&module=' . $module_title )
				);
			}
		}
		else
		{
			$return = '<span style="color:red">' .
			sprintf( _( '%s file missing or wrong permissions.' ), 'Menu.php' ) . '</span>';
		}
	}

	return $return;
}

/**
 * @param $module_title
 * @param $activated
 * @return mixed
 */
function _makeReadMe( $module_title, $activated = null )
{
	global $RosarioCoreModules;

	//format & translate module title

	if ( ! in_array( $module_title, $RosarioCoreModules ) && $activated )
	{
		$module_title_echo = dgettext( $module_title, str_replace( '_', ' ', $module_title ) );
	}
	else
	{
		$module_title_echo = _( str_replace( '_', ' ', $module_title ) );

		if ( $module_title === 'School_Setup' )
		{
			$module_title_echo = _( 'School' );
		}
	}

	$readme_path = 'modules/' . $module_title . '/README';

	//if README.md file, display in Colorbox

	if ( ! isset( $_REQUEST['_ROSARIO_PDF'] )
		&& ( file_exists( $readme_path )
			|| (  ( $readme_path = $readme_path . '.md' )
				&& file_exists( $readme_path ) ) ) )
	{
		// Get README.md content.
		$readme_content = file_get_contents( $readme_path );

		// Convert MarkDown text to HTML.
		$readme_content = '<div class="markdown-to-html">' . $readme_content . '</div>';

		$return = '<div style="display:none;"><div id="README_' . $module_title . '">' .
			$readme_content . '</div></div>';

		$return .= '<a class="colorboxinline" href="#README_' . $module_title . '">' .
			$module_title_echo . '</a>';
	}
	else
	{
		$return = $module_title_echo;
	}

	return $return;
}

function _saveRosarioModules()
{
	global $RosarioModules;

	$MODULES = DBEscapeString( serialize( $RosarioModules ) );

	DBQuery( "UPDATE config SET config_value='" . $MODULES . "' WHERE title='MODULES'" );

	return true;
}

function _reloadMenu()
{
	?>
	<script>ajaxLink('Side.php');</script>
	<?php

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
