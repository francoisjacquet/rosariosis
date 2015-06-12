<?php
/**
 * The base configurations of the WordPress.
 *
 * You can find more information in the INSTALL file
 */

/**
 * PostgreSQL Database Settings
 *
 * You can get this info from your web host
 */

// Database server hostname: use localhost if on same server
$DatabaseServer = 'localhost';

// Database username
$DatabaseUsername = 'rosariosis';

// Database password
$DatabasePassword = 'password';

// Database name
$DatabaseName = 'rosariosis';

// Database port: default is 5432
$DatabasePort = '5432';


/**
 * Paths
 */

// Specify the path to the PostrgeSQL database dump utility for this server
$pg_dumpPath = '/usr/bin/pg_dump';

/**
 * Full path to wkhtmltopdf binary file
 *
 * An empty string means wkhtmltopdf will not be called
 * and reports will be rendered in HTML instead of PDF
 */
$wkhtmltopdfPath = '/usr/bin/wkhtmltopdf';


/**
 * Default school year
 *
 * Do not change on install
 * Change after rollover
 * Should match the database to be able to login
 */
$DefaultSyear = '2015';


/**
 * email address
 * where to send error and new administrator notifications
 *
 * Leave empty to not receive email notifications
 */
$RosarioNotifyAddress = '';


/**
 * Locales
 *
 * Add other languages you want to support here
 * ex: array('en_US.utf8', 'fr_FR.utf8', 'es_ES.utf8');
 */
$RosarioLocales = array('en_US.utf8');

?>