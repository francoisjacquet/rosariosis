<?php

// example:
//
//	if(DeletePrompt(_('Title')))
//	{
//		DBQuery("DELETE FROM BOK WHERE id='$_REQUEST[benchmark_id]'");
//	}


function DeletePrompt($title,$action='Delete')
{
	$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('delete_ok'));

	if(!$_REQUEST['delete_ok'] && !$_REQUEST['delete_cancel'])
	{
		echo '<BR />';
		PopTable('header',_('Confirm').(mb_strpos($action,' ')===false?' '.($action=='Delete'?_('Delete'):$action):''));
		echo '<span class="center"><h4>'.sprintf(_('Are you sure you want to %s that %s?'),($action=='Delete'?_('Delete'):$action),$title).'</h4><FORM action="'.$PHP_tmp_SELF.'&delete_ok=1" METHOD="POST"><INPUT type="submit" value="'._('OK').'"><INPUT type="button" name="delete_cancel" value="'._('Cancel').'" onclick="javascript:this.form.action=\''.str_replace('&modfunc='.$_REQUEST['modfunc'], '', $PHP_tmp_SELF).'\';ajaxPostForm(this.form,true);"></FORM></span>';
		PopTable('footer');
		return false;
	}
	else
		return true;
}

function Prompt($title='Confirm',$question='',$message='',$pdf='')
{
	$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('delete_ok'),$pdf==true?array('_ROSARIO_PDF'=>true):array());

	if(!$_REQUEST['delete_ok'] && !$_REQUEST['delete_cancel'])
	{
		echo '<BR />';
		PopTable('header',($title=='Confirm'?_('Confirm'):$title));
		echo '<span class="center"><h4>'.$question.'</h4></span><FORM action="'.$PHP_tmp_SELF.'&delete_ok=1" METHOD="POST">'.$message.'<BR /><BR /><span class="center"><INPUT type="submit" value="'._('OK').'"><INPUT type="button" name="delete_cancel" value="'._('Cancel').'" onclick="javascript:this.form.action=\''.str_replace(array('&_ROSARIO_PDF='.$_REQUEST['_ROSARIO_PDF'], '&modfunc='.$_REQUEST['modfunc']), '', $PHP_tmp_SELF).'\';ajaxPostForm(this.form,true);"></span></FORM>';
		PopTable('footer');
		return false;
	}
	else
		return true;
}

?>