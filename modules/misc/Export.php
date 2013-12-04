<?php
include_once('ProgramFunctions/miscExport.fnc.php');
//echo '<pre>'; var_dump($_REQUEST); echo '</pre>';
$extra['extra_search'] .= '<TR><TD></TD><TD><DIV id="fields_div"></DIV></TD></TR>';
$extra['extra_search'] .= '<TR><TD></TD><TD><INPUT type="hidden" name="relation" /><INPUT type="hidden" name="residence" /><INPUT type="hidden" name="mailing" /><INPUT type="hidden" name="bus_pickup" /><INPUT type="hidden" name="bus_dropoff" /></TD></TR>';
$extra['action'] .= '" onsubmit="document.search.relation.value=document.getElementById(\'relation\').value; document.search.residence.value=document.getElementById(\'residence\').checked; document.search.mailing.value=document.getElementById(\'mailing\').checked; document.search.bus_pickup.value=document.getElementById(\'bus_pickup\').checked; document.search.bus_dropoff.value=document.getElementById(\'bus_dropoff\').checked;"';
$extra['new'] = true;

$_ROSARIO['CustomFields'] = true;
if($_REQUEST['fields']['ADDRESS'] || $_REQUEST['fields']['CITY'] || $_REQUEST['fields']['STATE'] || $_REQUEST['fields']['ZIPCODE'] || $_REQUEST['fields']['PHONE'] || $_REQUEST['fields']['MAIL_ADDRESS'] || $_REQUEST['fields']['MAIL_CITY'] || $_REQUEST['fields']['MAIL_STATE'] || $_REQUEST['fields']['MAIL_ZIPCODE'] || $_REQUEST['fields']['PARENTS'])
{
	$extra['SELECT'] .= ',a.ADDRESS_ID,a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.PHONE,'.db_case(array('sam.MAILING',"'Y'",'coalesce(a.MAIL_ADDRESS,a.ADDRESS)','NULL')).' AS MAIL_ADDRESS,'.db_case(array('sam.MAILING',"'Y'",'coalesce(a.MAIL_CITY,a.CITY)','NULL')).' AS MAIL_CITY,'.db_case(array('sam.MAILING',"'Y'",'coalesce(a.MAIL_STATE,a.STATE)','NULL')).' AS MAIL_STATE,'.db_case(array('sam.MAILING',"'Y'",'coalesce(a.MAIL_ZIPCODE,a.ZIPCODE)','NULL')).' AS MAIL_ZIPCODE';
	$extra['addr'] = true;
	if($_REQUEST['residence']!='false' || $_REQUEST['mailing']!='false' || $_REQUEST['bus_pickup']!='false' || $_REQUEST['bus_dropoff']!='false')
	{
		$extra['STUDENTS_JOIN_ADDRESS'] .= ' AND (';
		if($_REQUEST['residence']!='false')
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.RESIDENCE='Y' OR ";
		if($_REQUEST['mailing']!='false')
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.MAILING='Y' OR ";
		if($_REQUEST['bus_pickup']!='false')
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.BUS_PICKUP='Y' OR ";
		if($_REQUEST['bus_dropoff']!='false')
			$extra['STUDENTS_JOIN_ADDRESS'] .= "sam.BUS_DROPOFF='Y' OR ";
		$extra['STUDENTS_JOIN_ADDRESS'] .= 'FALSE)';
	}

	if($_REQUEST['fields']['PARENTS'])
	{
		$extra['SELECT'] .= ',ssm.STUDENT_ID AS PARENTS';
		$view_other_RET['ALL_CONTACTS'][1]['VALUE']='Y';
		//modif Francois: PrintClassLists with all contacts
		//if($_REQUEST['relation']!='')
		//{
			$_ROSARIO['makeParents'] = $_REQUEST['relation'];
			//$extra['STUDENTS_JOIN_ADDRESS'] .= " AND EXISTS (SELECT '' FROM STUDENTS_JOIN_PEOPLE sjp WHERE sjp.ADDRESS_ID=sam.ADDRESS_ID AND ".($_REQUEST['relation']!='!'?"lower(sjp.STUDENT_RELATION) LIKE '".mb_strtolower($_REQUEST['relation'])."%'":"sjp.STUDENT_RELATION IS NULL").") ";
		//}
	}
}
$extra['SELECT'] .= ',ssm.NEXT_SCHOOL,ssm.CALENDAR_ID,ssm.SYEAR,ssm.SCHOOL_ID AS SCHOOL_NUMBER,s.*';
if($_REQUEST['fields']['FIRST_INIT'])
	$extra['SELECT'] .= ',SUBSTR(s.FIRST_NAME,1,1) AS FIRST_INIT';
if($_REQUEST['fields']['GIVEN_NAME'])
	$extra['SELECT'] .= ",s.LAST_NAME||', '||s.FIRST_NAME||' '||coalesce(s.MIDDLE_NAME,' ') AS GIVEN_NAME";
if($_REQUEST['fields']['COMMON_NAME'])
	$extra['SELECT'] .= ",s.LAST_NAME||', '||s.FIRST_NAME AS COMMON_NAME";

if(!$extra['functions'])
	$extra['functions'] = array('NEXT_SCHOOL'=>'_makeNextSchool','CALENDAR_ID'=>'_makeCalendar','SCHOOL_ID'=>'GetSchool','SCHOOL_NUMBER'=>'GetSchool','PARENTS'=>'makeParents','LAST_LOGIN'=>'makeLogin');

if($_REQUEST['search_modfunc']=='list')
{
	if (empty($_REQUEST['fields']))
		if (isset($_REQUEST['_ROSARIO_PDF']))
			BackPrompt(_('You must choose at least one field'));
		else
			echo ErrorMessage(array(_('You must choose at least one field')), 'fatal');
	if(!$fields_list)
	{
//modif Francois: disable mailing address display
		if (Config('STUDENTS_USE_MAILING'))
			$fields_list = array('FULL_NAME'=>_('Last, First M'),'FIRST_NAME'=>_('First Name'),'FIRST_INIT'=>_('First Name Initial'),'LAST_NAME'=>_('Last Name'),'MIDDLE_NAME'=>_('Middle Name'),'NAME_SUFFIX'=>_('Suffix'),'STUDENT_ID'=>_('RosarioSIS ID'),'GRADE_ID'=>_('Grade Level'),'SCHOOL_ID'=>_('School'),'SCHOOL_NUMBER'=>_('School Number'),'NEXT_SCHOOL'=>_('Rolling / Retention Options'),'CALENDAR_ID'=>_('Calendar'),'USERNAME'=>_('Username'),'START_DATE'=>_('Enrollment Start Date'),'END_DATE'=>_('Enrollment End Date'),'ENROLLMENT_SHORT'=>_('Enrollment Code'),'DROP_SHORT'=>_('Drop Code'),'ADDRESS'=>_('Street'),'CITY'=>_('City'),'STATE'=>_('State'),'ZIPCODE'=>_('Zip Code'),'PHONE'=>_('Home Phone'),'MAIL_ADDRESS'=>_('Mailing Address'),'MAIL_CITY'=>_('Mailing City'),'MAIL_STATE'=>_('Mailing State'),'MAIL_ZIPCODE'=>_('Mailing Zipcode'),'PARENTS'=>_('Contacts'));
		else
			$fields_list = array('FULL_NAME'=>_('Last, First M'),'FIRST_NAME'=>_('First Name'),'FIRST_INIT'=>_('First Name Initial'),'LAST_NAME'=>_('Last Name'),'MIDDLE_NAME'=>_('Middle Name'),'NAME_SUFFIX'=>_('Suffix'),'STUDENT_ID'=>_('RosarioSIS ID'),'GRADE_ID'=>_('Grade Level'),'SCHOOL_ID'=>_('School'),'SCHOOL_NUMBER'=>_('School Number'),'NEXT_SCHOOL'=>_('Rolling / Retention Options'),'CALENDAR_ID'=>_('Calendar'),'USERNAME'=>_('Username'),'START_DATE'=>_('Enrollment Start Date'),'END_DATE'=>_('Enrollment End Date'),'ENROLLMENT_SHORT'=>_('Enrollment Code'),'DROP_SHORT'=>_('Drop Code'),'ADDRESS'=>_('Street'),'CITY'=>_('City'),'STATE'=>_('State'),'ZIPCODE'=>_('Zip Code'),'PHONE'=>_('Home Phone'),'PARENTS'=>_('Contacts'));
		
		
		if($extra['field_names'])
			$fields_list += $extra['field_names'];

		$fields_list['PERIOD_ATTENDANCE'] = _('Teacher');
		$periods_RET = DBGet(DBQuery("SELECT TITLE,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
		foreach($periods_RET as $period)
			$fields_list['PERIOD_'.$period['PERIOD_ID']] = $period['TITLE'].' '._('Teacher').' - '._('Room');
	}

	$custom_RET = DBGet(DBQuery("SELECT TITLE,ID,TYPE FROM CUSTOM_FIELDS WHERE ID!='200000002' ORDER BY SORT_ORDER,TITLE"),array(),array('ID'));

	foreach($custom_RET as $id=>$field)
	{
		if(!$fields_list['CUSTOM_'.$id])
			$fields_list['CUSTOM_'.$id] = $field[1]['TITLE'];
	}

	$address_RET = DBGet(DBQuery("SELECT TITLE,ID,TYPE FROM ADDRESS_FIELDS ORDER BY SORT_ORDER,TITLE"),array(),array('ID'));

	foreach($address_RET as $id=>$field)
	{
		if(!$fields_list['ADDRESS_'.$id])
		{
			$fields_list['ADDRESS_'.$id] = $field[1]['TITLE'];
			if($_REQUEST['fields']['ADDRESS_'.$id])
			{
				$extra['SELECT'] .= ',a.CUSTOM_'.$id.' AS ADDRESS_'.$id;
				$extra['addr'] = true;
			}
		}
	}
	if($_REQUEST['fields']['START_DATE'] || $_REQUEST['fields']['END_DATE'] || $_REQUEST['fields']['ENROLLMENT_SHORT'] || $_REQUEST['fields']['DROP_SHORT'])
	{
        	$extra['SELECT'] .= ',xse.START_DATE, xse.END_DATE, (select short_name from student_enrollment_codes where id = xse.enrollment_code and syear = xse.syear) as enrollment_short, (select short_name from student_enrollment_codes where id = xse.drop_code and syear = xse.syear) as drop_short' ;
        	$extra['FROM'] .= ',STUDENT_ENROLLMENT xse';
        	$extra['WHERE'] .= ' AND xse.STUDENT_ID=s.STUDENT_ID AND xse.SYEAR=\''.UserSyear().'\'';
	}

	if($_REQUEST['month_include_active_date'])
		$date = $_REQUEST['day_include_active_date'].'-'.$_REQUEST['month_include_active_date'].'-'.$_REQUEST['year_include_active_date'];
	else
		$date = DBDate();

	if($_REQUEST['fields']['PERIOD_ATTENDANCE'])
		//modif Francois: multiple school periods for a course period
		//$extra['SELECT'] .= ',(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \') FROM STAFF st,SCHEDULE ss,COURSE_PERIODS cp,SCHOOL_PERIODS p WHERE ss.STUDENT_ID=ssm.STUDENT_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID=st.STAFF_ID AND cp.PERIOD_ID=p.PERIOD_ID AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL) AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).') AND p.ATTENDANCE=\'Y\') AS PERIOD_ATTENDANCE';
		$extra['SELECT'] .= ',(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \') FROM STAFF st,SCHEDULE ss,COURSE_PERIODS cp,SCHOOL_PERIODS p,COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND ss.STUDENT_ID=ssm.STUDENT_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID=st.STAFF_ID AND cpsp.PERIOD_ID=p.PERIOD_ID AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL) AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).') AND p.ATTENDANCE=\'Y\') AS PERIOD_ATTENDANCE';
	foreach($periods_RET as $period)
	{
		if($_REQUEST['fields']['PERIOD_'.$period['PERIOD_ID']]=='Y')
		{
			//modif Francois: multiple school periods for a course period
			//$extra['SELECT'] .= ',array(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \') FROM STAFF st,SCHEDULE ss,COURSE_PERIODS cp WHERE ss.STUDENT_ID=ssm.STUDENT_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID=st.STAFF_ID AND cp.PERIOD_ID=\''.$period['PERIOD_ID'].'\' AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL) AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).')) AS PERIOD_'.$period['PERIOD_ID'];
			$extra['SELECT'] .= ',array(SELECT st.FIRST_NAME||\' \'||st.LAST_NAME||\' - \'||coalesce(cp.ROOM,\' \') FROM STAFF st,SCHEDULE ss,COURSE_PERIODS cp,COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND ss.STUDENT_ID=ssm.STUDENT_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND cp.TEACHER_ID=st.STAFF_ID AND cpsp.PERIOD_ID=\''.$period['PERIOD_ID'].'\' AND (\''.$date.'\' BETWEEN ss.START_DATE AND ss.END_DATE OR \''.$date.'\'>=ss.START_DATE AND ss.END_DATE IS NULL) AND ss.MARKING_PERIOD_ID IN ('.GetAllMP('QTR',GetCurrentMP('QTR',$date)).')) AS PERIOD_'.$period['PERIOD_ID'];
			$extra['functions']['PERIOD_'.$period['PERIOD_ID']] = '_makeTeachers';
		}
	}

	if($RosarioModules['Food_Service'] && ($_REQUEST['fields']['FS_ACCOUNT_ID']=='Y' || $_REQUEST['fields']['FS_DISCOUNT']=='Y' || $_REQUEST['fields']['FS_STATUS']=='Y' || $_REQUEST['fields']['FS_BARCODE']=='Y' || $_REQUEST['fields']['FS_BALANCE']=='Y'))
	{
		$extra['FROM'] .= ',FOOD_SERVICE_STUDENT_ACCOUNTS fssa';
		$extra['WHERE'] .= ' AND fssa.STUDENT_ID=ssm.STUDENT_ID';
		if($_REQUEST['fields']['FS_ACCOUNT_ID']=='Y')
			$extra['SELECT'] .= ',fssa.ACCOUNT_ID AS FS_ACCOUNT_ID';
		if($_REQUEST['fields']['FS_DISCOUNT']=='Y')
			$extra['SELECT'] .= ',coalesce(fssa.DISCOUNT,\'Full\') AS FS_DISCOUNT';
		if($_REQUEST['fields']['FS_STATUS']=='Y')
			$extra['SELECT'] .= ',coalesce(fssa.STATUS,\'Active\') AS FS_STATUS';
		if($_REQUEST['fields']['FS_BARCODE']=='Y')
			$extra['SELECT'] .= ',fssa.BARCODE AS FS_BARCODE';
		if($_REQUEST['fields']['FS_BALANCE']=='Y')
			$extra['SELECT'] .= ',(SELECT fsa.BALANCE FROM FOOD_SERVICE_ACCOUNTS fsa WHERE fsa.ACCOUNT_ID=fssa.ACCOUNT_ID) AS FS_BALANCE';
		$fields_list += array('FS_ACCOUNT_ID'=>'F/S '._('Account ID'),'FS_DISCOUNT'=>'F/S '._('Discount'),'FS_STATUS'=>'F/S '._('Status'),'FS_BARCODE'=>'F/S '._('Barcode'),'FS_BALANCE'=>'F/S '._('Balance'));
	}

	if($_REQUEST['fields'])
	{
		foreach($_REQUEST['fields'] as $field=>$on)
		{
			$columns[$field] = ParseMLField($fields_list[$field]);
			if(mb_substr($field,0,7)=='CUSTOM_')
			{
				if($custom_RET[mb_substr($field,7)][1]['TYPE']=='date' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'ProperDate';
				elseif($custom_RET[mb_substr($field,7)][1]['TYPE']=='codeds' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'DeCodeds';
				elseif($custom_RET[mb_substr($field,7)][1]['TYPE']=='exports' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'DeCodeds';
			}
			elseif(mb_substr($field,0,8)=='ADDRESS_')
			{
				if($address_RET[mb_substr($field,8)][1]['TYPE']=='date' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'ProperDate';
				elseif($address_RET[mb_substr($field,8)][1]['TYPE']=='codeds' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'DeCodeds';
				elseif($address_RET[mb_substr($field,8)][1]['TYPE']=='exports' && !$extra['functions'][$field])
					$extra['functions'][$field] = 'DeCodeds';
			}
		}
		if($_REQUEST['address_group'])
		{
			$extra['SELECT'] .= ",coalesce((SELECT ADDRESS_ID FROM STUDENTS_JOIN_ADDRESS WHERE STUDENT_ID=ssm.STUDENT_ID AND RESIDENCE='Y' LIMIT 1),-ssm.STUDENT_ID) AS FAMILY_ID";
			$extra['group'] = $extra['LO_group'] = array('FAMILY_ID');
		}

        Widgets('all',$extra);
		$extra['WHERE'] .= appendSQL('',array('NoSearchTerms'=>$extra['NoSearchTerms']));
		$extra['WHERE'] .= CustomFields('where','student',array('NoSearchTerms'=>$extra['NoSearchTerms']));
		$RET = GetStuList($extra);
		if($extra['array_function'] && function_exists($extra['array_function']))
			$extra['array_function']($RET);

		if(!$_REQUEST['LO_save'] && !$extra['suppress_save'])
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
			if($_SESSION['Back_PHP_SELF']!='student')
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset($_SESSION['Search_PHP_SELF']);
			}
			echo '<script type="text/javascript">var footer_link = document.createElement("a"); footer_link.href = "Bottom.php"; footer_link.target = "footer"; ajaxLink(footer_link);</script>';
		}
		if(!isset($_REQUEST['_ROSARIO_PDF']))
		{
			if(!isset($_REQUEST['_ROSARIO_PDF']))
			{
				if(!$_REQUEST['address_group'])
					$header_left = '<A HREF="'.PreparePHP_SELF($_REQUEST,array(),array('address_group'=>'Y')).'">'._('Group by Family').'</A>';
				else
					$header_left = '<A HREF="'.PreparePHP_SELF($_REQUEST,array(),array('address_group'=>'')).'">'._('Ungroup by Family').'</A>';
			}
		}
        
		DrawHeader($header_left);
		DrawHeader(str_replace('<BR />','<BR /> &nbsp;',mb_substr($_ROSARIO['SearchTerms'],0,-6)));
		if ($_REQUEST['address_group'])
			ListOutput($RET,$columns,'Family','Families',array(),$extra['LO_group'],$extra['LO_options']);
		else
			ListOutput($RET,$columns,'Student','Students',array(),$extra['LO_group'],$extra['LO_options']);
	}
}
else
{
	if(!$fields_list)
	{
		if(AllowUse('Students/Student.php&category_id=1'))
			$fields_list['General'] = array('FULL_NAME'=>_('Last, First M'),'FIRST_NAME'=>_('First Name'),'FIRST_INIT'=>_('First Name Initial'),'LAST_NAME'=>_('Last Name'),'MIDDLE_NAME'=>_('Middle Name'),'NAME_SUFFIX'=>_('Suffix'),'STUDENT_ID'=>_('RosarioSIS ID'),'GRADE_ID'=>_('Grade Level'),'SCHOOL_ID'=>_('School'),'SCHOOL_NUMBER'=>_('School Number'),'NEXT_SCHOOL'=>_('Rolling / Retention Options'),'CALENDAR_ID'=>_('Calendar'),'USERNAME'=>_('Username'),'START_DATE'=>_('Enrollment Start Date'),'END_DATE'=>_('Enrollment End Date'),'ENROLLMENT_SHORT'=>_('Enrollment Code'),'DROP_SHORT'=>_('Drop Code'),'LAST_LOGIN'=>_('Last Login'));
		if(AllowUse('Students/Student.php&category_id=3'))
		{
//modif Francois: disable mailing address display
			if (Config('STUDENTS_USE_MAILING'))
				$fields_list['Address'] = array('ADDRESS'=>_('Address'),'MAIL_ADDRESS'=>_('Mailing Address'),'CITY'=>_('City'),'MAIL_CITY'=>_('Mailing City'),'STATE'=>_('State'),'MAIL_STATE'=>_('Mailing State'),'ZIPCODE'=>_('Zip Code'),'MAIL_ZIPCODE'=>_('Mailing Zipcode'),'PHONE'=>_('Home Phone'),'PARENTS'=>_('Contacts'));
			else
				$fields_list['Address'] = array('ADDRESS'=>_('Street'),'CITY'=>_('City'),'STATE'=>_('State'),'ZIPCODE'=>_('Zip Code'),'PHONE'=>_('Home Phone'),'PARENTS'=>_('Contacts'));
				
			$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM ADDRESS_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
			$address_RET = DBGet(DBQuery("SELECT TITLE,ID,TYPE,CATEGORY_ID FROM ADDRESS_FIELDS ORDER BY SORT_ORDER,TITLE"),array(),array('CATEGORY_ID'));

			foreach($categories_RET as $category)
			{
				foreach($address_RET[$category['ID']] as $field)
				{
					$fields_list['Address']['ADDRESS_'.$field['ID']] = str_replace("'",'&#39;',$field['TITLE']);
				}
			}
		}
		if($extra['field_names'])
			$fields_list['General'] += $extra['field_names'];
	}

	$categories_RET = DBGet(DBQuery("SELECT ID,TITLE FROM STUDENT_FIELD_CATEGORIES ORDER BY SORT_ORDER,TITLE"));
	$custom_RET = DBGet(DBQuery("SELECT TITLE,ID,TYPE,CATEGORY_ID FROM CUSTOM_FIELDS ORDER BY SORT_ORDER,TITLE"),array(),array('CATEGORY_ID'));

	foreach($categories_RET as $category)
	{
		if(AllowUse('Students/Student.php&category_id='.$category['ID']))
		{
//modif Francois: fix error Warning: Invalid argument supplied for foreach()
			if (isset($custom_RET[$category['ID']]))
			{
				foreach($custom_RET[$category['ID']] as $field)
					$fields_list[$category['TITLE']]['CUSTOM_'.$field['ID']] = str_replace("'",'&#39;',$field['TITLE']);
			}
		}
	}

	if($RosarioModules['Food_Service'])
		$fields_list['Food_Service'] = array('FS_ACCOUNT_ID'=>_('Account ID'),'FS_DISCOUNT'=>_('Discount'),'FS_STATUS'=>_('Status'),'FS_BARCODE'=>_('Barcode'),'FS_BALANCE'=>_('Balance'));

	$fields_list['Schedule']['PERIOD_ATTENDANCE'] = _('Attendance Period Teacher').' - '._('Room');
	$periods_RET = DBGet(DBQuery("SELECT TITLE,PERIOD_ID FROM SCHOOL_PERIODS WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
	foreach($periods_RET as $period)
		$fields_list['Schedule']['PERIOD_'.$period['PERIOD_ID']] = $period['TITLE'].' '._('Teacher').' - '._('Room');

	DrawHeader('<OL><SPAN id=names_div></SPAN></OL>');
	echo '<TABLE><TR class="st"><TD class="valign-top">';
	echo '<BR />';
//modif Francois: css WPadmin
	PopTable('header',_('Fields'));
	echo '';
	foreach($fields_list as $category=>$fields)
	{
//modif Francois: add translation
		echo '<TABLE class="widefat cellspacing-0"><TR><TH colspan="2">'.(ParseMLField($category)==$category?_(str_replace('_', ' ', $category)):ParseMLField($category)).'</TH></TR><TR>';
		if(ParseMLField($category,'default')=='Address')
		{
//modif Francois: add <label> on checkbox
			echo '<TD><label><INPUT type="checkbox" id="residence" value="Y" />&nbsp;'._('Residence').'</label></TD>';
//modif Francois: disable mailing address display
			if (Config('STUDENTS_USE_MAILING'))
				echo '<TD><label><INPUT type="checkbox" id="mailing" value="Y" />&nbsp;'._('Mailing').'</label></TD>';
			else
				echo '<TD>&nbsp;<INPUT type="hidden" id="mailing" value="" /></TD>';
				
			echo '</TR><TR>';
			echo '<TD><label><INPUT type="checkbox" id="bus_pickup" value="Y" />&nbsp;'._('Bus Pickup').'</label></TD>';
			echo '<TD><label><INPUT type="checkbox" id="bus_dropoff" value="Y" />&nbsp;'._('Bus Dropoff').'</label></TD>';
			echo '</TR><TR>';
		}
		foreach($fields as $field=>$title)
		{
			$i++;
            echo '<TD><label><INPUT type="checkbox" onclick=\'addHTML("'.str_replace('"','\"','<LI>'.ParseMLField($title).'</LI>').'","names_div",false);addHTML("'.str_replace('"','\"','<INPUT type="hidden" name="fields['.$field.']" value="Y" />').'","fields_div",false);this.disabled=true\' />&nbsp;'.ParseMLField($title).'</label>';
			if(ParseMLField($category,'default')=='Address' && $field=='PARENTS')
			{
				$relations_RET = DBGet(DBQuery("SELECT DISTINCT STUDENT_RELATION FROM STUDENTS_JOIN_PEOPLE ORDER BY STUDENT_RELATION"));
				$select = '<SELECT id="relation"><OPTION value="">'._('N/A');
				foreach($relations_RET as $relation)
					if($relation['STUDENT_RELATION']!='')
						$select .= '<OPTION value="'.$relation['STUDENT_RELATION'].'">'.$relation['STUDENT_RELATION'];
					else
						$select .= '<OPTION value="!">'._('No Value');
				$select .= '</SELECT>';
				echo '&nbsp;-&nbsp;'._('Relation').':&nbsp;'.$select;
			}
			echo '</TD>';
			if($i%2==0)
				echo '</TR><TR>';
		}
		if($i%2!=0)
		{
			echo '<TD>&nbsp;</TD></TR><TR>';
			$i++;
		}
		echo '</TR></TABLE><br />';
	}
	PopTable('footer');
	echo '</TD><TD class="valign-top">';
	if($Search && function_exists($Search))
	{
		echo '</TD></TR></TABLE>';
	 	$Search($extra);
	}
	else
	{
		Search('student_id',$extra);
		echo '</TD></TR></TABLE>';
	}
}
?>
