<?php
DrawHeader(ProgramTitle());

include 'Menu.php';

if($_REQUEST['profile_id']!='')
{
	$exceptions_RET = DBGet(DBQuery("SELECT PROFILE_ID,MODNAME,CAN_USE,CAN_EDIT FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='$_REQUEST[profile_id]'"),array(),array('MODNAME'));
	$profile_RET = DBGet(DBQuery("SELECT PROFILE FROM USER_PROFILES WHERE ID='$_REQUEST[profile_id]'"));
	$xprofile = $profile_RET[1]['PROFILE'];
	if($xprofile=='student')
	{
		$xprofile = 'parent';
//modif Francois: enable password change for students
		//unset($menu['Users']);
		unset($menu['Users']['parent']['Users/User.php']);
	}
}

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	$profile_RET = DBGet(DBQuery("SELECT TITLE FROM USER_PROFILES WHERE ID='$_REQUEST[profile_id]'"));

	if(Prompt(_('Confirm Delete'),sprintf(_('Are you sure you want to delete the user profile <i>%s</i>?'), $profile_RET[1]['TITLE']),sprintf(_('Users of that profile will retain their permissions as a custom set which can be modified on a per-user basis through %s.'), _('User Permissions'))))
	{
		DBQuery("DELETE FROM USER_PROFILES WHERE ID='".$_REQUEST['profile_id']."'");
		DBQuery("DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID IN (SELECT STAFF_ID FROM STAFF WHERE PROFILE_ID='".$_REQUEST['profile_id']."')");
		DBQuery("INSERT INTO STAFF_EXCEPTIONS (USER_ID,MODNAME,CAN_USE,CAN_EDIT) SELECT s.STAFF_ID,e.MODNAME,e.CAN_USE,e.CAN_EDIT FROM STAFF s,PROFILE_EXCEPTIONS e WHERE s.PROFILE_ID='$_REQUEST[profile_id]' AND s.PROFILE_ID=e.PROFILE_ID");
		DBQuery("DELETE FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='".$_REQUEST['profile_id']."'");
		unset($_REQUEST['modfunc']);
		unset($_SESSION['_REQUEST_vars']['modfunc']);
		unset($_REQUEST['profile_id']);
		unset($_SESSION['_REQUEST_vars']['profile_id']);
	}
}

if($_REQUEST['modfunc']=='update' && !$_REQUEST['new_profile_title'] && AllowEdit())
{
	$tmp_menu = $menu;
	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES"));
	foreach($categories_RET as $category)
	{
		$file = 'Students/Student.php&category_id='.$category['ID'];
		$tmp_menu['Students'][$xprofile][$file] = ' &nbsp; &nbsp; &rsaquo; '.$category['TITLE'];
	}
	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STAFF_FIELD_CATEGORIES"));
	foreach($categories_RET as $category)
	{
		$file = 'Users/User.php&category_id='.$category['ID'];
		$tmp_menu['Users'][$xprofile][$file] = ' &nbsp; &nbsp; &rsaquo; '.$category['TITLE'];
	}
		
	//modif Francois: fix SQL bug TeacherPrograms inserted twice as in Users and other categories
	foreach($tmp_menu['Users'] as $profile => $modname_array)
	{
		foreach ($modname_array as $modname=>$title)
			if (mb_strpos($modname, 'TeacherPrograms') !== false)
				unset ($tmp_menu['Users'][$profile][$modname]);
	}
	
	foreach($tmp_menu as $modcat=>$profiles)
	{
		$values = $profiles[$xprofile];
		foreach($values as $modname=>$title)
		{
			if(!is_numeric($modname))
			{
				if(!count($exceptions_RET[$modname]) && ($_REQUEST['can_edit'][str_replace('.','_',$modname)] || $_REQUEST['can_use'][str_replace('.','_',$modname)]))
					DBQuery("INSERT INTO PROFILE_EXCEPTIONS (PROFILE_ID,MODNAME) values('$_REQUEST[profile_id]','$modname')");
				elseif(count($exceptions_RET[$modname]) && !$_REQUEST['can_edit'][str_replace('.','_',$modname)] && !$_REQUEST['can_use'][str_replace('.','_',$modname)])
					DBQuery("DELETE FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='$_REQUEST[profile_id]' AND MODNAME='$modname'");

                if ($_REQUEST['can_edit'][str_replace('.','_',$modname)] || $_REQUEST['can_use'][str_replace('.','_',$modname)])
                {
                    $update = "UPDATE PROFILE_EXCEPTIONS SET ";
                    if($_REQUEST['can_edit'][str_replace('.','_',$modname)])
                        $update .= "CAN_EDIT='Y',";
                    else
                        $update .= "CAN_EDIT=NULL,";
                    if($_REQUEST['can_use'][str_replace('.','_',$modname)])
                        $update .= "CAN_USE='Y'";
                    else
                        $update .= "CAN_USE=NULL";
                    $update .= " WHERE PROFILE_ID='$_REQUEST[profile_id]' AND MODNAME='$modname'";
                    DBQuery($update);
                }
			}
		}
	}
	$exceptions_RET = DBGet(DBQuery("SELECT MODNAME,CAN_USE,CAN_EDIT FROM PROFILE_EXCEPTIONS WHERE PROFILE_ID='$_REQUEST[profile_id]'"),array(),array('MODNAME'));
	unset($tmp_menu);
	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	unset($_REQUEST['can_edit']);
	unset($_SESSION['_REQUEST_vars']['can_edit']);
	unset($_REQUEST['can_use']);
	unset($_SESSION['_REQUEST_vars']['can_use']);
}

if($_REQUEST['new_profile_title'] && AllowEdit())
{
	$id = DBGet(DBQuery("SELECT ".db_seq_nextval('USER_PROFILES_SEQ')." AS ID".FROM_DUAL));
	$id = $id[1]['ID'];
	$exceptions_RET = array();
	DBQuery("INSERT INTO USER_PROFILES (ID,TITLE,PROFILE) values('$id','".$_REQUEST['new_profile_title']."','".$_REQUEST['new_profile_type']."')");
	$_REQUEST['profile_id'] = $id;
	$xprofile = $_REQUEST['new_profile_type'];
	unset($_REQUEST['new_profile_title']);
	unset($_SESSION['_REQUEST_vars']['new_profile_title']);
	unset($_REQUEST['new_profile_type']);
	unset($_SESSION['_REQUEST_vars']['new_profile_type']);
}

if($_REQUEST['modfunc']!='delete')
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update&profile_id='.$_REQUEST['profile_id'].'" method="POST">';
	DrawHeader(_('Select the programs that users of this profile can use and which programs those users can use to save information.'),SubmitButton(_('Save')));
	echo '<BR />';
	echo '<TABLE><TR class="st"><TD class="valign-top">';
//modif Francois: css WPadmin
	echo '<TABLE class="widefat cellspacing-0">';
//	$style = ' style="border:1; border-style: dashed none none none;"';
	$style = '';
	//$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE,PROFILE FROM USER_PROFILES"));
	$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE,PROFILE FROM USER_PROFILES ORDER BY ID"),array(),array('PROFILE','ID'));
	echo '<TR><TH colspan="3">'._('Profiles').'</TH></TR>';
	foreach(array('admin','teacher','parent','student') as $profiles)
	{
		foreach($profiles_RET[$profiles] as $id=>$profile)
		{
			if($_REQUEST['profile_id']!='' && $id==$_REQUEST['profile_id'])
				echo '<TR id="selected_tr" class="highlight"><TD>'.(AllowEdit()&&$id>3?button('remove','','"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete&profile_id='.$id.'"',20):'&nbsp;').'</TD><TD '.$style.'>';
			else
				echo '<TR onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'";\' onmouseout=\'this.style.cssText="background-color:transparent;";\'><TD>'.(AllowEdit()&&$id>3?button('remove','','"Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete&profile_id='.$id.'"',20):'&nbsp;').'</TD><TD'.$style.'>';
//			echo '<A style="cursor: pointer;">'.($id>3?'':'<b>').''.$profile[1]['TITLE'].' &nbsp; '.($id>3?'':'</b>').'</A>';
			echo '<A href="Modules.php?modname='.$_REQUEST['modname'].'&profile_id='.$id.'">'._($profile[1]['TITLE']).' &nbsp; </A>';
			echo '</TD>';
			echo '<TD'.$style.'><IMG SRC="assets/arrow_right.gif"></TD>';
			echo '</TR>';
		}
	}
	if($_REQUEST['profile_id']=='')
		echo '<TR id="selected_tr"><TD style="height:0px;"></TD><TD style="height:0px;"></TD><TD style="height:0px;"></TD></TR>';

	if(AllowEdit())
	{
		echo '<script type="text/javascript">
function changeHTML(show,hide){
	for(key in show)
		document.getElementById(key).innerHTML = document.getElementById(show[key]).innerHTML;
	for(i=0;i<hide.length;i++)
		document.getElementById(hide[i]).innerHTML = "";
}
</script>';
		echo '<TR id="new_tr" onmouseover=\'this.style.backgroundColor="'.Preferences('HIGHLIGHT').'"; this.style.cursor="pointer";\' onmouseout=\'this.style.cssText="background-color:transparent;";\' onclick=\'document.getElementById("selected_tr").onmouseover="this.style.backgroundColor=\"'.Preferences('HIGHLIGHT').'\";"; document.getElementById("selected_tr").onmouseout="this.style.cssText=\"background-color:transparent;\";"; document.getElementById("selected_tr").style.cssText="background-color:transparent;"; changeHTML({"new_id_div":"new_id_content"},["main_div"]);document.getElementById("new_tr").onmouseover="";document.getElementById("new_tr").onmouseout="";this.onclick="";\'><TD style="text-align:right; width: 20px;"'.$style.'>'.button('add','','',20).'</TD><TD'.$style.'>';
		echo '<A href="#">'._('Add a User Profile').'</A>&nbsp;<BR /><DIV id=new_id_div></DIV>';
		echo '</TD>';
		echo '<TD'.$style.'><IMG SRC="assets/arrow_right.gif"></TD>';
		echo '</TR>';
	}

	echo '</TABLE>';
	echo '</TD><TD style="width:20px;"></TD><TD>';
	echo '<DIV id=main_div>';
	if($_REQUEST['profile_id']!='')
	{
		PopTable('header',_('Permissions'));
//		echo '<TABLE cellspacing=0>';
		echo '<TABLE class="widefat cellspacing-0">';
		foreach($menu as $modcat=>$profiles)
		{
			$values = $profiles[$xprofile];

//modif Francois: css WPadmin
			echo '<TR><TD colspan="3"><h4>'._(str_replace('_',' ',$modcat)).'</h4></TD></TR>';
//modif Francois: add <label> on checkbox
			echo '<TR><TH style="text-align:right;"><label>'._('Can Use').' '.(AllowEdit()?'<INPUT type="checkbox" name="can_use_'.$modcat.'" onclick="checkAll(this.form,this.form.can_use_'.$modcat.'.checked,\'can_use['.$modcat.'\');">':'').'</label></TH>';
			if($xprofile=='admin' || $modcat=='Students' || $modcat=='Resources')
				echo '<TH style="text-align:right;"><label>'._('Can Edit').' '.(AllowEdit()?'<INPUT type="checkbox" name="can_edit_'.$modcat.'" onclick="checkAll(this.form,this.form.can_edit_'.$modcat.'.checked,\'can_edit['.$modcat.'\');">':'').'</label></TH>';
			else
				echo '<TH>&nbsp;</TH>';
			echo '<TH>&nbsp;</TH></TR>';
			if(count($values))
			{
				foreach($values as $file=>$title)
				{
					if(!is_numeric($file))
					{
						$can_use = $exceptions_RET[$file][1]['CAN_USE'];
						$can_edit = $exceptions_RET[$file][1]['CAN_EDIT'];

						//echo '<TR><TD>&nbsp;</TD><TD>&nbsp;</TD>';

						echo '<TR><TD style="text-align:right"><INPUT type="checkbox" name="can_use['.str_replace('.','_',$file).']" value="true"'.($can_use=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></TD>';
						if($xprofile=='admin' || $modcat=='Resources')
								echo '<TD style="text-align:right"><INPUT type="checkbox" name="can_edit['.str_replace('.','_',$file).']" value="true"'.($can_edit=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').' /></TD>';
						else
							echo '<TD class="center">&nbsp;</TD>';
						echo'<TD>'.$title.'</TD></TR>';

						if($modcat=='Students' && $file=='Students/Student.php')
						{
							$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
							foreach($categories_RET as $category)
							{
								$file = 'Students/Student.php&category_id='.$category['ID'];
								$title = '&nbsp;&nbsp;&rsaquo; '.ParseMLField($category['TITLE']);
								$can_use = $exceptions_RET[$file][1]['CAN_USE'];
								$can_edit = $exceptions_RET[$file][1]['CAN_EDIT'];

								//echo '<TR><TD>&nbsp;</TD><TD>&nbsp;</TD>';
								echo '<TR><TD style="text-align:right"><INPUT type="checkbox" name="can_use['.str_replace('.','_',$file).']" value="true"'.($can_use=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').' /></TD>';
								echo '<TD style="text-align:right"><INPUT type="checkbox" name="can_edit['.str_replace('.','_',$file).']" value="true"'.($can_edit=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').' /></TD>';
								echo '<TD>'.$title.'</TD></TR>';
							}
						}
						elseif($xprofile=='admin' && $modcat=='Users' && $file=='Users/User.php')
						{
							$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STAFF_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
							foreach($categories_RET as $category)
							{
								$file = 'Users/User.php&category_id='.$category['ID'];
								$title = '&nbsp;&nbsp;&rsaquo; '.ParseMLField($category['TITLE']);
								$can_use = $exceptions_RET[$file][1]['CAN_USE'];
								$can_edit = $exceptions_RET[$file][1]['CAN_EDIT'];

								//echo '<TR><TD>&nbsp;</TD><TD>&nbsp;</TD>';
								echo '<TR><TD style="text-align:right"><INPUT type="checkbox" name="can_use['.str_replace('.','_',$file).']" value="true"'.($can_use=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></TD>';
								echo '<TD style="text-align:right"><INPUT type="checkbox" name="can_edit['.str_replace('.','_',$file).']" value="true"'.($can_edit=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').' /></TD>';
								echo '<TD>'.$title.'</TD></TR>';
							}
						}
					}
					else
						echo '<TR><TD colspan="3" class="center"><b>- '.$title.' -</b></TD></TR>';

				}
			}
			//echo '<TR><TD colspan="3" style="text-align:center; height:20px;"></TD></TR>';
		}
		echo '</TABLE>';
		PopTable('footer');
		echo '<span class="center">'.SubmitButton(_('Save')).'</span>';
	}
	echo '</DIV>';
	echo '</TD></TR></TABLE>';
	echo '</FORM>';
	echo '<DIV id="new_id_content" style="position:absolute;visibility:hidden;">'._('Title').' <INPUT type="text" name="new_profile_title"><BR />';
	echo _('Type').' <SELECT name="new_profile_type"><OPTION value="admin">'._('Administrator').'<OPTION value="teacher">'._('Teacher').'<OPTION value="parent">'._('Parent').'</SELECT></DIV>';
}
?>