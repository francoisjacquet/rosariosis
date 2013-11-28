<?php


if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
		$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

		$mp_RET = DBGet(DBQuery("SELECT TITLE,END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND MARKING_PERIOD_ID='".UserMP()."'"));
		$school_info_RET = DBGet(DBQuery("SELECT TITLE,PRINCIPAL FROM SCHOOLS WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

		$extra['SELECT'] = ",s.FIRST_NAME AS NICK_NAME";
		$extra['SELECT'] .= ",(SELECT SORT_ORDER FROM SCHOOL_GRADELEVELS WHERE ID=ssm.GRADE_ID) AS SORT_ORDER";
		$extra['SELECT'] .= ",".db_case(array("exists(SELECT rg.GPA_VALUE FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))",'true','NULL',"'Y'"))." AS HIGH_HONOR";
		//$extra['SELECT'] .= ",(SELECT TITLE FROM SCHOOLS WHERE ID=ssm.SCHOOL_ID AND SYEAR=ssm.SYEAR) AS SCHOOL";
		//$extra['SELECT'] .= ",(SELECT PRINCIPAL FROM SCHOOLS WHERE ID=ssm.SCHOOL_ID AND SYEAR=ssm.SYEAR) AS PRINCIPAL";
		//modif Francois: multiple school periods for a course period
		//$extra['SELECT'] .= ",(SELECT coalesce(st.TITLE||' ',' ')||st.FIRST_NAME||coalesce(' '||st.MIDDLE_NAME||' ',' ')||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS TEACHER";
		$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||coalesce(' '||st.MIDDLE_NAME||' ',' ')||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp,SCHEDULE ss WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) LIMIT 1) AS TEACHER";
		$extra['SELECT'] .= ",(SELECT cp.ROOM FROM COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss,COURSE_PERIOD_SCHOOL_PERIODS cpsp WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID AND cpsp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS ROOM";
		$extra['ORDER_BY'] = 'HIGH_HONOR,SORT_ORDER DESC,ROOM,FULL_NAME';
		if($_REQUEST['list'])
			$extra['group'] = array('HIGH_HONOR');
		$RET = GetStuList($extra);

		if($_REQUEST['list'])
		{
			$handle = PDFStart();
			echo '<TABLE style="margin:0 auto; width:80%;">';
			echo '<TR class="center"><TD colspan="6"><B>'.sprintf(_('%s Honor Roll'),$school_info_RET[1]['TITLE']).' </B> - '.$mp_RET[1]['TITLE'].' - '.date('F j, Y',strtotime($mp_RET[1]['END_DATE'])).'</TD></TR>';
			echo '<TR class="center"><TD colspan="6">&nbsp;</TD></TR>';

			foreach(array('Y','') AS $high)
			{
				if($n = count($RET[$high]))
				{
					$n = (int) (($n+1)/2);
					echo '<TR class="center"><TD colspan="6" style="background-color:#C0C0C0;"><B>'.($high=='Y'?_('High Honor Roll'):_('Honor Roll')).'</B></TD></TR>';
					for($i=1; $i<=$n; $i++)
					{
						echo '<TR><TD>&nbsp;</TD>';
						$student = $RET[$high][$i];
						echo '<TD>'.$student['NICK_NAME'].' '.$student['LAST_NAME'].'</TD><TD>'.$student['ROOM'].'</TD>';
						echo '<TD>&nbsp;</TD>';
						$student = $RET[$high][$i+$n];
						echo '<TD>'.$student['NICK_NAME'].' '.$student['LAST_NAME'].'</TD><TD>'.$student['ROOM'].'</TD></TR>';
					}
					echo '<TR class="center"><TD colspan="6">&nbsp;</TD></TR>';
				}
			}
			echo '</TABLE>';
			PDFStop($handle);
		}
		else
		{
			//modif Francois: add Template
			$template_update = DBGet(DBQuery("SELECT 1 FROM TEMPLATES WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID = '".User('STAFF_ID')."'"));
			if (!$template_update)
				DBQuery("INSERT INTO TEMPLATES (MODNAME, STAFF_ID, TEMPLATE) VALUES ('".$_REQUEST['modname']."', '".User('STAFF_ID')."', '".$REQUEST_honor_roll_text."')");
			else
				DBQuery("UPDATE TEMPLATES SET TEMPLATE = '".$REQUEST_honor_roll_text."' WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID = '".User('STAFF_ID')."'");

			$no_margins = array('top'=> 0, 'bottom'=> 0, 'left'=> 0, 'right'=> 0);
			$handle = PDFStart(false, $no_margins);
			//echo '<!-- MEDIA SIZE 8.5x11in -->';
			$_SESSION['orientation'] = 'landscape';
			foreach($RET as $student)
			{
				//note Francois: bug: small white border at the bottom of page
				echo '<style type="text/css"> body {margin:0; padding:0;} div#background {background: width:1462px; height:1032px; position:relative;} div#background * {z-index:1; position:relative;}</style>';
				echo '<div id="background">';
				if (!empty($_REQUEST['frame']))
				{
					echo '<img src="assets/Frames/'.$_REQUEST['frame'].'" style="z-index:0; width:1462px; height:1032px; position:absolute;" />';
				}
				echo '<TABLE style="margin:auto auto; height:77%;">';
				
				$honor_roll_text = nl2br(str_replace("''","'",str_replace('  ',' &nbsp;',$REQUEST_honor_roll_text)));
				$honor_roll_text = str_replace(array('__FULL_NAME__','__FIRST_NAME__','__LAST_NAME__','__MIDDLE_NAME__','__GRADE_ID__','__SCHOOL_ID__','__SUBJECT__'),array($student['FULL_NAME'],$student['FIRST_NAME'],$student['LAST_NAME'],$student['MIDDLE_NAME'],$student['GRADE_ID'],$school_info_RET[1]['TITLE'],$_REQUEST['subject']),$honor_roll_text);
				$honor_roll_text = ($student['HIGH_HONOR']=='Y'? str_replace(_('Honor Roll'),_('High Honor Roll'),$honor_roll_text) : $honor_roll_text);
				
				echo '<TR><TD>'.$honor_roll_text.'</TD></TR></TABLE>';
				
				echo '<TABLE style="margin:0 auto; width:80%;">';
				echo '<TR><TD><span style="font-size:x-large;">'.$student['TEACHER'].'</span><BR /><span style="font-size:medium;">'._('Teacher').'</span></TD>';
				echo '<TD><span style="font-size:x-large;">'.$mp_RET[1]['TITLE'].'</span><BR /><span style="font-size:medium;">'._('Marking Period').'</span></TD></TR>';
				echo '<TR><TD><span style="font-size:x-large;">'.$school_info_RET[1]['PRINCIPAL'].'</span><BR /><span style="font-size:medium;">'._('Principal').'</span></TD>';
				echo '<TD><span style="font-size:x-large;">'.ProperDate(date('Y.m.d',strtotime($mp_RET[1]['END_DATE']))).'</span><BR /><span style="font-size:medium;">'._('Date').'</span></TD></TR>';
				echo '</TABLE></div>';
				echo '<div style="page-break-after: always;"></div>';
			}
			PDFStop($handle);
		}
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
			plugins : "contextmenu,inlinepopups,paste,table",
			
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
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&include_inactive='.$_REQUEST['include_inactive'].'&_ROSARIO_PDF=true" method="POST">';
		$extra['header_right'] = SubmitButton(_('Create Honor Roll for Selected Students'));

		$extra['extra_header_left'] = '<TABLE>';

//modif Francois: add <label> on radio
		$extra['extra_header_left'] .= '<TR><TD><label><INPUT type="radio" name="list" value="list"> '._('List').'</label></TD></TR>';
		$extra['extra_header_left'] .= '<TR><TD><label><INPUT type="radio" name="list" value="" checked /> '._('Certificates').':</label></TD></TR>';

//modif Francois: add TinyMCE to the textarea
		$extra['extra_header_left'] .= '<TR><TD>&nbsp;</TD></TR><TR class="st"><TD style="vertical-align: top;">'._('Text').'</TD><TD colspan="4"><TEXTAREA name="honor_roll_text" class="tinymce">';
		//modif Francois: add Template
		$templates = DBGet(DBQuery("SELECT TEMPLATE, STAFF_ID FROM TEMPLATES WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID IN (0,'".User('STAFF_ID')."')"), array(), array('STAFF_ID'));
		$extra['extra_header_left'] .= str_replace(array('<','>','"'),array('&lt;','&gt;','&quot;'),($templates[User('STAFF_ID')] ? $templates[User('STAFF_ID')][1]['TEMPLATE'] : $templates[0][1]['TEMPLATE']));
		$extra['extra_header_left'] .= '</TEXTAREA></TD></TR>';

		$extra['extra_header_left'] .= '<TR class="st"><TD style="vertical-align: top;">'.Localize('colon',_('Substitutions')).'</TD><TD><TABLE><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__FULL_NAME__</TD><TD>= '._('Last, First M').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__LAST_NAME__</TD><TD>= '._('Last Name').'</TD>';
		$extra['extra_header_left'] .= '</TR><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__FIRST_NAME__</TD><TD>= '._('First Name').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__MIDDLE_NAME__</TD><TD>= '._('Middle Name').'</TD>';
		$extra['extra_header_left'] .= '</TR><TR class="st">';
		$extra['extra_header_left'] .= '<TD>__SCHOOL_ID__</TD><TD>= '._('School').'</TD><TD>&nbsp;</TD>';
		$extra['extra_header_left'] .= '<TD>__GRADE_ID__</TD><TD>= '._('Grade Level').'</TD>';
		$extra['extra_header_left'] .= '</TR></TABLE></TD></TR>';

//modif Francois: add frames choice
		$frames = array();
		if (is_dir('assets/Frames/'))
			$frames = scandir('assets/Frames/');
		//no frame first and checked
		$extra['extra_header_left'] .= '<TR class="st"><TD style="vertical-align:top;">'._('Frame').'</TD><TD><DIV style="overflow-x:auto; height:160px;" id="framesList"><table class="cellpadding-0 cellspacing-0"><tr><td class="image-radio-list" style="height: auto;"><label class="image-radio-list"><INPUT type="radio" name="frame" value="" checked /> '._('No frame').'</label></td>';
		//create radio list with thumbnails
		$i = 1;
		foreach ($frames as $frame)
		{
			//filter images
			if ( in_array( mb_strtolower(mb_strrchr($frame, '.')), array('.jpg', '.jpeg', '.png', '.gif') ) )
			{
				//if ($i % 5 == 0) //change table row each five thumbnails
					//$extra['extra_header_left'] .= '</TR><TR>';
				$extra['extra_header_left'] .= '<td class="image-radio-list"><label class="image-radio-list"><INPUT type="radio" name="frame" value="'.$frame.'"> <img src="assets/Frames/'.$frame.'" class="image-radio-list" title="'.UCWords(str_replace(array('_', '.jpg', '.jpeg', '.png', '.gif'),array(' ', ''), $frame)).'" /></label></td>';
				$i++;
			}
		}
		$extra['extra_header_left'] .= '</tr></table></DIV></TD></TR>';
		$extra['extra_header_left'] .= '</TABLE>';
		$extra['extra_header_left'] .= '<script type="text/javascript">if (isTouchDevice()) {touchScroll(document.getElementById(\'framesList\'));}</script>';

	}

	if(!isset($_REQUEST['_ROSARIO_PDF']))
	{
		$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
		$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
		$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	}
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['new'] = true;
	$extra['options']['search'] = false;
	$extra['force_search'] = true;

	Widgets('course');
	MyWidgets('honor_roll');
	if($for_news_web)
		$extra['student_fields'] = array('search'=>"'$for_news_web'",'view'=>"'$for_news_web'");
				
	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center">'.SubmitButton(_('Create Honor Roll for Selected Students')).'</span>';
		echo "</FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{
	if($_REQUEST['honor_roll']=='Y' || $_REQUEST['high_honor_roll']=='Y')
		return '&nbsp;&nbsp;<INPUT type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
	else
		return '';
}

function MyWidgets($item)
{	global $extra,$_ROSARIO;

	switch($item)
	{
		case 'honor_roll':
			if($_REQUEST['honor_roll']=='Y' && $_REQUEST['high_honor_roll']=='Y')
			{
				$extra['SELECT'] .= ",".db_case(array("exists(SELECT rg.GPA_VALUE FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))",'true','NULL',"'<img src=\"assets/check_button.png\" height=\"15\" />'"))." AS HIGH_HONOR";
				$extra['WHERE'] .=  " AND exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y')";
				$extra['WHERE'] .= " AND NOT exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT  HR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";
				$extra['columns_after']['HIGH_HONOR'] = _('High Honor');
				if(!$extra['NoSearchTerms'])
//modif Francois: add translation
					$_ROSARIO['SearchTerms'] .= '<b>'._('Honor Roll').' & '._('High Honor Roll').'</b><BR />';
			}
			elseif($_REQUEST['honor_roll']=='Y')
			{
				$extra['WHERE'] .=  " AND exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y')";
				$extra['WHERE'] .= " AND NOT exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT  HR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";
				$extra['WHERE'] .= " AND exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";
				if(!$extra['NoSearchTerms'])
					$_ROSARIO['SearchTerms'] .= '<b>'._('Honor Roll').'</b><BR />';
			}
			elseif($_REQUEST['high_honor_roll']=='Y')
			{
				$extra['WHERE'] .=  " AND exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y')";
				$extra['WHERE'] .= " AND NOT exists(SELECT '' FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg WHERE sg.STUDENT_ID=s.STUDENT_ID AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR AND sg.MARKING_PERIOD_ID='".UserMP()."' AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID AND cp.DOES_HONOR_ROLL='Y' AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID AND sg.REPORT_CARD_GRADE_ID=rg.ID AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";
				if(!$extra['NoSearchTerms'])
					$_ROSARIO['SearchTerms'] .= '<b>'._('High Honor Roll').'</b><BR />';
			}
//modif Francois: add <label> on checkbox
			$extra['search'] .= '<TR><TD style="text-align:right;">'._('Honor Roll').'</TD><TD><label><INPUT type="checkbox" name="honor_roll" value="Y" checked /> '._('Honor').'</label> <label><INPUT type="checkbox" name="high_honor_roll" value="Y" checked /> '._('High Honor').'</label></TD></TR>';
		break;
	}
}
?>
