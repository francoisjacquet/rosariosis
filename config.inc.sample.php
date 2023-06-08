<?php
/**
 * The base configurations of RosarioSIS
 *
 * You can find more information in the INSTALL.md file
 *
 * @package RosarioSIS
 */

/**
 * Database Settings
 *
 * You can get this info from your web host
 */

// Database type: postgresql or mysql.
$DatabaseType = 'postgresql';

// Database server hostname: use localhost if on same server.
$DatabaseServer = 'localhost';

// Database username.
$DatabaseUsername = 'username_here';

// Database password.
$DatabasePassword = 'password_here';

// Database name.
$DatabaseName = 'database_name_here';


/**
 * Paths
 */

/**
 * Full path to the database dump utility for this server
 *
 * pg_dump for PostgreSQL
 * @example /usr/bin/pg_dump
 * @example C:/Progra~1/PostgreSQL/bin/pg_dump.exe
 *
 * mysqldump for MySQL
 * @example /usr/bin/mysqldump
 * @example C:/wamp/bin/mysql/mysql[version]/mysqldump.exe
 */
$DatabaseDumpPath = '';

/**
 * Full path to wkhtmltopdf binary file
 *
 * An empty string means wkhtmltopdf will not be called
 * and reports will be rendered in HTML instead of PDF
 *
 * @link http://wkhtmltopdf.org
 *
 * @example /usr/local/bin/wkhtmltopdf
 * @example C:/Progra~1/wkhtmltopdf/bin/wkhtmltopdf.exe
 */
$wkhtmltopdfPath = '';


/**
 * Default school year
 *
 * Do NOT change on install
 * Change after rollover
 * Should match the database to be able to login
 *
 * @see School > Rollover program
 */
$DefaultSyear = '2023';


/**
 * Email address to receive notifications
 * - new administrator account
 * - new student / user account
 * - new registration
 *
 * Leave empty to not receive email notifications
 */
$RosarioNotifyAddress = '';


/**
 * Email address to receive errors
 * - PHP fatal error
 * - database SQL error
 * - hacking attempts
 *
 * Leave empty to not receive errors
 */
$RosarioErrorsAddress = '';


/**
 * Locales
 *
 * Add other languages you want to support here
 *
 * @see locale/ folder
 *
 * For American, French and Spanish:
 *
 * @example [ 'en_US.utf8', 'fr_FR.utf8', 'es_ES.utf8' ];
 */
$RosarioLocales = [ 'en_US.utf8' ];
