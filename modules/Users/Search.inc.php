<?php
//if($_REQUEST['modfunc']=='search_fnc' || !$_REQUEST['modfunc'])
if($_REQUEST['search_modfunc']=='search_fnc' || !$_REQUEST['search_modfunc'])
{
	switch(User('PROFILE'))
	{
		case 'admin':
		case 'teacher':
			//if($_SESSION['staff_id'] && ($_REQUEST['modname']!='Users/Search.php' || $_REQUEST['student_id']=='new'))
			if($_SESSION['staff_id'] && User('PROFILE')=='admin' && $_REQUEST['staff_id']=='new')
			{
				unset($_SESSION['staff_id']);
				echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
			}

			$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back','advanced'));
			if($_SESSION['Back_PHP_SELF']!='staff')
			{
				$_SESSION['Back_PHP_SELF'] = 'staff';
				unset($_SESSION['List_PHP_SELF']);
			}
			echo '<script type="text/javascript">var footer_link = document.createElement("a"); footer_link.href = "Bottom.php"; footer_link.target = "footer"; ajaxLink(footer_link);</script>';
			echo '<BR />';
			PopTable('header',$extra['search_title']?$extra['search_title']:_('Find a User'));
			echo '<FORM name="search" id="search" action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&search_modfunc=list&next_modname='.$_REQUEST['next_modname'].'&advanced='.$_REQUEST['advanced'].$extra['action'].'" method="POST">';
			echo '<TABLE>';

			echo '<TR class="valign-top"><TD>';
//modif Francois: css WPadmin
			echo '<TABLE class="width-100p" id="general_table">';
			echo '<TR><TD style="text-align:right;"><label for="last">'._('Last Name').'</label></TD><TD><INPUT type="text" name="last" id="last" size="30"></TD></TR>';
			echo '<TR><TD style="text-align:right;"><label for="first">'._('First Name').'</label></TD><TD><INPUT type="text" name="first" id="first" size="30"></TD></TR>';
			echo '<TR><TD style="text-align:right;"><label for="usrid">'._('User ID').'</label></TD><TD><input type="text" name="usrid" id="usrid" size="30"></TD></TR>';
			echo '<TR><TD style="text-align:right;"><label for="username">'._('Username').'</label></TD><TD><INPUT type="text" name="username" id="username" size="30"></TD></TR>';
			if(User('PROFILE')=='admin')
				$options = array(''=>_('N/A'),'admin'=>_('Administrator'),'teacher'=>_('Teacher'),'parent'=>_('Parent'),'none'=>_('No Access'));
			else
				$options = array(''=>_('N/A'),'teacher'=>_('Teacher'),'parent'=>_('Parent'));
			if($extra['profile'])
				$options = array($extra['profile']=>$options[$extra['profile']]);
			echo '<TR><TD style="text-align:right;"><label for="profile">'._('Profile').'</label></TD><TD><SELECT name="profile" id="profile">';
			foreach($options as $key=>$val)
				echo '<OPTION value="'.$key.'">'.$val;
			echo '</SELECT></TD></TR>';
			if(!isset($extra))
				$extra = array();
			StaffWidgets('user',$extra);
			Search('staff_fields',is_array($extra['staff_fields'])?$extra['staff_fields']:array());
			echo '</TABLE>';
			echo '</TD><TR><TD class="center">';
			if($extra['search_second_col'])
				echo $extra['search_second_col'];
			if(User('PROFILE')=='admin')
			{
//modif Francois: add <label> on checkbox
//modif Francois: if only one school, no Search All Schools option
				if (SchoolInfo('SCHOOLS_NB') > 1)
					echo '<label><INPUT type="checkbox" name="_search_all_schools" value="Y"'.(Preferences('DEFAULT_ALL_SCHOOLS')=='Y'?' checked':'').'>&nbsp;'._('Search All Schools').'</label><BR />';
			}
			else
				echo '<label><INPUT type="checkbox" name="include_inactive" value="Y"> '._('Include Parents of Inactive Students').'</label><BR />';
			echo '<BR />';
			echo Buttons(_('Submit'),_('Reset'));
			echo '</TD></TR><TR><TD><TABLE>';

			if($extra['search'])
				echo $extra['search'];
			if($extra['extra_search'])
				echo $extra['extra_search'];
			if($extra['second_col'])
				echo $extra['second_col'];
				
			echo '</TABLE></TD></TR><TR class="valign-top"><TD><TABLE class="width-100p cellspacing-0 cellpadding-0"><TR><TD>';
			if($_REQUEST['advanced']=='Y')
			{
				$extra['search'] = '';
				StaffWidgets('all',$extra);
				if ($extra['search'])
				{
					echo '<TABLE class="postbox cellpadding-0 cellspacing-0"><TR><TH>';
					echo '<H3>'._('Widgets').'</H3></TH></TR>';
					echo $extra['search'];
					echo '</TABLE><br />';
				}

				if ($user_fields = Search('staff_fields_all',is_array($extra['staff_fields'])?$extra['staff_fields']:array()))
				{
					echo '<TABLE class="postbox cellpadding-0 cellspacing-0"><TR><TH>';
					echo '<H3>'._('User Fields').'</H3></TH></TR><TR><TD>';
					echo $user_fields;
					echo '</TD></TR>';
					echo '</TABLE>';
				}
				echo '<A href="'.PreparePHP_SELF($_REQUEST,array(),array('advanced'=>'N')).'">'._('Basic Search').'</A>';
			}
			else
				echo '<TR><TD><BR /><A href="'.PreparePHP_SELF($_REQUEST,array(),array('advanced'=>'Y')).'">'._('Advanced Search').'</A>';
			echo '</TD></TR></TABLE></TD>';
			echo '</TR>';

			echo '</TABLE>';
			echo '</FORM>';
			// set focus to last name text box
			echo '<script type="text/javascript"><!--
				document.search.last.focus();
				--></script>';
			PopTable('footer');
		break;

		default:
			echo User('PROFILE');
	}
}
//if($_REQUEST['search_modfunc']=='list')
else
{
	if(!$_REQUEST['next_modname'])
		$_REQUEST['next_modname'] = 'Users/User.php';

	if(User('PROFILE')=='admin')
	{
		if(!isset($extra))
			$extra = array();
		StaffWidgets('user',$extra);
		if($_REQUEST['advanced']=='Y')
			StaffWidgets('all',$extra);
	}

	if(!$extra['NoSearchTerms'])
	{
		if($_REQUEST['_search_all_schools']=='Y')
			$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'._('Search All Schools').'</b></span><BR />';
	}
	$extra['WHERE'] .= appendStaffSQL('',array('NoSearchTerms'=>$extra['NoSearchTerms']));
	$extra['WHERE'] .= CustomFields('where','staff',array('NoSearchTerms'=>$extra['NoSearchTerms']));
	if(!isset($_ROSARIO['DrawHeader'])) DrawHeader(_('Choose A User'));
	$staff_RET = GetStaffList($extra);
	if($extra['profile'])
	{
        // DO NOT translate those strings since they will be passed to ListOutput ultimately
		$options = array('admin'=>'Administrator','teacher'=>'Teacher','parent'=>'Parent','none'=>'No Access');
		$singular = $options[$extra['profile']];
		$plural = $singular.($options[$extra['profile']]=='none'?'':'s');
		$columns = array('FULL_NAME'=>$singular,'STAFF_ID'=>_('RosarioSIS ID'));
	}
	else
	{
		$columns = array('FULL_NAME'=>_('User'),'PROFILE'=>_('Profile'),'STAFF_ID'=>_('RosarioSIS ID'));
	}

	$name_link['FULL_NAME']['link'] = 'Modules.php?modname='.$_REQUEST['next_modname'];
	$name_link['FULL_NAME']['variables'] = array('staff_id'=>'STAFF_ID');
	if(is_array($extra['link']))
		$link = $extra['link'] + $name_link;
	else
		$link = $name_link;

	if(is_array($extra['columns_before']))
		$columns = $extra['columns_before'] + $columns;
	if(is_array($extra['columns_after']))
		$columns += $extra['columns_after'];

	if(count($staff_RET)>1 || $link['add'] || !$link['FULL_NAME'] || $extra['columns_before'] || $extra['columns_after'] || ($extra['BackPrompt']==false && count($staff_RET)==0) || ($extra['Redirect']===false && count($staff_RET)==1))
	{
		if($_REQUEST['expanded_view']!='true')
			DrawHeader('<A HREF="'.PreparePHP_SELF($_REQUEST,array(),array('expanded_view'=>'true')) . '">'._('Expanded View').'</A>',$extra['header_right']);
		else
			DrawHeader('<A HREF="'.PreparePHP_SELF($_REQUEST,array(),array('expanded_view'=>'false')) . '">'._('Original View').'</A>',$extra['header_right']);
		DrawHeader($extra['extra_header_left'],$extra['extra_header_right']);
		DrawHeader(str_replace('<BR />','<BR /> &nbsp;',mb_substr($_ROSARIO['SearchTerms'],0,-6)));
		if(!$_REQUEST['LO_save'] && !$extra['suppress_save'])
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
			if($_SESSION['Back_PHP_SELF']!='staff')
			{
				$_SESSION['Back_PHP_SELF'] = 'staff';
				unset($_SESSION['Search_PHP_SELF']);
			}
			echo '<script type="text/javascript">var footer_link = document.createElement("a"); footer_link.href = "Bottom.php"; footer_link.target = "footer"; ajaxLink(footer_link);</script>';
		}
		if($extra['profile'])
			ListOutput($staff_RET,$columns,$singular,$plural,$link,false,$extra['options']);
		else
//modif Francois: add translation
			ListOutput($staff_RET,$columns,'User','Users',$link,false,$extra['options']);
	}
	elseif(count($staff_RET)==1)
	{
		if(count($link['FULL_NAME']['variables']))
		{
			foreach($link['FULL_NAME']['variables'] as $var=>$val)
				$_REQUEST[$var] = $staff_RET['1'][$val];
		}
		if(!is_array($staff_RET[1]['STAFF_ID']))
		{
			$_SESSION['staff_id'] = $staff_RET[1]['STAFF_ID'];
			echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
			unset($_REQUEST['search_modfunc']);
		}
		if($_REQUEST['modname']!=$_REQUEST['next_modname'])
		{
			$modname = $_REQUEST['next_modname'];
			if(mb_strpos($modname,'?'))
				$modname = mb_substr($_REQUEST['next_modname'],0,mb_strpos($_REQUEST['next_modname'],'?'));
			if(mb_strpos($modname,'&'))
				$modname = mb_substr($_REQUEST['next_modname'],0,mb_strpos($_REQUEST['next_modname'],'&'));
			if($_REQUEST['modname'])
				$_REQUEST['modname'] = $modname;
			//modif Francois: security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
			if (mb_substr($modname, -4, 4)!='.php' || mb_strpos($modname, '..')!==false || !is_file('modules/'.$modname))	
				HackingLog();
			else
				include('modules/'.$modname);
		}
	}
	else
	{		
		DrawHeader('',$extra['header_right']);
		DrawHeader($extra['extra_header_left'],$extra['extra_header_right']);
		DrawHeader(str_replace('<BR />','<BR /> &nbsp;',mb_substr($_ROSARIO['SearchTerms'],0,-6)));

		echo ErrorMessage(array(_('No Users were found.')));
	}
}
?>