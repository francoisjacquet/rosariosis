<?php

StaffWidgets('fsa_status');
StaffWidgets('fsa_barcode');
StaffWidgets('fsa_exists_Y');

$extra['SELECT'] .= ",(SELECT coalesce(STATUS,'Active') FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS STATUS";
$extra['SELECT'] .= ",(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE";
$extra['functions'] += array('BALANCE'=>'red');
$extra['columns_after'] = array('BALANCE'=>_('Balance'),'STATUS'=>_('Status'));

Search('staff_id',$extra);

if(UserStaffID())
{
	$staff = DBGet(DBQuery("SELECT s.STAFF_ID,s.FIRST_NAME||' '||s.LAST_NAME AS FULL_NAME,(SELECT STAFF_ID FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS ACCOUNT_ID,(SELECT BALANCE FROM FOOD_SERVICE_STAFF_ACCOUNTS WHERE STAFF_ID=s.STAFF_ID) AS BALANCE FROM STAFF s WHERE s.STAFF_ID='".UserStaffID()."'"));
	$staff = $staff[1];

	echo '<FORM action="'.PreparePHP_SELF().'" method="POST">';
	DrawHeader(_('Timeframe').':'.PrepareDate($start_date,'_start').' '._('to').' '.PrepareDate($end_date,'_end').' : '.$type_select.' : <INPUT type="submit" value="'._('Go').'">');
	echo '</FORM>';

//modif Francois: fix bug no balance
//	DrawHeader(NoInput($staff['FULL_NAME'],'&nbsp;'.$staff['STAFF_ID']),'', NoInput(red($student['BALANCE']),_('Balance')));
	DrawHeader(NoInput($staff['FULL_NAME'],'&nbsp;'.$staff['STAFF_ID']),'', NoInput(red($staff['BALANCE']),_('Balance')));

	if($_REQUEST['detailed_view']!='true')
		DrawHeader("<A HREF=".PreparePHP_SELF($_REQUEST,array(),array('detailed_view'=>'true')).">"._('Detailed View')."</A>");
	else
		DrawHeader("<A HREF=".PreparePHP_SELF($_REQUEST,array(),array('detailed_view'=>'false')).">"._('Original View')."</A>");

	if($staff['ACCOUNT_ID'] && $staff['BALANCE']!='')
	{
		if($_REQUEST['type_select'])
			$where = " AND fst.SHORT_NAME='".$_REQUEST['type_select']."'";

		if($_REQUEST['detailed_view']=='true')
		{
            $RET = DBGet(DBQuery("SELECT fst.TRANSACTION_ID AS TRANS_ID,fst.TRANSACTION_ID,(SELECT sum(AMOUNT) FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,fst.STAFF_ID,fst.BALANCE,to_char(fst.TIMESTAMP,'YYYY-MM-DD') AS DATE,to_char(fst.TIMESTAMP,'HH:MI:SS AM') AS TIME,fst.DESCRIPTION,".db_case(array('fst.SELLER_ID',"''",'NULL',"(SELECT FIRST_NAME||' '||LAST_NAME FROM STAFF WHERE STAFF_ID=fst.SELLER_ID)"))." AS SELLER FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst WHERE fst.STAFF_ID='".UserStaffID()."' AND fst.SYEAR='".UserSyear()."' AND fst.TIMESTAMP BETWEEN '".$start_date."' AND date '".$end_date."' +1".$where." ORDER BY fst.TRANSACTION_ID DESC"),array('DATE'=>'ProperDate','BALANCE'=>'red'));
//modif Francois: add translation
			foreach($RET as $RET_key=>$RET_val) {
				$RET[$RET_key]=array_map('types_locale', $RET_val);
			}	
			
			// get details of each transaction
			foreach($RET as $key=>$value)
			{
				$tmpRET = DBGet(DBQuery('SELECT TRANSACTION_ID AS TRANS_ID,* FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID=\''.$value['TRANSACTION_ID'].'\''));
//modif Francois: add translation
				foreach($tmpRET as $RET_key=>$RET_val) {
					$tmpRET[$RET_key]=array_map('options_locale', $RET_val);
				}	
				// merge transaction and detail records
				$RET[$key] = array($RET[$key]) + $tmpRET;
			}
			$columns = array('TRANSACTION_ID'=>_('ID'),'DATE'=>_('Date'),'TIME'=>_('Time'),'BALANCE'=>_('Balance'),'DESCRIPTION'=>_('Description'),'AMOUNT'=>_('Amount'),'SELLER'=>_('User'));
			$group = array(array('TRANSACTION_ID'));
			$link['remove']['link'] = PreparePHP_SELF($_REQUEST,array(),array('modfunc'=>'delete'));
			$link['remove']['variables'] = array('transaction_id'=>'TRANS_ID','item_id'=>'ITEM_ID');
		}
		else
		{
			$RET = DBGet(DBQuery("SELECT fst.TRANSACTION_ID,(SELECT sum(AMOUNT) FROM FOOD_SERVICE_STAFF_TRANSACTION_ITEMS WHERE TRANSACTION_ID=fst.TRANSACTION_ID) AS AMOUNT,fst.BALANCE,to_char(fst.TIMESTAMP,'YYYY-MM-DD') AS DATE,to_char(fst.TIMESTAMP,'HH:MI:SS AM') AS TIME,fst.DESCRIPTION FROM FOOD_SERVICE_STAFF_TRANSACTIONS fst WHERE fst.STAFF_ID='".UserStaffID()."' AND SYEAR='".UserSyear()."' AND fst.TIMESTAMP BETWEEN '".$start_date."' AND date '".$end_date."' +1".$where." ORDER BY fst.TRANSACTION_ID DESC"),array('DATE'=>'ProperDate','BALANCE'=>'red'));
			$columns = array('TRANSACTION_ID'=>_('ID'),'DATE'=>_('Date'),'TIME'=>_('Time'),'BALANCE'=>_('Balance'),'DESCRIPTION'=>_('Description'),'AMOUNT'=>_('Amount'));
//modif Francois: add translation
			foreach($RET as $RET_key=>$RET_val) {
				$RET[$RET_key]=array_map('types_locale', $RET_val);
			}	
		}

		ListOutput($RET,$columns,'Transaction','Transactions',$link,$group);
	}
	else
		echo ErrorMessage(array(_('This user does not have a Meal Account.')));
}
?>
