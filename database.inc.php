<?php
// Establish DB connection.
function db_start()
{	global $DatabaseServer,$DatabaseUsername,$DatabasePassword,$DatabaseName,$DatabasePort,$DatabaseType;

	switch($DatabaseType)
	{
		case 'oracle':
			$connection = @ocilogon($DatabaseUsername,$DatabasePassword,$DatabaseServer);
		break;
		case 'postgres':
			$connectstring = '';
			if($DatabaseServer!='localhost')
				$connectstring = "host=$DatabaseServer ";
			if($DatabasePort!='5432')
				$connectstring .= "port=$DatabasePort ";
			$connectstring .= "dbname=$DatabaseName user=$DatabaseUsername";
			if(!empty($DatabasePassword))
				$connectstring.=" password=$DatabasePassword";
			$connection = pg_connect($connectstring);
		break;
		case 'mysql':
			$connection = mysql_connect($DatabaseServer,$DatabaseUsername,$DatabasePassword);
			mysql_select_db($DatabaseName);
		break;
	}

	// Error code for both.
	if($connection===false)
	{
		switch($DatabaseType)
		{
			case 'oracle':
				$errors = OciError();
				$errormessage = $errors['message'];
			break;
			case 'postgres':
				$errormessage = pg_last_error($connection);
			break;
			case 'mysql':
				$errormessage = mysql_error($connection);
			break;
		}
        // TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
    	db_show_error("",sprintf('Could not Connect to Database Server \'%s\'',$DatabaseServer),$errstring);
	}
	return $connection;
}

// This function connects, and does the passed query, then returns a connection identifier.
// Not receiving the return == unusable search.
//		ie, $processable_results = DBQuery("select * from students");
function DBQuery($sql)
{	global $DatabaseType;

	$connection = db_start();

	switch($DatabaseType)
	{
		case 'oracle':
			$result = @ociparse($connection, $sql);
            // TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
			if($result === false)
			{
				$errors = OCIError($connection);
				db_show_error($sql,"DB Parse Failed.", $errors['message']);
			}
			if(!@OciExecute($result))
			{
				$errors = OCIError($result);
				db_show_error($sql,"DB Execute Failed.", $errors['message']);
			}
			OciCommit($connection);
			OciLogoff($connection);
		break;
		case 'postgres':
            // TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
			$sql = preg_replace("/([,\(=])[\r\n\t ]*''/",'\\1NULL',$sql);
			$result = @pg_exec($connection,$sql);
			if($result===false)
			{
				$errstring = pg_last_error($connection);
				db_show_error($sql,"DB Execute Failed.",$errstring);
			}
		break;
		case 'mysql':
            // TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
			mysql_query("SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE");
			mysql_query("SET SESSION SQL_MODE='ANSI'");
			$sql = preg_replace("/([,\(=])[\r\n\t ]*''/",'\\1NULL',$sql);
			$result = mysql_query($sql);
			if($result===false)
			{
				$errstring = mysql_error();
				db_show_error($sql,"DB Execute Failed.",$errstring);
			}
		break;
	}
	return $result;
}

// return next row.
function db_fetch_row($result)
{	global $DatabaseType;

	switch($DatabaseType)
	{
		case 'oracle':
			OCIFetchInto($result,$row,OCI_ASSOC+OCI_RETURN_NULLS);
			$return = $row;
		break;
		case 'postgres':
			$return = @pg_fetch_array($result);
			if(is_array($return))
			{
				foreach($return as $key => $value)
				{
					if(is_int($key))
						unset($return[$key]);
				}
			}
		break;
		case 'mysql':
			$return = mysql_fetch_array($result);
			if(is_array($return))
			{
				foreach($return as $key => $value)
				{
					if(is_int($key))
						unset($return[$key]);
				}
			}
		break;
	}
	return @array_change_key_case($return,CASE_UPPER);
}

// returns code to go into SQL statement for accessing the next value of a sequenc	function db_seq_nextval($seqname)
function db_seq_nextval($seqname)
{	global $DatabaseType;

	if($DatabaseType=='oracle')
		$seq = $seqname.".nextval";
	elseif($DatabaseType=='postgres')
		$seq = "nextval('".$seqname."')";
	elseif($DatabaseType=='mysql')
	{
		DBQuery("UPDATE $seqname SET ID=last_insert_id(ID+1)");
		$seq = 'last_insert_id()';
	}

	return $seq;
}

// start transaction
function db_trans_start($connection)
{	global $DatabaseType;

	if($DatabaseType=='postres')
		db_trans_query($connection,"BEGIN WORK");
}

// run query on transaction -- if failure, runs rollback.
function db_trans_query($connection,$sql)
{	global $DatabaseType;

    // TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
	if($DatabaseType=='oracle')
	{
		$parse = ociparse($connection,$sql);
		if($parse===false)
		{
			db_trans_rollback($connection);
			db_show_error($sql,"DB Transaction Parse Failed.");
		}
		$result=OciExecute($parse,OCI_DEFAULT);
		if ($result===false)
		{
			db_trans_rollback($connection);
			db_show_error($sql,"DB Transaction Execute Failed.");
		}
		$result=$parse;
	}
	elseif($DatabaseType=='postgres')
	{
		$sql = preg_replace("/([,\(=])[\r\n\t ]*''/",'\\1NULL',$sql);
		$result = pg_query($connection,$sql);
		if($result===false)
		{
			db_trans_rollback($connection);
			db_show_error($sql,"DB Transaction Execute Failed.");
		}
	}

	return $result;
}

// rollback commands.
function db_trans_rollback($connection)
{	global $DatabaseType;

	if($DatabaseType=='oracle')
		OCIRollback($connection);
	elseif($DatabaseType=='postgres')
		pg_query($connection,"ROLLBACK");
}

// commit changes.
function db_trans_commit($connection)
{	global $DatabaseType;

	if($DatabaseType=='oracle')
		OCICommit($connection);
	elseif($DatabaseType=='postgres')
		pg_query($connection,"COMMIT");
}

// keyword mapping.
if($DatabaseType=='oracle')
	define("FROM_DUAL"," FROM DUAL ");
else
	define("FROM_DUAL"," ");

// DECODE and CASE-WHEN support
function db_case($array)
{	global $DatabaseType;

	$counter=0;
	switch($DatabaseType)
	{
		case 'oracle':
			$string=" decode( ";
			foreach($array as $value)
				$string.="$value,";
			$string[mb_strlen($string)-1]=")";
			$string.=" ";
		break;
		case 'postgres':
		case 'mysql':
			$array_count=count($array);
			$string = " CASE WHEN $array[0] =";
			$counter++;
			$arr_count = count($array);
			for($i=1;$i<$arr_count;$i++)
			{
				$value = $array[$i];

				if($value=="''" && mb_substr($string,-1)=='=')
				{
					$value = ' IS NULL';
					$string = mb_substr($string,0,-1);
				}

				$string.="$value";
				if($counter==($array_count-2) && $array_count%2==0)
					$string.=" ELSE ";
				elseif($counter==($array_count-1))
					$string.=" END ";
				elseif($counter%2==0)
					$string.=" WHEN $array[0]=";
				elseif($counter%2==1)
					$string.=" THEN ";

				$counter++;
			}
	}
	return $string;
}

// String position.
function db_mb_strpos($args)
{	global $DatabaseType;

	if($DatabaseType=='postgres')
		$ret = 'mb_strpos(';
	else
		$ret = 'instr(';

	foreach($args as $value)
		$ret .= $value . ',';
	$ret = mb_substr($ret,0,-1) . ')';

	return $ret;
}

// CONVERT VARCHAR TO NUMERIC
function db_to_number($text)
{	global $DatabaseType;

	if($DatabaseType=='postgres')
		return '('.$text.')::text::float::numeric';
	else
		return 'to_number('.$text.')';
}

// greatest/least - builtin to postgres 8 but not 7
function db_greatest($a,$b)
{	global $DatabaseType;

	if($DatabaseType=='postgres')
		return "(CASE WHEN $a IS NOT NULL AND $b IS NOT NULL THEN (CASE WHEN $a>$b THEN $a ELSE $b END) WHEN $a IS NOT NULL AND $b IS NULL THEN $a WHEN $a IS NULL AND $b IS NOT NULL THEN $b ELSE NULL END)";
	else
		return "greatest($a,$b)";
}

function db_least($a,$b)
{	global $DatabaseType;

	if($DatabaseType=='postgres')
		return "(CASE WHEN $a IS NOT NULL AND $b IS NOT NULL THEN (CASE WHEN $a<$b THEN $a ELSE $b END) WHEN $a IS NOT NULL AND $b IS NULL THEN $a WHEN $a IS NULL AND $b IS NOT NULL THEN $b ELSE NULL END)";
	else
		return "least($a,$b)";
}

// returns an array with the field names for the specified table as key with subkeys
// of SIZE, TYPE, SCALE and NULL.  TYPE: varchar, numeric, etc.
function db_properties($table)
{	global $DatabaseType,$DatabaseUsername;

	switch($DatabaseType)
	{
		case 'oracle':
			$sql="SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH, DATA_PRECISION,
				DATA_SCALE, NULLABLE, DATA_DEFAULT
				FROM ALL_TAB_COLUMNS WHERE TABLE_NAME='".mb_strtoupper($table)."'
				AND OWNER='".mb_strtoupper($DatabaseUsername)."' ORDER BY COLUMN_ID";
			$result = DBQuery($sql);
			while($row=db_fetch_row($result))
			{
				if($row['DATA_TYPE']=='VARCHAR2')
				{
					$properties[$row['COLUMN_NAME']]['TYPE'] = "VARCHAR";
					$properties[$row['COLUMN_NAME']]['SIZE'] = $row['DATA_LENGTH'];
				}
				elseif($row['DATA_TYPE']=='NUMBER')
				{
					$properties[$row['COLUMN_NAME']]['TYPE'] = "NUMERIC";
					$properties[$row['COLUMN_NAME']]['SIZE'] = $row['DATA_PRECISION'];
					$properties[$row['COLUMN_NAME']]['SCALE'] = $row['DATA_SCALE'];
				}
				else
				{
					$properties[$row['COLUMN_NAME']]['TYPE'] = $row['DATA_TYPE'];
					$properties[$row['COLUMN_NAME']]['SIZE'] = $row['DATA_LENGTH'];
					$properties[$row['COLUMN_NAME']]['SCALE'] = $row['DATA_SCALE'];
				}
				$properties[$row['COLUMN_NAME']]['NULL'] = $row['NULLABLE'];
			}
		break;
		case 'postgres':
			$sql = "SELECT a.attnum,a.attname AS field,t.typname AS type,
					a.attlen AS length,a.atttypmod AS lengthvar,
					a.attnotnull AS notnull
				FROM pg_class c, pg_attribute a, pg_type t
				WHERE c.relname = '".mb_strtolower($table)."'
					and a.attnum > 0 and a.attrelid = c.oid
					and a.atttypid = t.oid ORDER BY a.attnum";
			$result = DBQuery($sql);
			while($row = db_fetch_row($result))
			{
				$properties[mb_strtoupper($row['FIELD'])]['TYPE'] = mb_strtoupper($row['TYPE']);
				if(mb_strtoupper($row['TYPE'])=="NUMERIC")
				{
					$properties[mb_strtoupper($row['FIELD'])]['SIZE'] = ($row['LENGTHVAR'] >> 16) & 0xffff;
					$properties[mb_strtoupper($row['FIELD'])]['SCALE'] = ($row['LENGTHVAR'] -4) & 0xffff;
				}
				else
				{
					if($row['LENGTH']>0)
						$properties[mb_strtoupper($row['FIELD'])]['SIZE'] = $row['LENGTH'];
					elseif($row['LENGTHVAR']>0)
						$properties[mb_strtoupper($row['FIELD'])]['SIZE'] = $row['LENGTHVAR']-4;
				}
				if ($row['NOTNULL']=='t')
					$properties[mb_strtoupper($row['FIELD'])]['NULL'] = "N";
				else
					$properties[mb_strtoupper($row['FIELD'])]['NULL'] = "Y";
			}
		break;
		case 'mysql':
			$result = DBQuery("SHOW COLUMNS FROM $table");
			while($row = db_fetch_row($result))
			{
				$properties[mb_strtoupper($row['FIELD'])]['TYPE'] = mb_strtoupper($row['TYPE'],mb_strpos($row['TYPE'],'('));
				if(!$pos = mb_strpos($row['TYPE'],','))
					$pos = mb_strpos($row['TYPE'],')');
				else
					$properties[mb_strtoupper($row['FIELD'])]['SCALE'] = mb_substr($row['TYPE'],$pos+1);

				$properties[mb_strtoupper($row['FIELD'])]['SIZE'] = mb_substr($row['TYPE'],mb_strpos($row['TYPE'],'(')+1,$pos);

				if($row['NULL']!='')
					$properties[mb_strtoupper($row['FIELD'])]['NULL'] = "Y";
				else
					$properties[mb_strtoupper($row['FIELD'])]['NULL'] = "N";
			}
		break;
	}
	return $properties;
}

function db_show_error($sql,$failnote,$additional='')
{	global $RosarioVersion,$RosarioNotifyAddress;

    echo '<BR />';
	PopTable('header',_('We have a problem, please contact technical support ...'));
    // TRANSLATION: do NOT translate these since error messages need to stay in English for technical support
	echo '
		<TABLE style="border-collapse:separate; border-spacing:10px;">
		<TR>
			<TD style="text-align:right"><b>Date:</b></TD>
			<TD><pre>'.date("m/d/Y h:i:s").'</pre></TD>
		</TR><TR>
			<TD style="text-align:right"><b>Failure Notice:</b></TD>
			<TD><pre> '.$failnote.' </pre></TD>
		</TR><TR>
			<TD style="text-align:right"><b>Additional Information:</b></TD>
			<TD>'.$additional.'</TD>
		</TR>
		</TABLE>';
	//Something you have asked the system to do has thrown a database error.  A system administrator has been notified, and the problem will be fixed as soon as possible.  It might be that changing the input parameters sent to this program will cause it to run properly.  Thanks for your patience.
	PopTable('footer');
	echo "<!-- SQL STATEMENT: \n\n $sql \n\n -->";

	if($RosarioNotifyAddress)
	{
		$message = "System: ".ParseMLField(Config('TITLE'))." \n";
		$message .= "Date: ".date("m/d/Y h:i:s")."\n";
		$message .= "Page: ".$_SERVER['PHP_SELF'].' '.ProgramTitle()." \n\n";
		$message .= "Failure Notice:  $failnote \n";
		$message .= "Additional Info: $additional \n";
		$message .= "\n $sql \n";
		$message .= "Request Array: \n".ShowVar($_REQUEST,'Y', 'N');
		$message .= "\n\nSession Array: \n".ShowVar($_SESSION,'Y', 'N');
		
		//modif Francois: add email headers
		$headers = 'From:'.$RosarioNotifyAddress."\r\n";
		$headers .= 'Return-Path:'.$RosarioNotifyAddress."\r\n"; 
		$headers .= 'Reply-To:'.$RosarioNotifyAddress . "\r\n" . 'X-Mailer: PHP/' . phpversion();
		$params = '-f '.$RosarioNotifyAddress;
		
		mail($RosarioNotifyAddress,'Rosario Database Error',utf8_decode($message),$headers, $params);
	}

	die();
}

// $safe_string = DBEscapeString($string).  Escapes single quotes by using two for every
// one.  Requires preg support in PHP.

function DBEscapeString($input)
{
	return pg_escape_string($input);
	//return str_replace("'","''",$input);
}
?>