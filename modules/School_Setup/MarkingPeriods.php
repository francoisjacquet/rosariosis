<?php
DrawHeader(ProgramTitle());

if(!$_REQUEST['marking_period_id'] && count($fy_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER"))))
{
	$_REQUEST['marking_period_id'] = $fy_RET[1]['MARKING_PERIOD_ID'];
	$_REQUEST['mp_term'] = 'FY';
}

unset($_SESSION['_REQUEST_vars']['marking_period_id']);
unset($_SESSION['_REQUEST_vars']['mp_term']);

if($_REQUEST['marking_period_id']=='new')
switch($_REQUEST['mp_term'])
{
	case 'FY':
		$title = _('New Year');
	break;

	case 'SEM':
		$title = _('New Semester');
	break;

	case 'QTR':
		$title = _('New Marking Period');
	break;

	case 'PRO':
		$title = _('New Progress Period');
	break;
}

// UPDATING
if($_REQUEST['day_tables'] && $_POST['day_tables'])
{
	foreach($_REQUEST['day_tables'] as $id=>$values)
	{
		if($_REQUEST['day_tables'][$id]['START_DATE'] && $_REQUEST['month_tables'][$id]['START_DATE'] && $_REQUEST['year_tables'][$id]['START_DATE'])
			$_REQUEST['tables'][$id]['START_DATE'] = $_REQUEST['day_tables'][$id]['START_DATE'].'-'.$_REQUEST['month_tables'][$id]['START_DATE'].'-'.$_REQUEST['year_tables'][$id]['START_DATE'];
		elseif(isset($_REQUEST['day_tables'][$id]['START_DATE']) && isset($_REQUEST['month_tables'][$id]['START_DATE']) && isset($_REQUEST['year_tables'][$id]['START_DATE']))
			$_REQUEST['tables'][$id]['START_DATE'] = '';

		if($_REQUEST['day_tables'][$id]['END_DATE'] && $_REQUEST['month_tables'][$id]['END_DATE'] && $_REQUEST['year_tables'][$id]['END_DATE'])
			$_REQUEST['tables'][$id]['END_DATE'] = $_REQUEST['day_tables'][$id]['END_DATE'].'-'.$_REQUEST['month_tables'][$id]['END_DATE'].'-'.$_REQUEST['year_tables'][$id]['END_DATE'];
		elseif(isset($_REQUEST['day_tables'][$id]['END_DATE']) && isset($_REQUEST['month_tables'][$id]['END_DATE']) && isset($_REQUEST['year_tables'][$id]['END_DATE']))
			$_REQUEST['tables'][$id]['END_DATE'] = '';

		if($_REQUEST['day_tables'][$id]['POST_START_DATE'] && $_REQUEST['month_tables'][$id]['POST_START_DATE'] && $_REQUEST['year_tables'][$id]['POST_START_DATE'])
			$_REQUEST['tables'][$id]['POST_START_DATE'] = $_REQUEST['day_tables'][$id]['POST_START_DATE'].'-'.$_REQUEST['month_tables'][$id]['POST_START_DATE'].'-'.$_REQUEST['year_tables'][$id]['POST_START_DATE'];
		elseif(isset($_REQUEST['day_tables'][$id]['POST_START_DATE']) && isset($_REQUEST['month_tables'][$id]['POST_START_DATE']) && isset($_REQUEST['year_tables'][$id]['POST_START_DATE']))
			$_REQUEST['tables'][$id]['POST_START_DATE'] = '';

		if($_REQUEST['day_tables'][$id]['POST_END_DATE'] && $_REQUEST['month_tables'][$id]['POST_END_DATE'] && $_REQUEST['year_tables'][$id]['POST_END_DATE'])
			$_REQUEST['tables'][$id]['POST_END_DATE'] = $_REQUEST['day_tables'][$id]['POST_END_DATE'].'-'.$_REQUEST['month_tables'][$id]['POST_END_DATE'].'-'.$_REQUEST['year_tables'][$id]['POST_END_DATE'];
		elseif(isset($_REQUEST['day_tables'][$id]['POST_END_DATE']) && isset($_REQUEST['month_tables'][$id]['POST_END_DATE']) && isset($_REQUEST['year_tables'][$id]['POST_END_DATE']))
			$_REQUEST['tables'][$id]['POST_END_DATE'] = '';
	}
	if(!$_POST['tables'])
		$_POST['tables'] = $_REQUEST['tables'];
}

if($_REQUEST['tables'] && $_POST['tables'] && AllowEdit())
{
	foreach($_REQUEST['tables'] as $id=>$columns)
	{
//modif Francois: fix SQL bug invalid sort order
		if (empty($columns['SORT_ORDER']) || is_numeric($columns['SORT_ORDER']))
		{
			if($id!='new')
			{
				$sql = "UPDATE SCHOOL_MARKING_PERIODS SET ";

				foreach($columns as $column=>$value)
				{
					if($column=='START_DATE' || $column=='END_DATE' || $column=='POST_START_DATE' || $column=='POST_END_DATE')
					{
						//modif Francois: fix SQL bug START_DATE or END_DATE is null
						if((!VerifyDate($value) && $value!='') || (($column=='START_DATE' || $column=='END_DATE') && $value==''))
						{
							$error[] = _('Not all of the dates were entered correctly.');
							//goto error_exit; //modif Francois: goto avail. in PHP 5.3, use break instead
							break 2;
						}
					}
					$sql .= $column."='".$value."',";
				}
				$sql = mb_substr($sql,0,-1) . " WHERE MARKING_PERIOD_ID='$id'";
				$go = true;
			}
			else
			{
				$id_RET = DBGet(DBQuery('SELECT '.db_seq_nextval('MARKING_PERIOD_SEQ').' AS ID'.FROM_DUAL));

				$sql = "INSERT INTO SCHOOL_MARKING_PERIODS ";
				$fields = "MARKING_PERIOD_ID,MP,SYEAR,SCHOOL_ID,";
				$values = "'".$id_RET[1]['ID']."','$_REQUEST[mp_term]','".UserSyear()."','".UserSchool()."',";

				$_REQUEST['marking_period_id'] = $id_RET[1]['ID'];

				switch($_REQUEST['mp_term'])
				{
					case 'SEM':
						$fields .= "PARENT_ID,";
						$values .= "'$_REQUEST[year_id]',";
					break;

					case 'QTR':
						$fields .= "PARENT_ID,";
						$values .= "'$_REQUEST[semester_id]',";
					break;

					case 'PRO':
						$fields .= "PARENT_ID,";
						$values .= "'$_REQUEST[quarter_id]',";
					break;
				}

				$go = false;
				foreach($columns as $column=>$value)
				{
					if($column=='START_DATE' || $column=='END_DATE' || $column=='POST_START_DATE' || $column=='POST_END_DATE')
					{
						//modif Francois: fix SQL bug START_DATE or END_DATE is null
						if(!VerifyDate($value) && $value!='' || (($column=='START_DATE' || $column=='END_DATE') && $value==''))
						{
							$error[] = _('Not all of the dates were entered correctly.');
							//goto error_exit; //modif Francois: goto avail. in PHP 5.3, use break instead
							break 2;
						}
					}
					if($value)
					{
						$fields .= $column.',';
						$values .= "'".$value."',";
						$go = true;
					}
				}
				$sql .= '(' . mb_substr($fields,0,-1) . ') values(' . mb_substr($values,0,-1) . ')';
			}

			// CHECK TO MAKE SURE ONLY ONE MP & ONE GRADING PERIOD IS OPEN AT ANY GIVEN TIME
			$dates_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='$_REQUEST[mp_term]' AND (true=false"
				.(($columns['START_DATE'])?" OR '".$columns['START_DATE']."' BETWEEN START_DATE AND END_DATE":'')
				.(($columns['END_DATE'])?" OR '".$columns['END_DATE']."' BETWEEN START_DATE AND END_DATE":'')
				.(($columns['START_DATE'] && $columns['END_DATE'])?" OR START_DATE BETWEEN '".$columns['START_DATE']."' AND '".$columns['END_DATE']."'
				OR END_DATE BETWEEN '".$columns['START_DATE']."' AND '".$columns['END_DATE']."'":'')
				.") AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'".(($id!='new')?" AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID!='$id'":'')
			));
			$posting_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='$_REQUEST[mp_term]' AND (true=false"
				.(($columns['POST_START_DATE'])?" OR '".$columns['POST_START_DATE']."' BETWEEN POST_START_DATE AND POST_END_DATE":'')
				.(($columns['POST_END_DATE'])?" OR '".$columns['POST_END_DATE']."' BETWEEN POST_START_DATE AND POST_END_DATE":'')
				.(($columns['POST_START_DATE'] && $columns['POST_END_DATE'])?" OR POST_START_DATE BETWEEN '".$columns['POST_START_DATE']."' AND '".$columns['POST_END_DATE']."'
				OR POST_END_DATE BETWEEN '".$columns['POST_START_DATE']."' AND '".$columns['POST_END_DATE']."'":'')
				.") AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'".(($id!='new')?" AND MARKING_PERIOD_ID!='$id'":'')
			));

			if(count($dates_RET))
			{
				$error[] = sprintf(_('The beginning and end dates you specified for this marking period overlap with those of "%s".'),GetMP($dates_RET[1]['MARKING_PERIOD_ID']))." "._("Only one marking period can be open at any time.");
				//goto error_exit; //modif Francois: goto avail. in PHP 5.3, use break instead
				break 1;
			}
			if(count($posting_RET))
			{
				$error[] = sprintf(_('The grade posting dates you specified for this marking period overlap with those of "%s".'),GetMP($posting_RET[1]['MARKING_PERIOD_ID']))." "._("Only one grade posting period can be open at any time.");
				//goto error_exit; //modif Francois: goto avail. in PHP 5.3, use break instead
				break 1;
			}


			if($go)
				DBQuery($sql);
		}
		else
			$error[] = _('Please enter a valid Sort Order.');
	}
	//error_exit: //modif Francois: goto avail. in PHP 5.3, use break instead
	unset($_REQUEST['tables']);
	unset($_SESSION['_REQUEST_vars']['tables']);
}

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	$extra = array();
	switch($_REQUEST['mp_term'])
	{
		case 'FY':
//modif Francois: add translation
			$name = _('Year');
			$parent_term = ''; $parent_id = '';
			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID='$_REQUEST[marking_period_id]'))";
			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID='$_REQUEST[marking_period_id]')";
			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID='$_REQUEST[marking_period_id]'";
		break;

		case 'SEM':
			$name = _('Semester');
			$parent_term = 'FY'; $parent_id = $_REQUEST['year_id'];
			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID IN (SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID='$_REQUEST[marking_period_id]')";
			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID='$_REQUEST[marking_period_id]'";
		break;

		case 'QTR':
			$name = _('Quarter');
			$parent_term = 'SEM'; $parent_id = $_REQUEST['semester_id'];
			$extra[] = "DELETE FROM SCHOOL_MARKING_PERIODS WHERE PARENT_ID='$_REQUEST[marking_period_id]'";
		break;

		case 'PRO':
			$name = 'progress period';
			$parent_term = 'QTR'; $parent_id = $_REQUEST['quarter_id'];
		break;
	}

	if(DeletePrompt($name))
	{
		foreach($extra as $sql)
			DBQuery($sql);
		DBQuery("DELETE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID='$_REQUEST[marking_period_id]'");
		unset($_REQUEST['modfunc']);
		$_REQUEST['mp_term'] = $parent_term;
		$_REQUEST['marking_period_id'] = $parent_id;
	}
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if(empty($_REQUEST['modfunc']))
{
//modif Francois: fix SQL bug invalid sort order
	if(isset($error)) echo ErrorMessage($error);
	if(AllowEdit() && $_REQUEST['marking_period_id']!='new')
	{
		$delete_button = '<script type="text/javascript">var delete_link = document.createElement("a"); delete_link.href = "Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete&mp_term='.$_REQUEST['mp_term'].'&year_id='.$_REQUEST['year_id'].'&semester_id='.$_REQUEST['semester_id'].'&quarter_id='.$_REQUEST['quarter_id'].'&marking_period_id='.$_REQUEST['marking_period_id'].'"; delete_link.target = "body";</script>';
		$delete_button .= '<INPUT type="button" value="'._('Delete').'" onClick="javascript:ajaxLink(delete_link);" />';
	}

	// ADDING & EDITING FORM
	if($_REQUEST['marking_period_id'] && $_REQUEST['marking_period_id']!='new')
	{
		$sql = "SELECT TITLE,SHORT_NAME,SORT_ORDER,DOES_GRADES,DOES_COMMENTS,
						START_DATE,END_DATE,POST_START_DATE,POST_END_DATE
				FROM SCHOOL_MARKING_PERIODS
				WHERE MARKING_PERIOD_ID='$_REQUEST[marking_period_id]'";
		$QI = DBQuery($sql);
		$RET = DBGet($QI);
		$RET = $RET[1];
		$title = $RET['TITLE'];
	}

	if($_REQUEST['marking_period_id'])
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&mp_term='.$_REQUEST['mp_term'].'&marking_period_id='.$_REQUEST['marking_period_id'].'&year_id='.$_REQUEST['year_id'].'&semester_id='.$_REQUEST['semester_id'].'&quarter_id='.$_REQUEST['quarter_id'].'" method="POST">';
		DrawHeader($title,AllowEdit()?$delete_button.SubmitButton(_('Save')):'');
		$header .= '<TABLE class="width-100p cellpadding-3">';
		$header .= '<TR class="st">';

		$header .= '<TD>' . TextInput($RET['TITLE'],'tables['.$_REQUEST['marking_period_id'].'][TITLE]',(!$RET['TITLE']?'<span style="color:red">':'')._('Title').(!$RET['TITLE']?'</span>':''), 'required') . '</TD>';
		$header .= '<TD>' . TextInput($RET['SHORT_NAME'],'tables['.$_REQUEST['marking_period_id'].'][SHORT_NAME]',(!$RET['SHORT_NAME']?'<span style="color:red">':'')._('Short Name').(!$RET['SHORT_NAME']?'</span>':''), 'required') . '</TD>';
		$header .= '<TD>' . TextInput($RET['SORT_ORDER'],'tables['.$_REQUEST['marking_period_id'].'][SORT_ORDER]',_('Sort Order'),'size=3') . '</TD>';
		$header .= '<TD><TABLE class="width-100p cellpadding-0"><TR><TD>' . CheckboxInput($RET['DOES_GRADES'],'tables['.$_REQUEST['marking_period_id'].'][DOES_GRADES]',_('Graded'),$checked,$_REQUEST['marking_period_id']=='new','<IMG SRC="assets/check_button.png" height="15">','<IMG SRC="assets/x_button.png" height="15">') . '</TD>';
		$header .= '<TD>' . CheckboxInput($RET['DOES_COMMENTS'],'tables['.$_REQUEST['marking_period_id'].'][DOES_COMMENTS]',_('Comments'),$checked,$_REQUEST['marking_period_id']=='new','<IMG SRC="assets/check_button.png" height="15">','<IMG SRC="assets/x_button.png" height="15">') . '</TD></TR></TABLE></TD>';
		$header .= '</TR>';
		$header .= '<TR class="st">';

		$header .= '<TD>' . DateInput($RET['START_DATE'],'tables['.$_REQUEST['marking_period_id'].'][START_DATE]',(!$RET['START_DATE']?'<span style="color:red">':'')._('Begins').(!$RET['START_DATE']?'</span>':'')) . '</TD>';
		$header .= '<TD>' . DateInput($RET['END_DATE'],'tables['.$_REQUEST['marking_period_id'].'][END_DATE]',(!$RET['END_DATE']?'<span style="color:red">':'')._('Ends').(!$RET['END_DATE']?'</span>':'')) . '</TD>';
		$header .= '<TD>' . DateInput($RET['POST_START_DATE'],'tables['.$_REQUEST['marking_period_id'].'][POST_START_DATE]',($RET['DOES_GRADES'] && !$RET['POST_START_DATE']?'<span style="color:red">':'')._('Grade Posting Begins').($RET['DOES_GRADES'] && !$RET['POST_START_DATE']?'</span>':'')) . '</TD>';
		$header .= '<TD>' . DateInput($RET['POST_END_DATE'],'tables['.$_REQUEST['marking_period_id'].'][POST_END_DATE]',($RET['DOES_GRADES'] && !$RET['POST_END_DATE']?'<span style="color:red">':'')._('Grade Posting Ends').($RET['DOES_GRADES'] && !$RET['POST_END_DATE']?'</span>':'')) . '</TD>';

		$header .= '</TR>';
		$header .= '</TABLE>';
		DrawHeader($header);
		echo '</FORM>';
		unset($_SESSION['_REQUEST_vars']['marking_period_id']);
		unset($_SESSION['_REQUEST_vars']['mp_term']);
	}

	// DISPLAY THE MENU
	$LO_options = array('save'=>false,'search'=>false,'responsive'=>false);

	// FY
	$sql = "SELECT MARKING_PERIOD_ID,TITLE FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER";
	$QI = DBQuery($sql);
	$fy_RET = DBGet($QI);

	if(count($fy_RET))
	{
		if($_REQUEST['mp_term'])
		{
			if($_REQUEST['mp_term']=='FY')
				$_REQUEST['year_id'] = $_REQUEST['marking_period_id'];

			foreach($fy_RET as $key=>$value)
			{
				if($value['MARKING_PERIOD_ID']==$_REQUEST['year_id'])
					$fy_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
			}
		}
	}

	echo '<div class="st">';
	$columns = array('TITLE'=>_('Year'));
	$link = array();
	$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&mp_term=FY";
	$link['TITLE']['variables'] = array('marking_period_id'=>'MARKING_PERIOD_ID');
	if(!count($fy_RET))
		$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&mp_term=FY&marking_period_id=new";

	ListOutput($fy_RET,$columns,'Year','Years',$link,array(),$LO_options);
	echo '</div>';

	// SEMESTERS
	if(($_REQUEST['mp_term']=='FY' && $_REQUEST['marking_period_id']!='new') || $_REQUEST['mp_term']=='SEM' || $_REQUEST['mp_term']=='QTR' || $_REQUEST['mp_term']=='PRO')
	{
		$sql = "SELECT MARKING_PERIOD_ID,TITLE FROM SCHOOL_MARKING_PERIODS WHERE MP='SEM' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PARENT_ID='".$_REQUEST['year_id']."' ORDER BY SORT_ORDER";
		$QI = DBQuery($sql);
		$sem_RET = DBGet($QI);

		if(count($sem_RET))
		{
			if($_REQUEST['mp_term'])
			{
				if($_REQUEST['mp_term']=='SEM')
					$_REQUEST['semester_id'] = $_REQUEST['marking_period_id'];

				foreach($sem_RET as $key=>$value)
				{
					if($value['MARKING_PERIOD_ID']==$_REQUEST['semester_id'])
						$sem_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
				}
			}
		}

		echo '<div class="st">';
		$columns = array('TITLE'=>_('Semester'));
		$link = array();
		$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&mp_term=SEM&year_id=$_REQUEST[year_id]";
		$link['TITLE']['variables'] = array('marking_period_id'=>'MARKING_PERIOD_ID');
		$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&mp_term=SEM&marking_period_id=new&year_id=$_REQUEST[year_id]";

		ListOutput($sem_RET,$columns,'Semester','Semesters',$link,array(),$LO_options);
		echo '</div>';

		// QUARTERS
		if(($_REQUEST['mp_term']=='SEM' && $_REQUEST['marking_period_id']!='new') || $_REQUEST['mp_term']=='QTR' || $_REQUEST['mp_term']=='PRO')
		{
			$sql = "SELECT MARKING_PERIOD_ID,TITLE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PARENT_ID='".$_REQUEST['semester_id']."' ORDER BY SORT_ORDER";
			$QI = DBQuery($sql);
			$qtr_RET = DBGet($QI);

			if(count($qtr_RET))
			{
				if(($_REQUEST['mp_term']=='QTR' && $_REQUEST['marking_period_id']!='new') || $_REQUEST['mp_term']=='PRO')
				{
					if($_REQUEST['mp_term']=='QTR')
						$_REQUEST['quarter_id'] = $_REQUEST['marking_period_id'];

					foreach($qtr_RET as $key=>$value)
					{
						if($value['MARKING_PERIOD_ID']==$_REQUEST['quarter_id'])
							$qtr_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
					}
				}
			}

			echo '<div class="st">';
			$columns = array('TITLE'=>_('Quarter'));
			$link = array();
			$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&mp_term=QTR&year_id=$_REQUEST[year_id]&semester_id=$_REQUEST[semester_id]";
			$link['TITLE']['variables'] = array('marking_period_id'=>'MARKING_PERIOD_ID');
			$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&mp_term=QTR&marking_period_id=new&year_id=$_REQUEST[year_id]&semester_id=$_REQUEST[semester_id]";

			ListOutput($qtr_RET,$columns,'Quarter','Quarters',$link,array(),$LO_options);
			echo '</div>';

			// PROGRESS PERIODS
			if(($_REQUEST['mp_term']=='QTR' && $_REQUEST['marking_period_id']!='new') || $_REQUEST['mp_term']=='PRO')
			{
				$sql = "SELECT MARKING_PERIOD_ID,TITLE FROM SCHOOL_MARKING_PERIODS WHERE MP='PRO' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' AND PARENT_ID='".$_REQUEST['quarter_id']."' ORDER BY SORT_ORDER";
				$QI = DBQuery($sql);
				$pro_RET = DBGet($QI);

				if(count($pro_RET))
				{
					if(($_REQUEST['mp_term']=='PRO' && $_REQUEST['marking_period_id']!='new'))
					{
						$_REQUEST['progress_period_id'] = $_REQUEST['marking_period_id'];

						foreach($pro_RET as $key=>$value)
						{
							if($value['MARKING_PERIOD_ID']==$_REQUEST['marking_period_id'])
								$pro_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
						}
					}
				}

				echo '<div class="st">';
				$columns = array('TITLE'=>_('Progress Period'));
				$link = array();
				$link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&mp_term=PRO&year_id=$_REQUEST[year_id]&semester_id=$_REQUEST[semester_id]&quarter_id=$_REQUEST[quarter_id]";
				$link['TITLE']['variables'] = array('marking_period_id'=>'MARKING_PERIOD_ID');
				$link['add']['link'] = "Modules.php?modname=$_REQUEST[modname]&mp_term=PRO&marking_period_id=new&year_id=$_REQUEST[year_id]&semester_id=$_REQUEST[semester_id]&quarter_id=$_REQUEST[quarter_id]";

				ListOutput($pro_RET,$columns,'Progress Period','Progress Periods',$link,array(),$LO_options);
				echo '</div>';
			}
		}
	}
}
?>