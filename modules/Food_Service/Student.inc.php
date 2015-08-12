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

	echo '<TABLE class="width-100p cellspacing-0">';
	echo '<TR><TD class="valign-top">';

	echo '<TABLE class="width-100p">';
	echo '<TR>';
	echo '<TD>';

	// warn if account non-existent (balance query failed)
	if($student['BALANCE']=='')
	{
		echo TextInput(array($student['ACCOUNT_ID'],'<span style="color:red">'.$student['ACCOUNT_ID'].'</span>'),'food_service[ACCOUNT_ID]',_('Account ID'),'size=12 maxlength=10');

		$warning = _('Non-existent account!');

		$tipJS = '<script>var tiptitle1='.json_encode(_('Warning')).'; var tipmsg1='.json_encode($warning).';</script>';

		echo $tipJS.button('warning','','"#" onMouseOver="stm([tiptitle1,tipmsg1])" onMouseOut="htm()" onclick="return false;"');
	}
	else
	 	echo TextInput($student['ACCOUNT_ID'],'food_service[ACCOUNT_ID]','Account ID','size=12 maxlength=10');

	// warn if other students associated with the same account
	if(count($xstudents))
	{
		$warning = _('Other students associated with the same account').':<BR />';

		foreach($xstudents as $xstudent)
			$warning .= '&nbsp;'.$xstudent['FULL_NAME'].'<BR />';

		$tipJS = '<script>var tiptitle2='.json_encode(_('Warning')).'; var tipmsg2='.json_encode($warning).';</script>';

		echo $tipJS.button('warning','','"#" onMouseOver="stm([tiptitle2,tipmsg2])" onMouseOut="htm()" onclick="return false;"');
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
