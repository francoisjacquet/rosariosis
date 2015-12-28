<?php
DrawHeader(ProgramTitle());
//$_ROSARIO['allow_edit'] = true;

if ( $_REQUEST['tables'] && $_POST['tables'] && AllowEdit())
{
	$table = $_REQUEST['table'];
	foreach ( (array) $_REQUEST['tables'] as $id => $columns)
	{
//FJ fix SQL bug invalid sort order
		if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
		{
			//FJ added SQL constraint TITLE is not null
			if ((!isset($columns['TITLE']) || !empty($columns['TITLE'])))
			{
				if ( $id!='new')
				{
					if ( $columns['CATEGORY_ID'] && $columns['CATEGORY_ID']!=$_REQUEST['category_id'])
						$_REQUEST['category_id'] = $columns['CATEGORY_ID'];

					$sql = "UPDATE $table SET ";

					foreach ( (array) $columns as $column => $value)
						$sql .= $column."='".$value."',";
					$sql = mb_substr($sql,0,-1) . " WHERE ID='".$id."'";
					$go = true;
				}
				else
				{
					$sql = "INSERT INTO $table ";

					if ( $table=='SCHOOL_FIELDS')
					{
						$id = DBGet(DBQuery("SELECT ".db_seq_nextval('SCHOOL_SEQ').' AS ID '.FROM_DUAL));
						$id = $id[1]['ID'];
						$fields = "ID,";
						$values = $id.",";
						$_REQUEST['id'] = $id;

						switch ( $columns['TYPE'])
						{
							case 'text':
								DBQuery("ALTER TABLE SCHOOLS ADD CUSTOM_$id VARCHAR(255)");
							break;
							
							case 'numeric':
								DBQuery("ALTER TABLE SCHOOLS ADD CUSTOM_$id NUMERIC(20,2)");
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

					foreach ( (array) $columns as $column => $value)
					{
						if ( !empty($value) || $value=='0')
						{
							$fields .= $column.',';
							$values .= "'".$value."',";
							$go = true;
						}
					}
					$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
				}

				if ( $go)
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

if ( $_REQUEST['modfunc'] === 'delete'
	&& AllowEdit() )
{
	if ( isset( $_REQUEST['id'] )
		&& intval( $_REQUEST['id'] ) > 0 )
	{
		if ( DeletePrompt( _( 'School Field' ) ) )
		{
			DeleteDBField( 'STUDENTS', $_REQUEST['id'] );
			$_REQUEST['modfunc'] = false;

			unset( $_REQUEST['id'] );
		}
	}
}

//FJ fix SQL bug invalid sort order
if (isset($error))
	echo ErrorMessage($error);

if (empty($_REQUEST['modfunc']))
{
	/*if (AllowEdit() && $_REQUEST['id']!='new' && $_REQUEST['id'])
	{
		$delete_URL = "'Modules.php?modname=" . $_REQUEST['modname'] .
			'&modfunc=delete&id=' . $_REQUEST['id'] . "'";

		$delete_button = '<input type="button" value="' . _( 'Delete' ) . '" onClick="javascript:ajaxLink(' . $delete_URL . ');" />';
	}*/

	// ADDING & EDITING FORM
	if ( $_REQUEST['id'] && $_REQUEST['id']!='new')
	{
		$sql = "SELECT TITLE,TYPE,DEFAULT_SELECTION,SORT_ORDER,REQUIRED FROM SCHOOL_FIELDS WHERE ID='".$_REQUEST['id']."'";
		$RET = DBGet(DBQuery($sql));
		$RET = $RET[1];
		$title = ParseMLField($RET['TITLE']);
	}
	elseif ( $_REQUEST['id']=='new')
		$title = _('New School Field');

	/*if ( $_REQUEST['id'])
	{
		echo '<form action="Modules.php?modname='.$_REQUEST['modname'];

		if ( $_REQUEST['id']!='new')
			echo '&id='.$_REQUEST['id'];

		echo '&table=SCHOOL_FIELDS" method="POST">';

		DrawHeader($title,$delete_button.SubmitButton(_('Save')));

		$header .= '<table class="width-100p valign-top fixed-col"><tr class="st">';

		//FJ field name required
		$header .= '<td>' . MLTextInput($RET['TITLE'],'tables['.$_REQUEST['id'].'][TITLE]',(! $RET['TITLE']?'<span class="legend-red">':'')._('Field Name').(! $RET['TITLE']?'</span>':'')) . '</td>';

		// You can't change a student field type after it has been created
		// mab - allow changing between select and autos and edits and text and exports
		if ( $_REQUEST['id']!='new')
		{
			if ( $RET['TYPE']!='text')
			{
				$allow_edit = $_ROSARIO['allow_edit'];
				$AllowEdit = $_ROSARIO['AllowEdit'][ $modname ];
				$_ROSARIO['allow_edit'] = false;
				$_ROSARIO['AllowEdit'][ $modname ] = array();
				$type_options = array('text' => _('Text'),'numeric' => _('Number'),'date' => _('Date'),'textarea' => _('Long Text'));
			}
			else
				$type_options = array('text' => _('Text'));
		}
		else
			$type_options = array('text' => _('Text'),'numeric' => _('Number'),'date' => _('Date'),'textarea' => _('Long Text'));

		$header .= '<td>' . SelectInput($RET['TYPE'],'tables['.$_REQUEST['id'].'][TYPE]',_('Data Type'),$type_options,false) . '</td>';
		if ( $_REQUEST['id']!='new' && $RET['TYPE']!='text')
		{
			$_ROSARIO['allow_edit'] = $allow_edit;
			$_ROSARIO['AllowEdit'][ $modname ] = $AllowEdit;
		}

		$header .= '<td>' . TextInput($RET['SORT_ORDER'],'tables['.$_REQUEST['id'].'][SORT_ORDER]',_('Sort Order'),'size=5') . '</td>';

		$header .= '</tr><tr class="st">';
		$colspan = 2;
		$header .= '<td style="vertical-align:bottom;" colspan="'.$colspan.'">'.TextInput($RET['DEFAULT_SELECTION'],'tables['.$_REQUEST['id'].'][DEFAULT_SELECTION]',_('Default')).'<br />'._('* for dates: YYYY-MM-DD').'</td>';

		$new = ($_REQUEST['id']=='new');
		$header .= '<td>' . CheckboxInput($RET['REQUIRED'],'tables['.$_REQUEST['id'].'][REQUIRED]',_('Required'),'',$new) . '</td>';

		$header .= '</tr></table>';
	}
	else
		$header = false;

	if ( $header)
	{
		DrawHeader($header);
		echo '</form>';
	}*/

	require_once 'ProgramFunctions/Fields.fnc.php';

	echo GetFieldsForm(
		'SCHOOL',
		$title,
		$RET,
		$_REQUEST['id'],
		null,
		null,
		array('text' => _('Text'),'numeric' => _('Number'),'date' => _('Date'),'textarea' => _('Long Text'))
	);

	// DISPLAY THE MENU
	$LO_options = array('save'=>false,'search'=>false); //,'add'=>true);

	if (count($categories_RET))
	{
		if ( $_REQUEST['category_id'])
		{
			foreach ( (array) $categories_RET as $key => $value)
			{
				if ( $value['ID']==$_REQUEST['category_id'])
					$categories_RET[ $key ]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	// FIELDS
	$sql = "SELECT ID,TITLE,TYPE,SORT_ORDER FROM SCHOOL_FIELDS ORDER BY SORT_ORDER,TITLE";
	$fields_RET = DBGet(DBQuery($sql),array('TYPE' => '_makeType'));

	if (count($fields_RET))
	{
		if ( $_REQUEST['id'] && $_REQUEST['id']!='new')
		{
			foreach ( (array) $fields_RET as $key => $value)
			{
				if ( $value['ID']==$_REQUEST['id'])
					$fields_RET[ $key ]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	echo '<div class="st">';
	$columns = array('TITLE' => _('School Field'),'SORT_ORDER' => _('Order'),'TYPE' => _('Data Type'));
	$link = array();
	$link['TITLE']['link'] = 'Modules.php?modname='.$_REQUEST['modname'];
	$link['TITLE']['variables'] = array('id' => 'ID');
	$link['add']['link'] = 'Modules.php?modname='.$_REQUEST['modname'].'&id=new';

	$fields_RET = ParseMLArray($fields_RET,'TITLE');
	//FJ no responsive table
	$LO_options['responsive'] = false;
	ListOutput($fields_RET,$columns,'School Field','School Fields',$link,array(),$LO_options);

	echo '</div>';
}

function _makeType($value,$name)
{
	$options = array('text' => _('Text'),'date' => _('Date'),'numeric' => _('Number'),'textarea' => _('Long Text'));
	return $options[ $value ];
}
