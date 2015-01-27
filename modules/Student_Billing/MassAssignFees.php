<?php
/**
* @file $Id: MassAssignFees.php 422 2007-02-10 22:08:22Z focus-sis $
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
			$due_date = $_REQUEST['day'].'-'.$_REQUEST['month'].'-'.$_REQUEST['year'];
			if(VerifyDate($due_date))
			{
				foreach($_REQUEST['student'] as $student_id=>$yes)
				{
						$sql = "INSERT INTO BILLING_FEES (STUDENT_ID,ID,TITLE,AMOUNT,SYEAR,SCHOOL_ID,ASSIGNED_DATE,DUE_DATE,COMMENTS)
									values('".$student_id."',".db_seq_nextval('BILLING_FEES_SEQ').",'".$_REQUEST['title']."','".preg_replace('/[^0-9,.]+/','',$_REQUEST['amount'])."','".UserSyear()."','".UserSchool()."','".DBDate()."','".$due_date."','".$_REQUEST['comments']."')";
						DBQuery($sql);
				}
				$note[] = button('check') .'&nbsp;'._('That fee has been added to the selected students.');
			}
			else
				$error[] = _('The date you entered is not valid');
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
		DrawHeader('',SubmitButton(_('Add Fee to Selected Students')));

		echo '<BR />';

		PopTable('header', _('Fee'));

		echo '<TABLE class="col1-align-right">';

		echo '<TR><TD>'._('Title').'</TD><TD><INPUT type="text" name="title" required /></TD></TR>';

		echo '<TR><TD>'._('Amount').'</TD><TD><INPUT type="text" name="amount" size="5" maxlength="10" required /></TD></TR>';

		echo '<TR><TD>'._('Due Date').'</TD><TD>'.PrepareDate(DBDate(),'').'</TD></TR>';

		echo '<TR><TD>'._('Comment').'</TD><TD><INPUT type="text" name="comments" /></TD></TR>';

		echo '</TABLE>';

		PopTable('footer');

		echo '<BR />';
	}
}

if(empty($_REQUEST['modfunc']))

{
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",NULL AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'student\');"><A>');
	$extra['new'] = true;

	//Widgets('all');
	
	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center">'.SubmitButton(_('Add Fee to Selected Students')).'</span>';
		echo '</FORM>';
	}

}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return '&nbsp;&nbsp;<INPUT type="checkbox" name="student['.$THIS_RET['STUDENT_ID'].']" value="Y" />';
}

?>
