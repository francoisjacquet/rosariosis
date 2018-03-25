<?php
if ( $_REQUEST['modfunc'] === 'save' )
{
	if (count($_REQUEST['cp_arr']))
	{
		$cp_list = '\''.implode('\',\'',$_REQUEST['cp_arr']).'\'';

		$extra['DATE'] = DBGet(DBQuery("SELECT min(SCHOOL_DATE) AS START_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		$extra['DATE'] = $extra['DATE'][1]['START_DATE'];

		if ( ! $extra['DATE']
			|| DBDate() > $extra['DATE'] )
		{
			$extra['DATE'] = DBDate();
		}

		// get the fy marking period id, there should be exactly one fy marking period
		$fy_id = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
		$fy_id = $fy_id[1]['MARKING_PERIOD_ID'];

		//FJ multiple school periods for a course period
		//FJ add subject areas
		$course_periods_RET = DBGet( DBQuery ("SELECT cp.TITLE,cp.COURSE_PERIOD_ID,cp.TITLE,
		cp.MARKING_PERIOD_ID,cp.MP,c.TITLE AS COURSE_TITLE,cp.TEACHER_ID,
		(SELECT " . DisplayNameSQL() . " FROM STAFF WHERE STAFF_ID=cp.TEACHER_ID) AS TEACHER
		FROM COURSE_PERIODS cp,COURSES c
		WHERE c.COURSE_ID=cp.COURSE_ID
		AND cp.COURSE_PERIOD_ID IN (" . $cp_list . ")
		ORDER BY TEACHER" ) );

		$first_extra = $extra;
		$handle = PDFStart();

		$PCL_UserCoursePeriod = UserCoursePeriod(); // save/restore for teachers

		$no_students_backprompt = true;

		foreach ( (array) $course_periods_RET as $teacher_id => $course_period)
		{
			$_SESSION['UserCoursePeriod'] = $course_period['COURSE_PERIOD_ID'];

			$extra = array('SELECT_ONLY' => '1');

			//FJ prevent course period ID hacking
			if (User('PROFILE')=='teacher')
			{
				$extra['WHERE'] = " AND '".User('STAFF_ID')."'=(SELECT TEACHER_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".UserCoursePeriod()."')";
			}
			elseif (User('PROFILE')=='admin')
			{
				$extra['WHERE'] = $extraWHERE = " AND s.STUDENT_ID IN
				(SELECT STUDENT_ID
				FROM SCHEDULE
				WHERE COURSE_PERIOD_ID='".$course_period['COURSE_PERIOD_ID']."'
				AND '".DBDate()."'>=START_DATE
				AND ('".DBDate()."'<=END_DATE OR END_DATE IS NULL))";
			}

			$RET = GetStuList($extra);
			//echo '<pre>'; var_dump($RET); echo '</pre>';

			if (count($RET))
			{
				$no_students_backprompt = false;

				unset($_ROSARIO['DrawHeader']);
				DrawHeader(_('Class List'));

				DrawHeader($course_period['COURSE_TITLE'],$course_period['TITLE']);
				DrawHeader(SchoolInfo('TITLE'),ProperDate(DBDate()));

				$extra = $first_extra;
				$extra['MP'] = $course_period['MARKING_PERIOD_ID'];
				$extra['MPTable'] = $course_period['MP'];
				$extra['suppress_save'] = true;

				if (User('PROFILE')=='admin')
				{
					$extra['WHERE'] .= $extraWHERE;
				}

				require 'modules/misc/Export.php';

				echo '<div style="page-break-after: always;"></div>';
			}
		}
		$_SESSION['UserCoursePeriod'] = $PCL_UserCoursePeriod;

		if ( $no_students_backprompt)
			BackPrompt(_('No Students were found.'));

		PDFStop($handle);
	}
	else
		BackPrompt(_('You must choose at least one course period.'));
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader(ProgramTitle());

	if (User('PROFILE')!='admin')
		$_REQUEST['search_modfunc'] = 'list';

	if ( $_REQUEST['search_modfunc']=='list')
	{
		$_REQUEST['search_modfunc'] = 'select';
		$extra['header_right'] = '<input type="submit" value="'._('Create Class Lists for Selected Course Periods').'" />';

		$extra['extra_header_left'] = '<table><tr><td><label><input type="checkbox" name="include_inactive" value="Y"> '._('Include Inactive Students').'</label></td></tr></table>';

		$Search = 'mySearch';
		require 'modules/misc/Export.php';
	}
	else
	{
		$_SESSION['Search_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));

		if ( $_SESSION['Back_PHP_SELF']!='course')
		{
			$_SESSION['Back_PHP_SELF'] = 'course';
			unset($_SESSION['List_PHP_SELF']);
		}

		echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';

		echo '<br />';

		PopTable('header',_('Find a Course'));

		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc='.$_REQUEST['modfunc'].'&search_modfunc=list&next_modname='.$_REQUEST['next_modname'].'" method="POST">';

		echo '<table>';

		$RET = DBGet( DBQuery( "SELECT STAFF_ID," . DisplayNameSQL() . " AS FULL_NAME
			FROM STAFF
			WHERE PROFILE='teacher'
			AND (SCHOOLS IS NULL OR position('," . UserSchool() . ",' IN SCHOOLS)>0)
			AND SYEAR='" . UserSyear() . "'
			ORDER BY FULL_NAME" ) );

		echo '<tr class="st"><td>'._('Teacher').'</td><td>';

		echo '<select name="teacher_id"><option value="">'._('N/A').'</option>';

		foreach ( (array) $RET as $teacher)
			echo '<option value="'.$teacher['STAFF_ID'].'">'.$teacher['FULL_NAME'].'</option>';

		echo '</select></td></tr>';

		$RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TITLE"));
		echo '<tr class="st"><td>'._('Subject').'</td><td>';
		echo '<select name="subject_id"><option value="">'._('N/A').'</option>';

		foreach ( (array) $RET as $subject)
			echo '<option value="'.$subject['SUBJECT_ID'].'">'.$subject['TITLE'].'</option>';

		echo '</select></td></tr>';

		$RET = DBGet(DBQuery("SELECT PERIOD_ID,TITLE FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
		echo '<tr class="st"><td>'._('Period').'</td><td>';
		echo '<select name="period_id"><option value="">'._('N/A').'</option>';

		foreach ( (array) $RET as $period)
			echo '<option value="'.$period['PERIOD_ID'].'">'.$period['TITLE'].'</option>';

		echo '</select></td></tr>';

		Widgets('course');
		echo $extra['search'];

		echo '<tr><td colspan="2" class="center">';
		echo '<br />';
		echo Buttons(_('Submit'),_('Reset'));

		echo '</td></tr></table></form>';

		PopTable('footer');
	}
}

function mySearch($extra)
{
	echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&search_modfunc=list&_ROSARIO_PDF=true'.$extra['action'].'" method="POST" name="search">';

	DrawHeader('',$extra['header_right']);
	DrawHeader($extra['extra_header_left'],$extra['extra_header_right']);
	echo '<table>'.$extra['extra_search'].'</table>';

	$sql = 'SELECT \'<input type="checkbox" name="cp_arr[]" value="\'||cp.COURSE_PERIOD_ID||\'">\' AS CHECKBOX,cp.TITLE FROM COURSE_PERIODS cp';

	if (User('PROFILE')=='admin')
	{
		if ( ! empty( $_REQUEST['teacher_id'] ) )
			$where .= " AND cp.TEACHER_ID='".$_REQUEST['teacher_id']."'";

		if ( ! empty( $_REQUEST['first'] ) )
			$where .= " AND UPPER(s.FIRST_NAME) LIKE '".mb_strtoupper($_REQUEST['first'])."%'";

		if ( ! empty( $_REQUEST['w_course_period_id'] ) )
			if ( ! empty( $_REQUEST['w_course_period_id'] ) )
			{
				if ( $_REQUEST['w_course_period_id_which']=='course')
					$where .= " AND cp.COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."')";
				else
					$where .= " AND cp.COURSE_PERIOD_ID='".$_REQUEST['w_course_period_id']."'";
			}

		if ( ! empty( $_REQUEST['subject_id'] ) )
		{
			$from .= ",COURSES c";
			$where .= " AND c.COURSE_ID=cp.COURSE_ID AND c.SUBJECT_ID='".$_REQUEST['subject_id']."'";
		}

		//FJ multiple school periods for a course period
		if ( ! empty( $_REQUEST['period_id'] ) )
		{
			$from .= ',COURSE_PERIOD_SCHOOL_PERIODS cpsp';
			$where .= " AND cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cpsp.PERIOD_ID='".$_REQUEST['period_id']."'";
			//$where .= " AND cp.PERIOD_ID='".$_REQUEST['period_id']."'";
		}

		$sql .= "$from WHERE cp.SCHOOL_ID='".UserSchool()."' AND cp.SYEAR='".UserSyear()."'$where";
	}
	else // teacher
	{
		$sql .= " WHERE cp.SCHOOL_ID='".UserSchool()."' AND cp.SYEAR='".UserSyear()."' AND cp.TEACHER_ID='".User('STAFF_ID')."'";
	}
	//FJ multiple school periods for a course period
	//$sql .= ' ORDER BY (SELECT SORT_ORDER FROM SCHOOL_PERIODS WHERE PERIOD_ID=cp.PERIOD_ID)';
	$sql .= ' ORDER BY cp.SHORT_NAME,cp.TITLE';

	$course_periods_RET = DBGet(DBQuery($sql));
	$LO_columns = array('CHECKBOX' => '</a><input type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.checked,\'cp_arr\');"><A>','TITLE' => _('Course Period'));

	if ( empty( $_REQUEST['LO_save'] ) && ! $extra['suppress_save'] )
	{
		$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));

		if ( $_SESSION['Back_PHP_SELF']!='course')
		{
			$_SESSION['Back_PHP_SELF'] = 'course';
			unset($_SESSION['Search_PHP_SELF']);
		}

		echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';
	}

	ListOutput($course_periods_RET,$LO_columns,'Course Period','Course Periods');

	echo '<br /><div class="center"><input type="submit" value="'._('Create Class Lists for Selected Course Periods').'" /></div>';
	echo '</form>';
}
