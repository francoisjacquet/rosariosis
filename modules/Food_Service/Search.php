<?php

if(User('PROFILE')=='admin')
{
	$RET = DBGet(DBQuery("SELECT count(1) AS COUNT FROM FOOD_SERVICE_TRANSACTIONS WHERE SCHOOL_ID IS NULL UNION SELECT count(1) FROM FOOD_SERVICE_STAFF_TRANSACTIONS WHERE SCHOOL_ID IS NULL"));

	//if (!$_SESSION['FSA_type'])
		$_SESSION['FSA_type'] = 'student';

	if(($RET[1]['COUNT']>0 || $RET[2]['COUNT']>0) && AllowUse('Food_Service/AssignSchool.php'))
		$_REQUEST['modname'] = 'Food_Service/AssignSchool.php';
	else
		$_REQUEST['modname'] = 'Food_Service/Accounts.php';
}
else
{
	//if (!$_SESSION['FSA_type'])
		$_SESSION['FSA_type'] = 'student';

	$_REQUEST['modname'] = 'Food_Service/Accounts.php';
	//$_REQUEST['modname'] = 'Food_Service/TakeMenuCounts.php';
}

$modcat = 'Food_Service';

if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT type="text/javascript">modname="'.$_REQUEST['modname'].'"; openMenu(modname);</SCRIPT>';
	include("modules/$_REQUEST[modname]");
}

function _make_school_id($value,$column)
{
	if($value!='')
	{
		$value = trim($value,',');
		if(mb_strpos($value,','))
			$value = '';
	}
	return $value;
}
?>
