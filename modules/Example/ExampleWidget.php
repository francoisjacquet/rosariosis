<?php

/********************************************
 ExampleWidget.php file
 Optional
 - Example of Search Widget and PDF printing
*********************************************/

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save') //print PDF
{
	if(count($_REQUEST['st_arr'])) //if students selected, continue
	{		
		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
		$extra['WHERE'] = " AND s.STUDENT_ID IN (".$st_list.")"; //restrict student list to selected students

		//get Marking Period information
		$mp_RET = DBGet(DBQuery("SELECT TITLE,END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND MARKING_PERIOD_ID='".UserMP()."'"));
		//get School information
		$school_info_RET = DBGet(DBQuery("SELECT TITLE,PRINCIPAL FROM SCHOOLS WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

		//order by Grade levels
		$extra['SELECT'] .= ",(SELECT SORT_ORDER FROM SCHOOL_GRADELEVELS WHERE ID=ssm.GRADE_ID) AS SORT_ORDER";
		$extra['ORDER_BY'] = 'SORT_ORDER DESC,FULL_NAME';

		//get Teacher information
		$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||coalesce(' '||st.MIDDLE_NAME||' ',' ')||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp,SCHEDULE ss WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) LIMIT 1) AS TEACHER";

		//get student list
		$RET = GetStuList($extra);
		
		//PDF options
		$no_margins = array('top'=> 0, 'bottom'=> 0, 'left'=> 0, 'right'=> 0);
		$handle = PDFStart(false, $no_margins); //start PDF buffer

		$_SESSION['orientation'] = 'landscape';

		$first = true;

		//loop over the returned students array
		foreach($RET as $student)
		{
			if (!$first)
				//page break before new student
				echo '<div style="page-break-after: always;"></div>';
			else
				$first = false;

			echo '<br /><br /><TABLE style="margin:0 auto; height:77%;">';
			
			//format TEXTAREA content
			$subject_text = nl2br(str_replace("''","'",str_replace('  ',' &nbsp;',$_REQUEST['subject_text'])));
			
			//apply the substitutions
			$subject_text = str_replace(array('__FULL_NAME__','__FIRST_NAME__','__LAST_NAME__','__MIDDLE_NAME__','__GRADE_ID__','__SCHOOL_ID__','__SUBJECT__'),array($student['FULL_NAME'],$student['FIRST_NAME'],$student['LAST_NAME'],$student['MIDDLE_NAME'],$student['GRADE_ID'],$school_info_RET[1]['TITLE'],$_REQUEST['subject']),$subject_text);

			//generate the PDF content
			echo '<TR><TD><span style="font-size:xx-large;">'.$subject_text.'</span></TD></TR></TABLE>';

			echo '<br /><TABLE style="margin:0 auto; width:80%;">';
			echo '<TR><TD><span style="font-size:x-large;">'.$student['TEACHER'].'</span><BR /><span style="font-size:medium;">'._('Teacher').'</span></TD>';
			echo '<TD><span style="font-size:x-large;">'.$mp_RET[1]['TITLE'].'</span><BR /><span style="font-size:medium;">'._('Marking Period').'</span></TD></TR>';
			echo '<TR><TD><span style="font-size:x-large;">'.$school_info_RET[1]['PRINCIPAL'].'</span><BR /><span style="font-size:medium;">'._('Principal').'</span></TD>';
			echo '<TD><span style="font-size:x-large;">'.ProperDate(date('Y.m.d',strtotime($mp_RET[1]['END_DATE']))).'</span><BR /><span style="font-size:medium;">'._('Date').'</span></TD></TR>';
			echo '</TABLE>';
		}
		PDFStop($handle); //send PDF buffer to impression
	}
	else
		//use BackPrompt to display errors when printing PDF: will close the opened browser tab
		BackPrompt(_('You must choose at least one student.'));
}

if(empty($_REQUEST['modfunc'])) //display Search or list of students
{
	DrawHeader(ProgramTitle()); //display main header with Module icon and Program title

	if($_REQUEST['search_modfunc']=='list') //if list of students
	{
		//form used to send the students list and the text to be processed by the same script (see at the top)
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&include_inactive='.$_REQUEST['include_inactive'].'&_ROSARIO_PDF=true" method="POST">'; //_ROSARIO_PDF=true enables PDF printing
		
		//the $extra variable contains the options of the Search function & the extra headers
		$extra['header_right'] = SubmitButton(dgettext('Example', 'Create Subject PDF for Selected Students')); //SubmitButton is diplayed only if AllowEdit

		$extra['extra_header_left'] = '<TABLE>';
		$extra['extra_header_left'] .= '<TR class="st"><TD style="vertical-align: top;">'._('Text').'</TD><TD><TEXTAREA name="subject_text">';
		$extra['extra_header_left'] .= '</TEXTAREA></TD></TR>';

		//substitutions list
		$extra['extra_header_left'] .= '<TR class="st"><TD style="vertical-align: top;">'._('Substitutions').':</TD><TD><TABLE><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__FULL_NAME__</TD><TD>= '._('Last, First M').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__LAST_NAME__</TD><TD>= '._('Last Name').'</TD>';
		$extra['extra_header_left'] .= '</TR><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__FIRST_NAME__</TD><TD>= '._('First Name').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__MIDDLE_NAME__</TD><TD>= '._('Middle Name').'</TD>';
		$extra['extra_header_left'] .= '</TR><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__SCHOOL_ID__</TD><TD>= '._('School').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__GRADE_ID__</TD><TD>= '._('Grade Level').'</TD>';
		$extra['extra_header_left'] .= '</TR></TABLE>';
		$extra['extra_header_left'] .= '</TR></TABLE>';
	}

	if(!isset($_REQUEST['_ROSARIO_PDF'])) //if not printing page in PDF
	{
		$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
		$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
		$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	}
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['new'] = true;
	$extra['options']['search'] = false;
	$extra['force_search'] = true;

	//call our custom Widget
	MyWidgets('subject');
		
	Search('student_id',$extra);
	
	if($_REQUEST['search_modfunc']=='list') //if list of students
	{
		echo '<BR /><span class="center">'.SubmitButton(dgettext('Example', 'Create Subject PDF for Selected Students')).'</span>'; //SubmitButton is diplayed only if AllowEdit
		echo '</FORM>';
	}
}

//local function called by Search
//begin function name with an underscore "_" when it is local
function _makeChooseCheckbox($value,$title)
{
		return '&nbsp;&nbsp;<INPUT type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
}

//custom Widgets
function MyWidgets($item)
{	global $extra,$_ROSARIO;

	switch($item)
	{
		//subject Widget
		case 'subject':

			//if subject selected
			if(!empty($_REQUEST['subject_id']))
			{
				//limit student search to subject
				$extra['WHERE'] .=  " AND exists(SELECT '' FROM SCHEDULE sch, COURSE_PERIODS cp, COURSES c WHERE sch.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sch.SYEAR=ssm.SYEAR AND sch.MARKING_PERIOD_ID IN (".GetAllMP(UserMP()).") AND cp.COURSE_PERIOD_ID=sch.COURSE_PERIOD_ID AND cp.COURSE_ID=c.COURSE_ID AND c.SUBJECT_ID='".$_REQUEST['subject_id']."')";

				//add SearchTerms
				if(!$extra['NoSearchTerms'])
				{
					$subject_RET = DBGet(DBQuery("SELECT TITLE FROM COURSE_SUBJECTS WHERE SUBJECT_ID='".$_REQUEST['subject_id']."' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
					$_ROSARIO['SearchTerms'] .= '<b>'._('Subject').':</b> '.$subject_RET[1]['TITLE'];
					$_ROSARIO['SearchTerms'] .= '<input type="hidden" id="subject" name="subject" value="'.str_replace('"','&quot;',$subject_RET[1]['TITLE']).'" /><BR />';
				}
			}

			//get subjects
			$subjects_RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

			//create select input with subjects
			$select = '<SELECT name="subject_id">';
			if(count($subjects_RET))
			{
				foreach($subjects_RET as $subject)
					$select .= '<OPTION value="'.$subject['SUBJECT_ID'].'">'.$subject['TITLE'].'</OPTION>';
			}
			$select .= '</SELECT>';
			
			//add Widget to Search
			$extra['search'] .= '<TR><TD style="text-align:right;">'._('Subject').'</TD><TD>'.$select.'</TD></TR>';
		break;
	}
}
?>
