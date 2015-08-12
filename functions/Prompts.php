<?php

// example:
//
//	if(DeletePrompt(_('Title')))
//	{
//		DBQuery("DELETE FROM BOK WHERE id='".$_REQUEST['benchmark_id']."'");
//	}


function DeletePrompt($title,$action='Delete',$remove_modfunc_on_cancel=true)
{
	if(!$_REQUEST['delete_ok'] && !$_REQUEST['delete_cancel'])
	{
		echo '<BR />';

		$PHP_tmp_SELF = $PHP_tmp_SELF_cancel = PreparePHP_SELF($_REQUEST,array('delete_ok'));

		if ($remove_modfunc_on_cancel)
			$PHP_tmp_SELF_cancel = str_replace('&modfunc='.$_REQUEST['modfunc'], '', $PHP_tmp_SELF_cancel);

		PopTable('header',_('Confirm').(mb_strpos($action,' ')===false?' '.($action=='Delete'?_('Delete'):$action):''));

		echo '<span class="center"><h4>'.sprintf(_('Are you sure you want to %s that %s?'),($action=='Delete'?_('Delete'):$action),$title).'</h4><FORM action="'.$PHP_tmp_SELF.'&delete_ok=1" METHOD="POST"><INPUT type="submit" value="'._('OK').'"><INPUT type="button" name="delete_cancel" value="'._('Cancel').'" onclick="javascript:this.form.action=\''.$PHP_tmp_SELF_cancel.'&delete_cancel=1\';ajaxPostForm(this.form,true);"></FORM></span>';

		PopTable('footer');

		return false;
	}
	else
		return true;
}

function Prompt($title='Confirm',$question='',$message='',$pdf='',$remove_modfunc_on_cancel=true)
{
	if(!$_REQUEST['delete_ok'] && !$_REQUEST['delete_cancel'])
	{
		echo '<BR />';

		$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('delete_ok'),$pdf==true?array('_ROSARIO_PDF'=>true):array());

		$PHP_tmp_SELF_cancel = str_replace('&_ROSARIO_PDF='.$_REQUEST['_ROSARIO_PDF'], '', $PHP_tmp_SELF);

		if ($remove_modfunc_on_cancel)
			$PHP_tmp_SELF_cancel = str_replace('&modfunc='.$_REQUEST['modfunc'], '', $PHP_tmp_SELF_cancel);

		PopTable('header',($title=='Confirm'?_('Confirm'):$title));

		echo '<span class="center"><h4>'.$question.'</h4></span><FORM action="'.$PHP_tmp_SELF.'&delete_ok=1" METHOD="POST">'.$message.'<BR /><BR /><span class="center"><INPUT type="submit" value="'._('OK').'"><INPUT type="button" name="delete_cancel" value="'._('Cancel').'" onclick="javascript:this.form.action=\''.$PHP_tmp_SELF_cancel.'&delete_cancel=1\';ajaxPostForm(this.form,true);"></span></FORM>';

		PopTable('footer');

		return false;
	}
	else
		return true;
}

// Use the BackPrompt function only if there is an error in a script opened in a new window (ie. PDF printing)
// BackPrompt will alert the message and close the window
function BackPrompt($message)
{
//FJ errors not readable
//	echo '<SCRIPT>alert("'.str_replace(array("'",'"'),array('&#39;','&quot;'),$message).'");self.history.go(-1);</SCRIPT>';
	?>

	<SCRIPT>alert(<?php echo json_encode($message); ?>);window.close();</SCRIPT>

	<?php exit();
}
