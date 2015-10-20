<?php
// example:
//
//	if (($dp=DeletePrompt(_('Title')))
//		// OK
//		DBQuery("DELETE FROM BOK WHERE id='".$_REQUEST['benchmark_id']."'");
//	elseif ($dp==false)
//		// Cancel
//

function DeletePromptX($title,$action='Delete')
{
	$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('delete_ok','delete_cancel'));

	if (!$_REQUEST['delete_ok'] && !$_REQUEST['delete_cancel'])
	{
		echo '<BR />';
//FJ add translation
		PopTable('header',_('Confirm').(mb_strpos($action,' ')===false?' '.($action=='Delete'?_('Delete'):$action):''));
		echo '<div class="center"><h4>'.sprintf(_('Are you sure you want to %s that %s?'),($action=='Delete'?_('Delete'):$action),$title).'</h4><FORM action="'.$PHP_tmp_SELF.'" METHOD="POST"><INPUT type="submit" name="delete_ok" value="'._('OK').'"><INPUT type="submit" name="delete_cancel" value="'._('Cancel').'"></FORM></div>';
		PopTable('footer');
		return '';
	}
	if ($_REQUEST['delete_ok'])
	{
		unset($_REQUEST['delete_ok']);
		unset($_REQUEST['modfunc']);
		return true;
	}
	unset($_REQUEST['delete_cancel']);
	unset($_REQUEST['modfunc']);
	return false;
}
?>