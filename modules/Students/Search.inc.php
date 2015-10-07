<?php

if(!$_REQUEST['search_modfunc'])
{
	//if(UserStudentID() && User('PROFILE')!='parent' && User('PROFILE')!='student' && ($_REQUEST['modname']!='Students/Search.php' || $_REQUEST['student_id']=='new'))
	switch(User('PROFILE'))
	{
		case 'admin':
		case 'teacher':
			//if($_SESSION['student_id'] && ($_REQUEST['modname']!='Students/Search.php' || $_REQUEST['student_id']=='new'))
			if(UserStudentID() && $_REQUEST['student_id']=='new')
				unset($_SESSION['student_id']);

			$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back','advanced'));
			if($_SESSION['Back_PHP_SELF']!='student')
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset($_SESSION['List_PHP_SELF']);
			}

			echo '<BR />';

			PopTable('header',$extra['search_title']?$extra['search_title']:_('Find a Student'));

			echo '<FORM name="search" id="search" action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&search_modfunc=list&next_modname='.$_REQUEST['next_modname'].'&advanced='.$_REQUEST['advanced'].$extra['action'].'" method="GET">';

			echo '<TABLE><TR class="valign-top"><TD>';

			echo '<TABLE class="width-100p col1-align-right" id="general_table">';

			Search('general_info',$extra['grades']);

			if(!isset($extra))
				$extra = array();

			Widgets('user',$extra);

			Search('student_fields',is_array($extra['student_fields'])?$extra['student_fields']:array());


			echo '</TABLE></TD><TR><TD class="center">';

			if($extra['search_second_col'])
				echo $extra['search_second_col'];

			if(User('PROFILE')=='admin')
			{
//FJ add <label> on checkbox
//FJ css WPadmin
				echo '<label><INPUT type="checkbox" name="address_group" value="Y"'.(Preferences('DEFAULT_FAMILIES')=='Y'?' checked':'').'>&nbsp;'._('Group by Family').'</label><BR />';
//FJ if only one school, no Search All Schools option
				if (SchoolInfo('SCHOOLS_NB') > 1)
					echo '<label><INPUT type="checkbox" name="_search_all_schools" value="Y"'.(Preferences('DEFAULT_ALL_SCHOOLS')=='Y'?' checked':'').'>&nbsp;'._('Search All Schools').'</label><BR />';
			}

			echo '<label><INPUT type="checkbox" name="include_inactive" value="Y">&nbsp;'._('Include Inactive Students').'</label><BR /><BR />';

			echo Buttons(_('Submit'),_('Reset'));

			echo '</TD></TR>';
			
			if ($extra['search'] || $extra['extra_search'] || $extra['second_col'])
			{
				echo '<TR><TD><TABLE class="widefat width-100p cellspacing-0 col1-align-right">';

				if($extra['search'])
					echo $extra['search'];
				if($extra['extra_search'])
					echo $extra['extra_search'];
				if($extra['second_col'])
					echo $extra['second_col'];

				echo '</TABLE></TD></TR>';
			}

			echo '<TR class="valign-top"><TD>';

			if($_REQUEST['advanced']=='Y')
			{
				$extra['search'] = '';
				Widgets('all',$extra);

				echo '<TABLE class="postbox cellspacing-0"><THEAD><TR><TH>';
				echo '<H3>'._('Widgets').'</H3></TH></THEAD><TBODY></TR>';
				echo $extra['search'];
				echo '</TBODY></TABLE><br />';

				echo '<TABLE class="postbox cellspacing-0"><THEAD><TR><TH>';
				echo '<H3>'._('Student Fields').'</H3></TH></TR></THEAD><TBODY><TR><TD>';
				Search('student_fields_all',is_array($extra['student_fields'])?$extra['student_fields']:array());
				echo '</TD></TR>';
				echo '</TBODY></TABLE>';

				echo '<BR /><A href="'.PreparePHP_SELF($_REQUEST,array(),array('advanced'=>'N')).'">'._('Basic Search').'</A>';
			}
			else
				echo '<BR /><A href="'.PreparePHP_SELF($_REQUEST,array(),array('advanced'=>'Y')).'">'._('Advanced Search').'</A>';

			echo '</TD></TR></TABLE></FORM>';

			// set focus to last name text box
			// update Bottom.php
			echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';

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

			echo '<TR><TD colspan="2" class="center"><BR />';

			echo Buttons(_('Submit'),_('Reset'));

			echo '</TD></TR></TABLE></FORM>';

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
			$_ROSARIO['SearchTerms'] .= '<b>'._('Search All Schools').'</b><BR />';

		if($_REQUEST['include_inactive']=='Y')
			$_ROSARIO['SearchTerms'] .= '<b>'._('Include Inactive Students').'</b><BR />';
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

	if(isset($extra['link']) && is_array($extra['link']))
		$link = $extra['link'] + $name_link;
	else
		$link = $name_link;

	if(isset($extra['columns']) && is_array($extra['columns']))
		$columns = $extra['columns'];
	else
		$columns = array('FULL_NAME'=>_('Student'),'STUDENT_ID'=>sprintf(_('%s ID'),Config('NAME')),'GRADE_ID'=>_('Grade Level'));

	if(isset($extra['columns_before']) && is_array($extra['columns_before']))
		$columns = $extra['columns_before'] + $columns;

	if(isset($extra['columns_after']) && is_array($extra['columns_after']))
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
				echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';
		}

		if($_REQUEST['address_group'])
		{
			ListOutput($students_RET,$columns,'Family','Families',$link,$extra['LO_group'],$extra['options']);
		}
		else
		{
			//FJ override "Student" if extra singular/plural set
			if (!empty($extra['singular']) && !empty($extra['plural']))
				ListOutput($students_RET,$columns,$extra['singular'],$extra['plural'],$link,$extra['LO_group'],$extra['options']);
			else
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
			if($students_RET[1]['SCHOOL_ID']!=UserSchool())
				$_SESSION['UserSchool'] = $students_RET[1]['SCHOOL_ID'];

			SetUserStudentID($students_RET[1]['STUDENT_ID']);

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

			//FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
			if (mb_substr($modname, -4, 4)!='.php' || mb_strpos($modname, '..')!==false || !is_file('modules/'.$modname))
			{
				include('ProgramFunctions/HackingLog.fnc.php');
				HackingLog();
			}
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
