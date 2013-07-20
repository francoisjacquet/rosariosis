<?php
// example:
//
//	if(DeletePrompt(_('Title')))
//	{
//		DBQuery("DELETE FROM BOK WHERE id='$_REQUEST[benchmark_id]'");
//	}


function DeletePromptX($title,$action='Delete')
{
	$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('delete_ok','delete_cancel'));

	if(!$_REQUEST['delete_ok'] && !$_REQUEST['delete_cancel'])
	{
		echo '<BR />';
//modif Francois: add translation
		PopTable('header',_('Confirm').(mb_strpos($action,' ')===false?' '.($action=='Delete'?_('Delete'):$action):''));
		echo '<span class="center"><h4>'.sprintf(_('Are you sure you want to %s that %s?'),($action=='Delete'?_('Delete'):$action),$title).'</h4><FORM action="'.$PHP_tmp_SELF.'" METHOD="POST"><INPUT type="submit" name="delete_ok" value="'._('OK').'"><INPUT type="submit" name="delete_cancel" value="'._('Cancel').'"></FORM></span>';
		PopTable('footer');
		return false;
	}
	if($_REQUEST['delete_ok']) { unset($_REQUEST['delete_ok']); unset($_REQUEST['modfunc']); return true; }
	if($_REQUEST['delete_cancel']) { unset($_REQUEST['delete_cancel']); unset($_REQUEST['modfunc']); return false; }
	return false;
}

function PromptX($title='Confirm',$question='',$message='',$pdf='')
{
	$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('delete_ok'),$pdf==true?array('_ROSARIO_PDF'=>true):array());

	if(!$_REQUEST['delete_ok'] &&!$_REQUEST['delete_cancel'])
	{
		echo '<BR />';
		PopTable('header',$title);
		echo '<span class="center"><h4>'.$question.'</h4><FORM action="'.$PHP_tmp_SELF.'&delete_ok=1" METHOD="POST">'.$message.'<BR /><BR /><INPUT type="submit" value="'._('OK').'"><INPUT type="button" name="delete_cancel" value="'._('Cancel').'" onClick="javascript:history.back()"></FORM></span>';
		PopTable('footer');
		return false;
	}
	else
		return true;
}
?>
