<?php
if(!defined('CONFIG_INC'))
{
	define('CONFIG_INC',1);
	
	// Database Setup
	$DatabaseServer = 'localhost';	// postgres = host
	$DatabaseUsername = 'rosariosis';
	$DatabasePassword = 'password';
	$DatabaseName = 'rosariosis';
	$DatabasePort = '5432';

	// Server Names and Paths
	$RosarioPath = dirname(__FILE__).'/';
	$pg_dumpPath = '/usr/bin/pg_dump'; // Specify the path to the database dump utility for this server.
	//modif Francois: wkhtmltopdf
	$wkhtmltopdfPath = '/usr/bin/wkhtmltopdf'; // empty string means wkhtmltopdf will not be called and reports will be rendered in htlm instead of pdf
	$wkhtmltopdfAssetsPath = '/var/www/rosariosis/assets/'; // way wkhtmltopdf accesses the assets/ directory, empty string means no translation
	$StudentPicturesPath = 'assets/StudentPhotos/';
	$UserPicturesPath = 'assets/UserPhotos/';
	$PortalNotesFilesPath = 'assets/PortalNotesFiles/';
	$FS_IconsPath = 'assets/FS_icons/';

	$DefaultSyear = '2014';
	$RosarioNotifyAddress = '';
	$RosarioLocales = array('en_US.utf8');	// Add other languages you want to support here, ex: 'fr_FR.utf8', 'es_ES.utf8', ...
	$CurrencySymbol = '$'; // locale currency
	$LocalePath = 'locale'; // Path were the language packs are stored. You need to restart Apache at each change in this directory

}
?>
