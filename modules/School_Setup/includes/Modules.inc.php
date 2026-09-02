<?php

require_once 'modules/School_Setup/includes/Addon.fnc.php';

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
	(new RosarioSIS\Functions\Hacking)->log();
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
		$extract_to_path = $FileUploadsPath . 'upload-module/';

		$addon_dir_path = AddonUnzip( 'module', $addon_zip_path, $extract_to_path );

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

			// Check add-on is not a core module...
			if ( ! in_array( $addon_dir, $RosarioCoreModules ) )
			{
				if ( ! file_exists( 'modules/' . $addon_dir )
					|| AddonDelTree( 'modules/' . $addon_dir ) )
				{
					/**
					 * Remove warning for directories across filesystems or devices
					 *
					 * @link https://www.php.net/manual/en/function.rename.php#113943
					 */
					if ( ! @rename( $addon_dir_path, 'modules/' . $addon_dir ) )
					{
						/**
						 * Workaround: exec mv (move on Windows)
						 *
						 * @link https://bugs.php.net/bug.php?id=54097
						 */
						$move_cmd = stripos( PHP_OS, 'WIN' ) === 0 ? 'move' : 'mv';

						exec( $move_cmd . ' ' . escapeshellarg( $addon_dir_path ) . ' ' .
							escapeshellarg( 'modules/' . $addon_dir ) );
					}

					$note[] = button( 'check' ) . '&nbsp;' . _( 'Add-on successfully uploaded.' );
				}
				else
				{
					$error[] = sprintf( _( 'Folder not writable' ) . ': %s', 'modules/' . $addon_dir );
				}
			}
		}

		AddonDelTree( $extract_to_path );

		FileDelete( $addon_zip_path, '.zip' );
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
			Config( 'MODULES', serialize( $RosarioModules ) );

			if ( is_dir( 'modules/' . $_REQUEST['module'] ) )
			{
				//remove files & dir

				if ( ! AddonDelTree( 'modules/' . $_REQUEST['module'] ) )
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
			Config( 'MODULES', serialize( $RosarioModules ) );

			// Reload menu
			// @since 12.5 CSP remove unsafe-inline Javascript
			?>
			<script src="assets/js/csp/modules/ReloadMenu.js?v=12.5"></script>
			<?php
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

	// @since 12.2 Error if add-on folder has "-master" or "-main" suffix
	if ( mb_substr( $_REQUEST['module'], -7, 7 ) === '-master' )
	{
		$error[] = sprintf(
			_( 'Please rename the add-on directory: remove the "%s" suffix.' ),
			'-master'
		);
	}
	elseif ( mb_substr( $_REQUEST['module'], -5, 5 ) === '-main' )
	{
		$error[] = sprintf(
			_( 'Please rename the add-on directory: remove the "%s" suffix.' ),
			'-main'
		);
	}
	else
	{
		// @since 13.0 Check add-on requirements
		(new RosarioSIS\Functions\AddonInfo( 'module', $_REQUEST['module'] ))->checkRequirements();
	}

	//verify not already in $RosarioModules

	if ( ! $error
		&& ! in_array( $_REQUEST['module'], array_keys( $RosarioModules ) ) )
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

			$install_locale_paths = [
				// @since 10.9.3 Add-on SQL translation file can be named "install_es.sql" or "install_pt_BR.sql"
				'modules/' . $_REQUEST['module'] . '/install_' . mb_substr( $locale, 0, 2 ) . '.sql',
				'modules/' . $_REQUEST['module'] . '/install_' . mb_substr( $locale, 0, 5 ) . '.sql',
				// @since 12.1 Add-on SQL translation file can be under `locale/[locale_code]/` folder
				'modules/' . $_REQUEST['module'] . '/locale/' . $locale . '/install.sql',
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

			AddonInstallationStatisticsPost( 'module', $_REQUEST['module'] );

			$update_RosarioModules = true;
		}
		else
		{
			$error[] = _( 'Incomplete or nonexistent module.' );
		}
	}

	//verify in $RosarioModules
	elseif ( ! $error
		&& $RosarioModules[$_REQUEST['module']] == false && is_dir( 'modules/' . $_REQUEST['module'] ) )
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
		Config( 'MODULES', serialize( $RosarioModules ) );

		// Reload menu
		// @since 12.5 CSP remove unsafe-inline Javascript
		?>
		<script src="assets/js/csp/modules/ReloadMenu.js?v=12.5"></script>
		<?php
	}

	// Unset modfunc & module & redirect URL.
	RedirectURL( [ 'modfunc', 'module' ] );
}

if ( $_REQUEST['modfunc'] === 'installation_statistics_post'
	&& AllowEdit() )
{
	ob_clean();

	if ( ! empty( $_REQUEST['addon_dir'] ) )
	{
		// cURL POST in the background (AJAX request)
		AddonInstallationStatisticsPost( 'module', $_REQUEST['addon_dir'] );
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
	$modules_RET = [ '' ];

	foreach ( (array) $RosarioModules as $module_title => $activated )
	{
		$THIS_RET = [];
		$THIS_RET['DELETE'] = _makeDelete( $module_title, $activated );
		$THIS_RET['TITLE'] = AddonMakeReadMe( 'module', $module_title, $activated );
		$THIS_RET['ACTIVATED'] = AddonMakeActivated( $activated );
		$THIS_RET['UPDATE_AVAILABLE'] = '';

		if ( ! in_array( $module_title, $RosarioCoreModules ) )
		{
			if ( $check_updates )
			{
				// @since 13.0 Check for add-on updates
				$THIS_RET['UPDATE_AVAILABLE'] = AddonMakeUpdateAvailable( 'module', $module_title );
			}
		}

		$modules_RET[] = $THIS_RET;

		$directories_bypass[] = 'modules/' . $module_title;
	}

	// Scan modules/ folder for uninstalled modules.
	$modules = array_diff( glob( 'modules/*', GLOB_ONLYDIR ), $directories_bypass );

	$has_non_core_modules = ( count( $RosarioCoreModules ) < count( $RosarioModules ) ) || $modules;

	$check_updates_link = '';

	if ( $has_non_core_modules
		&& strpos( $_SERVER['HTTP_HOST'], '.rosariosis.com' ) === false
		&& AllowEdit() )
	{
		// @since 13.0 Check for add-on updates
		$check_updates_link = '<a href="Modules.php?modname=School_Setup/Configuration.php&tab=modules&modfunc=check_updates">' .
			_( 'Check for updates' ) . '</a>';
	}

	$lang_2_chars = mb_substr( $_SESSION['locale'], 0, 2 );

	$modules_url = 'https://www.rosariosis.org/' .
		( $lang_2_chars === 'es' || $lang_2_chars === 'fr' ? $lang_2_chars . '/' : '' ) .
		'modules/';

	DrawHeader(
		'<a href="' . $modules_url . '" target="_blank" rel="noreferrer">' . $modules_url .  '</a>',
		$check_updates_link
	);

	echo ErrorMessage( $error );

	echo ErrorMessage( $note, 'note' );

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

		if ( mb_substr( $module, -5, 5 ) === '-main'
			&& is_writable( $module ) )
		{
			// @since 12.0.1 Remove "-main" suffix from manually uploaded add-ons
			$module_without_main = mb_substr( $module, 0, mb_strlen( $module ) -5 );

			if ( ! file_exists( $module_without_main )
				&& @rename( $module, $module_without_main ) )
			{
				$module = $module_without_main;
			}
		}

		$module_title = str_replace( 'modules/', '', $module );

		$THIS_RET = [];
		$THIS_RET['DELETE'] = _makeDelete( $module_title );
		$THIS_RET['TITLE'] = AddonMakeReadMe( 'module', $module_title );
		$THIS_RET['ACTIVATED'] = AddonMakeActivated( false );

		if ( $check_updates )
		{
			// @since 13.0 Check for add-on updates
			$THIS_RET['UPDATE_AVAILABLE'] = AddonMakeUpdateAvailable( 'module', $module_title );
		}

		$modules_RET[] = $THIS_RET;
	}

	$columns = [
		'DELETE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>',
		'TITLE' => _( 'Title' ),
		'ACTIVATED' => _( 'Activated' ),
	];

	if ( $check_updates )
	{
		$columns['UPDATE_AVAILABLE'] = _( 'Update available' );
	}

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
