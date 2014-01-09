<?php
DrawHeader(ProgramTitle());

include 'Menu.php';

if(UserStaffID())
{
	$profile = DBGet(DBQuery("SELECT PROFILE_ID,PROFILE FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));
	if($profile[1]['PROFILE_ID'] || $profile[1]['PROFILE']=='none')
	{
		unset($_SESSION['staff_id']);
		echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
	}
}

StaffWidgets('permissions_N');
Search('staff_id',$extra);

$user_id = UserStaffID();
$profile = DBGet(DBQuery("SELECT PROFILE FROM STAFF WHERE STAFF_ID='$user_id'"));
$xprofile = $profile[1]['PROFILE'];
$exceptions_RET = DBGet(DBQuery("SELECT MODNAME,CAN_USE,CAN_EDIT FROM STAFF_EXCEPTIONS WHERE USER_ID='$user_id'"),array(),array('MODNAME'));
if($_REQUEST['modfunc']=='update' && AllowEdit())
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
					DBQuery("INSERT INTO STAFF_EXCEPTIONS (USER_ID,MODNAME) values('$user_id','$modname')");
				elseif(count($exceptions_RET[$modname]) && !$_REQUEST['can_edit'][str_replace('.','_',$modname)] && !$_REQUEST['can_use'][str_replace('.','_',$modname)])
					DBQuery("DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID='$user_id' AND MODNAME='$modname'");

				if($_REQUEST['can_edit'][str_replace('.','_',$modname)] || $_REQUEST['can_use'][str_replace('.','_',$modname)])
				{
					$update = "UPDATE STAFF_EXCEPTIONS SET ";
					if($_REQUEST['can_edit'][str_replace('.','_',$modname)])
						$update .= "CAN_EDIT='Y',";
					else
						$update .= "CAN_EDIT=NULL,";
					if($_REQUEST['can_use'][str_replace('.','_',$modname)])
						$update .= "CAN_USE='Y'";
					else
						$update .= "CAN_USE=NULL";
					$update .= " WHERE USER_ID='$user_id' AND MODNAME='$modname'";
					DBQuery($update);
				}
			}
		}
	}
	$exceptions_RET = DBGet(DBQuery("SELECT MODNAME,CAN_USE,CAN_EDIT FROM STAFF_EXCEPTIONS WHERE USER_ID='$user_id'"),array(),array('MODNAME'));
	unset($tmp_menu);
	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	unset($_REQUEST['can_edit']);
	unset($_SESSION['_REQUEST_vars']['can_edit']);
	unset($_REQUEST['can_use']);
	unset($_SESSION['_REQUEST_vars']['can_use']);
}
if(UserStaffID() && (empty($_REQUEST['modfunc']) || $_REQUEST['modfunc'] == 'search_fnc'))
{
$staff_RET = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME,PROFILE,PROFILE_ID FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));

if(!$staff_RET[1]['PROFILE_ID'])
{
	echo '<FORM action="Modules.php?modname='.$_REQUEST[modname].'&modfunc=update" method="POST">';
	DrawHeader(_('Select the programs with which this user can use and save information.'),SubmitButton(_('Save')));
	echo '<BR />';
	PopTable('header',_('Permissions'));
//	echo '<TABLE cellspacing=0>';
	echo '<TABLE class="widefat cellspacing-0">';
	foreach($menu as $modcat=>$profiles)
	{
		$values = $profiles[$staff_RET[1]['PROFILE']];

//modif Francois: css WPadmin
		echo '<TR><TD colspan="3"><h4>'._(str_replace('_',' ',$modcat)).'</h4></TD></TR>';
//modif Francois: add <label> on checkbox
		echo '<TR><TH style="text-align:right;"><label>'._('Can Use').' '.(AllowEdit()?'<INPUT type="checkbox" name="can_use_'.$modcat.'" onclick=\'checkAll(this.form,this.form.can_use_'.$modcat.'.checked,"can_use['.$modcat.'");\' />':'').'</span></label></TH><TH style="text-align:right;"><label>'._('Can Edit').' '.(AllowEdit()?'<INPUT type="checkbox" name="can_edit_'.$modcat.'" onclick=\'checkAll(this.form,this.form.can_edit_'.$modcat.'.checked,"can_edit['.$modcat.'");\' />':'').'</span></label></TH><TH>&nbsp;</TH></TR>';
		if(count($values))
		{
			foreach($values as $file=>$title)
			{
				if(!is_numeric($file))
				{
					$can_use = $exceptions_RET[$file][1]['CAN_USE'];
					$can_edit = $exceptions_RET[$file][1]['CAN_EDIT'];

					//echo '<TR><TD></TD><TD></TD>';

					echo '<TD style="text-align:right"><INPUT type="checkbox" name="can_use['.str_replace('.','_',$file).']" value="true"'.($can_use=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></TD>';
					if($staff_RET[1]['PROFILE']=='admin')
						echo '<TD style="text-align:right"><INPUT type="checkbox" name="can_edit['.str_replace('.','_',$file).']" value="true"'.($can_edit=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></TD>';
					else
						echo '<TD class="center">&nbsp;</TD>';
					echo '<TD>'.$title.'</TD></TR>';

					if($modcat=='Students' && $file=='Students/Student.php')
					{
						$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
						foreach($categories_RET as $category)
						{
							$file = 'Students/Student.php&category_id='.$category['ID'];
							$title = '&nbsp;&nbsp;&rsaquo; '.ParseMLField($category['TITLE']);
							$can_use = $exceptions_RET[$file][1]['CAN_USE'];
							$can_edit = $exceptions_RET[$file][1]['CAN_EDIT'];

							//echo "<TR><TD></TD><TD></TD>";
							echo '<TR><TD style="text-align:right"><INPUT type="checkbox" name="can_use['.str_replace('.','_',$file).']" value="true"'.($can_use=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></TD>';
							echo '<TD style="text-align:right"><INPUT type="checkbox" name="can_edit['.str_replace('.','_',$file).']" value="true"'.($can_edit=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></TD>';
							echo '<TD>'.$title.'</TD></TR>';
						}
					}
					elseif($modcat=='Users' && $file=='Users/User.php')
					{
						$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STAFF_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
						foreach($categories_RET as $category)
						{
							$file = 'Users/User.php&category_id='.$category['ID'];
							$title = '&nbsp;&nbsp;&rsaquo; '.ParseMLField($category['TITLE']);
							$can_use = $exceptions_RET[$file][1]['CAN_USE'];
							$can_edit = $exceptions_RET[$file][1]['CAN_EDIT'];

							//echo "<TR><TD></TD><TD></TD>";
							echo '<TR><TD style="text-align:right"><INPUT type="checkbox" name="can_use['.str_replace('.','_',$file).']" value="true"'.($can_use=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></TD>';
							echo '<TD style="text-align:right"><INPUT type="checkbox" name="can_edit['.str_replace('.','_',$file).']" value="true"'.($can_edit=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></TD>';
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

	echo '</DIV>';
	echo '</TD></TR></TABLE>';
	echo '</FORM>';
	echo '<DIV id="new_id_content" style="position:absolute;visibility:hidden;">'._('Title').' <INPUT type="text" name="new_profile_title" /><BR />';
	echo _('Type').' <SELECT name="new_profile_type"><OPTION value="admin">'._('Administrator').'<OPTION value="teacher">'._('Teacher').'<OPTION value="parent">'._('Parent').'</SELECT></DIV>';
}
else
{
	$profile_title = DBGet(DBQuery("SELECT TITLE FROM USER_PROFILES WHERE ID='".$staff_RET[1]['PROFILE_ID']."'"));
	echo '<BR />';
	PopTable('header',_('Error'),'width=50%');
	//modif Francois: remove ProgramLink function
	echo '<TABLE><TR><TD><IMG SRC="assets/warning_button.png" height="30" /></TD><TD>'.sprintf(_('%s %s is assigned to the profile %s.'),$staff_RET[1]['FIRST_NAME'],$staff_RET[1]['LAST_NAME'],$profile_title[1]['TITLE']).'<BR /><BR /> '.sprintf(_('To assign permissions to this user, either change the permissions for this profile using the %s setup or change this user to a user with custom permissions by using %s.'), (AllowUse('Users/Profiles.php') ? '<A href="Modules.php?modname=Users/Profiles.php">' : '')._('Profiles').(AllowUse('Users/Profiles.php') ? '</A>' : ''), (AllowUse('Users/User.php') ? '<A href="Modules.php?modname=Users/User.php">' : '')._('General Info').(AllowUse('Users/User.php') ? '</A>' : '')).'</TD></TR></TABLE>';
	PopTable('footer');
}
}
?>