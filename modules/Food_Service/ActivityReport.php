<?php

if($_REQUEST['month_date'] && $_REQUEST['day_date'] && $_REQUEST['year_date'])
	while(!VerifyDate($date = $_REQUEST['day_date'].'-'.$_REQUEST['month_date'].'-'.$_REQUEST['year_date']))
		$_REQUEST['day_date']--;
else
{
	$_REQUEST['day_date'] = date('d');
	$_REQUEST['month_date'] = mb_strtoupper(date('M'));
	$_REQUEST['year_date'] = date('Y');
	$date = $_REQUEST['day_date'].'-'.$_REQUEST['month_date'].'-'.$_REQUEST['year_date'];
}

if($_REQUEST['type'])
	$_SESSION['FSA_type'] = $_REQUEST['type'];
else
	$_SESSION['_REQUEST_vars']['type'] = $_REQUEST['type'] = $_SESSION['FSA_type'];

/*if($_REQUEST['type']=='staff')
{
	$tabcolor_s = '#DFDFDF'; $textcolor_s = '#999999';
	$tabcolor_u = Preferences('HEADER'); $textcolor_u = '#FFFFFF';
}
else
{
	$tabcolor_s = Preferences('HEADER'); $textcolor_s = '#FFFFFF';
	$tabcolor_u = '#DFDFDF'; $textcolor_u = '#999999';
}*/
//modif Francois: remove DrawTab params
$header .= '<a style="Modules.php?modname='.$_REQUEST['modname'].'&day_date='.$_REQUEST['day_date'].'&month_date='.$_REQUEST['month_date'].'&year_date='.$_REQUEST['year_date'].'&type=student">'._('Students').'</a>';
$header .= ' - <a style="Modules.php?modname='.$_REQUEST['modname'].'&day_date='.$_REQUEST['day_date'].'&month_date='.$_REQUEST['month_date'].'&year_date='.$_REQUEST['year_date'].'&type=staff">'._('Users').'</a>';

DrawHeader(($_SESSION['FSA_type']=='staff' ? _('User') : _('Student')).' &minus; '.ProgramTitle());
User('PROFILE')=='student'?'':DrawHeader($header);

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	require_once('modules/Food_Service/includes/DeletePromptX.fnc.php');
	if($_REQUEST['item_id']!='')
	{
//modif Francois: add translation
		if(DeletePromptX(_('Transaction Item')))
		{
			require_once('modules/Food_Service/includes/DeleteTransactionItem.fnc.php');
			DeleteTransactionItem($_REQUEST['transaction_id'],$_REQUEST['item_id'],$_REQUEST['type']);
			DBQuery('BEGIN; '.$sql1.'; '.$sql2.'; '.$sql3.'; COMMIT');
			unset($_REQUEST['modfunc']);
			unset($_REQUEST['delete_ok']);
			unset($_SESSION['_REQUEST_vars']['modfunc']);
			unset($_SESSION['_REQUEST_vars']['delete_ok']);
		}
	}
	else
	{
		if(DeletePromptX(_('Transaction')))
		{
			require_once('modules/Food_Service/includes/DeleteTransaction.fnc.php');
			DeleteTransaction($_REQUEST['transaction_id'],$_REQUEST['type']);
			unset($_REQUEST['modfunc']);
			unset($_REQUEST['delete_ok']);
			unset($_SESSION['_REQUEST_vars']['modfunc']);
			unset($_SESSION['_REQUEST_vars']['delete_ok']);
		}
	}
}

if(empty($_REQUEST['modfunc']))

{
$transaction_items = array('CASH'=>array(1=>array('DESCRIPTION'=>_('Cash'),'COUNT'=>0,'AMOUNT'=>0)),
			   'CHECK'=>array(1=>array('DESCRIPTION'=>_('Check'),'COUNT'=>0,'AMOUNT'=>0)),
			   'CREDIT CARD'=>array(1=>array('DESCRIPTION'=>_('Credit Card'),'COUNT'=>0,'AMOUNT'=>0)),
			   'DEBIT CARD'=>array(1=>array('DESCRIPTION'=>_('Debit Card'),'COUNT'=>0,'AMOUNT'=>0)),
			   'TRANSFER'=>array(1=>array('DESCRIPTION'=>_('Transfer'),'COUNT'=>0,'AMOUNT'=>0)),
			   ''=>array(1=>array('DESCRIPTION'=>'n/s','COUNT'=>0,'AMOUNT'=>0))
			   );

$menus_RET = DBGet(DBQuery('SELECT TITLE FROM FOOD_SERVICE_MENUS WHERE SCHOOL_ID=\''.UserSchool().'\' ORDER BY SORT_ORDER'));
//echo '<pre>'; var_dump($menus_RET); echo '</pre>';
$items = DBGet(DBQuery('SELECT SHORT_NAME,DESCRIPTION,0 AS COUNT FROM FOOD_SERVICE_ITEMS WHERE SCHOOL_ID=\''.UserSchool().'\' ORDER BY SORT_ORDER'),array(),array('SHORT_NAME'));
//echo '<pre>'; var_dump($items); echo '</pre>';

$types = array('DEPOSIT'=>array('DESCRIPTION'=>_('Deposit'),'COUNT'=>0,'AMOUNT'=>0,'ITEMS'=>$transaction_items),
	       'CREDIT'=>array('DESCRIPTION'=>_('Credit'),'COUNT'=>0,'AMOUNT'=>0,'ITEMS'=>$transaction_items),
	       'DEBIT'=>array('DESCRIPTION'=>_('Debit'),'COUNT'=>0,'AMOUNT'=>0,'ITEMS'=>$transaction_items)
	       );

foreach($menus_RET as $menu)
	$types += array($menu['TITLE']=>array('DESCRIPTION'=>$menu['TITLE'],'COUNT'=>0,'AMOUNT'=>0,'ITEMS'=>$items));

$type_select = '<span class="nobr">'._('Type').' <SELECT name="type_select"><OPTION value="">'._('Not Specified').'</OPTION>';
foreach($types as $short_name=>$type)
	$type_select .= '<OPTION value="'.$short_name.'"'.($_REQUEST['type_select']==$short_name ? ' SELECTED="SELECTED"' : '').'>'.$type['DESCRIPTION'].'</OPTION>';
$type_select .= '</SELECT></span>';

$staff_RET = DBGet(DBquery('SELECT STAFF_ID,FIRST_NAME||\' \'||LAST_NAME AS FULL_NAME FROM STAFF WHERE SYEAR=\''.UserSyear().'\' AND SCHOOLS LIKE \'%,'.UserSchool().',%\' AND PROFILE=\'admin\' ORDER BY LAST_NAME'));

$staff_select = '<span class="nobr">'._('User').' <SELECT name=staff_select><OPTION value="">'._('Not Specified').'</OPTION>';
foreach($staff_RET as $staff)
	$staff_select .= '<OPTION value="'.$staff['STAFF_ID'].'"'.($_REQUEST['staff_select']==$staff['STAFF_ID'] ? ' SELECTED="SELECTED"' : '').'>'.$staff['FULL_NAME'].'</OPTION>';
$staff_select .= '</SELECT></span>';

$PHP_tmp_SELF = PreparePHP_SELF();
echo '<FORM action="'.$PHP_tmp_SELF.'" method="POST">';
//modif Francois: add label on checkbox
DrawHeader(PrepareDate($date,'_date').' : '.$type_select.' : '.$staff_select.' : <INPUT type=submit value='._('Go').'>');
DrawHeader('<label>'.CheckBoxOnclick('by_name').' '._('Sort by Name').'</label>');
echo '</FORM>';

if($_REQUEST['type_select'])
	$where = "AND fst.SHORT_NAME='".$_REQUEST['type_select']."' ";

if($_REQUEST['staff_select'])
	$where = "AND fst.SELLER_ID='".$_REQUEST['staff_select']."' ";

if($_REQUEST['detailed_view']!='true')
	DrawHeader('<A HREF="'.PreparePHP_SELF($_REQUEST,array(),array('detailed_view'=>'true')).'">'._('Detailed View').'</A>');
else
	DrawHeader('<A HREF="'.PreparePHP_SELF($_REQUEST,array(),array('detailed_view'=>'false')).'">'._('Original View').'</A>');

//modif Francois: add translation
function types_locale($type) {
	$types = array('Deposit'=>_('Deposit'),'Credit'=>_('Credit'),'Debit'=>_('Debit'));
	if (array_key_exists($type, $types)) {
		return $types[$type];
	}
	return $type;
}
function options_locale($option) {
	$options = array('Cash '=>_('Cash'),'Check'=>_('Check'),'Credit Card'=>_('Credit Card'),'Debit Card'=>_('Debit Card'),'Transfer'=>_('Transfer'));
	if (array_key_exists($option, $options)) {
		return $options[$option];
	}
	return $option;
}

include('modules/Food_Service/'.($_REQUEST['type']=='staff' ? 'Users' : 'Students').'/ActivityReport.php');
//echo '<pre>'; var_dump($RET); echo '</pre>';

//echo '<pre>'; var_dump($types); echo '</pre>';
if($_REQUEST['detailed_view']=='true')
{
	$LO_types = array(array(array()));
	foreach($types as $type)
		if($type['COUNT'])
		{
			$LO_types[] = array(array('DESCRIPTION'=>$type['DESCRIPTION'],'DETAIL'=>'','COUNT'=>$type['COUNT'],'AMOUNT'=>number_format($type['AMOUNT'],2)));
			foreach($type['ITEMS'] as $item)
				if($item[1]['COUNT'])
					$LO_types[last($LO_types)][] = array('DESCRIPTION'=>$type['DESCRIPTION'],'DETAIL'=>$item[1]['DESCRIPTION'],'COUNT'=>$item[1]['COUNT'],'AMOUNT'=>number_format($item[1]['AMOUNT'],2));
		}
	$types_columns = array('DESCRIPTION'=>_('Description'),'DETAIL'=>_('Detail'),'COUNT'=>_('Count'),'AMOUNT'=>_('Amount'));
	$types_group = array('DESCRIPTION');
}
else
{
	$LO_types = array(array());
	foreach($types as $type)
		if($type['COUNT'])
			$LO_types[] = array('DESCRIPTION'=>$type['DESCRIPTION'],'COUNT'=>$type['COUNT'],'AMOUNT'=>number_format($type['AMOUNT'],2));
	$types_columns = array('DESCRIPTION'=>_('Description'),'COUNT'=>_('Count'),'AMOUNT'=>_('Amount'));
}
unset($LO_types[0]);
//echo '<pre>'; var_dump($LO_types); echo '</pre>';

ListOutput($LO_types,$types_columns,'Transaction Type','Transaction Types',false,$types_group,array('save'=>false,'search'=>false,'print'=>false));

ListOutput($RET,$columns,'Transaction','Transactions',$link,$group,array('save'=>false,'search'=>false,'print'=>false));
}

function last(&$array)
{
	end($array);
	return key($array);
}

function bump_count($value)
{	global $THIS_RET,$types;

	if($types[$value])
	{
		$types[$value]['COUNT']++;
		$types[$value]['AMOUNT'] += $THIS_RET['AMOUNT'];
	} else
		$types += array($value=>array('DESCRIPTION'=>'<span style="color:red">'.$value.'</span>','COUNT'=>1,'ITEMS'=>array(),'AMOUNT'=>$THIS_RET['AMOUNT']));
	return $value;
}

function bump_items_count($value)
{	global $THIS_RET,$types;

	if($types[$THIS_RET['TRANSACTION_SHORT_NAME']]['ITEMS'][$value])
	{
		$types[$THIS_RET['TRANSACTION_SHORT_NAME']]['ITEMS'][$value][1]['COUNT']++;
		$types[$THIS_RET['TRANSACTION_SHORT_NAME']]['ITEMS'][$value][1]['AMOUNT'] += $THIS_RET['AMOUNT'];;
	}
	else
		$types[$THIS_RET['TRANSACTION_SHORT_NAME']]['ITEMS'] += array($value=>array(1=>array('DESCRIPTION'=>'<span style="color:red">'.$value.'</span>','COUNT'=>1,'AMOUNT'=>$THIS_RET['AMOUNT'])));
	return $value;
}
?>