<?php

if($_REQUEST['modfunc']=='update')
{
	if(UserStudentID() && AllowEdit())
	{
		if(count($_REQUEST['food_service']))
		{
			$sql = "UPDATE FOOD_SERVICE_STUDENT_ACCOUNTS SET ";
			foreach($_REQUEST['food_service'] as $column_name=>$value)
				$sql .= $column_name."='".trim($value)."',";
			$sql = mb_substr($sql,0,-1)." WHERE STUDENT_ID='".UserStudentID()."'";
			DBQuery($sql);
		}
	}
	//unset($_REQUEST['modfunc']);
	unset($_REQUEST['food_service']);
	unset($_SESSION['_REQUEST_vars']['food_service']);
}

if(!$_REQUEST['modfunc'] && UserStudentID())
{
	$student = DBGet(DBQuery("SELECT s.STUDENT_ID,s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME,fssa.ACCOUNT_ID,fssa.STATUS,fssa.DISCOUNT,fssa.BARCODE,(SELECT BALANCE FROM FOOD_SERVICE_ACCOUNTS WHERE ACCOUNT_ID=fssa.ACCOUNT_ID) AS BALANCE FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fssa WHERE s.STUDENT_ID='".UserStudentID()."' AND fssa.STUDENT_ID=s.STUDENT_ID"));
	$student = $student[1];

	// find other students associated with the same account
	$xstudents = DBGet(DBQuery("SELECT s.STUDENT_ID,s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME FROM STUDENTS s,FOOD_SERVICE_STUDENT_ACCOUNTS fssa WHERE fssa.ACCOUNT_ID='".$student['ACCOUNT_ID']."' AND s.STUDENT_ID=fssa.STUDENT_ID AND s.STUDENT_ID!='".UserStudentID()."'"));

	echo '<TABLE class="width-100p">';
	echo '<TR>';
	echo '<TD class="valign-top">';
	echo '<TABLE class="width-100p"><TR>';

	echo '<TD class="valign-top">'.NoInput(($student['BALANCE']<0?'<span style="color:red">':'').$student['BALANCE'].($student['BALANCE']<0?'</span>':''),_('Balance')).'</TD>';

	echo '</TR></TABLE>';
	echo '</TD></TR></TABLE>';
	echo '<HR>';

	echo '<TABLE class="width-100p cellspacing-0 cellpadding-0">';
	echo '<TR><TD class="valign-top">';

	echo '<TABLE class="width-100p cellpadding-6">';
	echo '<TR>';
	echo '<TD>';
	// warn if account non-existent (balance query failed)
	if($student['BALANCE']=='')
	{
		echo TextInput(array($student['ACCOUNT_ID'],'<span style="color:red">'.$student['ACCOUNT_ID'].'</span>'),'food_service[ACCOUNT_ID]',_('Account ID'),'size=12 maxlength=10');
		$warning = _('Non-existent account!');
		echo button('warning','','"#" onMouseOver=\'stm(["'._('Warning').'","'.str_replace('"','\"',str_replace("'",'&#39;',$warning)).'"],tipmessageStyle); return false;\' onMouseOut=\'htm()\'');
	}
	else
	 	echo TextInput($student['ACCOUNT_ID'],'food_service[ACCOUNT_ID]','Account ID','size=12 maxlength=10');
	// warn if other students associated with the same account
	if(count($xstudents))
	{
		$warning = Localize('colon',_('Other students associated with the same account')).'<BR />';
		foreach($xstudents as $xstudent)
			$warning .= '&nbsp;'.str_replace('\'','&#39;',$xstudent['FULL_NAME']).'<BR />';
		echo button('warning','','"#" onMouseOver=\'stm(["'._('Warning').'","'.str_replace('"','\"',str_replace("'",'&#39;',$warning)).'"],tipmessageStyle); return false;\' onMouseOut=\'htm()\'');
	}
	echo '</TD>';
	$options = array('Inactive'=>_('Inactive'),'Disabled'=>_('Disabled'),'Closed'=>_('Closed'));
	echo '<TD>'.SelectInput($student['STATUS'],'food_service[STATUS]',_('Status'),$options,_('Active')).'</TD>';
	echo '</TR><TR>';
	$options = array('Reduced'=>'Reduced','Free'=>'Free');
	echo '<TD>'.SelectInput($student['DISCOUNT'],'food_service[DISCOUNT]',_('Discount'),$options,_('Full')).'</TD>';
	echo '<TD>'.TextInput($student['BARCODE'],'food_service[BARCODE]',_('Barcode'),'size=12 maxlength=25').'</TD>';
	echo '</TR>';
	echo '</TABLE>';

	echo '</TD></TR>';
	echo '</TABLE>';
}
?>
