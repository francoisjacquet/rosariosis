<?php
// TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
error_reporting(1);
session_start();

//modif Francois: check PHP version
if (version_compare(PHP_VERSION, '5.3.2') == -1) {
    $error[] = 'RosarioSIS requires PHP 5.3.2 to run, you version is : ' . PHP_VERSION;
}

if(!$_SESSION['STAFF_ID'])
{
	$unset_username = true;
	$_SESSION['USERNAME'] = 'diagnostic';
	$_SESSION['STAFF_ID'] = '-1';
}
if(!file_exists('./Warehouse.php'))
	$error[] = 'The diagnostic.php file needs to be in the RosarioSIS directory to be able to run.  Please move it there, and run it again.';
else
{
	include './Warehouse.php';
	if(!@opendir("$RosarioPath/functions"))
		$error[] = 'The value for $RosarioPath in config.inc.php is not correct or else the functions directory does not have the correct permissions to be read by the webserver.  Make sure $RosarioPath points to the RosarioSIS installation directory and that it is readable by all users.';

	if(!function_exists('pg_connect'))
		$error[] = 'PHP was not compiled with PostgreSQL support.  You need to recompile PHP using the --with-pgsql option for RosarioSIS to work.';
	else
	{
			if($DatabaseServer!='localhost')
				$connectstring = "host=$DatabaseServer ";
			if($DatabasePort!='5432')
				$connectstring .= "port=$DatabasePort ";
			$connectstring .= "dbname=$DatabaseName user=$DatabaseUsername";
			if(!empty($DatabasePassword))
				$connectstring.=" password=$DatabasePassword";
			$connection = @pg_connect($connectstring);

		if(!$connection)
			$error[] = 'RosarioSIS cannot connect to the Postgres database.  Either Postgres is not running, it was not started with the -i option, or connections from this host are not allowed in the pg_hba.conf file. Last Postgres Error: '.pg_last_error();
		else
		{
			$result = @pg_exec($connection,'SELECT * FROM CONFIG');
			if($result===false)
				$errstring = pg_last_error($connection);

			if(mb_strpos($errstring,'config: permission denied')!==false)
				$error[] = 'The database was created with the wrong permissions.  The user specified in the config.inc.php file does not have permission to access the rosario database.  Use the super-user (postgres) or recreate the database adding \connect - YOUR_USERNAME to the top of the rosario.sql file.';
			elseif(mb_strpos($errstring,'elation "config" does not exist')!==false)
				$error[] = 'At least one of the tables does not exist.  Make sure you ran the rosario.sql file as described in the INSTALL file.';
			elseif($errstring)
				$error[] = $errstring;
		}
	}
}

echo _ErrorMessage($error,'error');
if(!count($error))
	echo '<h3>Your RosarioSIS installation is properly configured.</h3>';
phpinfo();

if($unset_username)
{
	unset($_SESSION['USERNAME']);
	unset($_SESSION['STAFF_ID']);
}

function _ErrorMessage($errors,$code='error')
{
	if($errors)
	{
		$return .= '<TABLE><TR><TD style="text-align:left;">';
		if(count($errors)==1)
		{
			if($code=='error' || $code=='fatal')
				$return .= '<b><span style="color:#CC0000">Error:</span></b> ';
			else
				$return .= '<b><span style="color:#00CC00">Note:</span></b> ';
			$return .= (($errors[0])?$errors[0]:$errors[1]);
		}
		else
		{
			if($code=='error' || $code=='fatal')
				$return .= '<b><span style="color:#CC0000">Errors:</span></b>';
			else
				$return .= '<b><span style="color:#00CC00">Note:</span></b>';
			$return .= '<ul>';
			foreach($errors as $value)
				$return .= '<LI><span class="size-1">'.$value.'</span></LI>'."\n";
			$return .= '</ul>';
		}
			$return .= "</TD></TR></TABLE><BR />";

		if($code=='fatal')
		{
			echo $return;
			if(!isset($_REQUEST['_ROSARIO_PDF']))
				Warehouse('footer');
			exit;
		}

		return $return;
	}
}

?>