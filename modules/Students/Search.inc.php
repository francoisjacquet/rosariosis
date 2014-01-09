<?php
if($_ROSARIO['modules_search'] && $extra['force_search'])
	$_REQUEST['search_modfunc'] = '';

if(Preferences('SEARCH')!='Y' && !$extra['force_search'])
	$_REQUEST['search_modfunc'] = 'list';
if($_REQUEST['search_modfunc']=='search_fnc' || !$_REQUEST['search_modfunc'])
{
	//if($_SESSION['student_id'] && User('PROFILE')!='parent' && User('PROFILE')!='student' && ($_REQUEST['modname']!='Students/Search.php' || $_REQUEST['student_id']=='new'))
	switch(User('PROFILE'))
	{
		case 'admin':
		case 'teacher':
			//if($_SESSION['student_id'] && ($_REQUEST['modname']!='Students/Search.php' || $_REQUEST['student_id']=='new'))
			if($_SESSION['student_id'] && $_REQUEST['student_id']=='new')
			{
				unset($_SESSION['student_id']);
				echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
			}

			$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back','advanced'));
			if($_SESSION['Back_PHP_SELF']!='student')
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset($_SESSION['List_PHP_SELF']);
			}
			echo '<script type="text/javascript">var footer_link = document.createElement("a"); footer_link.href = "Bottom.php"; footer_link.target = "footer"; ajaxLink(footer_link);</script>';
			echo '<BR />';
			PopTable('header',$extra['search_title']?$extra['search_title']:_('Find a Student'));
			echo '<FORM name="search" id="search" action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&search_modfunc=list&next_modname='.$_REQUEST['next_modname'].'&advanced='.$_REQUEST['advanced'].$extra['action'].'" method="POST">';
			echo '<TABLE>';

			echo '<TR class="valign-top"><TD>';
			echo '<TABLE class="width-100p" id="general_table">';
			Search('general_info',$extra['grades']);
			if(!isset($extra))
				$extra = array();
			Widgets('user',$extra);
			Search('student_fields',is_array($extra['student_fields'])?$extra['student_fields']:array());
			echo '</TABLE>';
			echo '</TD><TR><TD class="center">';
			if($extra['search_second_col'])
				echo $extra['search_second_col'];
			if(User('PROFILE')=='admin')
			{
//modif Francois: add <label> on checkbox
//modif Francois: css WPadmin
				echo '<label><INPUT type="checkbox" name="address_group" value="Y"'.(Preferences('DEFAULT_FAMILIES')=='Y'?' checked':'').'>&nbsp;'._('Group by Family').'</label><BR />';
//modif Francois: if only one school, no Search All Schools option
				if (SchoolInfo('SCHOOLS_NB') > 1)
					echo '<label><INPUT type="checkbox" name="_search_all_schools" value="Y"'.(Preferences('DEFAULT_ALL_SCHOOLS')=='Y'?' checked':'').'>&nbsp;'._('Search All Schools').'</label><BR />';
			}
			echo '<label><INPUT type="checkbox" name="include_inactive" value="Y">&nbsp;'._('Include Inactive Students').'</label><BR />';
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
				Widgets('all',$extra);
				echo '<TABLE class="postbox cellpadding-0 cellspacing-0"><TR><TH>';
//				echo '<span style="color:'.Preferences('HEADER').'><B>'._('Widgets').'</B></span><BR />';
				echo '<H3>'._('Widgets').'</H3></TH></TR>';
				echo $extra['search'];
//				echo '</TD></TR>';
				echo '</TABLE><br />';

//				echo '<TR><TD>';
				echo '<TABLE class="postbox cellpadding-0 cellspacing-0"><TR><TH>';
//				echo '<span style="color:'.Preferences('HEADER').'><B>'._('Student Fields').'</B></span><BR />';
				echo '<H3>'._('Student Fields').'</H3></TH></TR><TR><TD>';
				Search('student_fields_all',is_array($extra['student_fields'])?$extra['student_fields']:array());
				echo '</TD></TR>';
//				echo '<TR><TD><BR /><A href='.PreparePHP_SELF($_REQUEST,array(),array('advanced'=>'N')).'>'._('Basic Search').'</A></TD></TR></TABLE>';
				echo '</TABLE><A href="'.PreparePHP_SELF($_REQUEST,array(),array('advanced'=>'N')).'">'._('Basic Search').'</A>';
			}
			else
				echo '<BR /><A href="'.PreparePHP_SELF($_REQUEST,array(),array('advanced'=>'Y')).'">'._('Advanced Search').'</A>';
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

		case 'parent':
		case 'student':
			echo '<BR />';
			PopTable('header',_('Search'));
			echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&search_modfunc=list&next_modname='.$_REQUEST['next_modname'].$extra['action'].'" method="POST">';
			echo '<TABLE>';
			if($extra['search'])
				echo $extra['search'];
			echo '<TR><TD colspan="2" class="center">';
			echo '<BR />';
			echo Buttons(_('Submit'),_('Reset'));
			echo '</TD></TR>';
			echo '</TABLE>';
			echo '</FORM>';
			PopTable('footer');
		break;
	}
}
//if($_REQUEST['search_modfunc']=='list')
else
{
	if(!$_REQUEST['next_modname'])
		$_REQUEST['next_modname'] = 'Students/Student.php';

	if(User('PROFILE')=='admin' || User('PROFILE')=='teacher')
	{
		if(!isset($extra))
			$extra = array();
		Widgets('user',$extra);
		if($_REQUEST['advanced']=='Y')
			Widgets('all',$extra);
	}

	if(!$extra['NoSearchTerms'])
	{
		if($_REQUEST['_search_all_schools']=='Y')
			$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'._('Search All Schools').'</b></span><BR />';
		if($_REQUEST['include_inactive']=='Y')
			$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'._('Include Inactive Students').'</b></span><BR />';
	}
	if($_REQUEST['address_group'])
	{
		$extra['SELECT'] .= ",coalesce((SELECT ADDRESS_ID FROM STUDENTS_JOIN_ADDRESS WHERE STUDENT_ID=ssm.STUDENT_ID AND RESIDENCE='Y' LIMIT 1),-ssm.STUDENT_ID) AS FAMILY_ID";
		$extra['group'] = $extra['LO_group'] = array('FAMILY_ID');
	}
	$extra['WHERE'] .= appendSQL('',array('NoSearchTerms'=>$extra['NoSearchTerms']));
	$extra['WHERE'] .= CustomFields('where','student',array('NoSearchTerms'=>$extra['NoSearchTerms']));
	$students_RET = GetStuList($extra);
	if($extra['array_function'] && function_exists($extra['array_function']))
		if($_REQUEST['address_group'])
			foreach($students_RET as $id=>$student_RET)
				$students_RET[$id] = $extra['array_function']($student_RET);
		else
			$students_RET = $extra['array_function']($students_RET);

	$name_link['FULL_NAME']['link'] = 'Modules.php?modname='.$_REQUEST['next_modname'];
	$name_link['FULL_NAME']['variables'] = array('student_id'=>'STUDENT_ID');
	if($_REQUEST['_search_all_schools'])
		$name_link['FULL_NAME']['variables']['school_id'] = 'SCHOOL_ID';
	if(is_array($extra['link']))
		$link = $extra['link'] + $name_link;
	else
		$link = $name_link;

	if(is_array($extra['columns']))
		$columns = $extra['columns'];
	else
		$columns = array('FULL_NAME'=>_('Student'),'STUDENT_ID'=>_('RosarioSIS ID'),'GRADE_ID'=>_('Grade Level'));
	if(is_array($extra['columns_before']))
		$columns = $extra['columns_before'] + $columns;
	if(is_array($extra['columns_after']))
		$columns += $extra['columns_after'];

	if(count($students_RET)>1 || $link['add'] || !$link['FULL_NAME'] || $extra['columns_before'] || $extra['columns'] || $extra['columns_after'] || ($extra['BackPrompt']==false && count($students_RET)==0) || (($extra['Redirect']===false || $_REQUEST['address_group']) && count($students_RET)==1))
	{
		if(!isset($_REQUEST['_ROSARIO_PDF']))
		{
			if($_REQUEST['expanded_view']!='true')
				$header_left = '<A HREF="'.PreparePHP_SELF($_REQUEST,array(),array('expanded_view'=>'true')).'">'._('Expanded View').'</A>';
			else
				$header_left = '<A HREF="'.PreparePHP_SELF($_REQUEST,array(),array('expanded_view'=>'false')).'">'._('Original View').'</A>';
			if(!$_REQUEST['address_group'])
				$header_left .= ' | <A HREF="'.PreparePHP_SELF($_REQUEST,array(),array('address_group'=>'Y')).'">'._('Group by Family').'</A>';
			else
				$header_left .= ' | <A HREF="'.PreparePHP_SELF($_REQUEST,array(),array('address_group'=>'')).'">'._('Ungroup by Family').'</A>';
		}
		DrawHeader($header_left,$extra['header_right']);
		DrawHeader($extra['extra_header_left'],$extra['extra_header_right']);
		DrawHeader(str_replace('<BR />','<BR /> &nbsp;',mb_substr($_ROSARIO['SearchTerms'],0,-6)));
		if(!$_REQUEST['LO_save'] && !$extra['suppress_save'])
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
			if($_SESSION['Back_PHP_SELF']!='student')
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset($_SESSION['Search_PHP_SELF']);
			}
			if (User('PROFILE')=='admin' || User('PROFILE')=='teacher')
				echo '<script type="text/javascript">var footer_link = document.createElement("a"); footer_link.href = "Bottom.php"; footer_link.target = "footer"; ajaxLink(footer_link);</script>';
		}
		if($_REQUEST['address_group'])
		{
            ListOutput($students_RET,$columns,'Family','Families',$link,$extra['LO_group'],$extra['options']);
		}
		else
		{
            ListOutput($students_RET,$columns,'Student','Students',$link,$extra['LO_group'],$extra['options']);
		}
	}
	elseif(count($students_RET)==1)
	{
		if(count($link['FULL_NAME']['variables']))
		{
			foreach($link['FULL_NAME']['variables'] as $var=>$val)
				$_REQUEST[$var] = $students_RET['1'][$val];
		}
		if(!is_array($students_RET[1]['STUDENT_ID']))
		{
			$_SESSION['student_id'] = $students_RET[1]['STUDENT_ID'];
			$_SESSION['UserSchool'] = $students_RET[1]['SCHOOL_ID'];
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

		echo ErrorMessage(array(_('No Students were found.')));
	}
}
?>