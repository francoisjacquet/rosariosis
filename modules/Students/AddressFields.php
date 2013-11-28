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
					$sql = mb_substr($sql,0,-1) . " WHERE ID='$id'";
					$go = true;
				}
				else
				{
					$sql = "INSERT INTO $table ";

					if($table=='ADDRESS_FIELDS')
					{
						if($columns['CATEGORY_ID'])
						{
							$_REQUEST['category_id'] = $columns['CATEGORY_ID'];
							unset($columns['CATEGORY_ID']);
						}
						$id = DBGet(DBQuery("SELECT ".db_seq_nextval('ADDRESS_FIELDS_SEQ').' AS ID '.FROM_DUAL));
						$id = $id[1]['ID'];
						$fields = "ID,CATEGORY_ID,";
						$values = $id.",'".$_REQUEST['category_id']."',";
						$_REQUEST['id'] = $id;

						switch($columns['TYPE'])
						{
							case 'radio':
								DBQuery("ALTER TABLE ADDRESS ADD CUSTOM_$id VARCHAR(1)");
							break;

							case 'text':
							case 'exports':
							case 'select':
							case 'autos':
							case 'edits':
								DBQuery("ALTER TABLE ADDRESS ADD CUSTOM_$id VARCHAR(255)");
							break;

							case 'codeds':
								DBQuery("ALTER TABLE ADDRESS ADD CUSTOM_$id VARCHAR(15)");
							break;

							case 'multiple':
								DBQuery("ALTER TABLE ADDRESS ADD CUSTOM_$id VARCHAR(1000)");
							break;

							case 'numeric':
								DBQuery("ALTER TABLE ADDRESS ADD CUSTOM_$id NUMERIC(10,2)");
							break;

							case 'date':
								DBQuery("ALTER TABLE ADDRESS ADD CUSTOM_$id DATE");
							break;

							case 'textarea':
								DBQuery("ALTER TABLE ADDRESS ADD CUSTOM_$id VARCHAR(5000)");
							break;
						}
						DBQuery("CREATE INDEX ADDRESS_IND$id ON ADDRESS (CUSTOM_$id)");
					}
					elseif($table=='ADDRESS_FIELD_CATEGORIES')
					{
						$id = DBGet(DBQuery("SELECT ".db_seq_nextval('ADDRESS_FIELD_CATEGORIES_SEQ').' AS ID '.FROM_DUAL));
						$id = $id[1]['ID'];
						$fields = "ID,";
						$values = $id.",";
						$_REQUEST['category_id'] = $id;
					}

					$go = false;

					foreach($columns as $column=>$value)
					{
						if($value)
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
				$error = ErrorMessage(array(_('Please fill in the required fields')));
		}
		else
			$error = ErrorMessage(array(_('Please enter a valid Sort Order.')));
	}
	unset($_REQUEST['tables']);
}

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if($_REQUEST['id'])
	{
		if(DeletePrompt(_('Address Field')))
		{
			$id = $_REQUEST['id'];
			DBQuery("DELETE FROM ADDRESS_FIELDS WHERE ID='$id'");
			DBQuery("ALTER TABLE ADDRESS DROP COLUMN CUSTOM_$id");
			$_REQUEST['modfunc'] = '';
			unset($_REQUEST['id']);
		}
	}
	elseif($_REQUEST['category_id'])
	{
		if(DeletePrompt(_('Address Field Category').' '._('and all fields in the category')))
		{
			$fields = DBGet(DBQuery("SELECT ID FROM ADDRESS_FIELDS WHERE CATEGORY_ID='$_REQUEST[category_id]'"));
			foreach($fields as $field)
			{
				DBQuery("DELETE FROM ADDRESS_FIELDS WHERE ID='$field[ID]'");
				DBQuery("ALTER TABLE ADDRESS DROP COLUMN CUSTOM_$field[ID]");
			}
			DBQuery("DELETE FROM ADDRESS_FIELD_CATEGORIES WHERE ID='$_REQUEST[category_id]'");
			$_REQUEST['modfunc'] = '';
			unset($_REQUEST['category_id']);
		}
	}
}

if(empty($_REQUEST['modfunc']))

{
//modif Francois: fix SQL bug invalid sort order
	if(isset($error)) echo $error;
	// CATEGORIES
	$sql = "SELECT ID,TITLE,SORT_ORDER FROM ADDRESS_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE";
	$QI = DBQuery($sql);
	$categories_RET = DBGet($QI);

	if(AllowEdit() && $_REQUEST['id']!='new' && $_REQUEST['category_id']!='new' && ($_REQUEST['id'] || $_REQUEST['category_id']))
	{
		$delete_button = '<script type="text/javascript">var delete_link = document.createElement("a"); delete_link.href = "Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete&category_id='.$_REQUEST['category_id'].'&id='.$_REQUEST['id'].'"; delete_link.target = "body";</script>';
		$delete_button .= '<INPUT type="button" value="'._('Delete').'" onClick="javascript:ajaxLink(delete_link);" />';
	}

	// ADDING & EDITING FORM
	if($_REQUEST['id'] && $_REQUEST['id']!='new')
	{
		$sql = "SELECT CATEGORY_ID,TITLE,TYPE,SELECT_OPTIONS,DEFAULT_SELECTION,SORT_ORDER,REQUIRED,(SELECT TITLE FROM ADDRESS_FIELD_CATEGORIES WHERE ID=CATEGORY_ID) AS CATEGORY_TITLE FROM ADDRESS_FIELDS WHERE ID='$_REQUEST[id]'";
		$RET = DBGet(DBQuery($sql));
		$RET = $RET[1];
		$title = ParseMLField($RET['CATEGORY_TITLE']).' - '.ParseMLField($RET['TITLE']);
	}
	elseif($_REQUEST['category_id'] && $_REQUEST['category_id']!='new' && $_REQUEST['id']!='new')
	{
		$sql = "SELECT TITLE,RESIDENCE,MAILING,BUS,SORT_ORDER
				FROM ADDRESS_FIELD_CATEGORIES
				WHERE ID='$_REQUEST[category_id]'";
		$RET = DBGet(DBQuery($sql));
		$RET = $RET[1];
		$title = ParseMLField($RET['TITLE']);
	}
	elseif($_REQUEST['id']=='new')
		$title = _('New Address Field');
	elseif($_REQUEST['category_id']=='new')
		$title = _('New Address Field Category');

	if($_REQUEST['id'])
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&category_id='.$_REQUEST['category_id'];
		if($_REQUEST['id']!='new')
			echo '&id='.$_REQUEST['id'];
		echo '&table=ADDRESS_FIELDS" method="POST">';

		DrawHeader($title,$delete_button.SubmitButton(_('Save')));
		$header .= '<TABLE class="width-100p cellpadding-3">';
		$header .= '<TR class="st">';

//modif Francois: field name required
		$header .= '<TD>' . MLTextInput($RET['TITLE'],'tables['.$_REQUEST['id'].'][TITLE]',(!$RET['TITLE']?'<span style="color:red">':'')._('Field Name').(!$RET['TITLE']?'</span>':''), 'required').'</TD>';

		// You can't change an address field type after it has been created
		// mab - allow changing between select and autos and edits and text and exports
		if($_REQUEST['id']!='new')
		{
			if($RET['TYPE']!='select' && $RET['TYPE']!='autos' && $RET['TYPE']!='edits' && $RET['TYPE']!='text' && $RET['TYPE']!='exports')
			{
				$allow_edit = $_ROSARIO['allow_edit'];
				$AllowEdit = $_ROSARIO['AllowEdit'][$modname];
				$_ROSARIO['allow_edit'] = false;
				$_ROSARIO['AllowEdit'][$modname] = array();
				$type_options = array('select'=>_('Pull-Down'),'autos'=>_('Auto Pull-Down'),'edits'=>_('Edit Pull-Down'),'text'=>_('Text'),'radio'=>_('Checkbox'),'codeds'=>_('Coded Pull-Down'),'exports'=>_('Export Pull-Down'),'numeric'=>_('Number'),'multiple'=>_('Select Multiple from Options'),'date'=>_('Date'),'textarea'=>_('Long Text'));
			}
			else
				$type_options = array('select'=>_('Pull-Down'),'autos'=>_('Auto Pull-Down'),'edits'=>_('Edit Pull-Down'),'exports'=>_('Export Pull-Down'),'text'=>_('Text'));
		}
		else
			$type_options = array('select'=>_('Pull-Down'),'autos'=>_('Auto Pull-Down'),'edits'=>_('Edit Pull-Down'),'text'=>_('Text'),'radio'=>_('Checkbox'),'codeds'=>_('Coded Pull-Down'),'exports'=>_('Export Pull-Down'),'numeric'=>_('Number'),'multiple'=>_('Select Multiple from Options'),'date'=>_('Date'),'textarea'=>_('Long Text'));

		$header .= '<TD>' . SelectInput($RET['TYPE'],'tables['.$_REQUEST['id'].'][TYPE]',_('Data Type'),$type_options,false) . '</TD>';
		if($_REQUEST['id']!='new' && $RET['TYPE']!='select' && $RET['TYPE']!='autos' && $RET['TYPE']!='edits' && $RET['TYPE']!='text' && $RET['TYPE']!='exports')
		{
			$_ROSARIO['allow_edit'] = $allow_edit;
			$_ROSARIO['AllowEdit'][$modname] = $AllowEdit;
		}
		foreach($categories_RET as $type)
			$categories_options[$type['ID']] = ParseMLField($type['TITLE']);

		$header .= '<TD>' . MLSelectInput($RET['CATEGORY_ID']?$RET['CATEGORY_ID']:$_REQUEST['category_id'],'tables['.$_REQUEST['id'].'][CATEGORY_ID]',_('Address Field Category'),$categories_options,false) . '</TD>';

		$header .= '<TD>' . TextInput($RET['SORT_ORDER'],'tables['.$_REQUEST['id'].'][SORT_ORDER]',_('Sort Order'),'size=5') . '</TD>';

		$header .= '</TR><TR class="st">';
		$colspan = 2;
		if($RET['TYPE']=='autos' || $RET['TYPE']=='edits' || $RET['TYPE']=='select' || $RET['TYPE']=='codeds' || $RET['TYPE']=='multiple' || $RET['TYPE']=='exports' || $_REQUEST['id']=='new')
		{
			$header .= '<TD colspan="2">'.TextAreaInput($RET['SELECT_OPTIONS'],'tables['.$_REQUEST['id'].'][SELECT_OPTIONS]',_('Pull-Down').'/'._('Auto Pull-Down').'/'._('Coded Pull-Down').'/'._('Select Multiple from Options').'<BR />'._('* one per line'),'rows=7 cols=40') . '</TD>';
			$colspan = 1;
		}
		$header .= '<TD style="vertical-align:bottom;" colspan="'.$colspan.'">'.TextInput($RET['DEFAULT_SELECTION'],'tables['.$_REQUEST['id'].'][DEFAULT_SELECTION]',_('Default')).'<BR />'._('* for dates: YYYY-MM-DD').',<BR />&nbsp;'._('for checkboxes: Y').'</TD>';

		$new = ($_REQUEST['id']=='new');
		$header .= '<TD>' . CheckboxInput($RET['REQUIRED'],'tables['.$_REQUEST['id'].'][REQUIRED]',_('Required'),'',$new) . '</TD>';

		$header .= '</TR>';
		$header .= '</TABLE>';
	}
	elseif($_REQUEST['category_id'])
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&table=ADDRESS_FIELD_CATEGORIES';
		if($_REQUEST['category_id']!='new')
			echo '&category_id='.$_REQUEST['category_id'];
		echo '" method="POST">';
		DrawHeader($title,$delete_button.SubmitButton(_('Save')));
		$header .= '<TABLE class="width-100p cellpadding-3">';
		$header .= '<TR class="st">';

//modif Francois: title required
		$header .= '<TD>' . MLTextInput($RET['TITLE'],'tables['.$_REQUEST['category_id'].'][TITLE]',(!$RET['TITLE']?'<span style="color:red">':'')._('Title').(!$RET['TITLE']?'</span>':''), 'required') . '</TD>';
		$header .= '<TD>' . TextInput($RET['SORT_ORDER'],'tables['.$_REQUEST['category_id'].'][SORT_ORDER]',_('Sort Order'),'size=5') . '</TD>';

		if($_REQUEST['category_id']=='new')
			$new = true;
		$header .= '<TD><TABLE><TR>';
		$header .= '<TD>' . CheckboxInput($RET['RESIDENCE'],'tables['.$_REQUEST['category_id'].'][RESIDENCE]',_('Residence'),'',$new,'<IMG SRC="assets/check_button.png" height="15">','<IMG SRC="assets/x_button.png" height="15">') . '</TD>';
		$header .= '<TD>' . CheckboxInput($RET['MAILING'],'tables['.$_REQUEST['category_id'].'][MAILING]',_('Mailing'),'',$new,'<IMG SRC="assets/check_button.png" height="15">','<IMG SRC="assets/x_button.png" height="15">') . '</TD>';
		$header .= '<TD>' . CheckboxInput($RET['BUS'],'tables['.$_REQUEST['category_id'].'][BUS]',_('Bus'),'',$new,'<IMG SRC="assets/check_button.png" height="15">','<IMG SRC="assets/x_button.png" height="15">') . '</TD>';
		$header .= '</TR><TR>';
		$header .= '<TD colspan="3"><span class="legend-gray">'._('Note: All unchecked means applies to all addresses').'</span></TD>';
		$header .= '</TR></TABLE></TD>';

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

	echo '<div class="st">';
	$columns = array('TITLE'=>_('Category'),'SORT_ORDER'=>_('Sort Order'));
	$link = array();
	$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]";
	$link['TITLE']['variables'] = array('category_id'=>'ID');
	$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&category_id=new";

    $categories_RET = ParseMLArray($categories_RET,'TITLE');
	//modif Francois: no responsive table
	$LO_options['responsive'] = false;
	ListOutput($categories_RET,$columns,'Address Field Category','Address Field Categories',$link,array(),$LO_options);
	echo '</div>';

	// FIELDS
	if($_REQUEST['category_id'] && $_REQUEST['category_id']!='new' && count($categories_RET))
	{
		$sql = "SELECT ID,TITLE,TYPE,SORT_ORDER FROM ADDRESS_FIELDS WHERE CATEGORY_ID='".$_REQUEST['category_id']."' ORDER BY SORT_ORDER,TITLE";
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
		$columns = array('TITLE'=>_('Address Field'),'SORT_ORDER'=>_('Sort Order'),'TYPE'=>_('Data Type'));
		$link = array();
		$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&category_id=$_REQUEST[category_id]";
		$link['TITLE']['variables'] = array('id'=>'ID');
		$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&category_id=$_REQUEST[category_id]&id=new";

        $fields_RET = ParseMLArray($fields_RET,'TITLE');
		ListOutput($fields_RET,$columns,'Address Field','Address Fields',$link,array(),$LO_options);

		echo '</div>';
	}
}

function _makeType($value,$name)
{
	$options = array('radio'=>_('Checkbox'),'text'=>_('Text'),'autos'=>_('Auto Pull-Down'),'edits'=>_('Edit Pull-Down'),'select'=>_('Pull-Down'),'codeds'=>_('Coded Pull-Down'),'exports'=>_('Export Pull-Down'),'date'=>_('Date'),'numeric'=>_('Number'),'textarea'=>_('Long Text'),'multiple'=>_('Select Multiple'));
	return $options[$value];
}
?>