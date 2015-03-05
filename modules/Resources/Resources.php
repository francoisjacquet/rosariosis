<?php
if($_REQUEST['modfunc']=='update')
{
	if($_REQUEST['values'] && $_POST['values'] && AllowEdit())
	{
		foreach($_REQUEST['values'] as $id=>$columns)
		{
			if($id!='new')
			{
				$sql = "UPDATE RESOURCES SET ";
							
				foreach($columns as $column=>$value)
				{
					$sql .= $column."='".$value."',";
				}
				$sql = mb_substr($sql,0,-1) . " WHERE ID='".$id."'";
				DBQuery($sql);
			}
			else
			{
				$sql = "INSERT INTO RESOURCES ";

				$fields = 'ID,SCHOOL_ID,';
				$values = db_seq_nextval('RESOURCES_SEQ').",'".UserSchool()."',";

				$go = 0;
				foreach($columns as $column=>$value)
				{
					if(!empty($value) || $value=='0')
					{
						$fields .= $column.',';
						$values .= "'".$value."',";
						$go = true;
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
			
				if($go)
					DBQuery($sql);
			}
		}
	}

	unset($_REQUEST['modfunc']);
}

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if(DeletePrompt(_('Resource')))
	{
		DBQuery("DELETE FROM RESOURCES WHERE ID='".$_REQUEST['id']."'");
		unset($_REQUEST['modfunc']);
	}
}

if(empty($_REQUEST['modfunc']))
{
	$sql = "SELECT ID,TITLE,LINK FROM RESOURCES WHERE SCHOOL_ID='".UserSchool()."' ORDER BY ID";
	$QI = DBQuery($sql);
	$resources_RET = DBGet($QI,array('TITLE'=>'_makeTextInput','LINK'=>'_makeLink'));

	$columns = array('TITLE'=>_('Title'),'LINK'=>_('Link'));
	$link['add']['html'] = array('TITLE'=>_makeTextInput('','TITLE'),'LINK'=>_makeLink('','LINK'));
	$link['remove']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=remove';
	$link['remove']['variables'] = array('id'=>'ID');
	
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));

	ListOutput($resources_RET,$columns,'Resource','Resources',$link);
	echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</FORM>';
}

function _makeTextInput($value,$name)
{	global $THIS_RET;
	
	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';
	
	if($name=='LINK')
		$extra = 'maxlength=1000';
		
	if($name=='TITLE')
		$extra = 'maxlength=256';

	return TextInput($value,'values['.$id.']['.$name.']','',$extra);
}

function _makeLink($value,$name)
{
	if (AllowEdit())
	{
		if($value)
			return '<div style="display:table-cell;"><a href="'.$value.'" target="_blank">'._('Link').'</a>&nbsp;</div><div style="display:table-cell;">'._makeTextInput($value,$name).'</div>';
		else
			return _makeTextInput($value,$name);
	}
	
	//truncate links > 100 chars
	$truncated_link = $value;
	if (mb_strlen($truncated_link) > 100)
	{
		$separator = '/.../';
		$separatorlength = mb_strlen($separator) ;
		$maxlength = 100 - $separatorlength;
		$start = $maxlength / 2 ;
		$trunc =  mb_strlen($truncated_link) - $maxlength;
		$truncated_link = substr_replace($truncated_link, $separator, $start, $trunc);
	}
	 
	return '<a href="'.$value.'" target="_blank">'.$truncated_link.'</a>';
}
?>
