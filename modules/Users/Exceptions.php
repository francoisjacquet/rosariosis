<?php

DrawHeader( ProgramTitle() );

// bugfix recreate $menu on page reload
if ( !isset( $menu ) )
{
	// include Menu.php for each active module
	foreach ( (array) $RosarioModules as $module => $active )
	{
		if ( $active )
		{
			if ( ROSARIO_DEBUG )
			{
				include 'modules/' . $module . '/Menu.php';
			}
			else
				@include 'modules/' . $module . '/Menu.php';
		}
	}
}

if (UserStaffID())
{
	$profile = DBGet(DBQuery("SELECT PROFILE_ID,PROFILE FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));
	if ( $profile[1]['PROFILE_ID'] || $profile[1]['PROFILE']=='none')
		unset($_SESSION['staff_id']);
}

StaffWidgets('permissions_N');
Search('staff_id',$extra);

$user_id = UserStaffID();
$profile = DBGet(DBQuery("SELECT PROFILE FROM STAFF WHERE STAFF_ID='".$user_id."'"));
$xprofile = $profile[1]['PROFILE'];
$exceptions_RET = DBGet(DBQuery("SELECT MODNAME,CAN_USE,CAN_EDIT FROM STAFF_EXCEPTIONS WHERE USER_ID='".$user_id."'"),array(),array('MODNAME'));

if ( $_REQUEST['modfunc']=='update' && AllowEdit())
{
	$tmp_menu = $menu;
	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES"));

	foreach ( (array) $categories_RET as $category)
	{
		$file = 'Students/Student.php&category_id='.$category['ID'];
		$tmp_menu['Students'][ $xprofile ][ $file ] = ' &nbsp; &nbsp; &rsaquo; '.$category['TITLE'];
	}

	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STAFF_FIELD_CATEGORIES"));

	foreach ( (array) $categories_RET as $category)
	{
		$file = 'Users/User.php&category_id='.$category['ID'];
		$tmp_menu['Users'][ $xprofile ][ $file ] = ' &nbsp; &nbsp; &rsaquo; '.$category['TITLE'];
	}

	//FJ fix SQL bug TeacherPrograms inserted twice as in Users and other categories
	foreach ( (array) $tmp_menu['Users'] as $profile => $modname_array)
	{
		foreach ($modname_array as $modname => $title)
			if (mb_strpos($modname, 'TeacherPrograms') !== false)
				unset ($tmp_menu['Users'][ $profile ][ $modname ]);
	}
	
	foreach ( (array) $tmp_menu as $modcat => $profiles)
	{
		$values = $profiles[ $xprofile ];
		foreach ( (array) $values as $modname => $title)
		{
			if ( ! is_numeric( $modname )
				&& $modname !== 'default'
				&& $modname !== 'title' )
			{
				if ( !count($exceptions_RET[ $modname ]) && ($_REQUEST['can_edit'][str_replace('.','_',$modname)] || $_REQUEST['can_use'][str_replace('.','_',$modname)]))
					DBQuery("INSERT INTO STAFF_EXCEPTIONS (USER_ID,MODNAME) values('".$user_id."','".$modname."')");
				elseif (count($exceptions_RET[ $modname ]) && ! $_REQUEST['can_edit'][str_replace('.','_',$modname)] && ! $_REQUEST['can_use'][str_replace('.','_',$modname)])
					DBQuery("DELETE FROM STAFF_EXCEPTIONS WHERE USER_ID='".$user_id."' AND MODNAME='".$modname."'");

				if ( $_REQUEST['can_edit'][str_replace('.','_',$modname)] || $_REQUEST['can_use'][str_replace('.','_',$modname)])
				{
					$update = "UPDATE STAFF_EXCEPTIONS SET ";

					if ( $_REQUEST['can_edit'][str_replace('.','_',$modname)])
						$update .= "CAN_EDIT='Y',";
					else
						$update .= "CAN_EDIT=NULL,";

					if ( $_REQUEST['can_use'][str_replace('.','_',$modname)])
						$update .= "CAN_USE='Y'";
					else
						$update .= "CAN_USE=NULL";

					$update .= " WHERE USER_ID='".$user_id."' AND MODNAME='".$modname."'";

					DBQuery($update);
				}
			}
		}
	}

	$exceptions_RET = DBGet(DBQuery("SELECT MODNAME,CAN_USE,CAN_EDIT FROM STAFF_EXCEPTIONS WHERE USER_ID='".$user_id."'"),array(),array('MODNAME'));

	unset($tmp_menu);
	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	unset($_REQUEST['can_edit']);
	unset($_SESSION['_REQUEST_vars']['can_edit']);
	unset($_REQUEST['can_use']);
	unset($_SESSION['_REQUEST_vars']['can_use']);
}

if (UserStaffID() && empty($_REQUEST['modfunc']))
{
$staff_RET = DBGet(DBQuery("SELECT FIRST_NAME,LAST_NAME,PROFILE,PROFILE_ID FROM STAFF WHERE STAFF_ID='".UserStaffID()."'"));

if ( ! $staff_RET[1]['PROFILE_ID'])
{
	echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=update" method="POST">';
	DrawHeader(_('Select the programs with which this user can use and save information.'),SubmitButton(_('Save')));
	echo '<br />';
	PopTable('header',_('Permissions'));
//	echo '<table cellspacing=0>';
	echo '<table class="widefat cellspacing-0">';
	foreach ( (array) $menu as $modcat => $profiles)
	{
		$values = $profiles[$staff_RET[1]['PROFILE']];

		if ( !in_array($modcat, $RosarioCoreModules))
			$module_title = dgettext($modcat, str_replace('_',' ',$modcat));
		else
			$module_title = _(str_replace('_',' ',$modcat));

		echo '<tr><td colspan="3"><h4>'.$module_title.'</h4></td></tr>';
//FJ add <label> on checkbox
		echo '<tr><th><label>'._('Can Use').' '.(AllowEdit()?'<input type="checkbox" name="can_use_'.$modcat.'" onclick=\'checkAll(this.form,this.form.can_use_'.$modcat.'.checked,"can_use['.$modcat.'");\' />':'').'</span></label></th><th><label>'._('Can Edit').' '.(AllowEdit()?'<input type="checkbox" name="can_edit_'.$modcat.'" onclick=\'checkAll(this.form,this.form.can_edit_'.$modcat.'.checked,"can_edit['.$modcat.'");\' />':'').'</span></label></th><th>&nbsp;</th></tr>';
		if (count($values))
		{
			foreach ( (array) $values as $file => $title)
			{
				if ( !is_numeric( $file )
					&& $file !== 'default'
					&& $file !== 'title' )
				{
					$can_use = $exceptions_RET[ $file ][1]['CAN_USE'];
					$can_edit = $exceptions_RET[ $file ][1]['CAN_EDIT'];

					//echo '<tr><td></td><td></td>';

					echo '<td style="text-align:right"><input type="checkbox" name="can_use['.str_replace('.','_',$file).']" value="true"'.($can_use=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></td>';

					if ( $staff_RET[1]['PROFILE']=='admin')
						echo '<td style="text-align:right"><input type="checkbox" name="can_edit['.str_replace('.','_',$file).']" value="true"'.($can_edit=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></td>';
					else
						echo '<td>&nbsp;</td>';

					echo '<td>'.$title.'</td></tr>';

					if ( $modcat=='Students' && $file=='Students/Student.php')
					{
						$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
						foreach ( (array) $categories_RET as $category)
						{
							$file = 'Students/Student.php&category_id='.$category['ID'];
							$title = '&nbsp;&nbsp;&rsaquo; '.ParseMLField($category['TITLE']);
							$can_use = $exceptions_RET[ $file ][1]['CAN_USE'];
							$can_edit = $exceptions_RET[ $file ][1]['CAN_EDIT'];

							echo '<tr><td style="text-align:right"><input type="checkbox" name="can_use['.str_replace('.','_',$file).']" value="true"'.($can_use=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></td>';

							echo '<td style="text-align:right"><input type="checkbox" name="can_edit['.str_replace('.','_',$file).']" value="true"'.($can_edit=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></td>';

							echo '<td>'.$title.'</td></tr>';
						}
					}
					elseif ( $modcat=='Users' && $file=='Users/User.php')
					{
						$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STAFF_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
						foreach ( (array) $categories_RET as $category)
						{
							$file = 'Users/User.php&category_id='.$category['ID'];
							$title = '&nbsp;&nbsp;&rsaquo; '.ParseMLField($category['TITLE']);
							$can_use = $exceptions_RET[ $file ][1]['CAN_USE'];
							$can_edit = $exceptions_RET[ $file ][1]['CAN_EDIT'];

							echo '<tr><td style="text-align:right"><input type="checkbox" name="can_use['.str_replace('.','_',$file).']" value="true"'.($can_use=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></td>';

							echo '<td style="text-align:right"><input type="checkbox" name="can_edit['.str_replace('.','_',$file).']" value="true"'.($can_edit=='Y'?' checked':'').(AllowEdit()?'':' DISABLED').'></td>';

							echo '<td>'.$title.'</td></tr>';
						}
					}
				}
				elseif ( $file !== 'default'
					&& $file !== 'title' )
				{
					echo '<tr><td colspan="3" class="center"><b>- '.$title.' -</b></td></tr>';
				}

			}
		}
		//echo '<tr><td colspan="3" style="text-align:center; height:20px;"></td></tr>';
	}
	echo '</table>';
	PopTable('footer');
	echo '<div class="center">' . SubmitButton( _( 'Save' ) ) . '</div>';

	echo '</div>';
	echo '</td></tr></table>';
	echo '</form>';
	echo '<div id="new_id_content" style="position:absolute;visibility:hidden;">'._('Title').' <input type="text" name="new_profile_title" /><br />';
	echo _('Type').' <select name="new_profile_type"><option value="admin">'._('Administrator').'<option value="teacher">'._('Teacher').'<option value="parent">'._('Parent').'</select></div>';
}
else
{
	$profile_title = DBGet(DBQuery("SELECT TITLE FROM USER_PROFILES WHERE ID='".$staff_RET[1]['PROFILE_ID']."'"));
	echo '<br />';

	$error[] = sprintf(_('%s %s is assigned to the profile %s.'),$staff_RET[1]['FIRST_NAME'],$staff_RET[1]['LAST_NAME'],$profile_title[1]['TITLE']);

	$error[] = sprintf(_('To assign permissions to this user, either change the permissions for this profile using the %s setup or change this user to a user with custom permissions by using %s.'), (AllowUse('Users/Profiles.php') ? '<a href="Modules.php?modname=Users/Profiles.php">' : '')._('Profiles').(AllowUse('Users/Profiles.php') ? '</a>' : ''), (AllowUse('Users/User.php') ? '<a href="Modules.php?modname=Users/User.php">' : '')._('General Info').(AllowUse('Users/User.php') ? '</a>' : ''));

	echo ErrorMessage($error);
}
}
