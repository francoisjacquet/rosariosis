<?php
/**
* @file $Id: MassAssignPayments.php 422 2007-02-10 22:08:22Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if (count($_REQUEST['student']))
	{
//modif Francois: fix SQL bug invalid amount
		if (is_numeric($_REQUEST['amount']))
		{
			foreach($_REQUEST['student'] as $student_id=>$yes)
			{
				$sql = "INSERT INTO BILLING_PAYMENTS (ID,SYEAR,SCHOOL_ID,STUDENT_ID,PAYMENT_DATE,AMOUNT,COMMENTS)
							values(".db_seq_nextval('BILLING_PAYMENTS_SEQ').",'".UserSyear()."','".UserSchool()."','".$student_id."','".DBDate()."','".preg_replace('/[^0-9,.]+/','',$_REQUEST['amount'])."','".$_REQUEST['comments']."')";
				DBQuery($sql);
			}
			$note[] = '<IMG SRC="assets/check_button.png" class="alignImg" />&nbsp;'._('That payment has been added to the selected students.');
		}
		else
			$error[] = _('Please enter a valid Amount.');
	}
	else
		$error[] = _('You must choose at least one student.');
		
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	unset($_REQUEST['modfunc']);
}

if(empty($_REQUEST['modfunc']))

{
	DrawHeader(ProgramTitle());
	
	if (isset($error))
		echo ErrorMessage($error);
	if(isset($note))
		echo ErrorMessage($note, 'note');
		
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
		DrawHeader('',SubmitButton(_('Add Payment to Selected Students')));
		
//modif Francois: css WPadmin
		echo '<BR /><TABLE class="postbox cellpadding-0 cellspacing-0" style="margin:0 auto;">';
		echo '<TR><TD class="center"><H3>'._('Payment').'</H3></TD></TR><TR><TD><TABLE class="width-100p cellspacing-0 cellpadding-5">';
		echo '<TR><TD style="text-align:right">'._('Payment Amount').'</TD><TD><INPUT type="text" name="amount" size="5" maxlength="10" required /></TD></TR>';
		echo '<TR><TD style="text-align:right">'._('Comment').'</TD><TD><INPUT type="text" name="comments" /></TD></TR>';
		echo '</TABLE></TD></TR>';
		echo '</TABLE><BR />';
	}
}

if(empty($_REQUEST['modfunc']))

{
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",NULL AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'student\');" /><A>');
	$extra['new'] = true;

	//Widgets('all');
	
	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center">'.SubmitButton(_('Add Payment to Selected Students')).'</span>';
		echo '</FORM>';
	}

}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return '&nbsp;&nbsp;<INPUT type="checkbox" name="student['.$THIS_RET['STUDENT_ID'].']" value="Y" />';
}

?>