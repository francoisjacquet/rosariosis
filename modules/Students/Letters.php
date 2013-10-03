<?php

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
		$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

		if($_REQUEST['mailing_labels']=='Y')
			Widgets('mailing_labels');
		$extra['SELECT'] .= ",s.FIRST_NAME AS NICK_NAME";
		$extra['functions']['SCHOOL_ID'] = 'GetSchool';
		if(User('PROFILE')=='admin')
		{
			if($_REQUEST['w_course_period_id_which']=='course_period' && $_REQUEST['w_course_period_id'])
			{
				$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID='$_REQUEST[w_course_period_id]') AS TEACHER";
				$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='$_REQUEST[w_course_period_id]') AS ROOM";
			}
			else
			{
				//modif Francois: multiple school periods for a course period
				//$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS TEACHER";
				$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss,COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND st.STAFF_ID=cp.TEACHER_ID AND cpsp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS TEACHER";
				//$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss WHERE cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS ROOM";
				$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss,COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cpsp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS ROOM";
			}
		}
		else
		{
			$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||' '||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID='".UserCoursePeriod()."') AS TEACHER";
			$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp WHERE cp.COURSE_PERIOD_ID='".UserCoursePeriod()."') AS ROOM";
		}

		$RET = GetStuList($extra);

		if(count($RET))
		{
			//modif Francois: add Template
			$template_update = DBGet(DBQuery("SELECT 1 FROM TEMPLATES WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID = '".User('STAFF_ID')."'"));
			if (!$template_update)
				DBQuery("INSERT INTO TEMPLATES (MODNAME, STAFF_ID, TEMPLATE) VALUES ('".$_REQUEST['modname']."', '".User('STAFF_ID')."', '".$REQUEST_letter_text."')");
			else
				DBQuery("UPDATE TEMPLATES SET TEMPLATE = '".$REQUEST_letter_text."' WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID = '".User('STAFF_ID')."'");

			$REQUEST_letter_text = nl2br(str_replace("''","'",str_replace('  ',' &nbsp;',$REQUEST_letter_text)));

			$css = true;
			$handle = PDFStart($css);
			foreach($RET as $student)
			{
				$student_points = $total_points = 0;
				unset($_ROSARIO['DrawHeader']);

				if($_REQUEST['mailing_labels']=='Y')
					echo '<BR /><BR /><BR />';
				//DrawHeader(ParseMLField(Config('TITLE')).' Letter');
				DrawHeader('&nbsp;');
				DrawHeader($student['FULL_NAME'],$student['STUDENT_ID']);
				DrawHeader($student['GRADE_ID'],GetSchool(UserSchool()));
				//DrawHeader('',GetMP(GetCurrentMP('QTR',DBDate(),false)));
				DrawHeader(ProperDate(DBDate()));

				if($_REQUEST['mailing_labels']=='Y')
					echo '<BR /><BR /><TABLE class="width-100p"><TR><TD style="width:50px;"> &nbsp; </TD><TD>'.$student['MAILING_LABEL'].'</TD></TR></TABLE><BR />';

				$letter_text = $REQUEST_letter_text;
				foreach($student as $column=>$value)
					$letter_text = str_replace('__'.$column.'__',$value,$letter_text);

				echo '<BR />'.$letter_text;
				echo '<div style="page-break-after: always;"></div>';
			}
			PDFStop($handle);
		}
		else
			BackPrompt(_('No Students were found.'));
	}
	else
		BackPrompt(_('You must choose at least one student.'));
}

if(empty($_REQUEST['modfunc']))

{
	DrawHeader(ProgramTitle());

	if($_REQUEST['search_modfunc']=='list')
	{
		//modif Francois: add TinyMCE to the textarea
		?>
<!-- Load TinyMCE -->
<script type="text/javascript" src="assets/js/tiny_mce_3.5.8_jquery/jquery.tinymce.js"></script>
<script type="text/javascript">
	// Rosario customed version of TinyMCE jQuery
	$().ready(function() {
		var resize_tinymce = (screen.width<768 ? true : false);
		$('textarea.tinymce').tinymce({
			// Location of TinyMCE script
			script_url : 'assets/js/tiny_mce_3.5.8_jquery/tiny_mce.js',

			// General options
			theme : "advanced",
			plugins : "contextmenu,inlinepopups,pagebreak,paste,table",
			
			// Plugins options
			pagebreak_separator : '<div style="page-break-after: always;"></div>',

			// Language
			language : "<?php echo mb_substr($locale,0,2); ?>",
			
			// Theme options
			theme_advanced_buttons1 : "cut,copy,paste,pastetext,pasteword,|,undo,redo,|,image,code,cleanup,help",
			theme_advanced_buttons2 : "formatselect,fontsizeselect,|,bold,italic,underline,strikethrough",
			theme_advanced_buttons3 : "justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,forecolor",
			theme_advanced_buttons4 : "pagebreak,|,sub,sup,charmap,blockquote,|,hr,removeformat,visualaid",
			theme_advanced_buttons5 : "tablecontrols",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : resize_tinymce, //textarea size fits a PDF page!
			
			// Produce BR elements on enter/return instead of P elements
			forced_root_block : false,

			// Example content CSS (should be your site CSS)
			//content_css : "assets/themes/<?php echo Preferences('THEME'); ?>/stylesheet.css",
		});
	});
</script>
<!-- /TinyMCE -->

		<?php
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&include_inactive='.$_REQUEST['include_inactive'].'&_search_all_schools='.$_REQUEST['_search_all_schools'].'&_ROSARIO_PDF=true" method="POST">';
		$extra['header_right'] = '<INPUT type="submit" value="'._('Print Letters for Selected Students').'" />';

		$extra['extra_header_left'] = '<TABLE>';

		Widgets('mailing_labels');
		$extra['extra_header_left'] .= $extra['search'];
		$extra['search'] = '';
		//modif Francois: add Template
		$templates = DBGet(DBQuery("SELECT TEMPLATE, STAFF_ID FROM TEMPLATES WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID IN (0,'".User('STAFF_ID')."')"), array(), array('STAFF_ID'));
		//modif Francois: add TinyMCE to the textarea
		$extra['extra_header_left'] .= '<TR class="st"><TD style="vertical-align: top;">'._('Letter Text').'</TD><TD><TEXTAREA name="letter_text" class="tinymce">'.str_replace(array('<','>','"'),array('&lt;','&gt;','&quot;'),($templates[User('STAFF_ID')] ? $templates[User('STAFF_ID')][1]['TEMPLATE'] : $templates[0][1]['TEMPLATE'])).'</TEXTAREA></TD></TR>';

		$extra['extra_header_left'] .= '<TR class="st"><TD style="vertical-align: top;">'.Localize('colon',_('Substitutions')).'</TD><TD><TABLE><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__FULL_NAME__</TD><TD>= '._('Last, First M').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '</TR><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__FIRST_NAME__</TD><TD>= '._('First Name').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__LAST_NAME__</TD><TD>= '._('Last Name').'</TD>';
		$extra['extra_header_left'] .= '</TR><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__MIDDLE_NAME__</TD><TD>= '._('Middle Name').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__STUDENT_ID__</TD><TD>= '._('RosarioSIS ID').'</TD>';
		$extra['extra_header_left'] .= '</TR><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__SCHOOL_ID__</TD><TD>= '._('School').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__GRADE_ID__</TD><TD>= '._('Grade Level').'</TD>';
		$extra['extra_header_left'] .= '</TR><TR class="st">';
		if(User('PROFILE')=='admin')
		{
			$extra['extra_header_left'] .= '<TD>__TEACHER__</TD><TD>= '._('Attendance Teacher').'</TD><TD></TD>';
			$extra['extra_header_left'] .= '<TD>__ROOM__</TD><TD>= '._('Attendance Room').'</TD>';
		}
		else
		{
			$extra['extra_header_left'] .= '<TD>__TEACHER__</TD><TD>= '._('Your Name').'</TD><TD></TD>';
			$extra['extra_header_left'] .= '<TD>__ROOM__</TD><TD>= '._('Your Room').'</TD>';
		}
		$extra['extra_header_left'] .= '</TR></TABLE></TD></TR>';

		$extra['extra_header_left'] .= '</TABLE>';
	}

	//$extra['force_search'] = true;

	$extra['SELECT'] .= ",s.STUDENT_ID AS CHECKBOX";
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center"><INPUT type="submit" value="'._('Print Letters for Selected Students').'" /></span>';
		echo '</FORM>';
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '&nbsp;&nbsp;<INPUT type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
}
?>