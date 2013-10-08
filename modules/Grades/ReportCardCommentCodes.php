<?php
include 'modules/Grades/DeletePromptX.fnc.php';
//echo '<pre>'; var_dump($_REQUEST); echo '</pre>';
DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='update' && AllowEdit())
{
	if($_REQUEST['values'] && $_POST['values'])
	{
		if($_REQUEST['tab_id'])
		{
			foreach($_REQUEST['values'] as $id=>$columns)
			{
		//modif Francois: fix SQL bug invalid sort order
				if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
				{
					if($id!='new')
					{
						if($_REQUEST['tab_id']!='new')
							$sql = "UPDATE REPORT_CARD_COMMENT_CODES SET ";
						else
							$sql = "UPDATE REPORT_CARD_COMMENT_CODE_SCALES SET ";

						foreach($columns as $column=>$value)
							$sql .= $column."='".$value."',";

						if($_REQUEST['tab_id']!='new')
							$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
						else
							$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
						DBQuery($sql);
					}
					else
					{
						if($_REQUEST['tab_id']!='new')
						{
							$sql = 'INSERT INTO REPORT_CARD_COMMENT_CODES ';
							$fields = 'ID,SCHOOL_ID,SCALE_ID,';
							$values = db_seq_nextval('REPORT_CARD_COMMENT_CODES_SEQ').',\''.UserSchool().'\',\''.$_REQUEST['tab_id'].'\',';
						}
						else
						{
							$sql = 'INSERT INTO REPORT_CARD_COMMENT_CODE_SCALES ';
							$fields = 'ID,SCHOOL_ID,';
							$values = db_seq_nextval('REPORT_CARD_COMMENT_CODE_SCALES_SEQ').',\''.UserSchool().'\',';
						}

						$go = false;
						foreach($columns as $column=>$value)
							if($value)
							{
								$fields .= $column.',';
								$values .= '\''.$value.'\',';
								$go = true;
							}
						$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';

						if($go)
							DBQuery($sql);
					}
				}
				else
					$error = ErrorMessage(array(_('Please enter a valid Sort Order.')));
			}
		}
	}
	unset($_REQUEST['modfunc']);
}

if($_REQUEST['modfunc']=='remove' && AllowEdit())
{
	if($_REQUEST['tab_id']!='new')
	{
//modif Francois: add translation
		if(DeletePromptX(_('Report Card Comment')))
		{
			DBQuery("DELETE FROM REPORT_CARD_COMMENT_CODES WHERE ID='$_REQUEST[id]'");
		}
	}
	else
		if(DeletePromptX(_('Report Card Grading Scale')))
		{
			DBQuery("UPDATE REPORT_CARD_COMMENTS SET SCALE_ID=NULL WHERE SCALE_ID='$_REQUEST[id]'");
			DBQuery("DELETE FROM REPORT_CARD_COMMENT_CODES WHERE SCALE_ID='$_REQUEST[id]'");
			DBQuery("DELETE FROM REPORT_CARD_COMMENT_CODE_SCALES WHERE ID='$_REQUEST[id]'");
		}
}

if(empty($_REQUEST['modfunc']))

{
	$comment_scales_RET = DBGet(DBQuery('SELECT ID,TITLE FROM REPORT_CARD_COMMENT_CODE_SCALES WHERE SCHOOL_ID=\''.UserSchool().'\' ORDER BY SORT_ORDER,ID'),array(),array('ID'));
	if($_REQUEST['tab_id']=='' || $_REQUEST['tab_id']!='new' && !$comment_scales_RET[$_REQUEST['tab_id']])
		if(count($comment_scales_RET))
			$_REQUEST['tab_id'] = key($comment_scales_RET).'';
		else
			$_REQUEST['tab_id'] = 'new';

	$tabs = array();
	$comment_scale_select = array();
	foreach($comment_scales_RET as $id=>$comment_scale)
	{
		$tabs[] = array('title'=>$comment_scale[1]['TITLE'],'link'=>"Modules.php?modname=$_REQUEST[modname]&tab_id=$id");
		$comment_scale_select[$id] = $comment_scale[1]['TITLE'];
	}

	if($_REQUEST['tab_id']!='new')
	{
		$sql = 'SELECT * FROM REPORT_CARD_COMMENT_CODES WHERE SCALE_ID=\''.$_REQUEST['tab_id'].'\' AND SCHOOL_ID=\''.UserSchool().'\' ORDER BY SORT_ORDER,ID';
		$functions = array('TITLE'=>'makeCommentsInput','SHORT_NAME'=>'makeCommentsInput','COMMENT'=>'makeCommentsInput','SORT_ORDER'=>'makeCommentsInput');
		$LO_columns = array('TITLE'=>_('Title'),'SHORT_NAME'=>_('Short Name'),'COMMENT'=>_('Comment'),'SORT_ORDER'=>_('Sort Order'));

		if(User('PROFILE')=='admin' && AllowEdit())
		{
			$functions += array('SCALE_ID'=>'makeCommentsInput');
			$LO_columns += array('SCALE_ID'=>_('Comment Scale'));
		}

		$link['add']['html'] = array('TITLE'=>makeCommentsInput('','TITLE'),'SHORT_NAME'=>makeCommentsInput('','SHORT_NAME'),'COMMENT'=>makeCommentsInput('','COMMENT'),'SORT_ORDER'=>makeCommentsInput('','SORT_ORDER'));
		$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&tab_id=$_REQUEST[tab_id]";
		$link['remove']['variables'] = array('id'=>_('ID'));
		$link['add']['html']['remove'] = button('add');

		if(User('PROFILE')=='admin')
			$tabs[] = array('title'=>button('add','','',14),'link'=>"Modules.php?modname=$_REQUEST[modname]&tab_id=new");
		$subject = 'Codes';
	}
	else
	{
		$sql = 'SELECT * FROM REPORT_CARD_COMMENT_CODE_SCALES WHERE SCHOOL_ID=\''.UserSchool().'\' ORDER BY SORT_ORDER,ID';
		$functions = array('TITLE'=>'makeTextInput','COMMENT'=>'makeTextInput','SORT_ORDER'=>'makeTextInput');
		$LO_columns = array('TITLE'=>_('Comment Scale'),'COMMENT'=>_('Comment'),'SORT_ORDER'=>_('Sort Order'));

		$link['add']['html'] = array('TITLE'=>makeTextInput('','TITLE'),'COMMENT'=>makeTextInput('','COMMENT'),'HHR_GPA_VALUE'=>makeCommentsInput('','HHR_GPA_VALUE'),'HR_GPA_VALUE'=>makeCommentsInput('','HR_GPA_VALUE'),'SORT_ORDER'=>makeTextInput('','SORT_ORDER'));
		$link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&tab_id=new";
		$link['remove']['variables'] = array('id'=>_('ID'));
		$link['add']['html']['remove'] = button('add');

		$tabs[] = array('title'=>button('add','','',14),'link'=>"Modules.php?modname=$_REQUEST[modname]&tab_id=new");
		$subject = 'Comment Code Scales';
	}
	$LO_ret = DBGet(DBQuery($sql),$functions);

	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update&tab_id='.$_REQUEST['tab_id'].'" method="POST">';
	DrawHeader('',SubmitButton(_('Save')));
	echo '<BR />';
//modif Francois: fix SQL bug invalid sort order
	if(isset($error)) echo $error;

	$LO_options = array('save'=>false,'search'=>false,'header'=>WrapTabs($tabs,"Modules.php?modname=$_REQUEST[modname]&tab_id=$_REQUEST[tab_id]"));
        
    if ($subject == 'Codes')
	    ListOutput($LO_ret,$LO_columns,'Code','Codes',$link,array(),$LO_options);
    elseif ($subject == 'Comment Code Scales') 
        ListOutput($LO_ret,$LO_columns,'Comment Code Scale','Comment Code Scales',$link,array(),$LO_options);
        
	echo '<br /><span class="center">'.SubmitButton(_('Save')).'</span>';
	echo '</TD></TR></TABLE></FORM>';
}

function makeCommentsInput($value,$name)
{	global $THIS_RET,$comment_scale_select;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if($name=='SCALE_ID')
		return SelectInput($value,"values[$id][$name]",'',$comment_scale_select,false);
	elseif($name=='COMMENT')
		$extra = 'size=15 maxlength=100';
	elseif($name=='SHORT_NAME')
		$extra = 'size=15 maxlength=100';
	elseif($name=='SORT_ORDER')
		$extra = 'size=5 maxlength=5';
	else
		$extra = 'size=5 maxlength=5';

	return TextInput($value,"values[$id][$name]",'',$extra);
}

function makeTextInput($value,$name)
{	global $THIS_RET;

	if($THIS_RET['ID'])
		$id = $THIS_RET['ID'];
	else
		$id = 'new';

	if($name=='TITLE')
		$extra = 'size=15 maxlength=25';
	elseif($name=='COMMENT')
		$extra = 'size=15 maxlength=100';
	else
		$extra = 'size=5 maxlength=5';

	return TextInput($value,"values[$id][$name]",'',$extra);
}
?>
