<?php

if ( ! $_REQUEST['search_modfunc'])
{
	//if (UserStudentID() && User('PROFILE')!='parent' && User('PROFILE')!='student' && ($_REQUEST['modname']!='Students/Search.php' || $_REQUEST['student_id']=='new'))
	switch (User('PROFILE'))
	{
		case 'admin':
		case 'teacher':
			//if ( $_SESSION['student_id'] && ($_REQUEST['modname']!='Students/Search.php' || $_REQUEST['student_id']=='new'))
			if (UserStudentID() && $_REQUEST['student_id']=='new')
				unset($_SESSION['student_id']);

			$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back','advanced'));
			if ( $_SESSION['Back_PHP_SELF']!='student')
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset($_SESSION['List_PHP_SELF']);
			}

			echo '<br />';

			PopTable(
				'header',
				$extra['search_title'] ? $extra['search_title'] : _( 'Find a Student' )
			);

			echo '<form name="search" id="search" action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&search_modfunc=list&next_modname='.$_REQUEST['next_modname'].'&advanced='.$_REQUEST['advanced'].$extra['action'].'" method="GET">';

			echo '<table class="width-100p col1-align-right" id="general_table">';

			Search( 'general_info', $extra['grades'] );

			if ( !isset( $extra ) )
				$extra = array();

			Widgets( 'user', $extra );

			Search(
				'student_fields',
				is_array( $extra['student_fields'] ) ? $extra['student_fields'] : array()
			);


			echo '</table><div class="center">';

			if ( $extra['search_second_col'] )
			{
				echo $extra['search_second_col'];
			}

			if ( User( 'PROFILE' ) === 'admin' )
			{
				echo '<label><input type="checkbox" name="address_group" value="Y"' .
					( Preferences( 'DEFAULT_FAMILIES' ) == 'Y' ? ' checked' : '' ) . '>&nbsp;' .
					_( 'Group by Family' ) . '</label><br />';

				// FJ if only one school, no Search All Schools option.
				// Restrict Search All Schools to user schools.
				if ( SchoolInfo( 'SCHOOLS_NB' ) > 1
					&& ( ! trim( User( 'SCHOOLS' ), ',' )
						|| mb_substr_count( User( 'SCHOOLS' ), ',' ) > 2 ) )
				{
					echo '<label><input type="checkbox" name="_search_all_schools" value="Y"' .
						( Preferences( 'DEFAULT_ALL_SCHOOLS' ) == 'Y' ? ' checked' : '' ) . '>&nbsp;' .
						_( 'Search All Schools' ) . '</label><br />';
				}
			}

			echo '<label><input type="checkbox" name="include_inactive" value="Y">&nbsp;' .
				_( 'Include Inactive Students' ) . '</label><br />';

			echo '<br />' . Buttons( _( 'Submit' ), _( 'Reset' ) ) . '</div><br />';

			if ( $extra['search']
				|| $extra['extra_search']
				|| $extra['second_col'] )
			{
				echo '<table class="widefat width-100p col1-align-right">';

				if ( $extra['search'] )
					echo $extra['search'];

				if ( $extra['extra_search'] )
					echo $extra['extra_search'];

				if ( $extra['second_col'] )
					echo $extra['second_col'];

				echo '</table><br />';
			}

			if ( $_REQUEST['advanced'] === 'Y' )
			{
				$extra['search'] = '';

				Widgets( 'all', $extra );

				echo PopTable( 'header', _( 'Widgets' ) );

				echo $extra['search'];

				echo PopTable( 'footer' ) . '<br />';

				echo PopTable( 'header', _( 'Student Fields' ) );

				Search(
					'student_fields_all',
					is_array( $extra['student_fields'] ) ? $extra['student_fields'] : array()
				);

				echo PopTable( 'footer' ) . '<br />';

				echo '<a href="'.PreparePHP_SELF($_REQUEST,array(),array('advanced' => 'N')).'">'._('Basic Search').'</a>';
			}
			else
				echo '<br /><a href="'.PreparePHP_SELF($_REQUEST,array(),array('advanced' => 'Y')).'">'._('Advanced Search').'</a>';

			echo '</form>';

			// update Bottom.php
			echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';

			PopTable( 'footer' );

		break;

		case 'parent':
		case 'student':

			echo '<br />';

			PopTable('header',_('Search'));

			echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&search_modfunc=list&next_modname='.$_REQUEST['next_modname'].$extra['action'].'" method="POST">';
			echo '<table>';

			if ( $extra['search'])
				echo $extra['search'];

			echo '<tr><td colspan="2" class="center"><br />';

			echo Buttons(_('Submit'),_('Reset'));

			echo '</td></tr></table></form>';

			PopTable( 'footer' );

		break;
	}
}
//if ( $_REQUEST['search_modfunc']=='list')
else
{
	if ( ! $_REQUEST['next_modname'])
		$_REQUEST['next_modname'] = 'Students/Student.php';

	if (User('PROFILE')=='admin' || User('PROFILE')=='teacher')
	{
		if ( !isset($extra))
			$extra = array();
		Widgets('user',$extra);
	}

	if ( ! $extra['NoSearchTerms'])
	{
		if ( $_REQUEST['_search_all_schools']=='Y')
			$_ROSARIO['SearchTerms'] .= '<b>'._('Search All Schools').'</b><br />';

		if ( $_REQUEST['include_inactive']=='Y')
			$_ROSARIO['SearchTerms'] .= '<b>'._('Include Inactive Students').'</b><br />';
	}

	if ( $_REQUEST['address_group'])
	{
		$extra['SELECT'] .= ",coalesce((SELECT ADDRESS_ID FROM STUDENTS_JOIN_ADDRESS WHERE STUDENT_ID=ssm.STUDENT_ID AND RESIDENCE='Y' LIMIT 1),-ssm.STUDENT_ID) AS FAMILY_ID";
		$extra['group'] = $extra['LO_group'] = array('FAMILY_ID');
	}

	$students_RET = GetStuList($extra);

	if ( $extra['array_function'] && function_exists($extra['array_function']))
		if ( $_REQUEST['address_group'])
			foreach ( (array) $students_RET as $id => $student_RET)
				$students_RET[ $id ] = $extra['array_function']($student_RET);
		else
			$students_RET = $extra['array_function']($students_RET);

	$name_link['FULL_NAME']['link'] = 'Modules.php?modname='.$_REQUEST['next_modname'];
	$name_link['FULL_NAME']['variables'] = array('student_id' => 'STUDENT_ID');

	if ( isset( $_REQUEST['_search_all_schools'] )
		&& $_REQUEST['_search_all_schools'] === 'Y' )
	{
		$name_link['FULL_NAME']['variables']['school_id'] = 'SCHOOL_ID';
	}

	if (isset($extra['link']) && is_array($extra['link']))
		$link = $extra['link'] + $name_link;
	else
		$link = $name_link;

	if (isset($extra['columns']) && is_array($extra['columns']))
		$columns = $extra['columns'];
	else
		$columns = array('FULL_NAME' => _('Student'),'STUDENT_ID'=>sprintf(_('%s ID'),Config('NAME')),'GRADE_ID' => _('Grade Level'));

	if (isset($extra['columns_before']) && is_array($extra['columns_before']))
		$columns = $extra['columns_before'] + $columns;

	if (isset($extra['columns_after']) && is_array($extra['columns_after']))
		$columns += $extra['columns_after'];

	if (count($students_RET)>1 || $link['add'] || ! $link['FULL_NAME'] || $extra['columns_before'] || $extra['columns'] || $extra['columns_after'] || ($extra['BackPrompt']==false && count($students_RET)==0) || (($extra['Redirect']===false || $_REQUEST['address_group']) && count($students_RET)==1))
	{
		if ( !isset($_REQUEST['_ROSARIO_PDF']))
		{
			if ( $_REQUEST['expanded_view']!='true')
				$header_left = '<a href="'.PreparePHP_SELF($_REQUEST,array(),array('expanded_view' => 'true')).'">'._('Expanded View').'</a>';
			else
				$header_left = '<a href="'.PreparePHP_SELF($_REQUEST,array(),array('expanded_view' => 'false')).'">'._('Original View').'</a>';

			if ( ! $_REQUEST['address_group'])
				$header_left .= ' | <a href="'.PreparePHP_SELF($_REQUEST,array(),array('address_group' => 'Y')).'">'._('Group by Family').'</a>';
			else
				$header_left .= ' | <a href="'.PreparePHP_SELF($_REQUEST,array(),array('address_group' => '')).'">'._('Ungroup by Family').'</a>';
		}

		DrawHeader($header_left,$extra['header_right']);

		if ( $extra['extra_header_left']
			|| $extra['extra_header_right'] )
		{
			DrawHeader( $extra['extra_header_left'], $extra['extra_header_right'] );
		}

		DrawHeader( mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) );

		if ( ! $_REQUEST['LO_save'] && ! $extra['suppress_save'])
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));

			if ( $_SESSION['Back_PHP_SELF']!='student')
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset($_SESSION['Search_PHP_SELF']);
			}

			if (User('PROFILE')=='admin' || User('PROFILE')=='teacher')
				echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';
		}

		if ( $_REQUEST['address_group'])
		{
			ListOutput($students_RET,$columns,'Family','Families',$link,$extra['LO_group'],$extra['options']);
		}
		else
		{
			//FJ override "Student" if extra singular/plural set
			if ( !empty($extra['singular']) && !empty($extra['plural']))
				ListOutput($students_RET,$columns,$extra['singular'],$extra['plural'],$link,$extra['LO_group'],$extra['options']);
			else
				ListOutput($students_RET,$columns,'Student','Students',$link,$extra['LO_group'],$extra['options']);
		}
	}
	elseif (count($students_RET)==1)
	{
		if (count($link['FULL_NAME']['variables']))
		{
			foreach ( (array) $link['FULL_NAME']['variables'] as $var => $val)
				$_REQUEST[ $var ] = $students_RET['1'][ $val ];
		}

		if ( !is_array($students_RET[1]['STUDENT_ID']))
		{
			if ( $students_RET[1]['SCHOOL_ID']!=UserSchool())
				$_SESSION['UserSchool'] = $students_RET[1]['SCHOOL_ID'];

			SetUserStudentID($students_RET[1]['STUDENT_ID']);

			unset($_REQUEST['search_modfunc']);
		}

		if ( $_REQUEST['modname']!=$_REQUEST['next_modname'])
		{
			$modname = $_REQUEST['next_modname'];

			if (mb_strpos($modname,'?'))
				$modname = mb_substr($_REQUEST['next_modname'],0,mb_strpos($_REQUEST['next_modname'],'?'));

			if (mb_strpos($modname,'&'))
				$modname = mb_substr($_REQUEST['next_modname'],0,mb_strpos($_REQUEST['next_modname'],'&'));

			if ( $_REQUEST['modname'])
				$_REQUEST['modname'] = $modname;

			//FJ security fix, cf http://www.securiteam.com/securitynews/6S02U1P6BI.html
			if (mb_substr($modname, -4, 4)!='.php' || mb_strpos($modname, '..')!==false || !is_file('modules/'.$modname))
			{
				require_once 'ProgramFunctions/HackingLog.fnc.php';
				HackingLog();
			}
			else
				require_once 'modules/'.$modname;
		}
	}
	else
	{
		DrawHeader('',$extra['header_right']);

		if ( $extra['extra_header_left']
			|| $extra['extra_header_right'] )
		{
			DrawHeader( $extra['extra_header_left'], $extra['extra_header_right'] );
		}

		DrawHeader( mb_substr( $_ROSARIO['SearchTerms'], 0, -6 ) );

		echo ErrorMessage(array(_('No Students were found.')));
	}
}
