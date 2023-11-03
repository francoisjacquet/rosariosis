<?php

if ( $_REQUEST['modfunc'] !== 'backup' )
{
	Drawheader( ProgramTitle() );
}

if ( $_REQUEST['modfunc'] === 'backup'
	&& isset( $_REQUEST['_ROSARIO_PDF'] ) )
{
	if ( ! empty( $pg_dumpPath )
		&& empty( $DatabaseDumpPath )
		&& $DatabaseType === 'postgresql' )
	{
		// @since 10.0 Rename $pg_dumpPath configuration variable to $DatabaseDumpPath
		$DatabaseDumpPath = $pg_dumpPath;
	}

	if ( ! isset( $DatabaseDumpPath ) )
	{
		$DatabaseDumpPath = '';
	}

	$exe = escapeshellcmd( $DatabaseDumpPath );

	// Obtain the dump utility version number and check if the path is good.
	$version = [];
	preg_match( "/(\d+(?:\.\d+)?)(?:\.\d+)?.*$/", exec( $exe . " --version" ), $version );

	if ( empty( $version ) )
	{
		$error[] = sprintf( 'The path to the database dump utility specified in the configuration file (config.inc.php) is wrong! (%s)', $DatabaseDumpPath );

		ErrorMessage( $error, 'fatal' );
	}

	$ctype = "application/force-download";

	header( "Pragma: public" );
	header( "Expires: 0" );
	header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	header( "Cache-Control: public" );
	header( "Content-Description: File Transfer" );
	header( "Content-Type: $ctype" );

	// Fix download backup filename when contains spaces: use double quotes.
	$filename = '"' . Config( 'NAME' ) . '_database_backup_' . date( 'Y.m.d' ) . '.sql"';

	$header = "Content-Disposition: attachment; filename=" . $filename . ";";

	header( $header );
	header( "Content-Transfer-Encoding: binary" );

	if ( $DatabaseType === 'postgresql' )
	{
		// Code inspired by phpPgAdmin.
		putenv( 'PGHOST=' . $DatabaseServer );
		putenv( 'PGDATABASE=' . $DatabaseName );
		putenv( 'PGUSER=' . $DatabaseUsername );
		putenv( 'PGPASSWORD=' . $DatabasePassword );

		if ( ! empty( $DatabasePort ) )
		{
			putenv( 'PGPORT=' . $DatabasePort );
		}

		// Build command for executing pg_dump. '--inserts' means dump data as INSERT commands (rather than COPY).
		$cmd = $exe . ' --inserts';
	}
	else
	{
		// @since 10.0 Build command for executing mysqldump.
		// @since 11.3 MySQL dump: export procedures, functions and triggers
		$cmd = $exe . ' --user=' . escapeshellarg( $DatabaseUsername ) .
			' --password=' . escapeshellarg( $DatabasePassword ) .
			' --host=' . escapeshellarg( $DatabaseServer ) .
			( ! empty( $DatabasePort ) ? ' --port=' . escapeshellarg( $DatabasePort ) : '' ) .
			' --routines --triggers' .
			' ' . escapeshellarg( $DatabaseName );
	}

	// Execute command and return the output to the screen.
	passthru( $cmd );

	exit;
}

if ( ! $_REQUEST['modfunc'] )
{
	echo '<br />';
	PopTable( 'header', _( 'Database Backup' ) );
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=backup&_ROSARIO_PDF=true' ) . '" method="POST">';
	echo '<br />';
	echo _( 'Download backup files periodically in case of system failure.' );
	echo '<br /><br />';
	echo '<div class="center">' . SubmitButton( _( 'Download Backup File' ) ) . '</div>';
	echo '</form>';
	PopTable( 'footer' );
}
