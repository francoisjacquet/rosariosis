<?php
DrawHeader(ProgramTitle());
//$_ROSARIO['allow_edit'] = true;

if($_REQUEST['tables'] && $_POST['tables'] && AllowEdit())
{
	$table = $_REQUEST['table'];
	foreach($_REQUEST['tables'] as $id=>$columns)
	{
//modif Francois: fix SQL bug invalid sort order
		if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
		{
			//modif Francois: added SQL constraint TITLE is not null
			if ((!isset($columns['TITLE']) || !empty($columns['TITLE'])))
			{
				if($id!='new')
				{
					if($columns['CATEGORY_ID'] && $columns['CATEGORY_ID']!=$_REQUEST['category_id'])
						$_REQUEST['category_id'] = $columns['CATEGORY_ID'];

					$sql = "UPDATE $table SET ";

					foreach($columns as $column=>$value)
						$sql .= $column."='".$value."',";
					$sql = mb_substr($sql,0,-1) . " WHERE ID='".$id."'";
					$go = true;
				}
				else
				{
					$sql = "INSERT INTO $table ";

					if($table=='SCHOOL_FIELDS')
					{
						$id = DBGet(DBQuery("SELECT ".db_seq_nextval('SCHOOL_SEQ').' AS ID '.FROM_DUAL));
						$id = $id[1]['ID'];
						$fields = "ID,";
						$values = $id.",";
						$_REQUEST['id'] = $id;

						switch($columns['TYPE'])
						{
							case 'text':
								DBQuery("ALTER TABLE SCHOOLS ADD CUSTOM_$id VARCHAR(255)");
							break;
							
							case 'numeric':
								DBQuery("ALTER TABLE SCHOOLS ADD CUSTOM_$id NUMERIC(10,2)");
							break;

							case 'date':
								DBQuery("ALTER TABLE SCHOOLS ADD CUSTOM_$id DATE");
							break;

							case 'textarea':
								DBQuery("ALTER TABLE SCHOOLS ADD CUSTOM_$id VARCHAR(5000)");
							break;
						}
					}

					$go = false;

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
				}

				if($go)
					DBQuery($sql);
			}
			else
				$error[] = _('Please fill in the required fields');
		}
		else
			$error[] = _('Please enter a valid Sort Order.');
	}
	unset($_REQUEST['tables']);
}

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if($_REQUEST['id'])
	{
		if(DeletePrompt(_('Student Field')))
		{
			$id = $_REQUEST['id'];
			DBQuery("DELETE FROM CUSTOM_FIELDS WHERE ID='".$id."'");
			DBQuery("ALTER TABLE STUDENTS DROP COLUMN CUSTOM_$id");
			$_REQUEST['modfunc'] = '';
			unset($_REQUEST['id']);
		}
	}
	elseif($_REQUEST['category_id'])
	{
		if(DeletePrompt(_('Student Field Category').' '._('and all fields in the category')))
		{
			$fields = DBGet(DBQuery("SELECT ID FROM CUSTOM_FIELDS WHERE CATEGORY_ID='".$_REQUEST['category_id']."'"));
			foreach($fields as $field)
			{
				DBQuery("DELETE FROM CUSTOM_FIELDS WHERE ID='".$field['ID']."'");
				DBQuery("ALTER TABLE STUDENTS DROP COLUMN CUSTOM_$field[ID]");
			}
			DBQuery("DELETE FROM STUDENT_FIELD_CATEGORIES WHERE ID='".$_REQUEST['category_id']."'");
			// remove from profiles and permissions
			DBQuery("DELETE FROM PROFILE_EXCEPTIONS WHERE MODNAME='Students/Student.php&category_id=$_REQUEST[category_id]'");
			DBQuery("DELETE FROM STAFF_EXCEPTIONS WHERE MODNAME='Students/Student.php&category_id=$_REQUEST[category_id]'");
			$_REQUEST['modfunc'] = '';
			unset($_REQUEST['category_id']);
		}
	}
}

//modif Francois: fix SQL bug invalid sort order
if(isset($error))
	echo ErrorMessage($error);

if(empty($_REQUEST['modfunc']))
{
	if(AllowEdit() && $_REQUEST['id']!='new' && $_REQUEST['id'])
	{
		$delete_button = '<script>var delete_link = document.createElement("a"); delete_link.href = "Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete&id='.$_REQUEST['id'].'"; delete_link.target = "body";</script>';
		$delete_button .= '<INPUT type="button" value="'._('Delete').'" onClick="javascript:ajaxLink(delete_link);" />';
	}

	// ADDING & EDITING FORM
	if($_REQUEST['id'] && $_REQUEST['id']!='new')
	{
		$sql = "SELECT TITLE,TYPE,DEFAULT_SELECTION,SORT_ORDER,REQUIRED FROM SCHOOL_FIELDS WHERE ID='".$_REQUEST['id']."'";
		$RET = DBGet(DBQuery($sql));
		$RET = $RET[1];
		$title = ParseMLField($RET['TITLE']);
	}
	elseif($_REQUEST['id']=='new')
		$title = _('New School Field');

	if($_REQUEST['id'])
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'];
		if($_REQUEST['id']!='new')
			echo '&id='.$_REQUEST['id'];
		echo '&table=SCHOOL_FIELDS" method="POST">';

		DrawHeader($title,$delete_button.SubmitButton(_('Save')));
		$header .= '<TABLE class="width-100p">';
		$header .= '<TR class="st">';
 
//modif Francois: field name required
		$header .= '<TD>' . MLTextInput($RET['TITLE'],'tables['.$_REQUEST['id'].'][TITLE]',(!$RET['TITLE']?'<span style="color:red">':'')._('Field Name').(!$RET['TITLE']?'</span>':'')) . '</TD>';

		// You can't change a student field type after it has been created
		// mab - allow changing between select and autos and edits and text and exports
		if($_REQUEST['id']!='new')
		{
			if($RET['TYPE']!='text')
			{
				$allow_edit = $_ROSARIO['allow_edit'];
				$AllowEdit = $_ROSARIO['AllowEdit'][$modname];
				$_ROSARIO['allow_edit'] = false;
				$_ROSARIO['AllowEdit'][$modname] = array();
				$type_options = array('text'=>_('Text'),'numeric'=>_('Number'),'date'=>_('Date'),'textarea'=>_('Long Text'));
			}
			else
				$type_options = array('text'=>_('Text'));
		}
		else
			$type_options = array('text'=>_('Text'),'numeric'=>_('Number'),'date'=>_('Date'),'textarea'=>_('Long Text'));

		$header .= '<TD>' . SelectInput($RET['TYPE'],'tables['.$_REQUEST['id'].'][TYPE]',_('Data Type'),$type_options,false) . '</TD>';
		if($_REQUEST['id']!='new' && $RET['TYPE']!='text')
		{
			$_ROSARIO['allow_edit'] = $allow_edit;
			$_ROSARIO['AllowEdit'][$modname] = $AllowEdit;
		}

		$header .= '<TD>' . TextInput($RET['SORT_ORDER'],'tables['.$_REQUEST['id'].'][SORT_ORDER]',_('Sort Order'),'size=5') . '</TD>';

		$header .= '</TR><TR class="st">';
		$colspan = 2;
		$header .= '<TD style="vertical-align:bottom;" colspan="'.$colspan.'">'.TextInput($RET['DEFAULT_SELECTION'],'tables['.$_REQUEST['id'].'][DEFAULT_SELECTION]',_('Default')).'<BR />'._('* for dates: YYYY-MM-DD').'</TD>';

		$new = ($_REQUEST['id']=='new');
		$header .= '<TD>' . CheckboxInput($RET['REQUIRED'],'tables['.$_REQUEST['id'].'][REQUIRED]',_('Required'),'',$new) . '</TD>';

		$header .= '</TR>';
		$header .= '</TABLE>';
	}
	else
		$header = false;

	if($header)
	{
		DrawHeader($header);
		echo '</FORM>';
	}

	// DISPLAY THE MENU
	$LO_options = array('save'=>false,'search'=>false); //,'add'=>true);

	if(count($categories_RET))
	{
		if($_REQUEST['category_id'])
		{
			foreach($categories_RET as $key=>$value)
			{
				if($value['ID']==$_REQUEST['category_id'])
					$categories_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	// FIELDS
	$sql = "SELECT ID,TITLE,TYPE,SORT_ORDER FROM SCHOOL_FIELDS ORDER BY SORT_ORDER,TITLE";
	$fields_RET = DBGet(DBQuery($sql),array('TYPE'=>'_makeType'));

	if(count($fields_RET))
	{
		if($_REQUEST['id'] && $_REQUEST['id']!='new')
		{
			foreach($fields_RET as $key=>$value)
			{
				if($value['ID']==$_REQUEST['id'])
					$fields_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	echo '<div class="st">';
	$columns = array('TITLE'=>_('School Field'),'SORT_ORDER'=>_('Order'),'TYPE'=>_('Data Type'));
	$link = array();
	$link['TITLE']['link'] = 'Modules.php?modname='.$_REQUEST['modname'];
	$link['TITLE']['variables'] = array('id'=>'ID');
	$link['add']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&id=new';

	$fields_RET = ParseMLArray($fields_RET,'TITLE');
	//modif Francois: no responsive table
	$LO_options['responsive'] = false;
	ListOutput($fields_RET,$columns,'School Field','School Fields',$link,array(),$LO_options);

	echo '</div>';
}

function _makeType($value,$name)
{
	$options = array('text'=>_('Text'),'date'=>_('Date'),'numeric'=>_('Number'),'textarea'=>_('Long Text'));
	return $options[$value];
}
?>
